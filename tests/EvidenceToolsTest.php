<?php

declare(strict_types=1);

namespace TaskTrack\Tests;

use PHPUnit\Framework\TestCase;
use TaskTrack\CsvMigration;
use TaskTrack\ResourceReport;

final class EvidenceToolsTest extends TestCase
{
    public function testMigrationRejectsUnknownStatusWithoutOverwritingOutput(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'csv-');
        $target = tempnam(sys_get_temp_dir(), 'json-');
        self::assertIsString($source);
        self::assertIsString($target);
        try {
            file_put_contents($source, "title,status\nContoh,unknown\n");
            file_put_contents($target, 'original');
            try {
                CsvMigration::migrate($source, $target);
                self::fail('Status tidak valid harus ditolak');
            } catch (\RuntimeException $error) {
                self::assertSame('Status tugas tidak valid.', $error->getMessage());
                self::assertSame('original', file_get_contents($target));
            }
        } finally {
            unlink($source);
            unlink($target);
        }
    }

    public function testMigratesCsvIntoJsonWithoutLosingUnicode(): void
    {
        self::assertTrue(class_exists(CsvMigration::class));
        $source = tempnam(sys_get_temp_dir(), 'csv-');
        $target = tempnam(sys_get_temp_dir(), 'json-');
        self::assertIsString($source);
        self::assertIsString($target);
        try {
            file_put_contents($source, "title,status\nBelajar SQL,todo\nMengulas tugas,done\n");
            CsvMigration::migrate($source, $target);
            self::assertSame([
                ['title' => 'Belajar SQL', 'status' => 'todo'],
                ['title' => 'Mengulas tugas', 'status' => 'done'],
            ], \TaskTrack\TaskFile::read($target));
        } finally {
            unlink($source);
            unlink($target);
        }
    }

    public function testResourceBarShowsValueAndDemonstrationThreshold(): void
    {
        self::assertTrue(class_exists(ResourceReport::class));
        $report = ResourceReport::memoryBar(45.0, 80.0);
        self::assertStringContainsString('45.0%', $report);
        self::assertStringContainsString('80.0%', $report);
        self::assertStringContainsString('normal', $report);
        self::assertStringContainsString('peringatan', ResourceReport::memoryBar(81.0, 80.0));
    }
}
