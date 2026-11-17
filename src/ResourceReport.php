<?php

declare(strict_types=1);

namespace TaskTrack;

use InvalidArgumentException;

/** Visualisasi teks dengan ambang yang dipilih untuk demonstrasi lokal. */
final class ResourceReport
{
    public static function memoryBar(float $percent, float $threshold): string
    {
        if ($percent < 0 || $percent > 100 || $threshold <= 0 || $threshold > 100) {
            throw new InvalidArgumentException('Persentase harus berada antara 0 dan 100.');
        }
        $filled = (int) round($percent / 5);
        return sprintf('[%s%s] %.1f%% | ambang %.1f%% | %s',
            str_repeat('#', $filled), str_repeat('-', 20 - $filled), $percent, $threshold,
            $percent >= $threshold ? 'peringatan' : 'normal');
    }
}
