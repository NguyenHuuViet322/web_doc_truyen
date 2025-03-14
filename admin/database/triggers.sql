DELIMITER //

-- Trigger cho bảng comics
CREATE TRIGGER after_delete_comic
AFTER DELETE ON comics
FOR EACH ROW
BEGIN
    UPDATE comics 
    SET id = id - 1 
    WHERE id > OLD.id;
    
    ALTER TABLE comics AUTO_INCREMENT = 1;
END;//

-- Trigger cho bảng users
CREATE TRIGGER after_delete_user
AFTER DELETE ON users
FOR EACH ROW
BEGIN
    UPDATE users 
    SET id = id - 1 
    WHERE id > OLD.id;
    
    ALTER TABLE users AUTO_INCREMENT = 1;
END;//

DELIMITER ;