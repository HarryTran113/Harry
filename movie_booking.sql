-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 25, 2026 lúc 07:42 PM
-- Phiên bản máy phục vụ: 8.0.46
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `movie_booking`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `showtime_id` bigint UNSIGNED NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'paid',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `bookings`
--

INSERT INTO `bookings` (`id`, `code`, `user_id`, `showtime_id`, `total_price`, `status`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'CG-882190', 2, 1, 170000.00, 'paid', NULL, '2026-09-25 12:43:13', '2026-09-25 14:43:13'),
(2, 'CG-452109', 3, 2, 240000.00, 'paid', NULL, '2026-09-25 09:43:13', '2026-09-25 14:43:13'),
(3, 'CG-991203', 4, 3, 135000.00, 'pending', NULL, '2026-09-25 14:28:13', '2026-09-25 14:43:13'),
(4, 'CG-331294', 5, 4, 190000.00, 'paid', NULL, '2026-09-24 14:43:13', '2026-09-25 14:43:13'),
(5, 'CG-118239', 2, 22, 170000.00, 'paid', NULL, '2026-09-25 11:43:13', '2026-09-25 14:43:13'),
(6, 'CG-772910', 3, 21, 95000.00, 'cancelled', NULL, '2026-09-25 08:43:13', '2026-09-25 14:46:15'),
(7, 'CG-663821', 4, 13, 170000.00, 'cancelled', NULL, '2026-09-25 06:43:13', '2026-09-25 14:54:51'),
(8, 'CG-552918', 5, 6, 230000.00, 'paid', NULL, '2026-09-25 14:13:13', '2026-09-25 14:55:50'),
(9, 'CG-138825', 6, 8, 85000.00, 'paid', NULL, '2026-09-25 14:55:22', '2026-09-25 17:38:26');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `booking_seats`
--

