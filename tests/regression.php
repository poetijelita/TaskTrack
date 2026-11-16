<?php

declare(strict_types=1);
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
$cookie = "";
$url = "http://localhost/";
function httpReq(string $path = "", ?array $data = null): array
{
    global $cookie, $url;
    $h = "Cookie: {$cookie}\r\n";
    if ($data !== null) {
        $h .= "Content-Type: application/x-www-form-urlencoded\r\n";
    }
    $ctx = stream_context_create([
        "http" => [
            "method" => $data === null ? "GET" : "POST",
            "header" => $h,
            "content" => $data === null ? "" : http_build_query($data),
            "ignore_errors" => true,
            "follow_location" => 0,
        ],
    ]);
    $body = file_get_contents($url . $path, false, $ctx);
    $headers = $http_response_header ?? [];
    foreach ($headers as $line) {
        if (stripos($line, "Set-Cookie: PHPSESSID=") === 0) {
            $cookie = explode(";", substr($line, 12))[0];
        }
    }
    preg_match("/HTTP\/\S+ (\d+)/", $headers[0] ?? "", $m);
    return [(int) ($m[1] ?? 0), (string) $body];
}
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function token(string $body): string
{
    preg_match('/name="csrf" value="([^"]+)"/', $body, $m);
    check(isset($m[1]), "Token tidak ditemukan");
    return $m[1];
}
function submit(string $action, array $data = []): array
{
    [, $body] = httpReq("?page=tasks");
    return httpReq(
        "",
        array_merge(["csrf" => token($body), "action" => $action], $data),
    );
}
$user = 0;
try {
    $q = $db->prepare(
        "INSERT INTO users(name,email,password_hash) VALUES(?,?,?)",
    );
    $q->execute([
        "Tes",
        "{$tag}@example.test",
        password_hash("password", PASSWORD_DEFAULT),
    ]);
    $user = (int) $db->lastInsertId();
    [, $body] = httpReq();
    [$status, $body] = httpReq("", [
        "csrf" => token($body),
        "action" => "login",
        "email" => "{$tag}@example.test",
        "password" => "salah",
    ]);
    check($status === 200 && str_contains($body, "salah"), "Login salah");
    [$status] = httpReq("", [
        "csrf" => token($body),
        "action" => "login",
        "email" => "{$tag}@example.test",
        "password" => "password",
    ]);
    check($status === 302, "Login benar");
    [$status, $body] = httpReq();
    check($status === 200 && str_contains($body, "Halo, Tes"), "Dashboard");
    [$status] = submit("category_save", ["name" => "Kerja"]);
    check($status === 302, "Tambah kategori");
    $category = (int) $db->lastInsertId();
    $q = $db->prepare("SELECT id FROM categories WHERE user_id=?");
    $q->execute([$user]);
    $category = (int) $q->fetchColumn();
    [$status] = submit("category_save", [
        "id" => $category,
        "name" => "Proyek",
    ]);
    check($status === 302, "Ubah kategori");
    [, $body] = httpReq("?page=categories");
    check(str_contains($body, "Proyek"), "Kategori berubah");
    [$status] = submit("task_save", [
        "title" => "Alpha",
        "description" => "Uji",
        "status" => "doing",
        "priority" => "high",
        "category_id" => $category,
        "due_date" => "2027-01-01",
    ]);
    check($status === 302, "Tambah tugas");
    $q = $db->prepare("SELECT id FROM tasks WHERE user_id=?");
    $q->execute([$user]);
    $task = (int) $q->fetchColumn();
    check($task > 0, "Tugas tersimpan");
    [$status] = submit("task_save", [
        "id" => $task,
        "title" => "Beta",
        "description" => "Ubah",
        "status" => "done",
        "priority" => "low",
        "category_id" => $category,
        "due_date" => "2028-01-01",
    ]);
    check($status === 302, "Ubah tugas");
    [$status] = submit("task_save", [
        "title" => "Gamma",
        "description" => "",
        "status" => "todo",
        "priority" => "high",
        "category_id" => $category,
        "due_date" => "2027-01-01",
    ]);
    check($status === 302, "Tambah tugas kedua");
    [, $body] = httpReq("?page=tasks&sort=due");
    check(strpos($body, "Gamma") < strpos($body, "Beta"), "Urutan tenggat");
    [, $body] = httpReq("?page=tasks&sort=priority");
    check(strpos($body, "Gamma") < strpos($body, "Beta"), "Urutan prioritas");
    [, $body] = httpReq("?page=tasks&sort=newest");
    check(strpos($body, "Gamma") < strpos($body, "Beta"), "Urutan terbaru");
    [, $body] = httpReq(
        "?page=tasks&q=Beta&status=done&priority=low&category=" .
            $category .
            "&sort=due",
    );
    check(str_contains($body, "Beta"), "Pencarian dan filter");
    [, $body] = httpReq("?page=tasks&status=doing&sort=priority");
    check(!str_contains($body, "Beta"), "Filter status");
    [$status, $body] = submit("task_save", [
        "title" => "",
        "description" => "",
        "status" => "todo",
        "priority" => "medium",
    ]);
    check(
        $status === 200 && str_contains($body, "Judul wajib"),
        "Validasi judul",
    );
    [$status] = httpReq("", [
        "csrf" => "invalid",
        "action" => "task_delete",
        "id" => $task,
    ]);
    check($status === 403, "CSRF harus ditolak");
    [$status] = submit("category_delete", ["id" => $category]);
    check($status === 302, "Hapus kategori");
    $q = $db->prepare("SELECT category_id FROM tasks WHERE id=?");
    $q->execute([$task]);
    check($q->fetchColumn() === null, "Kategori tugas harus kosong");
    [$status] = submit("task_delete", ["id" => $task]);
    check($status === 302, "Hapus tugas");
    $q = $db->prepare("SELECT COUNT(*) FROM tasks WHERE id=?");
    $q->execute([$task]);
    check((int) $q->fetchColumn() === 0, "Tugas terhapus");
    [, $body] = httpReq();
    [$status] = httpReq("", ["csrf" => token($body), "action" => "logout"]);
    check($status === 302, "Logout");
    [, $body] = httpReq();
    check(str_contains($body, "Masuk ke TaskTrack"), "Akses setelah logout");
    echo "Regresi HTTP lulus.\n";
} finally {
    if ($user) {
        $q = $db->prepare("DELETE FROM users WHERE id=?");
        $q->execute([$user]);
    }
}
