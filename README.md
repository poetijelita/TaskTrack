# TaskTrack

Aplikasi manajemen tugas pribadi berbasis PHP dan MariaDB untuk mengatur, melacak, dan mengelompokkan pekerjaan sehari-hari.

## Menjalankan secara lokal

Siapkan Docker Desktop dan jalankan:

```sh
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/seed.php
```

Buka http://localhost:8080 dan masuk dengan `demo@tasktrack.local` / `tasktrack123`. Akun dan konfigurasi ini hanya untuk penggunaan lokal. phpMyAdmin tersedia di http://localhost:8081 dengan akun `tasktrack` / `tasktrack_local`.

Data disimpan dalam volume Docker. `docker compose down` menghentikan layanan; `docker compose down -v` juga menghapus seluruh data database. Skema diterapkan ketika volume database pertama kali dibuat.

## Pengujian

```sh
docker compose exec app composer test
docker compose exec app composer analyse
docker compose exec -T app php tests/regression.php
docker compose exec -T app php tests/authorization.php
```

Tes `tests/database_failure.php` membuat dan menghapus database sementara dan hanya boleh dijalankan dalam lingkungan Docker lokal.