CREATE TABLE `booking_seats` (
  `id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `showtime_id` bigint UNSIGNED NOT NULL,
  `seat_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `booking_seats`
--

INSERT INTO `booking_seats` (`id`, `booking_id`, `showtime_id`, `seat_id`, `created_at`) VALUES
(1, 1, 1, 1, '2026-09-25 14:43:13'),
(2, 1, 1, 2, '2026-09-25 14:43:13'),
(3, 2, 2, 6, '2026-09-25 14:43:13'),
(4, 2, 2, 7, '2026-09-25 14:43:13'),
(5, 3, 3, 6, '2026-09-25 14:43:13'),
(6, 4, 4, 8, '2026-09-25 14:43:13'),
(7, 4, 4, 9, '2026-09-25 14:43:13'),
(8, 5, 22, 1, '2026-09-25 14:43:13'),
(9, 5, 22, 2, '2026-09-25 14:43:13'),
(10, 6, 21, 8, '2026-09-25 14:43:13'),
(11, 7, 13, 1, '2026-09-25 14:43:13'),
(12, 7, 13, 2, '2026-09-25 14:43:13'),
(13, 8, 6, 6, '2026-09-25 14:43:13'),
(14, 8, 6, 7, '2026-09-25 14:43:13'),
(15, 9, 8, 10, '2026-09-25 14:55:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cinemas`
--

CREATE TABLE `cinemas` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `cinemas`
--

INSERT INTO `cinemas` (`id`, `name`, `address`, `city`, `created_at`, `updated_at`) VALUES
(1, 'Cinego Central', 'Số 123 Nguyễn Huệ, Quận 1', 'TP. Hồ Chí Minh', '2026-09-25 13:54:42', '2026-09-25 17:20:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `movies`
--

CREATE TABLE `movies` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `genre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` int NOT NULL,
  `poster` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trailer_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'dang_chieu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `genre`, `duration`, `poster`, `trailer_url`, `release_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mai', 'Bộ phim tâm lý tình cảm của đạo diễn Trấn Thành.', 'Tâm lý, Tình cảm', 131, 'https://m.media-amazon.com/images/M/MV5BMjA4OGEzYzMtYjFkNy00MTRiLWIwNWYtNWFhNzU4OWZkZDJkXkEyXkFqcGc@._V1_.jpg', NULL, '2026-02-10', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(2, 'Lật Mặt 7: Một Điều Ước', 'Câu chuyện cảm động về tình mẫu tử của đạo diễn Lý Hải.', 'Gia đình, Tâm lý', 138, 'https://m.media-amazon.com/images/M/MV5BNmU4M2E4MTctZDRiYy00ZjkyLWJlM2UtZjUxMWZiMDVhZmEzXkEyXkFqcGc@._V1_.jpg', NULL, '2026-04-26', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(3, 'Dune: Hành Tinh Cát 2', 'Paul Atreides liên minh cùng người Fremen để trả thù.', 'Khoa học viễn tưởng, Phiêu lưu', 166, 'https://m.media-amazon.com/images/M/MV5BN2QyZGU4ZDctOWMzMy00NWUzLWI1MmEtZDY3NmAzebd1M2I1XkEyXkFqcGc@._V1_.jpg', NULL, '2026-03-01', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(4, 'Godzilla x Kong: Đế Chế Mới', 'Hai quái thú khổng lồ hợp lực đối đầu hiểm họa Trái Đất Rỗng.', 'Hành động, Quái thú', 115, 'https://m.media-amazon.com/images/M/MV5BMTY0MWY3OWItZTkzMi00MDRjLWI1NWItMzBiMjgxYTIyNTk4XkEyXkFqcGc@._V1_.jpg', NULL, '2026-03-29', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(5, 'Kung Fu Panda 4', 'Po tìm kiếm người kế vị Thần Long Đại Hiệp mới.', 'Hoạt hình, Hài, Võ thuật', 94, 'https://m.media-amazon.com/images/M/MV5BN2E3YzJhODktMjM1OC00MTE2LTlkYzAtNTBkYWYzYjBmZGE1XkEyXkFqcGc@._V1_.jpg', NULL, '2026-03-08', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(6, 'Deadpool & Wolverine', 'Cặp đôi siêu anh hùng lầy lội tái xuất giải cứu đa vũ trụ.', 'Hành động, Hài, Siêu anh hùng', 128, 'https://m.media-amazon.com/images/M/MV5BYzA3NzQ3NjEtNGM5Ni00YmU2LTg3MDgtYWRhNTQ3MGU1ODc0XkEyXkFqcGc@._V1_.jpg', NULL, '2026-07-26', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 14:28:02'),
(7, 'Inside Out 2: Những Mảnh Ghép Cảm Xúc', 'Riley bước vào tuổi dậy thì với những cảm xúc hoàn toàn mới.', 'Hoạt hình, Tâm lý', 96, 'https://m.media-amazon.com/images/M/MV5BYWY3MmVkYjQtN2RhYy00OGJkLTkyNWQtYjFhMTZlYjZlNzQ2XkEyXkFqcGc@._V1_.jpg', NULL, '2026-06-14', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(8, 'Kẻ Trộm Mặt Trăng 4', 'Gia đình Gru chào đón thành viên mới và quái thú Minions.', 'Hoạt hình, Phiêu lưu', 95, 'https://m.media-amazon.com/images/M/MV5BNjc4NDgxMzAtMGFjMS00NzY1LTg5ODAtOTcwNDM2YmMyOWFkXkEyXkFqcGc@._V1_.jpg', NULL, '2026-07-05', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(9, 'Quật Mộ Trùng Ma (Exhuma)', 'Hiện tượng kinh dị tâm linh rúng động phòng vé châu Á.', 'Kinh dị, Bí ẩn', 134, 'https://m.media-amazon.com/images/M/MV5BNjRmMjM0ODItYWRjNy00MDc0LTk1ODgtMzQzZTZjZGFmOTg3XkEyXkFqcGc@._V1_.jpg', NULL, '2026-03-15', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(10, 'Mèo Đi Hia: Điều Ước Cuối Cùng', 'Hành trình tìm ngôi sao ước nguyện của chú Mèo Đi Hia.', 'Hoạt hình, Phiêu lưu', 102, 'https://m.media-amazon.com/images/M/MV5BNjMyMDBjMGUtNDUzZi00N2MwLTg1MjItZTk2MDE1OTBhYzlmXkEyXkFqcGc@._V1_.jpg', NULL, '2026-01-15', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(11, 'Pháo Hoa Lúc Bình Minh', 'Bộ phim anime sâu lắng và rực rỡ cảm xúc tuổi thanh xuân.', 'Hoạt hình, Lãng mạn', 110, 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop', NULL, '2026-05-18', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(12, 'Quỷ Cẩu', 'Hiện tượng kinh dị lấy cảm hứng từ truyền thuyết linh dị Việt Nam.', 'Kinh dị, Giật gân', 108, 'https://images.unsplash.com/photo-1509281373149-e957c6296406?w=500&auto=format&fit=crop', NULL, '2026-01-05', 'ngung_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(13, 'Conan: Ngôi Sao 5 Cánh 1 Triệu Đô', 'Vụ án kiếm sĩ bí ẩn tại Hakodate cùng Siêu trộm Kid.', 'Hoạt hình, Trinh thám', 111, 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=500&auto=format&fit=crop', NULL, '2026-08-02', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(14, 'Cám', 'Dị bản kinh dị đen tối từ truyện cổ tích Tấm Cám.', 'Kinh dị, Dân gian', 122, 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=500&auto=format&fit=crop', NULL, '2026-09-20', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(15, 'Venom: Kèo Cuối', 'Cuộc chiến sinh tử cuối cùng của Eddie Brock và Venom.', 'Hành động, Khoa học viễn tưởng', 110, 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=500&auto=format&fit=crop', NULL, '2026-10-25', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(16, 'Joker: Điên Có Đôi', 'Mối tình điên loạn giữa Arthur Fleck và Harley Quinn.', 'Tâm lý, Tội phạm', 138, 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=500&auto=format&fit=crop', NULL, '2026-10-04', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(17, 'Furiosa: Câu Chuyện Từ Max Điên', 'Nguồn gốc của nữ chiến binh Furiosa nơi hoang mạc chết chóc.', 'Hành động, Hậu tận thế', 148, 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=500&auto=format&fit=crop', NULL, '2026-05-24', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 14:56:15'),
(18, 'Vùng Đất Câm Lặng: Ngày Một', 'Ngày đầu tiên New York bị quái vật thính giác xâm chiếm.', 'Kinh dị, Giật gân', 99, 'https://images.unsplash.com/photo-1514306191717-452ec28c7814?w=500&auto=format&fit=crop', NULL, '2026-06-28', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:42:48'),
(19, 'Hành Tinh Khỉ: Vương Quốc Mới', 'Nhiều thế hệ sau Caesar, một thủ lĩnh khỉ bạo tàn trỗi dậy.', 'Hành động, Viễn tưởng', 145, 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=500&auto=format&fit=crop', NULL, '2026-05-10', 'dang_chieu', '2026-09-25 13:38:36', '2026-09-25 13:42:50'),
(20, 'Ngày Xưa Có Một Chuyện Tình', 'Chuyển thể từ truyện dài của nhà văn Nguyễn Nhật Ánh.', 'Lãng mạn, Tuổi trẻ', 135, 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=500&auto=format&fit=crop', NULL, '2026-11-01', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 13:38:36'),
(21, 'Mufasa: Vua Sư Tử', 'Hành trình từ chú sư tử mồ côi trở thành chúa tể muôn loài.', 'Phiêu lưu, Gia đình', 118, 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=500&auto=format&fit=crop', NULL, '2026-12-20', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 14:56:26'),
(22, 'Đào, Phở và Piano', 'Bản hùng ca hào hoa của Hà Nội 60 ngày đêm khói lửa.', 'Lịch sử, Chiến tranh', 100, 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=500&auto=format&fit=crop', NULL, '2026-02-15', 'sap_chieu', '2026-09-25 13:38:36', '2026-09-25 15:03:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `cinema_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_rows` int DEFAULT '8',
  `total_cols` int DEFAULT '10',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `cinema_id`, `name`, `total_rows`, `total_cols`, `created_at`, `updated_at`) VALUES
(1, 1, 'Phòng 01 (2D Digital)', 8, 10, '2026-09-25 13:54:42', '2026-09-25 13:54:42'),
(2, 1, 'Phòng 02 (IMAX Laser)', 10, 12, '2026-09-25 13:54:42', '2026-09-25 13:54:42'),
(3, 1, 'Phòng 03 (3D Dolby Atmos)', 8, 10, '2026-09-25 13:54:42', '2026-09-25 13:54:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `seats`
--

CREATE TABLE `seats` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `row_label` varchar(10) NOT NULL,
  `seat_number` int NOT NULL,
  `type` varchar(50) DEFAULT 'standard',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `seats`
--

INSERT INTO `seats` (`id`, `room_id`, `row_label`, `seat_number`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'A', 1, 'standard', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(2, 1, 'A', 2, 'standard', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(3, 1, 'A', 3, 'standard', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(4, 1, 'B', 5, 'vip', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(5, 1, 'B', 6, 'vip', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(6, 2, 'C', 7, 'vip', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(7, 2, 'C', 8, 'vip', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(8, 3, 'D', 1, 'standard', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(9, 3, 'D', 2, 'standard', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(10, 1, 'D', 7, 'standard', '2026-09-25 14:55:22', '2026-09-25 14:55:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `showtimes`
--

CREATE TABLE `showtimes` (
  `id` bigint UNSIGNED NOT NULL,
  `movie_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `start_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '85000.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Đang đổ dữ liệu cho bảng `showtimes`
--

INSERT INTO `showtimes` (`id`, `movie_id`, `room_id`, `start_time`, `price`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-09-25 23:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(2, 1, 2, '2026-09-26 03:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(3, 2, 2, '2026-09-26 00:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(4, 2, 1, '2026-09-26 04:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(5, 3, 2, '2026-09-26 01:32:43', 135000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(6, 4, 3, '2026-09-25 22:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(7, 4, 2, '2026-09-26 05:32:43', 135000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(8, 5, 1, '2026-09-26 02:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(9, 6, 2, '2026-09-26 06:32:43', 135000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(10, 6, 3, '2026-09-26 09:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(11, 7, 1, '2026-09-26 07:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(12, 8, 1, '2026-09-26 08:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(13, 9, 2, '2026-09-26 10:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(14, 10, 3, '2026-09-26 11:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(15, 11, 1, '2026-09-26 12:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(16, 12, 3, '2026-09-26 13:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(17, 13, 1, '2026-09-26 21:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(18, 13, 2, '2026-09-27 00:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(19, 14, 3, '2026-09-27 02:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(20, 15, 2, '2026-09-27 04:32:43', 135000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(21, 16, 3, '2026-09-27 06:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(22, 17, 2, '2026-09-27 23:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(23, 18, 1, '2026-09-28 01:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(24, 19, 2, '2026-09-28 03:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(25, 20, 1, '2026-09-28 05:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(26, 21, 3, '2026-09-28 07:32:43', 95000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(27, 21, 1, '2026-09-28 23:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(28, 22, 1, '2026-09-26 23:32:43', 85000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43'),
(29, 22, 2, '2026-09-27 22:32:43', 120000.00, '2026-09-25 14:32:43', '2026-09-25 14:32:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Quản trị viên', 'admin@gmail.com', '$2y$12$e80MvQ0ZgWp5lV.QJ0yK8edK2mO1j1sDqPz7r6K8t3o3l0Y5J8m3i', NULL, 'admin', '2026-09-25 11:55:55', '2026-09-25 11:55:55'),
(2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', '$2y$12$e80MvQ0ZgWp5lV.QJ0yK8edK2mO1j1sDqPz7r6K8t3o3l0Y5J8m3i', '0912345678', 'user', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(3, 'Trần Thị Bích', 'bich.tran@gmail.com', '$2y$12$e80MvQ0ZgWp5lV.QJ0yK8edK2mO1j1sDqPz7r6K8t3o3l0Y5J8m3i', '0987654321', 'user', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(4, 'Lê Hoàng Cường', 'cuong.le@gmail.com', '$2y$12$e80MvQ0ZgWp5lV.QJ0yK8edK2mO1j1sDqPz7r6K8t3o3l0Y5J8m3i', '0905123456', 'user', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(5, 'Phạm Quỳnh Nga', 'nga.pham@gmail.com', '$2y$12$e80MvQ0ZgWp5lV.QJ0yK8edK2mO1j1sDqPz7r6K8t3o3l0Y5J8m3i', '0938112233', 'user', '2026-09-25 14:43:13', '2026-09-25 14:43:13'),
(6, 'Trần Hùng Huy', 'guest_1790348122@cingo.vn', '$2y$10$MU6fL05fbxR8DxQwDYPJzOeemXO1dKL1I1HRi4KeLpRYEQz/XlZK6', '09999999999', 'user', '2026-09-25 14:55:22', '2026-09-25 14:55:22'),
(7, 'Quản Trị Viên', 'admin@cinego.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', '2026-09-25 16:55:29', '2026-09-25 16:55:29');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `showtime_id` (`showtime_id`);

--
-- Chỉ mục cho bảng `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `seat_id` (`seat_id`);

--
-- Chỉ mục cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cinema_id` (`cinema_id`);

--
-- Chỉ mục cho bảng `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Chỉ mục cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `booking_seats`
--
ALTER TABLE `booking_seats`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `cinemas`
--
ALTER TABLE `cinemas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `movies`
--
ALTER TABLE `movies`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `seats`
--
ALTER TABLE `seats`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`showtime_id`) REFERENCES `showtimes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `booking_seats`
--
ALTER TABLE `booking_seats`
  ADD CONSTRAINT `booking_seats_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `booking_seats_ibfk_2` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`cinema_id`) REFERENCES `cinemas` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `showtimes`
--
ALTER TABLE `showtimes`
  ADD CONSTRAINT `showtimes_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `showtimes_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
