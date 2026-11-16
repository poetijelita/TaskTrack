<?php

declare(strict_types=1);
use TaskTrack\TaskInput;
require __DIR__ . "/../src/TaskInput.php";
session_start(["cookie_httponly" => true, "cookie_samesite" => "Lax"]);
set_exception_handler(static function (Throwable $error): void {
    error_log("TaskTrack error: " . $error->getMessage());
    http_response_code(500);
    exit("Terjadi kesalahan. Silakan coba lagi.");
});
function e(mixed $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
function go(string $p = ""): never
{
    header("Location: /" . $p);
    exit();
}
function csrf(): string
{
    return $_SESSION["csrf"] ??= bin2hex(random_bytes(32));
}
function query(PDO $db, string $sql, array $args = []): PDOStatement
{
    $s = $db->prepare($sql);
    $s->execute($args);
    return $s;
}
try {
    $db = new PDO(
        "mysql:host=" .
            getenv("DB_HOST") .
            ";dbname=" .
            getenv("DB_NAME") .
            ";charset=utf8mb4",
        getenv("DB_USER"),
        getenv("DB_PASSWORD"),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
} catch (PDOException $ex) {
    error_log("TaskTrack database unavailable: " . $ex->getMessage());
    http_response_code(503);
    exit("Database belum tersedia. Coba lagi beberapa saat.");
}
$page = (string) ($_GET["page"] ?? "dashboard");
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals(csrf(), (string) ($_POST["csrf"] ?? ""))) {
        http_response_code(403);
        exit("Token tidak valid.");
    }
    $action = (string) ($_POST["action"] ?? "");
    if ($action === "login") {
        $u = query($db, "SELECT * FROM users WHERE email = ?", [
            trim((string) ($_POST["email"] ?? "")),
        ])->fetch(PDO::FETCH_ASSOC);
        if (
            $u &&
            password_verify(
                (string) ($_POST["password"] ?? ""),
                $u["password_hash"],
            )
        ) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = (int) $u["id"];
            $_SESSION["name"] = $u["name"];
            unset($_SESSION["csrf"]);
            go();
        }
        $error = "Email atau kata sandi salah.";
        $page = "login";
    } elseif ($action === "logout") {
        $_SESSION = [];
        session_destroy();
        go("?page=login");
    } elseif (!isset($_SESSION["user_id"])) {
        go("?page=login");
    } else {
        $uid = (int) $_SESSION["user_id"];
        $id = (int) ($_POST["id"] ?? 0);
        if (
            $id > 0 &&
            in_array(
                $action,
                [
                    "task_save",
                    "task_delete",
                    "category_save",
                    "category_delete",
                ],
                true,
            )
        ) {
            $table = str_starts_with($action, "task_") ? "tasks" : "categories";
            if (
                !query(
                    $db,
                    "SELECT id FROM {$table} WHERE id=? AND user_id=?",
                    [$id, $uid],
                )->fetch()
            ) {
                http_response_code(404);
                exit("Data tidak ditemukan.");
            }
        }
        if ($action === "category_save") {
            $name = is_string($_POST["name"] ?? null)
                ? trim($_POST["name"])
                : "";
            if ($name === "" || mb_strlen($name) > 100) {
                $error = "Nama kategori wajib diisi dan maksimal 100 karakter.";
            } else {
                try {
                    if ($id) {
                        query(
                            $db,
                            "UPDATE categories SET name=? WHERE id=? AND user_id=?",
                            [$name, $id, $uid],
                        );
                    } else {
                        query(
                            $db,
                            "INSERT INTO categories (name,user_id) VALUES (?,?)",
                            [$name, $uid],
                        );
                    }
                    go("?page=categories");
                } catch (PDOException $ex) {
                    if ($ex->getCode() !== "23000") {
                        throw $ex;
                    }
                    $error = "Nama kategori sudah digunakan.";
                }
            }
            $page = "categories";
        } elseif ($action === "category_delete") {
            query($db, "DELETE FROM categories WHERE id=? AND user_id=?", [
                $id,
                $uid,
            ]);
            go("?page=categories");
        } elseif ($action === "task_delete") {
            query($db, "DELETE FROM tasks WHERE id=? AND user_id=?", [
                $id,
                $uid,
            ]);
            go("?page=tasks");
        } elseif ($action === "task_save") {
            $errors = TaskInput::validate($_POST);
            $cat = (int) ($_POST["category_id"] ?? 0);
            if (
                $cat &&
                !query(
                    $db,
                    "SELECT id FROM categories WHERE id=? AND user_id=?",
                    [$cat, $uid],
                )->fetch()
            ) {
                $errors[] = "Kategori tidak valid.";
            }
            if ($errors) {
                $error = implode(" ", $errors);
                $page = "task_form";
            } else {
                $data = [
                    trim((string) $_POST["title"]),
                    trim((string) ($_POST["description"] ?? "")),
                    $_POST["status"],
                    $_POST["priority"],
                    $_POST["due_date"] ?? "" ?: null,
                    $cat ?: null,
                ];
                if ($id) {
                    query(
                        $db,
                        "UPDATE tasks SET title=?,description=?,status=?,priority=?,due_date=?,category_id=? WHERE id=? AND user_id=?",
                        [...$data, $id, $uid],
                    );
                } else {
                    query(
                        $db,
                        "INSERT INTO tasks (title,description,status,priority,due_date,category_id,user_id) VALUES (?,?,?,?,?,?,?)",
                        [...$data, $uid],
                    );
                }
                go("?page=tasks");
            }
        }
    }
}
if (!isset($_SESSION["user_id"])) {
    $page = "login";
}
$uid = (int) ($_SESSION["user_id"] ?? 0);
$categories = [];
$tasks = [];
$edit = null;
$counts = [];
if ($uid) {
    $categories = query(
        $db,
        "SELECT * FROM categories WHERE user_id=? ORDER BY name",
        [$uid],
    )->fetchAll(PDO::FETCH_ASSOC);
    if ($page === "dashboard") {
        $counts = query(
            $db,
            "SELECT status, COUNT(*) AS total FROM tasks WHERE user_id=? GROUP BY status",
            [$uid],
        )->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    if ($page === "task_form" && !empty($_GET["id"])) {
        $edit = query($db, "SELECT * FROM tasks WHERE id=? AND user_id=?", [
            (int) $_GET["id"],
            $uid,
        ])->fetch(PDO::FETCH_ASSOC);
        if (!$edit) {
            http_response_code(404);
            exit("Tugas tidak ditemukan.");
        }
    }
    if ($page === "categories" && !empty($_GET["id"])) {
        $edit = query(
            $db,
            "SELECT * FROM categories WHERE id=? AND user_id=?",
            [(int) $_GET["id"], $uid],
        )->fetch(PDO::FETCH_ASSOC);
        if (!$edit) {
            http_response_code(404);
            exit("Kategori tidak ditemukan.");
        }
    }
    if ($page === "tasks") {
        $where = ["t.user_id=?"];
        $params = [$uid];
        $search = trim((string) ($_GET["q"] ?? ""));
        if ($search !== "") {
            $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $params[] = "%" . $search . "%";
            $params[] = "%" . $search . "%";
        }
        foreach (
            [
                "status" => ["todo", "doing", "done"],
                "priority" => ["low", "medium", "high"],
            ]
            as $field => $allowed
        ) {
            if (in_array($_GET[$field] ?? "", $allowed, true)) {
                $where[] = "t.{$field}=?";
                $params[] = $_GET[$field];
            }
        }
        if ((int) ($_GET["category"] ?? 0) > 0) {
            $where[] = "t.category_id=?";
            $params[] = (int) $_GET["category"];
        }
        $orders = [
            "newest" => "t.created_at DESC, t.id DESC",
            "due" => "t.due_date IS NULL, t.due_date ASC, t.id DESC",
            "priority" => "FIELD(t.priority,'high','medium','low'), t.id DESC",
        ];
        $order = $orders[$_GET["sort"] ?? "newest"] ?? $orders["newest"];
        $tasks = query(
            $db,
            "SELECT t.*,c.name AS category FROM tasks t LEFT JOIN categories c ON c.id=t.category_id AND c.user_id=t.user_id WHERE " .
                implode(" AND ", $where) .
                " ORDER BY " .
                $order,
            $params,
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
require __DIR__ . "/../views/layout.php";
