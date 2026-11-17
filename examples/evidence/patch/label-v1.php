<?php

declare(strict_types=1);

function taskStatusLabel(string $status): string
{
    return match ($status) {
        'todo' => 'Belum dikerjakan',
        'doing' => 'Dikerjakan',
        'done' => 'Selesai',
    };
}
