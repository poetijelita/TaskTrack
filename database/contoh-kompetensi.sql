-- Jalankan di database demonstrasi, bukan database produksi.
CREATE OR REPLACE VIEW ringkasan_status AS
SELECT user_id, status, COUNT(*) AS jumlah
FROM tasks
GROUP BY user_id, status;

SELECT * FROM ringkasan_status;
EXPLAIN SELECT * FROM tasks WHERE user_id = 1 AND status = 'todo';

-- Demonstrasi rollback tidak menyimpan perubahan.
START TRANSACTION;
UPDATE tasks SET priority = 'high' WHERE user_id = 1;
ROLLBACK;

-- COMMIT menyimpan perubahan; gunakan hanya pada data latihan.
START TRANSACTION;
SELECT COUNT(*) FROM tasks;
COMMIT;
