<?php

declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

if ($argc !== 3 || !is_numeric($argv[1]) || !is_numeric($argv[2])) {
    fwrite(STDERR, "Gunakan: php resource.php persentase_memori ambang_demo\n");
    exit(1);
}
echo TaskTrack\ResourceReport::memoryBar((float) $argv[1], (float) $argv[2]), PHP_EOL;
