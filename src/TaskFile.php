<?php

declare(strict_types=1);

namespace TaskTrack;

use RuntimeException;

/** Menyimpan dan membaca data tugas dalam berkas JSON. */
final class TaskFile
{
    /** @param list<array<string, string>> $tasks */
    public static function write(string $path, array $tasks): void
    {
        $json = json_encode($tasks, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException('Berkas tidak dapat ditulis.');
        }
    }

    /** @return list<array<string, string>> */
    public static function read(string $path): array
    {
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Berkas tidak dapat dibaca.');
        }
        $tasks = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($tasks) || !array_is_list($tasks)) {
            throw new RuntimeException('Isi berkas tugas tidak valid.');
        }
        foreach ($tasks as $task) {
            if (!is_array($task)) {
                throw new RuntimeException('Isi berkas tugas tidak valid.');
            }
            foreach ($task as $key => $value) {
                if (!is_string($key) || !is_string($value)) {
                    throw new RuntimeException('Isi berkas tugas tidak valid.');
                }
            }
        }
        return $tasks;
    }
}
