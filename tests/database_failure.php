<?php

declare(strict_types=1);
$admin = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";charset=utf8mb4",
    "root",
    "root_local",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$name = "tt_test_" . bin2hex(random_bytes(6));
$admin->exec("CREATE DATABASE `{$name}`");
$p = null;
try {
    $admin->exec("GRANT ALL PRIVILEGES ON `{$name}`.* TO 'tasktrack'@'%'");
    $env = array_merge($_ENV, [
        "DB_HOST" => getenv("DB_HOST"),
        "DB_NAME" => $name,
        "DB_USER" => getenv("DB_USER"),
        "DB_PASSWORD" => getenv("DB_PASSWORD"),
    ]);
    $p = proc_open(
        [PHP_BINARY, "-S", "127.0.0.1:18991", "-t", "/var/www/html/public"],
        [
            0 => ["pipe", "r"],
            1 => ["file", "/dev/null", "a"],
            2 => ["file", "/dev/null", "a"],
        ],
        $pipes,
        null,
        $env,
    );
    if (!is_resource($p)) {
        throw new RuntimeException("Server uji gagal");
    }
    fclose($pipes[0]);
    $body = false;
    for ($i = 0; $i < 30 && $body === false; $i++) {
        usleep(100000);
        $body = @file_get_contents("http://127.0.0.1:18991/");
    }
    if ($body === false) {
        throw new RuntimeException("Server uji tidak tersedia");
    }
    $headers = $http_response_header;
    preg_match('/name="csrf" value="([^"]+)"/', $body, $m);
    $token = $m[1] ?? "";
    $cookie = "";
    foreach ($headers as $line) {
        if (stripos($line, "Set-Cookie: PHPSESSID=") === 0) {
            $cookie = explode(";", substr($line, 12))[0];
        }
    }
    $ctx = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Cookie: {$cookie}\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            "content" => http_build_query([
                "csrf" => $token,
                "action" => "login",
                "email" => "x",
                "password" => "x",
            ]),
            "ignore_errors" => true,
        ],
    ]);
    $out = file_get_contents("http://127.0.0.1:18991/", false, $ctx);
    $status = $http_response_header[0] ?? "";
    if (
        !str_contains($status, "500") ||
        !str_contains($out, "Terjadi kesalahan. Silakan coba lagi.") ||
        str_contains($out, "SQLSTATE")
    ) {
        throw new RuntimeException(
            "Penanganan query gagal: " . $status . " " . $out,
        );
    }
    echo "Kegagalan query ditangani dengan HTTP 500 tanpa membocorkan rincian.\n";
    proc_terminate($p);
    proc_close($p);
    $p = null;
    $env["DB_NAME"] = $name . "_missing";
    $p = proc_open(
        [PHP_BINARY, "-S", "127.0.0.1:18992", "-t", "/var/www/html/public"],
        [
            0 => ["pipe", "r"],
            1 => ["file", "/dev/null", "a"],
            2 => ["file", "/dev/null", "a"],
        ],
        $pipes,
        null,
        $env,
    );
    if (!is_resource($p)) {
        throw new RuntimeException("Server koneksi uji gagal");
    }
    fclose($pipes[0]);
    $out = false;
    for ($i = 0; $i < 30 && $out === false; $i++) {
        usleep(100000);
        $ctx = stream_context_create(["http" => ["ignore_errors" => true]]);
        $out = @file_get_contents("http://127.0.0.1:18992/", false, $ctx);
    }
    $status = $http_response_header[0] ?? "";
    if (
        $out === false ||
        !str_contains($status, "503") ||
        !str_contains($out, "Database belum tersedia") ||
        str_contains($out, "SQLSTATE")
    ) {
        throw new RuntimeException("Penanganan koneksi gagal: " . $status);
    }
    echo "Kegagalan koneksi ditangani dengan HTTP 503.\n";
} finally {
    if (is_resource($p)) {
        proc_terminate($p);
        proc_close($p);
    }
    $admin->exec("DROP DATABASE `{$name}`");
}
