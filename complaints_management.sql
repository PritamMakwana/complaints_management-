-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 09:05 AM
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
-- Database: `complaints_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('New','Assigned to Coordinator','Assigned to Service Person','Closed') NOT NULL DEFAULT 'New',
  `receptionist_user_id` int(11) NOT NULL,
  `coordinator_user_id` int(11) DEFAULT NULL,
  `spare_parts_coordinator_user_id` int(11) DEFAULT NULL,
  `service_person_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  `closing_remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `customer_id`, `product_name`, `description`, `status`, `receptionist_user_id`, `coordinator_user_id`, `spare_parts_coordinator_user_id`, `service_person_id`, `created_at`, `closed_at`, `closing_remark`) VALUES
(1, 1, 'Model B', 'new user pritam', 'Closed', 1, 1, 1, 1, '2025-08-26 10:35:52', '2025-08-26 10:35:52', 'Note: Other details'),
(2, 2, 'Model C', 'Gopal New User', 'Closed', 1, 1, 1, 1, '2025-08-26 10:34:10', '2025-08-26 10:28:13', 'Service Fulfilled: all is done'),
(3, 2, 'Model B', 'new', 'Closed', 1, 1, 1, 1, '2025-08-26 17:14:14', '2025-08-29 04:54:50', 'Service Fulfilled: done'),
(4, 3, 'Model A', '', 'Closed', 1, 1, 1, 1, '2025-08-26 17:12:22', '2025-08-27 10:22:11', 'Warranty Expired: expiry '),
(5, 2, 'Model A', '', 'Assigned to Service Person', 1, 1, 0, 2, '2025-08-27 12:28:16', NULL, NULL),
(6, 4, 'Model B', 'Anim deleniti tenetu', 'Closed', 1, 1, 1, 1, '2025-08-27 12:38:26', '2025-08-27 12:38:26', 'Note: close'),
(7, 5, 'Model B', 'hello', 'Assigned to Service Person', 1, 1, 1, 2, '2025-08-27 11:49:52', NULL, NULL),
(8, 2, 'Model C', '', 'Closed', 1, 1, 0, 1, '2025-08-27 12:37:38', '2025-08-27 12:37:38', 'Service Fulfilled: Ready'),
(9, 6, 'Model B', 'Jay Product', 'New', 1, NULL, NULL, NULL, '2025-08-27 14:13:32', NULL, NULL),
(10, 6, 'Model C', 'All Ready Not', 'Closed', 1, 1, 1, 1, '2025-08-27 14:19:24', '2025-08-27 14:19:24', 'Warranty Expired: done'),
(11, 7, 'Model B', 'new complaint 28', 'Closed', 1, 1, 1, 1, '2025-08-28 06:56:06', '2025-08-28 06:56:06', 'Note: close done');

-- --------------------------------------------------------

--
-- Table structure for table `complaint_details`
--

CREATE TABLE `complaint_details` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `service_needed` tinyint(1) NOT NULL DEFAULT 0,
  `free_spare_parts_needed` tinyint(1) NOT NULL DEFAULT 0,
  `paid_spare_parts_needed` tinyint(1) NOT NULL DEFAULT 0,
  `num_of_coolers` int(11) NOT NULL DEFAULT 1,
  `coordinator_remark` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint_details`
--

