<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Http\HttpException;
use PDO;

final class EventRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array{page: int, per_page: int, total: int}}
     */
    public function findPaginated(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];

        if (!empty($query['search'])) {
            $where[] = '(title LIKE ? OR description LIKE ?)';
            $term = '%' . $query['search'] . '%';
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($query['venue'])) {
            $where[] = 'venue LIKE ?';
            $params[] = '%' . $query['venue'] . '%';
        }

        if (!empty($query['from'])) {
            $where[] = 'start_at >= ?';
            $params[] = $query['from'];
        }

        if (!empty($query['to'])) {
            $where[] = 'start_at <= ?';
            $params[] = $query['to'];
        }

        $sqlWhere = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM events WHERE {$sqlWhere}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->pdo->prepare(
            "SELECT id, title, description, venue, start_at, end_at, tickets_total, tickets_available, price_cents, created_at
             FROM events WHERE {$sqlWhere}
             ORDER BY start_at ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);
        $rows = $listStmt->fetchAll();

        return [
            'data' => $rows,
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
            'SELECT id, title, description, venue, start_at, end_at, tickets_total, tickets_available, price_cents, created_at
             FROM events WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array{title: string, description?: string, venue: string, start_at: string, end_at: string, tickets_total: int, price_cents?: int} $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $tickets = (int) $data['tickets_total'];
        $price = (int) ($data['price_cents'] ?? 0);
        $stmt = $this->pdo->prepare(
            'INSERT INTO events (title, description, venue, start_at, end_at, tickets_total, tickets_available, price_cents)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['venue'],
            $data['start_at'],
            $data['end_at'],
            $tickets,
            $tickets,
            $price,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $created = $this->findById($id);
        if ($created === null) {
            throw new HttpException('Failed to load created event', 500);
        }

        return $created;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            throw new HttpException('Event not found', 404);
        }

        $title = $data['title'] ?? $existing['title'];
        $description = $data['description'] ?? $existing['description'];
        $venue = $data['venue'] ?? $existing['venue'];
        $startAt = $data['start_at'] ?? $existing['start_at'];
        $endAt = $data['end_at'] ?? $existing['end_at'];
        $priceCents = isset($data['price_cents']) ? (int) $data['price_cents'] : (int) $existing['price_cents'];

        if (array_key_exists('tickets_total', $data)) {
            $newTotal = (int) $data['tickets_total'];
            $sold = (int) $existing['tickets_total'] - (int) $existing['tickets_available'];
            if ($newTotal < $sold) {
                throw new HttpException('tickets_total cannot be less than already reserved tickets', 400);
            }
            $newAvailable = $newTotal - $sold;
            $stmt = $this->pdo->prepare(
                'UPDATE events SET title = ?, description = ?, venue = ?, start_at = ?, end_at = ?,
                 tickets_total = ?, tickets_available = ?, price_cents = ? WHERE id = ?'
            );
            $stmt->execute([$title, $description, $venue, $startAt, $endAt, $newTotal, $newAvailable, $priceCents, $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE events SET title = ?, description = ?, venue = ?, start_at = ?, end_at = ?, price_cents = ? WHERE id = ?'
            );
            $stmt->execute([$title, $description, $venue, $startAt, $endAt, $priceCents, $id]);
        }

        $updated = $this->findById($id);
        if ($updated === null) {
            throw new HttpException('Event not found after update', 500);
        }

        return $updated;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM events WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new HttpException('Event not found', 404);
        }
    }

    /**
     * @internal Used by booking flow
     * @return array<string, mixed>
     */
    public function lockRowForUpdate(int $id, \PDO $pdo): array
    {
        $stmt = $pdo->prepare(
            'SELECT id, title, tickets_total, tickets_available, price_cents FROM events WHERE id = ? FOR UPDATE'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new HttpException('Event not found', 404);
        }

        return $row;
    }

    public function decrementTickets(\PDO $pdo, int $eventId, int $quantity): void
    {
        $stmt = $pdo->prepare(
            'UPDATE events SET tickets_available = tickets_available - ? WHERE id = ? AND tickets_available >= ?'
        );
        $stmt->execute([$quantity, $eventId, $quantity]);
        if ($stmt->rowCount() === 0) {
            throw new HttpException('Not enough tickets available', 409);
        }
    }

    public function incrementTickets(\PDO $pdo, int $eventId, int $quantity): void
    {
        $stmt = $pdo->prepare(
            'UPDATE events SET tickets_available = tickets_available + ? WHERE id = ?'
        );
        $stmt->execute([$quantity, $eventId]);
    }
}
