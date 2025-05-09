-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 02:23 AM
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
-- Database: `login_crud`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `visibility` enum('admin','staff','user','all') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `visibility`, `created_at`, `is_read`) VALUES
(2, 6, 'staff added a new pet: Anecito Randy E. Calunod Jr. (dog)', 'admin', '2025-05-05 22:55:07', 1),
(3, 6, 'staff added a new pet: Anecito Randy E. Calunod Jr. (dog)', 'staff', '2025-05-05 22:55:07', 0),
(4, 6, 'A new pet was added: Anecito Randy E. Calunod Jr. (dog)', 'user', '2025-05-05 22:55:07', 0),
(5, 7, 'New user registered: yes', 'admin', '2025-05-05 23:05:50', 1),
(6, 7, 'New user registered: yes', 'staff', '2025-05-05 23:05:50', 0),
(7, 1, ' deleted pet:  (ID 9)', 'admin', '2025-05-05 23:31:30', 1),
(8, 1, ' deleted pet:  (ID 9)', 'staff', '2025-05-05 23:31:30', 0),
(9, 1, 'A pet has been deleted. ID: 9, Name: ', 'user', '2025-05-05 23:31:30', 0),
(10, 1, 'admin added a new pet: cj (cat)', 'admin', '2025-05-05 23:35:09', 1),
(11, 1, 'admin added a new pet: cj (cat)', 'staff', '2025-05-05 23:35:09', 0),
(12, 1, 'A new pet was added: cj (cat)', 'user', '2025-05-05 23:35:09', 0),
(13, 1, ' deleted pet: cj (ID 11)', 'admin', '2025-05-05 23:35:40', 1),
(14, 1, ' deleted pet: cj (ID 11)', 'staff', '2025-05-05 23:35:40', 0),
(15, 1, 'A pet has been deleted. ID: 11, Name: cj', 'user', '2025-05-05 23:35:40', 0),
(16, 1, ' deleted pet: cj (ID 11)', 'admin', '2025-05-05 23:35:40', 1),
(17, 1, ' deleted pet: cj (ID 11)', 'staff', '2025-05-05 23:35:40', 0),
(18, 1, 'A pet has been deleted. ID: 11, Name: cj', 'user', '2025-05-05 23:35:40', 0),
(19, 1, 'admin added a new pet: waggy (dog)', 'admin', '2025-05-05 23:42:23', 1),
(20, 1, 'admin added a new pet: waggy (dog)', 'staff', '2025-05-05 23:42:23', 0),
(21, 1, 'A new pet was added: waggy (dog)', 'user', '2025-05-05 23:42:23', 0),
(22, 1, 'admin deleted a pet: waggy (ID 12)', 'admin', '2025-05-05 23:42:38', 1),
(23, 1, 'admin deleted a pet: waggy (ID 12)', 'staff', '2025-05-05 23:42:38', 0),
(24, 1, 'A pet has been deleted. ID: 12, Name: waggy', 'user', '2025-05-05 23:42:38', 0),
(25, 1, 'admin added a new pet: waggy (cat)', 'admin', '2025-05-06 00:07:50', 1),
(26, 1, 'admin added a new pet: waggy (cat)', 'staff', '2025-05-06 00:07:51', 0),
(27, 1, 'A new pet was added: waggy (cat)', 'user', '2025-05-06 00:07:51', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pets_rec`
--

CREATE TABLE `pets_rec` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `breed` varchar(50) NOT NULL,
  `photo` varchar(255) NOT NULL DEFAULT 'default.php'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets_rec`
--

INSERT INTO `pets_rec` (`id`, `name`, `breed`, `photo`) VALUES
(10, 'Anecito Randy E. Calunod Jr.', 'dog', 'default.jpg'),
(13, 'waggy', 'cat', 'default.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','user','staff') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `user_type`) VALUES
(1, 'admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', 'admin'),
(2, 'user', '04f8996da763b7a969b1028ee3007569eaf3a635486ddab211d512c85b9df8fb', 'user'),
(3, 'Randy', 'ac866d4cc7b3cc8837daef920a1ea241d9592ec959fd344f9d814b56ec01c067', 'user'),
(4, 'airuxph', 'ac866d4cc7b3cc8837daef920a1ea241d9592ec959fd344f9d814b56ec01c067', 'user'),
(6, 'staff', '1562206543da764123c21bd524674f0a8aaf49c8a89744c97352fe677f7e4006', 'staff'),
(7, 'yes', '8a798890fe93817163b10b5f7bd2ca4d25d84c52739a645a889c173eee7d9d3d', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pets_rec`
--
ALTER TABLE `pets_rec`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pets_rec`
--
ALTER TABLE `pets_rec`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
