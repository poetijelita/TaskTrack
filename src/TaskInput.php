<?php

declare(strict_types=1);
namespace TaskTrack;

final class TaskInput
{
    /** @param array<string, mixed> $input @return list<string> */
    public static function validate(array $input): array
    {
        $errors = [];
        $title = is_string($input["title"] ?? null)
            ? trim($input["title"])
            : "";
        if ($title === "" || mb_strlen($title) > 200) {
            $errors[] = "Judul wajib diisi, maksimal 200 karakter.";
        }
        $description = $input["description"] ?? "";
        if (!is_string($description) || strlen($description) > 65535) {
            $errors[] = "Deskripsi tidak valid atau terlalu panjang.";
        }
        if (
            !in_array($input["status"] ?? "", ["todo", "doing", "done"], true)
        ) {
            $errors[] = "Status tidak valid.";
        }
        if (
            !in_array($input["priority"] ?? "", ["low", "medium", "high"], true)
        ) {
            $errors[] = "Prioritas tidak valid.";
        }
        $date = $input["due_date"] ?? "";
        if (!is_string($date)) {
            $errors[] = "Tanggal tidak valid.";
            return $errors;
        }
        if (
            $date !== "" &&
            (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ||
                !checkdate(
                    (int) substr($date, 5, 2),
                    (int) substr($date, 8, 2),
                    (int) substr($date, 0, 4),
                ))
        ) {
            $errors[] = "Tanggal tidak valid.";
        }
        return $errors;
    }
}
