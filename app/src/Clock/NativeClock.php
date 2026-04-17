<?php

declare(strict_types=1);

namespace App\Clock;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/** PSR-20 clock for JWT time validation without extra Composer packages. */
final class NativeClock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
