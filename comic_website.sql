-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 19, 2024 at 03:07 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `comic_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `id` int NOT NULL,
  `comic_id` int DEFAULT NULL,
  `chapter_number` float NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapters`
--

INSERT INTO `chapters` (`id`, `comic_id`, `chapter_number`, `title`, `created_at`) VALUES
(1, 3, 1, '', '2024-12-19 10:46:56'),
(4, 3, 2, '', '2024-12-19 10:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `chapter_images`
--

CREATE TABLE `chapter_images` (
  `id` int NOT NULL,
  `chapter_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapter_images`
--

INSERT INTO `chapter_images` (`id`, `chapter_id`, `image_path`, `image_order`, `created_at`) VALUES
(1, 1, '6763f9a0b688e.png', 0, '2024-12-19 17:46:56'),
(2, 1, '6763f9a0b6aea.png', 0, '2024-12-19 17:46:56'),
(3, 1, '6763f9a0b6e22.png', 0, '2024-12-19 17:46:56'),
(4, 1, '6763f9a0b7286.png', 0, '2024-12-19 17:46:56'),
(5, 1, '6763f9a0b748d.png', 0, '2024-12-19 17:46:56'),
(6, 1, '6763f9a0b7671.png', 0, '2024-12-19 17:46:56'),
(7, 1, '6763f9a0b784f.png', 0, '2024-12-19 17:46:56'),
(8, 1, '6763f9a0b7a26.png', 0, '2024-12-19 17:46:56'),
(9, 1, '6763f9a0b7c02.png', 0, '2024-12-19 17:46:56'),
(10, 1, '6763f9a0b7ddc.png', 0, '2024-12-19 17:46:56'),
(11, 1, '6763f9a0b7fb6.png', 0, '2024-12-19 17:46:56'),
(12, 4, '6763fc27a7ac9.png', 1, '2024-12-19 17:57:43'),
(13, 4, '6763fc27a801d.png', 2, '2024-12-19 17:57:43'),
(14, 4, '6763fc27a8492.png', 3, '2024-12-19 17:57:43'),
(15, 4, '6763fc27a88f6.png', 4, '2024-12-19 17:57:43'),
(16, 4, '6763fc27a8cb8.png', 5, '2024-12-19 17:57:43'),
(17, 4, '6763fc27a9233.png', 6, '2024-12-19 17:57:43'),
(18, 4, '6763fc27a966a.png', 7, '2024-12-19 17:57:43'),
(19, 4, '6763fc27a9a06.png', 8, '2024-12-19 17:57:43'),
(20, 4, '6763fc27a9d20.png', 9, '2024-12-19 17:57:43'),
(21, 4, '6763fc27aa004.png', 10, '2024-12-19 17:57:43');

-- --------------------------------------------------------

--
-- Table structure for table `comics`
--

CREATE TABLE `comics` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ongoing','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'ongoing',
  `views` int DEFAULT '0',
  `latest_chapter` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `categories` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comics`
--

INSERT INTO `comics` (`id`, `title`, `description`, `cover_image`, `author`, `status`, `views`, `latest_chapter`, `created_at`, `updated_at`, `categories`) VALUES
(3, 'One Piece', 'Truyện tranh One Piece:\r\nOne Piece là câu truyện kể về Luffy và các thuyền viên của mình. Khi còn nhỏ, Luffy ước mơ trở thành Vua Hải Tặc. Cuộc sống của cậu bé thay đổi khi cậu vô tình có được sức mạnh có thể co dãn như cao su, nhưng đổi lại, cậu không bao giờ có thể bơi được nữa. Giờ đây, Luffy cùng những người bạn hải tặc của mình ra khơi tìm kiếm kho báu One Piece, kho báu vĩ đại nhất trên thế giới. Trong One Piece, mỗi nhân vật trong đều mang một nét cá tính đặc sắc kết hợp cùng các tình huống kịch tính, lối dẫn truyện hấp dẫn chứa đầy các bước ngoặt bất ngờ và cũng vô cùng hài hước đã biến One Piece trở thành một trong những bộ truyện nổi tiếng nhất không thể bỏ qua. Hãy đọc One Piece để hòa mình vào một thế giới của những hải tặc rộng lớn, đầy màu sắc, sống động và thú vị, cùng đắm chìm với những nhân vật yêu tự do, trên hành trình đi tìm ước mơ của mình.', '6763f8a4b2353.png', 'Eiichiro Oda', 'ongoing', 0, 0, '2024-12-19 10:42:44', '2024-12-19 11:13:24', 'action,adventure,comedy,fantasy');

--
-- Triggers `comics`
--
DELIMITER $$
CREATE TRIGGER `after_delete_comic` AFTER DELETE ON `comics` FOR EACH ROW BEGIN
    UPDATE comics 
    SET id = id - 1 
    WHERE id > OLD.id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `comic_genres`
--

CREATE TABLE `comic_genres` (
  `comic_id` int NOT NULL,
  `genre_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comic_genres`
--

INSERT INTO `comic_genres` (`comic_id`, `genre_id`) VALUES
(3, 1),
(3, 2),
(3, 3),
(3, 6),
(3, 10);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `comic_id` int DEFAULT NULL,
  `chapter_id` int DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','hidden') COLLATE utf8mb4_unicode_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `comic_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genres`
--

CREATE TABLE `genres` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `genres`
--

INSERT INTO `genres` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Hành động', 'hanh-dong', NULL, '2024-12-19 16:45:58'),
(2, 'Phiêu lưu', 'phieu-luu', NULL, '2024-12-19 16:45:58'),
(3, 'Hài hước', 'hai-huoc', NULL, '2024-12-19 16:45:58'),
(4, 'Tình cảm', 'tinh-cam', NULL, '2024-12-19 16:45:58'),
(5, 'Kinh dị', 'kinh-di', NULL, '2024-12-19 16:45:58'),
(6, 'Viễn tưởng', 'vien-tuong', NULL, '2024-12-19 16:45:58'),
(7, 'Thể thao', 'the-thao', NULL, '2024-12-19 16:45:58'),
(8, 'Học đường', 'hoc-duong', NULL, '2024-12-19 16:45:58'),
(9, 'Đời thường', 'doi-thuong', NULL, '2024-12-19 16:45:58'),
(10, 'Fantasy', 'fantasy', NULL, '2024-12-19 16:45:58'),
(11, 'Hành động', 'action', NULL, '2024-12-19 18:28:22'),
(12, 'Phiêu lưu', 'adventure', NULL, '2024-12-19 18:28:22'),
(13, 'Hài hước', 'comedy', NULL, '2024-12-19 18:28:22'),
(14, 'Drama', 'drama', NULL, '2024-12-19 18:28:22'),
(15, 'Tình cảm', 'romance', NULL, '2024-12-19 18:28:22'),
(16, 'Kinh dị', 'horror', NULL, '2024-12-19 18:28:22'),
(17, 'Bí ẩn', 'mystery', NULL, '2024-12-19 18:28:22'),
(18, 'Tâm lý', 'psychological', NULL, '2024-12-19 18:28:22'),
(19, 'Khoa học viễn tưởng', 'sci-fi', NULL, '2024-12-19 18:28:22'),
(20, 'Đời thường', 'slice-of-life', NULL, '2024-12-19 18:28:22'),
(21, 'Thể thao', 'sports', NULL, '2024-12-19 18:28:22'),
(22, 'Siêu nhiên', 'supernatural', NULL, '2024-12-19 18:28:22'),
(23, 'Võ thuật', 'martial-arts', NULL, '2024-12-19 18:28:22'),
(24, 'Học đường', 'school-life', NULL, '2024-12-19 18:28:22'),
(25, 'Shounen', 'shounen', NULL, '2024-12-19 18:28:22'),
(26, 'Shoujo', 'shoujo', NULL, '2024-12-19 18:28:22'),
(27, 'Seinen', 'seinen', NULL, '2024-12-19 18:28:22'),
(28, 'Josei', 'josei', NULL, '2024-12-19 18:28:22'),
(29, 'Mecha', 'mecha', NULL, '2024-12-19 18:28:22'),
(30, 'Phép thuật', 'magic', NULL, '2024-12-19 18:28:22'),
(31, 'Quân sự', 'military', NULL, '2024-12-19 18:28:22'),
(32, 'Lịch sử', 'historical', NULL, '2024-12-19 18:28:22'),
(33, 'Âm nhạc', 'music', NULL, '2024-12-19 18:28:22'),
(34, 'Game', 'game', NULL, '2024-12-19 18:28:22'),
(35, 'Dị giới', 'isekai', NULL, '2024-12-19 18:28:22'),
(36, 'Quỷ vật', 'demons', NULL, '2024-12-19 18:28:22'),
(37, 'Ma cà rồng', 'vampire', NULL, '2024-12-19 18:28:22'),
(38, 'Harem', 'harem', NULL, '2024-12-19 18:28:22'),
(39, 'Ecchi', 'ecchi', NULL, '2024-12-19 18:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `comic_id` int DEFAULT NULL,
  `rating` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Website Truyện', '2024-12-19 10:36:31', '2024-12-19 10:36:31'),
(2, 'site_description', 'Website đọc truyện online', '2024-12-19 10:36:31', '2024-12-19 10:36:31'),
(3, 'site_logo', 'logo.png', '2024-12-19 10:36:31', '2024-12-19 10:36:31'),
(4, 'maintenance_mode', '0', '2024-12-19 10:36:31', '2024-12-19 10:36:31'),
(5, 'items_per_page', '10', '2024-12-19 10:36:31', '2024-12-19 10:36:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `status` enum('active','banned') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `avatar`, `created_at`, `role`, `status`, `last_login`) VALUES
(1, 'admin', 'admin@example.com', 'Vietaz420', 'default.jpg', '2024-12-19 09:28:33', 'admin', 'active', NULL),
(4, 'admin2', 'admin2@example.com', '$2y$10$kGEzG923wm2mVIOLOSaUAO/zEt56XHiAARTulAmCUMxYPLHN4eS6C', 'default.jpg', '2024-12-19 09:35:14', 'admin', 'active', '2024-12-19 16:35:35');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_delete_user` AFTER DELETE ON `users` FOR EACH ROW BEGIN
    UPDATE users 
    SET id = id - 1 
    WHERE id > OLD.id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE `views` (
  `id` int NOT NULL,
  `chapter_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comic_id` (`comic_id`);

--
-- Indexes for table `chapter_images`
--
ALTER TABLE `chapter_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- Indexes for table `comics`
--
ALTER TABLE `comics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comic_genres`
--
ALTER TABLE `comic_genres`
  ADD PRIMARY KEY (`comic_id`,`genre_id`),
  ADD KEY `genre_id` (`genre_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `comic_id` (`comic_id`),
  ADD KEY `chapter_id` (`chapter_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`user_id`,`comic_id`),
  ADD KEY `comic_id` (`comic_id`);

--
-- Indexes for table `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`user_id`,`comic_id`),
  ADD KEY `comic_id` (`comic_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `views`
--
ALTER TABLE `views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chapter_id` (`chapter_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chapters`
--
ALTER TABLE `chapters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chapter_images`
--
ALTER TABLE `chapter_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `comics`
--
ALTER TABLE `comics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `follows`
--
ALTER TABLE `follows`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `views`
--
ALTER TABLE `views`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chapter_images`
--
ALTER TABLE `chapter_images`
  ADD CONSTRAINT `chapter_images_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comic_genres`
--
ALTER TABLE `comic_genres`
  ADD CONSTRAINT `comic_genres_ibfk_1` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comic_genres_ibfk_2` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`),
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`);

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`comic_id`) REFERENCES `comics` (`id`);

--
-- Constraints for table `views`
--
ALTER TABLE `views`
  ADD CONSTRAINT `views_ibfk_1` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
