<?php

declare(strict_types=1);

namespace TaskTrack\Tests;

/** Sumber status pengganti untuk pengujian tanpa mematikan database. */
final class HealthProbeStub
{
    public function __construct(private bool $healthy) {}

    public function isHealthy(): bool
    {
        return $this->healthy;
    }
}
