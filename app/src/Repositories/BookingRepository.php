<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\HttpException;
use PDO;

final class BookingRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly EventRepository $events,
    ) {
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function findPaginatedForActor(array $actor, array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if ($actor['role'] !== 'admin') {
            $where[] = 'b.user_id = ?';
            $params[] = $actor['sub'];
        } else {
            if (!empty($query['user_id'])) {
                $where[] = 'b.user_id = ?';
                $params[] = (int) $query['user_id'];
            }
        }

        if (!empty($query['event_id'])) {
            $where[] = 'b.event_id = ?';
            $params[] = (int) $query['event_id'];
        }

        if (!empty($query['status'])) {
            $where[] = 'b.status = ?';
            $params[] = $query['status'];
        }

        $sqlWhere = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM bookings b WHERE {$sqlWhere}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->pdo->prepare(
            "SELECT b.id, b.user_id, b.event_id, b.quantity, b.status, b.external_payment_ref, b.created_at,
                    e.title AS event_title, e.venue, e.start_at, e.price_cents
             FROM bookings b
             JOIN events e ON e.id = b.event_id
             WHERE {$sqlWhere}
             ORDER BY b.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);

        return [
            'data' => $listStmt->fetchAll(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.id, b.user_id, b.event_id, b.quantity, b.status, b.external_payment_ref, b.created_at,
                    e.title AS event_title, e.venue, e.start_at, e.price_cents
             FROM bookings b JOIN events e ON e.id = b.event_id
             WHERE b.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(int $userId, int $eventId, int $quantity): array
    {
        if ($quantity < 1) {
            throw new HttpException('quantity must be at least 1', 400);
        }

        $this->pdo->beginTransaction();
        try {
            $event = $this->events->lockRowForUpdate($eventId, $this->pdo);
            $this->events->decrementTickets($this->pdo, $eventId, $quantity);

            $stmt = $this->pdo->prepare(
                'INSERT INTO bookings (user_id, event_id, quantity, status) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $eventId, $quantity, 'pending']);
            $bookingId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException('Could not create booking', 500);
        }

        $created = $this->findById($bookingId);
        if ($created === null) {
            throw new HttpException('Failed to load created booking', 500);
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(int $id, string $status): array
    {
        if (!in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            throw new HttpException('Invalid status', 400);
        }

        $stmt = $this->pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        if ($stmt->rowCount() === 0) {
            throw new HttpException('Booking not found', 404);
        }

        $row = $this->findById($id);
        if ($row === null) {
            throw new HttpException('Booking not found', 500);
        }

        return $row;
    }

    public function setPaymentRef(int $id, string $ref): void
    {
        $stmt = $this->pdo->prepare('UPDATE bookings SET external_payment_ref = ? WHERE id = ?');
        $stmt->execute([$ref, $id]);
    }

    /**
     * Cancel booking and return tickets if it was pending or paid.
     */
    public function cancelForUser(int $bookingId, int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, user_id, event_id, quantity, status FROM bookings WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$bookingId]);
            $b = $stmt->fetch();
            if ($b === false) {
                throw new HttpException('Booking not found', 404);
            }
            if ((int) $b['user_id'] !== $userId) {
                throw new HttpException('Forbidden', 403);
            }
            if ($b['status'] === 'cancelled') {
                $this->pdo->commit();

                return;
            }
            if ($b['status'] === 'paid') {
                throw new HttpException('Paid bookings cannot be cancelled by users', 409);
            }

            $qty = (int) $b['quantity'];
            $eventId = (int) $b['event_id'];
            $this->events->incrementTickets($this->pdo, $eventId, $qty);

            $upd = $this->pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $upd->execute(['cancelled', $bookingId]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException('Could not cancel booking', 500);
        }
    }

    /**
     * Admin delete: remove row and restore tickets unless already cancelled.
     */
    public function adminDelete(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'SELECT id, event_id, quantity, status FROM bookings WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$id]);
            $b = $stmt->fetch();
            if ($b === false) {
                throw new HttpException('Booking not found', 404);
            }
            if ($b['status'] !== 'cancelled') {
                $this->events->incrementTickets($this->pdo, (int) $b['event_id'], (int) $b['quantity']);
            }
            $del = $this->pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $del->execute([$id]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException('Could not delete booking', 500);
        }
    }
}
