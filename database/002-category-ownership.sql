-- Periksa dan perbaiki relasi lintas pemilik sebelum menjalankan migrasi.
DELIMITER //

CREATE OR REPLACE TRIGGER tasks_owner_insert
BEFORE INSERT ON tasks FOR EACH ROW
BEGIN
    IF NEW.category_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM categories
        WHERE id = NEW.category_id AND user_id = NEW.user_id
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kategori bukan milik pengguna';
    END IF;
END//

CREATE OR REPLACE TRIGGER tasks_owner_update
BEFORE UPDATE ON tasks FOR EACH ROW
BEGIN
    IF NEW.category_id IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM categories
        WHERE id = NEW.category_id AND user_id = NEW.user_id
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kategori bukan milik pengguna';
    END IF;
END//

CREATE OR REPLACE TRIGGER categories_owner_update
BEFORE UPDATE ON categories FOR EACH ROW
BEGIN
    IF NEW.user_id <> OLD.user_id AND EXISTS (
        SELECT 1 FROM tasks
        WHERE category_id = OLD.id AND user_id <> NEW.user_id
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Kategori masih digunakan oleh pemilik sebelumnya';
    END IF;
END//

DELIMITER ;
