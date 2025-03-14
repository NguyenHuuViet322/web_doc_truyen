CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm dữ liệu mặc định
INSERT INTO settings (name, value) VALUES
('site_name', 'Website Truyện'),
('site_description', 'Website đọc truyện online'),
('site_logo', 'logo.png'),
('maintenance_mode', '0'),
('items_per_page', '10'); 