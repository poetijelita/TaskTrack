<?php
$statuses = [
    'todo' => 'Belum dikerjakan',
    'doing' => 'Dikerjakan',
    'done' => 'Selesai',
];
$priorities = [
    'low' => 'Rendah',
    'medium' => 'Sedang',
    'high' => 'Tinggi',
];

function hidden(string $action, int $id = 0): void
{
    echo '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
    echo '<input type="hidden" name="action" value="' . e($action) . '">';
    echo '<input type="hidden" name="id" value="' . $id . '">';
}

function opts(array $items, mixed $selected): void
{
    foreach ($items as $key => $label) {
        $isSelected = (string) $key === (string) $selected ? ' selected' : '';
        echo '<option value="' . e($key) . '"' . $isSelected . '>' . e($label) . '</option>';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>TaskTrack</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <header>
        <a class="brand" href="/">TaskTrack</a>
        <?php if ($uid): ?>
            <nav>
                <a href="/">Dashboard</a>
                <a href="?page=tasks">Tugas</a>
                <a href="?page=categories">Kategori</a>
            </nav>
            <form method="post">
                <?php hidden('logout'); ?>
                <button class="secondary">Keluar</button>
            </form>
        <?php endif; ?>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($page === 'login'): ?>
            <section class="login">
                <p class="eyebrow">SELAMAT DATANG</p>
                <h1>Masuk ke TaskTrack</h1>
                <p>Kelola tugas harian Anda dengan lebih terarah.</p>
                <form method="post">
                    <?php hidden('login'); ?>
                    <label>Email<input name="email" type="email" required autocomplete="username"></label>
                    <label>Kata sandi<input name="password" type="password" required autocomplete="current-password"></label>
                    <button>Masuk</button>
                </form>
            </section>

        <?php elseif ($page === 'dashboard'): ?>
            <p class="eyebrow">RINGKASAN</p>
            <h1>Halo, <?= e($_SESSION['name']) ?>.</h1>
            <p>Pantau pekerjaan Anda hari ini.</p>
            <div class="cards">
                <?php foreach ($statuses as $key => $label): ?>
                    <div class="card">
                        <span><?= e($label) ?></span>
                        <strong><?= e($counts[$key] ?? 0) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="button" href="?page=task_form">+ Tambah tugas</a>

        <?php elseif ($page === 'tasks'): ?>
            <div class="heading">
                <div>
                    <p class="eyebrow">DAFTAR PEKERJAAN</p>
                    <h1>Tugas</h1>
                </div>
                <a class="button" href="?page=task_form">+ Tambah tugas</a>
            </div>

            <form class="filters" method="get">
                <input type="hidden" name="page" value="tasks">
                <label>Cari<input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari tugas"></label>
                <label>Status
                    <select name="status">
                        <option value="">Semua</option>
                        <?php opts($statuses, $_GET['status'] ?? ''); ?>
                    </select>
                </label>
                <label>Prioritas
                    <select name="priority">
                        <option value="">Semua</option>
                        <?php opts($priorities, $_GET['priority'] ?? ''); ?>
                    </select>
                </label>
                <label>Kategori
                    <select name="category">
                        <option value="">Semua</option>
                        <?php foreach ($categories as $c): ?>
                            <?php opts([$c['id'] => $c['name']], $_GET['category'] ?? ''); ?>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Urutkan
                    <select name="sort">
                        <?php opts(['newest' => 'Terbaru', 'due' => 'Tenggat', 'priority' => 'Prioritas'], $_GET['sort'] ?? 'newest'); ?>
                    </select>
                </label>
                <button>Terapkan</button>
            </form>

            <?php if (!$tasks): ?>
                <div class="panel">Belum ada tugas yang cocok.</div>
            <?php endif; ?>
            <?php foreach ($tasks as $t): ?>
                <article class="task">
                    <div>
                        <h2><?= e($t['title']) ?></h2>
                        <p><?= e($t['description']) ?></p>
                        <div class="meta">
                            <span><?= e($statuses[$t['status']]) ?></span>
                            <span>Prioritas <?= e($priorities[$t['priority']]) ?></span>
                            <span><?= e($t['category'] ?? 'Tanpa kategori') ?></span>
                            <span><?= e($t['due_date'] ?: 'Tanpa tenggat') ?></span>
                        </div>
                    </div>
                    <div class="actions">
                        <a href="?page=task_form&amp;id=<?= (int) $t['id'] ?>">Ubah</a>
                        <form method="post" data-confirm="Hapus tugas ini?">
                            <?php hidden('task_delete', (int) $t['id']); ?>
                            <button class="link danger">Hapus</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>

        <?php elseif ($page === 'task_form'): ?>
            <?php $v = ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) ? $_POST : ($edit ?: []); ?>
            <p class="eyebrow">TUGAS</p>
            <h1><?= empty($v['id']) ? 'Tambah' : 'Ubah' ?> tugas</h1>
            <form method="post" class="panel form">
                <?php hidden('task_save', (int) ($v['id'] ?? 0)); ?>
                <label>Judul<input name="title" required maxlength="200" value="<?= e($v['title'] ?? '') ?>"></label>
                <label>Deskripsi<textarea name="description" rows="4"><?= e($v['description'] ?? '') ?></textarea></label>
                <div class="grid">
                    <label>Status<select name="status"><?php opts($statuses, $v['status'] ?? 'todo'); ?></select></label>
                    <label>Prioritas<select name="priority"><?php opts($priorities, $v['priority'] ?? 'medium'); ?></select></label>
                    <label>Tenggat<input type="date" name="due_date" value="<?= e($v['due_date'] ?? '') ?>"></label>
                    <label>Kategori
                        <select name="category_id">
                            <option value="">Tanpa kategori</option>
                            <?php foreach ($categories as $c): ?>
                                <?php opts([$c['id'] => $c['name']], $v['category_id'] ?? ''); ?>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                <div><button>Simpan tugas</button> <a href="?page=tasks">Batal</a></div>
            </form>

        <?php elseif ($page === 'categories'): ?>
            <p class="eyebrow">PENGELOMPOKAN</p>
            <h1>Kategori</h1>
            <form method="post" class="panel inline">
                <?php hidden('category_save', (int) ($edit['id'] ?? 0)); ?>
                <label><?= $edit ? 'Ubah' : 'Tambah' ?> kategori
                    <input name="name" required maxlength="100" value="<?= e($edit['name'] ?? '') ?>">
                </label>
                <button>Simpan</button>
                <?php if ($edit): ?>
                    <a href="?page=categories">Batal</a>
                <?php endif; ?>
            </form>
            <?php foreach ($categories as $c): ?>
                <div class="task">
                    <strong><?= e($c['name']) ?></strong>
                    <div class="actions">
                        <a href="?page=categories&amp;id=<?= (int) $c['id'] ?>">Ubah</a>
                        <form method="post" data-confirm="Hapus kategori? Tugas tetap ada.">
                            <?php hidden('category_delete', (int) $c['id']); ?>
                            <button class="link danger">Hapus</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <h1>Halaman tidak ditemukan</h1>
        <?php endif; ?>
    </main>
    <script src="/app.js"></script>
</body>
</html>
