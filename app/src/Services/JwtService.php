<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\HttpException;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use App\Clock\NativeClock;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

final class JwtService
{
    private Configuration $config;
    private int $ttlSeconds;

    public function __construct(?string $secret = null, int $ttlSeconds = 86400)
    {
        $key = $secret ?? (getenv('JWT_SECRET') ?: 'dev-change-me');
        $this->config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($key),
        );
        $this->ttlSeconds = $ttlSeconds;
    }

    public function encode(int $userId, string $role): string
    {
        $now = new DateTimeImmutable();

        $token = $this->config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+' . $this->ttlSeconds . ' seconds'))
            ->relatedTo((string) $userId)
            ->withClaim('role', $role)
            ->getToken($this->config->signer(), $this->config->signingKey());

        return $token->toString();
    }

    /** @return array{sub: int, role: string} */
    public function decode(string $tokenString): array
    {
        try {
            $token = $this->config->parser()->parse($tokenString);
        } catch (\Throwable) {
            throw new HttpException('Invalid or expired token', 401);
        }

        if (!$token instanceof UnencryptedToken) {
            throw new HttpException('Invalid token', 401);
        }

        $constraints = [
            new SignedWith($this->config->signer(), $this->config->signingKey()),
            new LooseValidAt(new NativeClock()),
        ];

        if (!$this->config->validator()->validate($token, ...$constraints)) {
            throw new HttpException('Invalid or expired token', 401);
        }

        $claims = $token->claims();
        if (!$claims->has('sub') || !$claims->has('role')) {
            throw new HttpException('Invalid token payload', 401);
        }

        return [
            'sub' => (int) $claims->get('sub'),
            'role' => (string) $claims->get('role'),
        ];
    }

    /** @return array{sub: int, role: string}|null */
    public function tryDecodeFromHeader(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return null;
        }

        try {
            return $this->decode($token);
        } catch (HttpException) {
            return null;
        }
    }

    /** @return array{sub: int, role: string} */
    public function requireBearer(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            throw new HttpException('Missing or invalid Authorization header', 401);
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            throw new HttpException('Missing bearer token', 401);
        }

        return $this->decode($token);
    }
}
