<?php

declare(strict_types=1);

namespace TaskTrack;

use RuntimeException;

/** Contoh migrasi data latihan dari CSV ke JSON; tidak mengubah database. */
final class CsvMigration
{
    public static function migrate(string $source, string $target): void
    {
        $file = fopen($source, 'rb');
        if ($file === false) {
            throw new RuntimeException('Berkas CSV tidak dapat dibuka.');
        }
        try {
            if (fgetcsv($file, 0, ',', '"', '\\') !== ['title', 'status']) {
                throw new RuntimeException('Kolom CSV harus title,status.');
            }
            $tasks = [];
            while (($row = fgetcsv($file, 0, ',', '"', '\\')) !== false) {
                if (count($row) !== 2 || !is_string($row[0]) || !is_string($row[1])) {
                    throw new RuntimeException('Baris CSV tidak valid.');
                }
                $tasks[] = ['title' => $row[0], 'status' => $row[1]];
            }
            TaskFile::write($target, $tasks);
        } finally {
            fclose($file);
        }
    }
}
