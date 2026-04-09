<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Services\JwtService;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService $jwt,
    ) {
    }

    public function register(Request $request): void
    {
        $body = $request->requireJson();
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpException('Valid email is required', 422);
        }
        if (strlen($password) < 8) {
            throw new HttpException('Password must be at least 8 characters', 422);
        }

        if ($this->users->findByEmail($email) !== null) {
            throw new HttpException('Email is already registered', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $user = $this->users->create($email, $hash, 'user');

        Response::json(201, [
            'message' => 'Registered successfully',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);
    }

    public function login(Request $request): void
    {
        $body = $request->requireJson();
        $email = isset($body['email']) ? trim((string) $body['email']) : '';
        $password = isset($body['password']) ? (string) $body['password'] : '';

        if ($email === '' || $password === '') {
            throw new HttpException('Email and password are required', 422);
        }

        $row = $this->users->findByEmail($email);
        if ($row === null || !$this->users->verifyPassword($row['password_hash'], $password)) {
            throw new HttpException('Invalid credentials', 401);
        }

        $token = $this->jwt->encode((int) $row['id'], (string) $row['role']);

        Response::json(200, [
            'token' => $token,
            'user' => [
                'id' => (int) $row['id'],
                'email' => $row['email'],
                'role' => $row['role'],
            ],
        ]);
    }

    public function me(Request $request): void
    {
        if ($request->user === null) {
            throw new HttpException('Authentication required', 401);
        }

        $profile = $this->users->findProfile($request->user['sub']);
        if ($profile === null) {
            throw new HttpException('User not found', 404);
        }

        Response::json(200, ['data' => $profile]);
    }
}
