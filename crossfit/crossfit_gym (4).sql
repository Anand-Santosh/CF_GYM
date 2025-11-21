-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 07, 2025 at 08:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crossfit_gym`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `time_slot` varchar(50) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `member_id`, `trainer_id`, `package_id`, `start_date`, `end_date`, `time_slot`, `status`, `notes`) VALUES
(12, 10, NULL, 1, '2025-09-18', '2025-10-18', NULL, 'active', NULL),
(13, 10, NULL, 2, '2026-02-18', '2026-05-18', NULL, 'active', 'dfg'),
(15, 13, NULL, 1, '2025-11-20', '2025-12-20', NULL, 'active', 'weight loss'),
(16, 13, NULL, 1, '2025-11-20', '2025-12-20', NULL, 'active', 'weight loss'),
(17, 14, 3, 2, '2025-12-20', '2026-03-20', NULL, 'active', 'weight loss'),
(18, 15, 5, 1, '2025-09-20', '2025-10-20', NULL, 'active', 'weight loss'),
(19, 16, NULL, 2, '2025-12-20', '2026-03-20', NULL, 'active', NULL),
(20, 16, 5, 2, '2025-12-20', '2026-03-20', NULL, 'active', 'weight loss'),
(21, 17, NULL, 1, '2025-10-09', '2025-11-09', NULL, 'active', NULL),
(22, 17, 5, 1, '2025-10-09', '2025-11-09', NULL, 'active', 'weight loss'),
(23, 18, NULL, 2, '2025-10-10', '2026-01-10', NULL, 'active', NULL),
(24, 18, 5, 2, '2025-10-10', '2026-01-10', NULL, 'active', ''),
(25, 18, NULL, 1, '2025-10-10', '2025-11-10', NULL, 'active', NULL),
(26, 19, NULL, 3, '2025-10-31', '2026-10-31', NULL, 'active', NULL),
(27, 19, 5, 3, '2025-10-31', '2026-10-31', NULL, 'active', 'weight loss');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(255) DEFAULT 'default.jpg',
  `join_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `user_id`, `full_name`, `dob`, `gender`, `phone`, `address`, `image`, `join_date`) VALUES
