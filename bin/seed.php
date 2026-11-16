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
$email = "demo@tasktrack.local";
$stmt = $db->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "Akun demo sudah ada.\n";
    exit();
}
$db->beginTransaction();
try {
    $stmt = $db->prepare(
        "INSERT INTO users(name,email,password_hash) VALUES(?,?,?)",
    );
    $stmt->execute([
        "Pengguna Demo",
        $email,
        password_hash("tasktrack123", PASSWORD_DEFAULT),
    ]);
    $uid = (int) $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO categories(user_id,name) VALUES(?,?)");
    $stmt->execute([$uid, "Belajar"]);
    $category = (int) $db->lastInsertId();
    $stmt = $db->prepare(
        "INSERT INTO tasks(user_id,category_id,title,description,status,priority,due_date) VALUES(?,?,?,?,?,?,?)",
    );
    $stmt->execute([
        $uid,
        $category,
        "Pelajari TaskTrack",
        "Coba ubah status dan tambahkan tugas baru.",
        "todo",
        "medium",
        null,
    ]);
    $db->commit();
    echo "Akun demo dibuat: demo@tasktrack.local / tasktrack123\n";
} catch (Throwable $e) {
    $db->rollBack();
    throw $e;
}
