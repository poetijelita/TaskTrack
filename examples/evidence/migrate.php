<?php

declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

if ($argc !== 3 || is_file($argv[2])) {
    fwrite(STDERR, "Gunakan: php migrate.php sumber.csv tujuan-baru.json\n");
    exit(1);
}
TaskTrack\CsvMigration::migrate($argv[1], $argv[2]);
printf("Migrasi selesai: %d tugas.\n", count(TaskTrack\TaskFile::read($argv[2])));
