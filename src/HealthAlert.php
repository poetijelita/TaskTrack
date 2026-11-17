<?php

declare(strict_types=1);

namespace TaskTrack;

use InvalidArgumentException;

/** Menghasilkan peristiwa gangguan dan pemulihan untuk satu proses pemantau. */
final class HealthAlert
{
    private bool $unavailable = false;
    private ?int $lastAlert = null;

    public function __construct(private int $repeatSeconds)
    {
        if ($repeatSeconds < 1) {
            throw new InvalidArgumentException('Interval harus positif.');
        }
    }

    public function check(bool $healthy, int $now): ?string
    {
        if ($healthy) {
            if ($this->unavailable) {
                $this->unavailable = false;
                $this->lastAlert = null;
                return 'recovered';
            }
            return null;
        }
        $this->unavailable = true;
        if ($this->lastAlert === null || $now - $this->lastAlert >= $this->repeatSeconds) {
            $this->lastAlert = $now;
            return 'unavailable';
        }
        return null;
    }
}
