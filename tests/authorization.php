<?php

declare(strict_types=1);
// Tes memakai dua akun sementara yang dibersihkan setelah pengujian.
$db = new PDO(
    "mysql:host=" .
        getenv("DB_HOST") .
        ";dbname=" .
        getenv("DB_NAME") .
        ";charset=utf8mb4",
    getenv("DB_USER"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$tag = bin2hex(random_bytes(8));
function request(string $url, ?array $data, string &$cookie): array
{
    $headers = "Cookie: {$cookie}\r\n";
    if ($data !== null) {
        $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
    }
    $context = stream_context_create([
        "http" => [
            "method" => $data === null ? "GET" : "POST",
            "header" => $headers,
            "content" => $data === null ? "" : http_build_query($data),
            "ignore_errors" => true,
            "follow_location" => 0,
        ],
    ]);
    $body = file_get_contents($url, false, $context);
    $lines = $http_response_header ?? [];
    foreach ($lines as $line) {
        if (stripos($line, "Set-Cookie: PHPSESSID=") === 0) {
            $cookie = explode(";", substr($line, 12))[0];
        }
    }
    preg_match("/HTTP\/\S+ (\d+)/", $lines[0] ?? "", $m);
    return [(int) ($m[1] ?? 0), (string) $body];
}
$ids = [];
try {
    $insert = $db->prepare(
        "INSERT INTO users(name,email,password_hash) VALUES(?,?,?)",
    );
    foreach (["a", "b"] as $x) {
        $insert->execute([
            $x,
            "{$tag}-{$x}@example.test",
            password_hash("password", PASSWORD_DEFAULT),
        ]);
        $ids[] = (int) $db->lastInsertId();
    }
    $q = $db->prepare("INSERT INTO categories(user_id,name) VALUES(?,?)");
    $q->execute([$ids[0], "private"]);
    $category = (int) $db->lastInsertId();
    $q = $db->prepare(
        "INSERT INTO tasks(user_id,category_id,title,description) VALUES(?,?,?,?)",
    );
    $q->execute([$ids[0], $category, "private task", ""]);
    $task = (int) $db->lastInsertId();
    try {
        $q->execute([$ids[1], $category, "invalid association", ""]);
        throw new RuntimeException("Database menerima kategori pengguna lain");
    } catch (PDOException $e) {
        if ($e->getCode() !== "45000") {
            throw $e;
        }
    }
    try {
        $q = $db->prepare("UPDATE tasks SET user_id=? WHERE id=?");
        $q->execute([$ids[1], $task]);
        throw new RuntimeException(
            "Database menerima perubahan pemilik tugas lintas kategori",
        );
    } catch (PDOException $e) {
        if ($e->getCode() !== "45000") {
            throw $e;
        }
    }
    try {
        $q = $db->prepare("UPDATE categories SET user_id=? WHERE id=?");
        $q->execute([$ids[1], $category]);
        throw new RuntimeException(
            "Database menerima pemindahan kategori yang masih digunakan",
        );
    } catch (PDOException $e) {
        if ($e->getCode() !== "45000") {
            throw $e;
        }
    }
    $cookie = "";
    $url = "http://localhost/";
    [, $body] = request($url, null, $cookie);
    preg_match('/name="csrf" value="([^"]+)"/', $body, $m);
    if (empty($m[1])) {
        throw new RuntimeException("Token login tidak ditemukan");
    }
    [$status] = request(
        $url,
        [
            "csrf" => $m[1],
            "action" => "login",
            "email" => "{$tag}-b@example.test",
            "password" => "password",
        ],
        $cookie,
    );
    if ($status !== 302) {
        throw new RuntimeException("Login gagal: " . $status);
    }
    [, $body] = request($url . "?page=tasks", null, $cookie);
    if (str_contains($body, "private task")) {
        throw new RuntimeException("Tugas pengguna lain terlihat");
    }
    preg_match('/name="csrf" value="([^"]+)"/', $body, $m);
    $token = $m[1] ?? "";
    foreach (
        [
            ["task_delete", $task],
            ["category_delete", $category],
            ["task_save", $task],
            ["category_save", $category],
        ]
        as [$action, $id]
    ) {
        [$status] = request(
            $url,
            [
                "csrf" => $token,
                "action" => $action,
                "id" => $id,
                "title" => "Intrusi",
                "description" => "",
                "status" => "todo",
                "priority" => "low",
                "name" => "Intrusi",
            ],
            $cookie,
        );
        if ($status !== 404) {
            throw new RuntimeException(
                "{$action} harus 404, mendapat {$status}",
            );
        }
    }
    [$status] = request($url . "?page=task_form&id=" . $task, null, $cookie);
    if ($status !== 404) {
        throw new RuntimeException("Form tugas harus 404");
    }
    [$status] = request(
        $url . "?page=categories&id=" . $category,
        null,
        $cookie,
    );
    if ($status !== 404) {
        throw new RuntimeException("Form kategori harus 404");
    }
    echo "Otorisasi dua pengguna lulus.\n";
} finally {
    if ($ids) {
        $s = $db->prepare("DELETE FROM users WHERE id=?");
        foreach ($ids as $id) {
            $s->execute([$id]);
        }
    }
}