INSERT INTO `complaint_details` (`id`, `complaint_id`, `service_needed`, `free_spare_parts_needed`, `paid_spare_parts_needed`, `num_of_coolers`, `coordinator_remark`, `updated_at`) VALUES
(1, 3, 1, 1, 0, 1, 'new ', '2025-08-26 04:51:24'),
(2, 4, 0, 0, 0, 3, '', '2025-08-26 10:20:40'),
(3, 2, 1, 0, 1, 1, 'free', '2025-08-26 10:27:22'),
(4, 1, 1, 1, 1, 1, 'dwaw awdaw', '2025-08-26 10:30:01'),
(5, 6, 1, 1, 1, 1, 'dawd awdaw awdfawd', '2025-08-27 11:44:16'),
(6, 7, 1, 1, 1, 1, 'dwad hello', '2025-08-27 11:49:07'),
(21, 8, 0, 1, 0, 1, '', '2025-08-27 12:29:25'),
(22, 5, 1, 1, 1, 345, 'In sed consequatur ', '2025-08-27 12:28:16'),
(23, 10, 1, 1, 1, 1, 'Coordinator  remake', '2025-08-27 14:16:59'),
(24, 11, 1, 1, 1, 3, 'new 28', '2025-08-28 06:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `mobile_number`, `city`, `state`, `address`, `created_at`) VALUES
(1, 'pritam makwana', '9723417588', 'Amreli', 'Gujarat', '', '2025-08-26 10:30:01'),
(2, 'Kiara Saunders', '1212121212', 'Culpa totam tenetur', 'Cupiditate architect', 'Minus incididunt und', '2025-08-27 12:28:16'),
(3, 'Rohit Sharma R', '1313131313', 'Mumbai', 'maharashtra', 'main mumbai', '2025-08-26 10:20:40'),
(4, 'John Cena', '7081111111', 'Amreli', 'Gujarat', 'Hanumanpara amreli', '2025-08-27 11:44:15'),
(5, 'yes makwana', '3561111111', 'Amreli', 'Gujarat', 'Hanumanpara amreli', '2025-08-27 11:50:37'),
(6, 'Jay Joshi', '1213121212', 'Amreli', 'Gujarat', 'Hanumanpara amreli', '2025-08-27 14:16:59'),
(7, 'rahul joshi', '1414141414', 'amreli', 'gujarat', 'Hanumanpara amreli', '2025-08-28 06:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `service_persons`
--

CREATE TABLE `service_persons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile_number` varchar(255) NOT NULL,
  `area_of_service` varchar(255) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_persons`
--

INSERT INTO `service_persons` (`id`, `name`, `mobile_number`, `area_of_service`, `is_available`) VALUES
(1, 'Rakesh Joshi', '1313131313', 'main rajkot', 1),
(2, 'Rahul Sharma', '1414141414', 'Central Ahmedabad', 1);

-- --------------------------------------------------------

--
-- Table structure for table `spare_parts_list`
--

CREATE TABLE `spare_parts_list` (
  `id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('Pending','Shipped','Received','') NOT NULL,
  `courier_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `spare_parts_list`
--

INSERT INTO `spare_parts_list` (`id`, `complaint_id`, `part_name`, `quantity`, `status`, `courier_details`) VALUES
(1, 3, 'a', 10, 'Received', 'a ok'),
(2, 3, 'b', 20, 'Received', 'b ok'),
(3, 4, '1a', 20, 'Pending', NULL),
(4, 4, '1c', 23, 'Pending', NULL),
(5, 2, 'z1', 233, 'Pending', NULL),
(6, 2, 'z2', 12, 'Pending', NULL),
(7, 1, 'n', 23, 'Received', '23eq2 ve dfq'),
(8, 1, 'm', 34, 'Received', '32 eq2 q2e'),
(9, 6, 'zx', 12, 'Pending', NULL),
(10, 6, 'zc', 15, 'Pending', NULL),
(11, 7, 'cs', 12, 'Shipped', ''),
(12, 7, 'ca', 12, 'Shipped', ''),
(13, 5, '1', 1, 'Pending', NULL),
(14, 5, '1', 1, 'Pending', NULL),
(17, 10, 'av', 12, 'Pending', 'ok a'),
(18, 10, 'ac', 14, 'Pending', 'ok b'),
(19, 11, 'ac', 12, 'Received', 'd1'),
(20, 11, 'ab', 15, 'Received', 'd2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_complaints_customer` (`customer_id`),
  ADD KEY `fk_complaints_serviceperson` (`service_person_id`);

--
-- Indexes for table `complaint_details`
--
ALTER TABLE `complaint_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_complaintdetails_complaint` (`complaint_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_number` (`mobile_number`);

--
-- Indexes for table `service_persons`
--
ALTER TABLE `service_persons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_number` (`mobile_number`);

--
-- Indexes for table `spare_parts_list`
--
ALTER TABLE `spare_parts_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_spareparts_complaint` (`complaint_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `complaint_details`
--
ALTER TABLE `complaint_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `service_persons`
--
ALTER TABLE `service_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `spare_parts_list`
--
ALTER TABLE `spare_parts_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_complaints_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_complaints_serviceperson` FOREIGN KEY (`service_person_id`) REFERENCES `service_persons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `complaint_details`
--
ALTER TABLE `complaint_details`
  ADD CONSTRAINT `fk_complaintdetails_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `spare_parts_list`
--
ALTER TABLE `spare_parts_list`
  ADD CONSTRAINT `fk_spareparts_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
