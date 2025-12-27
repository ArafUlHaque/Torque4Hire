-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2025 at 03:36 PM
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
-- Database: `torque4hire_db`
--
CREATE DATABASE IF NOT EXISTS `torque4hire_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `torque4hire_db`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `email` varchar(120) NOT NULL,
  `admin_level` int(11) DEFAULT 1,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machinery`
--

DROP TABLE IF EXISTS `machinery`;
CREATE TABLE `machinery` (
  `machine_id` int(11) NOT NULL,
  `owner_email` varchar(120) NOT NULL,
  `category_id` int(11) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `status` enum('AVAILABLE','RENTED','MAINTENANCE') DEFAULT 'AVAILABLE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machinery`
--

INSERT INTO `machinery` (`machine_id`, `owner_email`, `category_id`, `model_name`, `daily_rate`, `status`) VALUES
(1, 'wearecharliekirk@gmail.com', 1, 'Kirkmobile 67', 500.00, 'AVAILABLE'),
(2, 'sahir.jawad.chowdhury@g.bracu.ac.bd', 1, 'Tolowar 15', 1000.67, 'AVAILABLE'),
(4, 'ahmedrakin@gmail.com', 1, 'Flower Roller', 9.99, 'AVAILABLE'),
(5, 'sm.arham.ali@g.bracu.ac.bd', 2, 'BUCYRUS 41-B', 80.00, 'AVAILABLE');

-- --------------------------------------------------------

--
-- Table structure for table `machine_categories`
--

DROP TABLE IF EXISTS `machine_categories`;
CREATE TABLE `machine_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machine_categories`
--

INSERT INTO `machine_categories` (`category_id`, `category_name`, `details`) VALUES
(1, 'Excavator', 'Heavy digging machine'),
(2, 'Crane', 'Lifting machine'),
(18, 'Forklift', 'A powered industrial truck used to lift and move materials over short distances. Essential for warehouses and construction sites.'),
(19, 'Bulldozer', 'A large and heavy tractor equipped with a substantial metal plate (blade) used to push large quantities of soil, sand, or rubble.'),
(20, 'Dump Truck', 'A truck used for transporting loose material (such as sand, gravel, or demolition waste) for construction.'),
(21, 'Tractor', 'A powerful motor vehicle with large rear wheels, used chiefly on farms for hauling equipment and trailers.'),
(22, 'Backhoe Loader', 'A heavy equipment vehicle that consists of a tractor-like unit fitted with a loader-style shovel/bucket on the front and a backhoe on the back.'),
(23, 'Concrete Mixer', 'A device that homogeneously combines cement, aggregate such as sand or gravel, and water to form concrete.'),
(24, 'Skid Steer Loader', 'A small, rigid-frame engine-powered machine with lift arms used to attach a wide variety of labor-saving tools or buckets.'),
(25, 'Road Roller', 'A compactor-type engineering vehicle used to compact soil, gravel, concrete, or asphalt in the construction of roads and foundations.'),
(26, 'Motor Grader', 'A construction machine with a long blade used to create a flat surface during the grading process.'),
(27, 'Scissor Lift', 'A mobile elevated work platform that can only move vertically. Used for temporary access for contractors and maintenance.'),
(28, 'Combine Harvester', 'A versatile machine designed to efficiently harvest a variety of grain crops. essential for large-scale farming.');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

DROP TABLE IF EXISTS `maintenance`;
CREATE TABLE `maintenance` (
  `machine_id` int(11) NOT NULL,
  `maintenance_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

DROP TABLE IF EXISTS `owners`;
CREATE TABLE `owners` (
  `owner_email` varchar(120) NOT NULL,
  `company_name` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_email`, `company_name`) VALUES
('ahmedrakin@gmail.com', 'Bi-axial'),
('araf.ul.haque@g.bracu.ac.bd', 'Ball Association'),
('sahir.jawad.chowdhury@g.bracu.ac.bd', 'Tolwar LTD.'),
('sm.arham.ali@g.bracu.ac.bd', 'Ma er dua GOI.'),
('wearecharliekirk@gmail.com', 'CarryTheFlame');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `rental_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `admin_email` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalties`
--

DROP TABLE IF EXISTS `penalties`;
CREATE TABLE `penalties` (
  `penalty_id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `penalty_amount` decimal(10,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `penalty_status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

DROP TABLE IF EXISTS `rentals`;
CREATE TABLE `rentals` (
  `rental_id` int(11) NOT NULL,
  `renter_email` varchar(120) NOT NULL,
  `machine_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `rental_status` varchar(50) DEFAULT 'REQUESTED'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `renters`
--

DROP TABLE IF EXISTS `renters`;
CREATE TABLE `renters` (
  `renter_email` varchar(120) NOT NULL,
  `license_no` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renters`
--

INSERT INTO `renters` (`renter_email`, `license_no`) VALUES
('kingjames@gmail.com', '232323');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
CREATE TABLE `trainers` (
  `trainer_email` varchar(120) NOT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `availability` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_sessions`
--

DROP TABLE IF EXISTS `training_sessions`;
CREATE TABLE `training_sessions` (
  `session_id` int(11) NOT NULL,
  `trainer_email` varchar(120) NOT NULL,
  `renter_email` varchar(120) NOT NULL,
  `session_start` datetime NOT NULL,
  `session_end` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `email` varchar(120) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`email`, `name`, `phone`, `address`, `password_hash`, `created_at`) VALUES
('ahmedrakin@gmail.com', 'Ahmed Rakin', NULL, NULL, '$2y$10$rq.YitR4uDFR7udYVOfZnuCEOWLvvK6DaW8rduDte0jXlqs5qNL9O', '2025-12-20 12:31:47'),
('araf.ul.haque@g.bracu.ac.bd', 'Araf Ul Haque', NULL, NULL, '$2y$10$fVywLVUCVc/dUtshawymPuRKGgyc.IIfjWynVhsQnrvri9QIUAPYO', '2025-12-20 10:19:43'),
('kingjames@gmail.com', 'Lebron James', NULL, NULL, '$2y$10$w9KoPWnrQQUh38MBPg0ofuZYUH7LK5321HiJN8Rp71sSUCtEPX132', '2025-12-20 11:08:57'),
('sahir.jawad.chowdhury@g.bracu.ac.bd', 'Sahir Jawad Chowdhury', NULL, NULL, '$2y$10$a4yWXJTkIKaelvfF9Dcv3OKt/kBFpakfmZbazwBgo6cznsl7.Ijly', '2025-12-20 10:45:34'),
('sm.arham.ali@g.bracu.ac.bd', 'Arham Ali', NULL, NULL, '$2y$10$LZXVtTy.IlvQm.zcpq.NguDzjXsaMIJCu7xMASx2EOe.QtNYbwXEG', '2025-12-20 13:05:57'),
('wearecharliekirk@gmail.com', 'Charlie Kirk', NULL, NULL, '$2y$10$MFng/huGbYB9g3WXLOswd.RyCAY8c3JmjhurXuktwT/r3ZonchuIC', '2025-12-20 10:34:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `machinery`
--
ALTER TABLE `machinery`
  ADD PRIMARY KEY (`machine_id`),
  ADD KEY `fk_machine_owner` (`owner_email`),
  ADD KEY `fk_machine_cat` (`category_id`);

--
-- Indexes for table `machine_categories`
--
ALTER TABLE `machine_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`machine_id`,`maintenance_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`rental_id`,`payment_id`),
  ADD KEY `fk_payment_admin` (`admin_email`);

--
-- Indexes for table `penalties`
--
ALTER TABLE `penalties`
  ADD PRIMARY KEY (`penalty_id`),
  ADD KEY `fk_penalty_rental` (`rental_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `fk_rental_renter` (`renter_email`),
  ADD KEY `fk_rental_machine` (`machine_id`);

--
-- Indexes for table `renters`
--
ALTER TABLE `renters`
  ADD PRIMARY KEY (`renter_email`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_email`);

--
-- Indexes for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_session_trainer` (`trainer_email`),
  ADD KEY `fk_session_renter` (`renter_email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `machinery`
--
ALTER TABLE `machinery`
  MODIFY `machine_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `machine_categories`
--
ALTER TABLE `machine_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `penalties`
--
ALTER TABLE `penalties`
  MODIFY `penalty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_sessions`
--
ALTER TABLE `training_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `machinery`
--
ALTER TABLE `machinery`
  ADD CONSTRAINT `fk_machine_cat` FOREIGN KEY (`category_id`) REFERENCES `machine_categories` (`category_id`),
  ADD CONSTRAINT `fk_machine_owner` FOREIGN KEY (`owner_email`) REFERENCES `owners` (`owner_email`) ON UPDATE CASCADE;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maint_machine` FOREIGN KEY (`machine_id`) REFERENCES `machinery` (`machine_id`) ON DELETE CASCADE;

--
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `fk_owner_user` FOREIGN KEY (`owner_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_admin` FOREIGN KEY (`admin_email`) REFERENCES `admins` (`email`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`) ON DELETE CASCADE;

--
-- Constraints for table `penalties`
--
ALTER TABLE `penalties`
  ADD CONSTRAINT `fk_penalty_rental` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`rental_id`);

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `fk_rental_machine` FOREIGN KEY (`machine_id`) REFERENCES `machinery` (`machine_id`),
  ADD CONSTRAINT `fk_rental_renter` FOREIGN KEY (`renter_email`) REFERENCES `renters` (`renter_email`) ON UPDATE CASCADE;

--
-- Constraints for table `renters`
--
ALTER TABLE `renters`
  ADD CONSTRAINT `fk_renter_user` FOREIGN KEY (`renter_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trainers`
--
ALTER TABLE `trainers`
  ADD CONSTRAINT `fk_trainer_user` FOREIGN KEY (`trainer_email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `training_sessions`
--
ALTER TABLE `training_sessions`
  ADD CONSTRAINT `fk_session_renter` FOREIGN KEY (`renter_email`) REFERENCES `renters` (`renter_email`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_session_trainer` FOREIGN KEY (`trainer_email`) REFERENCES `trainers` (`trainer_email`) ON UPDATE CASCADE;
COMMIT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