(10, 40, 'Saji', NULL, '', '', 'dfgg', 'member_40_1758122521.jpg', NULL),
(12, 47, 'joban', NULL, NULL, NULL, NULL, 'default.jpg', NULL),
(13, 49, 'Albin', NULL, 'male', '1234567894', 'hghghghgh', 'default.jpg', NULL),
(14, 50, 'kevin', NULL, NULL, NULL, NULL, 'default.jpg', NULL),
(15, 51, 'Jerin Jose', '2012-05-10', 'male', '1234567890', 'ghghgh', 'member_51_1758347358.jpg', NULL),
(16, 52, 'sajikuttan', '2011-12-20', 'male', '1234567899', 'bhj', 'member_52_1759989145.jpg', NULL),
(17, 53, 'Anand', NULL, NULL, NULL, NULL, 'default.jpg', NULL),
(18, 54, 'adityn', NULL, NULL, NULL, NULL, 'default.jpg', NULL),
(19, 55, 'joel', NULL, '', '', '', 'member_55_1762093557.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `sent_at`) VALUES
(1, 52, 45, 'hi', '2025-09-22 12:37:31'),
(2, 45, 52, 'hello', '2025-09-22 16:43:14'),
(3, 45, 52, 'hello', '2025-09-22 16:43:27'),
(4, 45, 52, 'how r u\r\n', '2025-09-22 16:49:33'),
(5, 52, 45, 'hello', '2025-10-09 05:05:42'),
(6, 45, 52, 'hello\r\n', '2025-10-09 05:06:54'),
(7, 45, 52, 'hola', '2025-10-09 05:23:24'),
(8, 45, 52, 'hi\r\n', '2025-10-09 05:27:46'),
(9, 45, 52, 'ho r u\r\n', '2025-10-09 05:31:49'),
(10, 52, 45, 'hi', '2025-10-09 05:32:46'),
(11, 52, 45, 'im fine', '2025-10-09 06:03:16'),
(12, 52, 45, 'need a training plan', '2025-10-30 15:53:18'),
(13, 55, 45, 'hi', '2025-10-31 05:00:48');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_months` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `features` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`package_id`, `name`, `description`, `duration_months`, `price`, `features`) VALUES
(1, 'Basic Membership', 'Access to gym facilities during standard hours', 1, 50.00, 'Gym access, Locker room'),
(2, 'Premium Membership', 'Full access including classes and personal training', 3, 180.00, 'Gym access, All classes, 1 personal training session'),
(3, 'Annual Membership', 'Best value for long-term commitment', 12, 500.00, 'Gym access, All classes, 5 personal training sessions');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'gym_name', 'CrossFit Revolution', '2025-09-14 09:18:21', '2025-09-14 09:18:21'),
(2, 'gym_address', '123 Fitness Street,Kochi', '2025-09-14 09:18:21', '2025-09-14 09:18:21'),
(3, 'gym_phone', '1239874569', '2025-09-14 09:18:21', '2025-09-17 12:04:36'),
(4, 'gym_email', 'info@crossfitrevolution.com', '2025-09-14 09:18:21', '2025-09-14 09:18:21'),
(5, 'business_hours', 'Monday-Friday: 5AM - 10PM, Saturday: 7AM - 8PM, Sunday: 8AM - 6PM', '2025-09-14 09:18:21', '2025-09-14 09:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `supplements`
--

CREATE TABLE `supplements` (
  `supplement_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplements`
--

INSERT INTO `supplements` (`supplement_id`, `name`, `description`, `price`, `stock`, `image`) VALUES
(1, 'Whey Protein', 'High quality whey protein isolate', 29.99, 19, 'whey.jpg'),
(3, 'BCAA Powder', 'Branch chain amino acids for recovery', 24.99, 39, 'bcaa.jpg'),
(6, 'Creatine', 'High Quality Creatine ', 799.00, 20, '68c9849840ea0.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `supplement_orders`
--

CREATE TABLE `supplement_orders` (
  `order_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `supplement_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `status` enum('pending','ready','collected') DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplement_orders`
--

INSERT INTO `supplement_orders` (`order_id`, `member_id`, `supplement_id`, `quantity`, `pickup_date`, `status`, `order_date`) VALUES
(3, 10, 1, 1, '2025-11-10', 'pending', '2025-09-17 15:01:15'),
(4, 12, 1, 1, '2025-09-18', 'pending', '2025-09-18 05:31:34'),
(5, 13, 1, 1, '2025-12-20', 'pending', '2025-09-20 05:29:47'),
(6, 16, 3, 1, '2025-12-20', 'pending', '2025-09-21 06:45:31'),
(7, 19, 1, 1, '2025-10-31', 'pending', '2025-10-30 11:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `certification` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_document` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `trainer_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `user_id`, `full_name`, `specialization`, `bio`, `profile_photo`, `certification`, `is_verified`, `verification_document`, `experience_years`, `created_at`, `updated_at`, `trainer_password`) VALUES
(3, 43, 'Albin', 'weight training', 'fhghghgh', 'trainer_3_1758170459.jpg', 'ghghg', 0, NULL, 0, '2025-09-17 17:42:20', '2025-09-18 04:40:59', NULL),
(5, 45, 'abc', 'abc', 'fgfgfg', 'trainer_5_1758352259.jpg', 'nasm certified', 0, NULL, 1, '2025-09-18 05:13:24', '2025-09-20 07:11:19', NULL),
(7, 57, 'Abin', 'leg', 'hghghg', NULL, 'nasm certified', 0, NULL, 1, '2025-11-01 06:16:43', '2025-11-01 06:16:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `training_plans`
--

CREATE TABLE `training_plans` (
  `plan_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_weeks` int(11) DEFAULT 4,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `goals` text DEFAULT NULL,
  `exercises` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exercises`)),
  `nutrition_guidelines` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','paused') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_plans`
--

INSERT INTO `training_plans` (`plan_id`, `request_id`, `trainer_id`, `member_id`, `plan_name`, `description`, `duration_weeks`, `difficulty_level`, `goals`, `exercises`, `nutrition_guidelines`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 5, 19, 'weight loss program for jowl', 'simple,get on going', 4, 'beginner', 'weight loss', '[{\"name\":\"bench press\",\"sets\":\"3\",\"reps\":\"8-12\",\"rest\":\"60\",\"notes\":\"focus on belly\"}]', 'eat protein rich food like egg,chicken and all', '2025-10-31', NULL, 'active', '2025-10-31 09:03:30', '2025-10-31 09:03:30');
--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','trainer','member') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trainer_status` enum('pending','approved','rejected') DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `verification_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `created_at`, `trainer_status`, `certificate_path`, `verification_date`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@crossfit.com', 'admin', '2025-09-14 09:17:08', NULL, NULL, NULL),
(2, 'trainer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer@crossfit.com', 'trainer', '2025-09-14 09:17:08', NULL, NULL, NULL),
(30, NULL, '$2y$10$U/tzvLwBLqe4ulpvhI42cuu3FL7yUi5ME7SqEdl4ZLe.fMxB90UsC', 'joban123@gmail.com', 'trainer', '2025-09-17 02:06:12', NULL, NULL, NULL),
(31, NULL, '$2y$10$UD85TyLhE92OqgGiWu.2BugjVo18nUfnU9nf1uP2bWF0n14Zk2o8K', 'anand@gmail.com', 'trainer', '2025-09-17 02:40:35', NULL, NULL, NULL),
(37, NULL, '$2y$10$j0qP3HTjy0xqTl3iVp6mX.ryOajYrSpkvoemW.EA8hrxRzedyFuTm', 'keni@gmail.com', 'trainer', '2025-09-17 12:39:38', NULL, NULL, NULL),
(38, NULL, '$2y$10$O9..kP7Mx2iW5a4iaHdGxeYunLH9MdHd76Sv3Eeu.c9nJDUL37gje', 'rohit1@gmail.com', 'trainer', '2025-09-17 14:00:19', NULL, NULL, NULL),
(40, NULL, '$2y$10$GYVBuIxYj8zZhbYVKKNS7.1oyM7nbBXg9AYudIerGL2j/EkVwTB0m', 'saji@gmail.com', 'member', '2025-09-17 14:59:51', NULL, NULL, NULL),
(43, NULL, '$2y$10$5fiyZ/b6iShpMcZt951/sOdmtDK5K2MKiapNhk8ZWDN0Yq4S78xcq', 'albin1@gmail.com', 'trainer', '2025-09-17 17:42:20', NULL, NULL, NULL),
(45, NULL, '$2y$10$kOYtW8VSzfCOWmGNxJTw1Od7jdGkCRwP4obLLFwu3yr6Fi3q5vxCa', 'ab@gmail.com', 'trainer', '2025-09-18 05:13:24', NULL, NULL, NULL),
(47, NULL, '$2y$10$pR4Vi7LV8oQnxgS7a433qOXRKiQZsgF7gpVKFSjqrr27j2/Wvp25a', 'joban11@gmail.com', 'member', '2025-09-18 05:31:08', NULL, NULL, NULL),
(49, NULL, '$2y$10$m9O.uwWW9dzIBX.dmxWm0eaOUxH7Rl69ZcHO8rv7YwjMthzVNgVC.', 'albin2@gmail.com', 'member', '2025-09-20 05:14:04', NULL, NULL, NULL),
(50, NULL, '$2y$10$xNSpzvPKgFWkPy3ihbKM3.ggUgdpOXsW98BBa2Y13X16Ul7J9zE62', 'kevin@gmail.com', 'member', '2025-09-20 05:38:14', NULL, NULL, NULL),
(51, NULL, '$2y$10$N3Lrvz7KEz0N5w/iqrVeO.cxKroNjW9ni7APM2veE7D/sWFp8mj.e', 'jerin@gmail.com', 'member', '2025-09-20 05:42:09', NULL, NULL, NULL),
(52, NULL, '$2y$10$B9oct2l5rmIYXdJnOVLzheyfPUPs4/1xFLZ26OyWwbuz2/Sd2f6Xu', 'saji1@gmail.com', 'member', '2025-09-20 16:34:03', NULL, NULL, NULL),
(53, NULL, '$2y$10$AOwvVLytCwJa/Ln51/f.De41OEO7YzLZEHgkXmrzg8dYG4td.xlAu', 'anand1@gmail.com', 'member', '2025-10-09 06:04:25', NULL, NULL, NULL),
(54, NULL, '$2y$10$J3eyPaQicOUChT.do2Qrce/yj1o2P.kkpI38v7z/VGZbDHL6nQtmq', 'adityn@gmail.com', 'member', '2025-10-09 06:12:06', NULL, NULL, NULL),
(55, NULL, '$2y$10$LqLPr7Giz8fDKABLMVeu6eIDLkLmBqW6NLYpFR6615UWW.8Z1tsUy', 'joel@gmail.com', 'member', '2025-10-30 11:31:41', NULL, NULL, NULL),
(57, NULL, '$2y$10$SyThmnGtqAVTtRRAu.NUU.p9QmP0webLY/wVHqiqVcx/w4rtaukBS', 'abhin@gmail', 'trainer', '2025-11-01 06:16:43', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `supplements`
--
ALTER TABLE `supplements`
  ADD PRIMARY KEY (`supplement_id`);

--
-- Indexes for table `supplement_orders`
--
ALTER TABLE `supplement_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `supplement_id` (`supplement_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `training_plans`
--
ALTER TABLE `training_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `trainer_id` (`trainer_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `training_plan_requests`
--
ALTER TABLE `training_plan_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `supplements`
--
ALTER TABLE `supplements`
  MODIFY `supplement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `supplement_orders`
--
ALTER TABLE `supplement_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `trainer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `training_plans`
--
ALTER TABLE `training_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `training_plan_requests`
--
ALTER TABLE `training_plan_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `supplement_orders`
--
ALTER TABLE `supplement_orders`
  ADD CONSTRAINT `supplement_orders_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplement_orders_ibfk_2` FOREIGN KEY (`supplement_id`) REFERENCES `supplements` (`supplement_id`) ON DELETE CASCADE;

--
-- Constraints for table `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `trainers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `training_plans`
--
ALTER TABLE `training_plans`
  ADD CONSTRAINT `training_plans_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `training_plan_requests` (`request_id`),
  ADD CONSTRAINT `training_plans_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`),
  ADD CONSTRAINT `training_plans_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`);

--
-- Constraints for table `training_plan_requests`
--
ALTER TABLE `training_plan_requests`
  ADD CONSTRAINT `training_plan_requests_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`),
  ADD CONSTRAINT `training_plan_requests_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
