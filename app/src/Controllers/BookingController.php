<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;
use App\Services\PaymentService;

final class BookingController
{
    public function __construct(
        private readonly BookingRepository $bookings,
        private readonly EventRepository $events,
        private readonly PaymentService $payments,
    ) {
    }

    public function index(Request $request): void
    {
        $actor = $this->requireUser($request);
        $result = $this->bookings->findPaginatedForActor($actor, $request->query);
        Response::json(200, $result);
    }

    public function show(Request $request, int $id): void
    {
        $actor = $this->requireUser($request);
        $row = $this->bookings->findById($id);
        if ($row === null) {
            throw new HttpException('Booking not found', 404);
        }
        if ($actor['role'] !== 'admin' && (int) $row['user_id'] !== $actor['sub']) {
            throw new HttpException('Forbidden', 403);
        }
        Response::json(200, ['data' => $row]);
    }

    public function store(Request $request): void
    {
        $actor = $this->requireUser($request);
        $body = $request->requireJson();
        if (!isset($body['event_id'], $body['quantity'])) {
            throw new HttpException('event_id and quantity are required', 422);
        }
        $eventId = (int) $body['event_id'];
        $quantity = (int) $body['quantity'];

        $created = $this->bookings->create($actor['sub'], $eventId, $quantity);
        Response::json(201, ['data' => $created]);
    }

    public function update(Request $request, int $id): void
    {
        $actor = $this->requireUser($request);
        if ($actor['role'] !== 'admin') {
            throw new HttpException('Forbidden', 403);
        }
        $body = $request->requireJson();
        if (!isset($body['status'])) {
            throw new HttpException('status is required', 422);
        }
        $updated = $this->bookings->updateStatus($id, (string) $body['status']);
        Response::json(200, ['data' => $updated]);
    }

    public function destroy(Request $request, int $id): void
    {
        $actor = $this->requireUser($request);
        if ($actor['role'] === 'admin') {
            $this->bookings->adminDelete($id);
            Response::json(200, ['message' => 'Booking deleted']);
        }

        $this->bookings->cancelForUser($id, $actor['sub']);
        Response::json(200, ['message' => 'Booking cancelled']);
    }

    public function pay(Request $request, int $id): void
    {
        $actor = $this->requireUser($request);
        $row = $this->bookings->findById($id);
        if ($row === null) {
            throw new HttpException('Booking not found', 404);
        }
        if ((int) $row['user_id'] !== $actor['sub']) {
            throw new HttpException('Forbidden', 403);
        }
        if ($row['status'] !== 'pending') {
            throw new HttpException('Only pending bookings can be paid', 409);
        }

        $event = $this->events->findById((int) $row['event_id']);
        if ($event === null) {
            throw new HttpException('Event not found', 404);
        }

        $booking = [
            'quantity' => (int) $row['quantity'],
            'status' => (string) $row['status'],
        ];

        $payment = $this->payments->processPayment($booking, $event);

        $pdo = Database::get();
        $pdo->beginTransaction();
        try {
            $this->bookings->setPaymentRef($id, $payment['reference']);
            $this->bookings->updateStatus($id, 'paid');
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            if ($e instanceof HttpException) {
                throw $e;
            }
            throw new HttpException('Could not finalize payment', 500);
        }

        $fresh = $this->bookings->findById($id);
        Response::json(200, [
            'message' => 'Payment completed successfully.',
            'payment' => $payment,
            'data' => $fresh,
        ]);
    }

    /** @return array{sub: int, role: string} */
    private function requireUser(Request $request): array
    {
        if ($request->user === null) {
            throw new HttpException('Authentication required', 401);
        }

        return $request->user;
    }
}
