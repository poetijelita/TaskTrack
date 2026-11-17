# Contoh migrasi dan pemantauan

Migrasi CSV ke JSON dilakukan pada data latihan, bukan perpindahan teknologi database TaskTrack. File sumber tetap ada dan tujuan wajib berupa file baru. Contoh perbandingan: CSV mudah diubah dalam spreadsheet; JSON dapat menyimpan struktur data dan dibaca langsung oleh PHP dengan `json_decode`. Pemilihan JSON pada contoh ini tidak membuktikan keunggulan kinerja.

```sh
docker compose exec -T app php examples/evidence/migrate.php examples/evidence/tasks.csv /tmp/tasktrack-migrasi.json
docker stats --no-stream --format 'table {{.Name}}\t{{.MemPerc}}\t{{.MemUsage}}'
docker compose exec -T app php examples/evidence/resource.php 25 80
```

Masukkan persentase hasil `docker stats` ke argumen pertama resource.php. Ambang 80% adalah contoh visualisasi, bukan batas kegagalan hasil pengukuran. `/tmp/tasktrack-migrasi.json` merupakan keluaran latihan di container; untuk rollback hapus hanya file keluaran latihan tersebut. Database aplikasi tidak berubah.
