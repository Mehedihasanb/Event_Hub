<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\EventRepository;

final class EventController
{
    public function __construct(private readonly EventRepository $events)
    {
    }

    public function index(Request $request): void
    {
        $result = $this->events->findPaginated($request->query);
        Response::json(200, $result);
    }

    public function show(Request $request, int $id): void
    {
        $event = $this->events->findById($id);
        if ($event === null) {
            throw new HttpException('Event not found', 404);
        }
        Response::json(200, ['data' => $event]);
    }

    public function store(Request $request): void
    {
        $body = $request->requireJson();
        $this->validateEventPayload($body, false);

        $created = $this->events->create([
            'title' => (string) $body['title'],
            'description' => (string) ($body['description'] ?? ''),
            'venue' => (string) $body['venue'],
            'start_at' => (string) $body['start_at'],
            'end_at' => (string) $body['end_at'],
            'tickets_total' => (int) $body['tickets_total'],
            'price_cents' => (int) ($body['price_cents'] ?? 0),
        ]);

        Response::json(201, ['data' => $created]);
    }

    public function update(Request $request, int $id): void
    {
        $body = $request->requireJson();
        $this->validateEventPayload($body, true);

        $updated = $this->events->update($id, $body);
        Response::json(200, ['data' => $updated]);
    }

    public function destroy(int $id): void
    {
        $this->events->delete($id);
        Response::json(200, ['message' => 'Event deleted']);
    }

    /** @param array<string, mixed> $body */
    private function validateEventPayload(array $body, bool $partial): void
    {
        $required = ['title', 'venue', 'start_at', 'end_at'];
        if (!$partial) {
            $required[] = 'tickets_total';
        }
        foreach ($required as $key) {
            if (!$partial && (!isset($body[$key]) || $body[$key] === '')) {
                throw new HttpException("Field {$key} is required", 422);
            }
        }
        if (isset($body['tickets_total']) && (int) $body['tickets_total'] < 0) {
            throw new HttpException('tickets_total must be non-negative', 422);
        }
        if (isset($body['price_cents']) && (int) $body['price_cents'] < 0) {
            throw new HttpException('price_cents must be non-negative', 422);
        }
    }
}
