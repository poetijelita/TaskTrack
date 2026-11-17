-- Contoh terisolasi untuk demonstrasi pada database latihan.
-- Hanya buat dan hapus objek contoh dengan nama ini.
DELIMITER //

CREATE OR REPLACE PROCEDURE contoh_jumlah_tugas(IN pemilik INT, OUT jumlah INT)
BEGIN
    SELECT COUNT(*) INTO jumlah FROM tasks WHERE user_id = pemilik;
END//

CREATE OR REPLACE FUNCTION contoh_prioritas_label(nilai VARCHAR(10))
RETURNS VARCHAR(20)
DETERMINISTIC NO SQL
BEGIN
    RETURN CASE nilai
        WHEN 'high' THEN 'Tinggi'
        WHEN 'medium' THEN 'Sedang'
        ELSE 'Rendah'
    END;
END//

DELIMITER ;

CALL contoh_jumlah_tugas(1, @jumlah);
SELECT @jumlah, contoh_prioritas_label('high');
DROP PROCEDURE contoh_jumlah_tugas;
DROP FUNCTION contoh_prioritas_label;
