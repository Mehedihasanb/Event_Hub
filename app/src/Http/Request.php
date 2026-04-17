<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /**
     * @param array<string, string> $query
     * @param array{sub: int, role: string}|null $user JWT claims when authenticated
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly ?array $json,
        public readonly array $routeParams,
        public readonly ?array $user = null,
    ) {
    }

    /**
     * @param array{sub: int, role: string}|null $user
     */
    public static function fromGlobals(array $routeParams = [], ?array $user = null): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
        $query = $_GET;
        $raw = file_get_contents('php://input') ?: '';
        $json = null;
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $json = $decoded;
            }
        }

        return new self($method, $uri, $query, $json, $routeParams, $user);
    }

    public function requireJson(): array
    {
        if ($this->json === null) {
            throw new HttpException('Invalid or missing JSON body', 400);
        }

        return $this->json;
    }
}
