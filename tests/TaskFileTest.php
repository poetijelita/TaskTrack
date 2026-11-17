<?php

declare(strict_types=1);

namespace TaskTrack\Tests;

use PHPUnit\Framework\TestCase;
use TaskTrack\TaskFile;

final class TaskFileTest extends TestCase
{
    public function testRejectsAJsonObjectInsteadOfAList(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tasktrack-');
        self::assertIsString($path);
        try {
            file_put_contents($path, '{"title":"Belajar"}');
            $this->expectException(\RuntimeException::class);
            TaskFile::read($path);
        } finally {
            unlink($path);
        }
    }

    public function testTasksCanBeWrittenAndReadBack(): void
    {
        $this->assertTrue(class_exists(TaskFile::class), 'TaskFile should be available');
        $path = tempnam(sys_get_temp_dir(), 'tasktrack-');
        self::assertIsString($path);
        try {
            $tasks = [['title' => 'Belajar SQL', 'status' => 'todo']];
            TaskFile::write($path, $tasks);
            self::assertSame($tasks, TaskFile::read($path));
        } finally {
            unlink($path);
        }
    }
}
