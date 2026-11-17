<?php

declare(strict_types=1);

namespace TaskTrack\Tests;

use PHPUnit\Framework\TestCase;
use TaskTrack\HealthAlert;

final class HealthAlertTest extends TestCase
{
    public function testFailureAlertsRepeatAtIntervalAndRecoveryAlertsOnce(): void
    {
        self::assertTrue(class_exists(HealthAlert::class));
        $monitor = new HealthAlert(60);
        self::assertSame('unavailable', $monitor->check(false, 100));
        self::assertNull($monitor->check(false, 159));
        self::assertSame('unavailable', $monitor->check(false, 160));
        self::assertSame('recovered', $monitor->check(true, 161));
        self::assertNull($monitor->check(true, 162));
        self::assertSame('unavailable', $monitor->check(false, 163));
    }

    public function testHealthProbeStubSuppliesUnhealthyStatus(): void
    {
        $probe = new HealthProbeStub(false);
        $monitor = new HealthAlert(60);
        self::assertSame('unavailable', $monitor->check($probe->isHealthy(), 0));
    }
}
