CREATE DATABASE IF NOT EXISTS podcast_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE podcast_db;

DROP TABLE IF EXISTS podcasts;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE podcasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    audio_url VARCHAR(500) NOT NULL,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name) VALUES 
('Âm Nhạc & Thư Giãn'),
('Đời Sống & Kỹ Năng'),
('Công Nghệ & DevOps');

INSERT INTO podcasts (title, description, audio_url, category_id) VALUES 
('Tập 1: Giới thiệu Docker và Container', 'Hướng dẫn căn bản về Docker, Nginx, MySQL cho sinh viên CNTT.', 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', 3),
('Tập 2: Nhạc Piano Không Lời Trung Lãm', 'Giai điệu piano nhẹ nhàng giúp nâng cao hiệu suất làm việc.', 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3', 1),
('Tập 3: Kỹ Năng Quản Lý Thời Gian', 'Phương pháp tối ưu thời gian học tập và làm việc hiệu quả.', 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3', 2);