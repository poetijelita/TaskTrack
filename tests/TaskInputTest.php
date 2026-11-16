<?php

declare(strict_types=1);
namespace TaskTrack\Tests;

use PHPUnit\Framework\TestCase;
use TaskTrack\TaskInput;
final class TaskInputTest extends TestCase
{
    public function testAcceptsValidTask(): void
    {
        self::assertSame(
            [],
            TaskInput::validate([
                "title" => "Belajar",
                "status" => "todo",
                "priority" => "medium",
                "due_date" => "2026-12-31",
            ]),
        );
    }
    public function testRejectsEmptyTitleAndUnknownChoices(): void
    {
        self::assertCount(
            3,
            TaskInput::validate([
                "title" => " ",
                "status" => "wrong",
                "priority" => "wrong",
            ]),
        );
    }
    public function testRejectsInvalidDate(): void
    {
        self::assertNotEmpty(
            TaskInput::validate([
                "title" => "Belajar",
                "status" => "todo",
                "priority" => "high",
                "due_date" => "2026-02-30",
            ]),
        );
    }
    public function testUnicodeTitleIsMeasuredInCharacters(): void
    {
        self::assertSame(
            [],
            TaskInput::validate([
                "title" => str_repeat("é", 150),
                "status" => "todo",
                "priority" => "medium",
            ]),
        );
    }
    public function testRejectsNonStringTitleAndDescription(): void
    {
        self::assertNotEmpty(
            TaskInput::validate([
                "title" => ["x"],
                "description" => ["x"],
                "status" => "todo",
                "priority" => "medium",
            ]),
        );
    }
}
