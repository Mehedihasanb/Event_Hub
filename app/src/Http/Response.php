<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    /** @param array<string, mixed>|list<mixed> $data */
    public static function json(int $statusCode, array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
