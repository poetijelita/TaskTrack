<?php

declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';

// Simulasi menggunakan waktu relatif; tidak memutus layanan database.
$alert = new TaskTrack\HealthAlert(60);
foreach ([[0, false], [30, false], [60, false], [90, true]] as [$seconds, $healthy]) {
    $event = $alert->check($healthy, $seconds);
    if ($event !== null) {
        printf("t+%ds: %s\n", $seconds, $event);
    }
}
