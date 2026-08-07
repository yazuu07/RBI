-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 07, 2026 at 12:49 PM
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
-- Database: `rbis_db`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `age_distribution`
-- (See below for the actual view)
--
CREATE TABLE `age_distribution` (
`age_group` varchar(5)
,`count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `audit_trails`
--

CREATE TABLE `audit_trails` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_trails`
--

INSERT INTO `audit_trails` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 2, 'LOGIN', 'users', 2, 'User logged in', '::1', '2026-07-25 10:44:29'),
(2, 2, 'LOGOUT', 'users', 2, 'User logged out', '::1', '2026-07-25 10:51:59'),
(3, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', '2026-07-25 10:52:05'),
(4, 1, 'CREATE', 'household_records', 1, 'Added household: Nimrod Palomar', '::1', '2026-07-25 10:57:54'),
(5, 1, 'CREATE', 'individual_records', 1, 'Added citizen: Nimrod Palomar', '::1', '2026-07-25 10:58:17'),
(6, 1, 'CREATE', 'individual_records', 2, 'Added citizen: Ralvin Valiente', '::1', '2026-07-25 11:02:06'),
(7, 1, 'VIEW', 'individual_records', 2, 'Viewed citizen: Ralvin Valiente', '::1', '2026-07-25 11:02:08'),
(8, 1, 'CREATE', 'individual_records', 3, 'Added citizen: Christian Cuescano', '::1', '2026-07-25 11:11:53'),
(9, 1, 'VIEW', 'individual_records', 3, 'Viewed citizen: Christian Cuescano', '::1', '2026-07-25 11:11:54'),
(10, 1, 'CREATE', 'household_records', 2, 'Added household: Ralvin Valiente', '::1', '2026-07-25 11:16:17'),
(11, 1, 'CREATE', 'individual_records', 4, 'Added citizen: AEN DEE', '::1', '2026-07-25 11:26:45'),
(12, 1, 'VIEW', 'individual_records', 4, 'Viewed citizen: AEN DEE', '::1', '2026-07-25 11:26:47'),
(13, 1, 'DELETE', 'household_records', 2, 'Deleted household: Ralvin Valiente', '::1', '2026-07-25 11:33:07'),
(14, 1, 'DELETE', 'household_records', 1, 'Deleted household: Nimrod Palomar', '::1', '2026-07-25 11:33:08'),
(15, 1, 'CREATE', 'household_records', 3, 'Added household: Nimrod Palomar', '::1', '2026-07-25 11:34:16'),
(16, 1, 'VIEW', 'household_records', 3, 'Viewed household: Nimrod Palomar', '::1', '2026-07-25 11:37:29'),
(17, 1, 'LOGOUT', 'users', 1, 'User logged out', '::1', '2026-07-25 12:00:06'),
(18, 2, 'LOGIN', 'users', 2, 'User logged in', '::1', '2026-07-25 12:03:55'),
(19, 2, 'LOGOUT', 'users', 2, 'User logged out', '::1', '2026-07-25 12:03:58'),
(20, 4, 'LOGIN', 'users', 4, 'User logged in', '::1', '2026-07-25 12:04:03'),
(21, 4, 'LOGOUT', 'users', 4, 'User logged out', '::1', '2026-07-25 12:04:08'),
(22, 3, 'LOGIN', 'users', 3, 'User logged in', '::1', '2026-07-25 12:04:17'),
(23, 3, 'LOGOUT', 'users', 3, 'User logged out', '::1', '2026-07-25 12:04:19'),
(24, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', '2026-07-25 12:04:24'),
(25, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', '2026-08-04 07:32:43'),
(26, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', '2026-08-06 09:33:31'),
(27, 1, 'LOGIN', 'users', 1, 'User logged in', '::1', '2026-08-07 09:56:49'),
(28, 1, 'VIEW', 'individual_records', 4, 'Viewed citizen: AEN DEE', '::1', '2026-08-07 10:33:38'),
(29, 1, 'CREATE', 'individual_records', 5, 'Added citizen: Miles Morales', '::1', '2026-08-07 10:36:31'),
(30, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:36:33'),
(31, 1, 'DELETE', 'individual_records', 4, 'Deleted citizen: AEN DEE', '::1', '2026-08-07 10:37:26'),
(32, 1, 'DELETE', 'individual_records', 3, 'Deleted citizen: Christian Cuescano', '::1', '2026-08-07 10:37:29'),
(33, 1, 'DELETE', 'individual_records', 2, 'Deleted citizen: Ralvin Valiente', '::1', '2026-08-07 10:37:31'),
(34, 1, 'DELETE', 'individual_records', 1, 'Deleted citizen: Nimrod Palomar', '::1', '2026-08-07 10:37:33'),
(35, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:38:52'),
(36, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:39:20'),
(37, 1, 'VIEW', 'household_records', 3, 'Viewed household: Nimrod Palomar', '::1', '2026-08-07 10:39:53'),
(38, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:40:06'),
(39, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:41:47'),
(40, 1, 'VIEW', 'individual_records', 5, 'Viewed citizen: Miles Morales', '::1', '2026-08-07 10:42:09');

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `business_type` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `permit_number` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Pending') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `certificate_type` varchar(50) DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Pending','Issued','Expired','Cancelled') DEFAULT 'Pending',
  `issued_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `demographic_stats`
-- (See below for the actual view)
--
CREATE TABLE `demographic_stats` (
`total_population` bigint(21)
,`total_households` bigint(21)
,`total_male` bigint(21)
,`total_female` bigint(21)
,`birthday_today` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `household_records`
--

CREATE TABLE `household_records` (
  `id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `ext_name` varchar(10) DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated') NOT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `disability` text DEFAULT NULL,
  `pets` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `household_type` varchar(50) DEFAULT 'Nuclear',
  `dwelling_type` varchar(50) DEFAULT NULL,
  `household_name` varchar(100) DEFAULT NULL,
  `position_in_household` varchar(50) DEFAULT NULL,
  `tenure_status` varchar(50) DEFAULT 'Owner',
  `monthly_income` decimal(10,2) DEFAULT 0.00,
  `head_of_family_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `household_records`
--

INSERT INTO `household_records` (`id`, `last_name`, `first_name`, `middle_name`, `ext_name`, `place_of_birth`, `date_of_birth`, `age`, `sex`, `civil_status`, `citizenship`, `occupation`, `profession`, `disability`, `pets`, `profile_picture`, `created_by`, `created_at`, `updated_at`, `household_type`, `dwelling_type`, `household_name`, `position_in_household`, `tenure_status`, `monthly_income`, `head_of_family_id`) VALUES
(3, 'Palomar', 'Nimrod', '', 'Jr.', 'Caloocan', '1980-10-23', 45, 'Male', 'Single', 'Filipino', 'Student', 'NA', 'NA', 'Cat', NULL, 1, '2026-07-25 11:34:16', '2026-07-25 11:34:16', 'Nuclear', 'Single Family House', 'Palomar', 'Father', 'Owner', 100000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `individual_records`
--

CREATE TABLE `individual_records` (
  `id` int(11) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `ext_name` varchar(10) DEFAULT NULL,
  `place_of_birth` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` enum('Male','Female','Other') NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated') NOT NULL,
  `highest_education` enum('Elementary','High School','Vocational','College','Post Graduate','Doctorate') DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `educational_status` varchar(50) DEFAULT NULL,
  `philsys_number` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `telephone_number` varchar(20) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `city_municipality` varchar(100) DEFAULT NULL,
  `barangay_address` varchar(100) DEFAULT NULL,
  `house_address` varchar(100) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `subdivision` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `blood_type` varchar(10) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` varchar(50) DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `registered_voter` tinyint(1) DEFAULT 0,
  `voter_not_resident` tinyint(1) DEFAULT 0,
  `ethnicity` varchar(50) DEFAULT NULL,
  `position_in_household` varchar(50) DEFAULT NULL,
  `mother_maiden_name` varchar(100) DEFAULT NULL,
  `has_pet` tinyint(1) DEFAULT 0,
  `sectors` text DEFAULT NULL,
  `sector_other` varchar(100) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `individual_records`
--

INSERT INTO `individual_records` (`id`, `last_name`, `first_name`, `middle_name`, `ext_name`, `place_of_birth`, `date_of_birth`, `age`, `sex`, `civil_status`, `highest_education`, `profile_picture`, `created_by`, `created_at`, `updated_at`, `educational_status`, `philsys_number`, `email`, `mobile_number`, `telephone_number`, `region`, `province`, `city_municipality`, `barangay_address`, `house_address`, `street`, `subdivision`, `zip_code`, `blood_type`, `weight`, `height`, `citizenship`, `registered_voter`, `voter_not_resident`, `ethnicity`, `position_in_household`, `mother_maiden_name`, `has_pet`, `sectors`, `sector_other`, `profession`) VALUES
(5, 'Morales', 'Miles', 'M', '', 'New City', '2007-12-23', 18, 'Male', 'Single', 'College', '6a75b52f64bc5.jpg', 1, '2026-08-07 10:36:31', '2026-08-07 10:36:31', 'Graduate', '123123123123123', 'nimrodomar@gmail.com', '09158541234', '631231234', 'Autonomous Region in Muslim Mindanao', 'Basilan', 'Quezon City', 'Calut', 'NANANANANANANANANA', 'NA', '', '1132', 'A-', 60.00, '6\'1', 'Filipino', 0, 1, 'Christian', 'Son', 'Tognony', 0, 'Unemployed,Student', '', 'Computer Science');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `pet_name` varchar(100) NOT NULL,
  `pet_type` varchar(50) NOT NULL,
  `breed` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT 'Male',
  `weight` decimal(5,2) DEFAULT NULL,
  `microchip_number` varchar(50) DEFAULT NULL,
  `vaccination_status` enum('Up to Date','Partial','None') DEFAULT 'None',
  `registration_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Deceased') DEFAULT 'Active',
  `pet_photo` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sql_query_log`
--

CREATE TABLE `sql_query_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `query` text DEFAULT NULL,
  `query_type` varchar(20) DEFAULT NULL,
  `affected_rows` int(11) DEFAULT NULL,
  `query_time` decimal(10,3) DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role_id`, `last_login`, `is_active`, `created_at`) VALUES
(1, 'superadmin', 'admin123', 'Super Administrator', 'superadmin@barangay.gov.ph', 1, '2026-08-07 09:56:49', 1, '2026-07-25 09:54:26'),
(2, 'admin', 'admin123', 'System Administrator', 'admin@barangay.gov.ph', 2, '2026-07-25 12:03:55', 1, '2026-07-25 09:54:26'),
(3, 'enumerator', 'admin123', 'Field Enumerator', 'enumerator@barangay.gov.ph', 3, '2026-07-25 12:04:17', 1, '2026-07-25 09:54:26'),
(4, 'editor', 'admin123', 'Data Editor', 'editor@barangay.gov.ph', 4, '2026-07-25 12:04:03', 1, '2026-07-25 09:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `role_name`, `role_description`, `permissions`, `created_at`) VALUES
(1, 'superadmin', 'Full system access with SQL execution capabilities', NULL, '2026-07-25 09:54:26'),
(2, 'admin', 'Management level with limited system access', NULL, '2026-07-25 09:54:26'),
(3, 'enumerator', 'Field data collection and certification', NULL, '2026-07-25 09:54:26'),
(4, 'editor', 'Data management with reporting capabilities', NULL, '2026-07-25 09:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `year_model` int(11) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Expired') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `age_distribution`
--
DROP TABLE IF EXISTS `age_distribution`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `age_distribution`  AS SELECT CASE WHEN `individual_records`.`age` <= 17 THEN '0-17' WHEN `individual_records`.`age` <= 25 THEN '18-25' WHEN `individual_records`.`age` <= 35 THEN '26-35' WHEN `individual_records`.`age` <= 45 THEN '36-45' WHEN `individual_records`.`age` <= 55 THEN '46-55' WHEN `individual_records`.`age` <= 65 THEN '56-65' ELSE '65+' END AS `age_group`, count(0) AS `count` FROM `individual_records` WHERE `individual_records`.`age` is not null GROUP BY CASE WHEN `individual_records`.`age` <= 17 THEN '0-17' WHEN `individual_records`.`age` <= 25 THEN '18-25' WHEN `individual_records`.`age` <= 35 THEN '26-35' WHEN `individual_records`.`age` <= 45 THEN '36-45' WHEN `individual_records`.`age` <= 55 THEN '46-55' WHEN `individual_records`.`age` <= 65 THEN '56-65' ELSE '65+' END ;

-- --------------------------------------------------------

--
-- Structure for view `demographic_stats`
--
DROP TABLE IF EXISTS `demographic_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `demographic_stats`  AS SELECT (select count(0) from `individual_records`) AS `total_population`, (select count(0) from `household_records`) AS `total_households`, (select count(0) from `individual_records` where `individual_records`.`sex` = 'Male') AS `total_male`, (select count(0) from `individual_records` where `individual_records`.`sex` = 'Female') AS `total_female`, (select count(0) from `individual_records` where date_format(`individual_records`.`date_of_birth`,'%m-%d') = date_format(curdate(),'%m-%d')) AS `birthday_today` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_trails`
--
ALTER TABLE `audit_trails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table_name` (`table_name`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_record_id` (`record_id`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permit_number` (`permit_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_business_type` (`business_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `idx_resident_id` (`resident_id`),
  ADD KEY `idx_certificate_type` (`certificate_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_issued_date` (`issued_date`);

--
-- Indexes for table `household_records`
--
ALTER TABLE `household_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_first_name` (`first_name`),
  ADD KEY `idx_sex` (`sex`),
  ADD KEY `idx_civil_status` (`civil_status`),
  ADD KEY `idx_citizenship` (`citizenship`),
  ADD KEY `idx_occupation` (`occupation`),
  ADD KEY `idx_age` (`age`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_name_search` (`last_name`,`first_name`,`middle_name`);

--
-- Indexes for table `individual_records`
--
ALTER TABLE `individual_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_first_name` (`first_name`),
  ADD KEY `idx_sex` (`sex`),
  ADD KEY `idx_civil_status` (`civil_status`),
  ADD KEY `idx_education` (`highest_education`),
  ADD KEY `idx_age` (`age`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_name_search` (`last_name`,`first_name`,`middle_name`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_pet_type` (`pet_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_pet_name` (`pet_name`);

--
-- Indexes for table `sql_query_log`
--
ALTER TABLE `sql_query_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role_id` (`role_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_plate_number` (`plate_number`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_trails`
--
ALTER TABLE `audit_trails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `household_records`
--
ALTER TABLE `household_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `individual_records`
--
ALTER TABLE `individual_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sql_query_log`
--
ALTER TABLE `sql_query_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_trails`
--
ALTER TABLE `audit_trails`
  ADD CONSTRAINT `audit_trails_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `businesses`
--
ALTER TABLE `businesses`
  ADD CONSTRAINT `businesses_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `household_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `businesses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `individual_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `household_records`
--
ALTER TABLE `household_records`
  ADD CONSTRAINT `household_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `individual_records`
--
ALTER TABLE `individual_records`
  ADD CONSTRAINT `individual_records_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `household_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sql_query_log`
--
ALTER TABLE `sql_query_log`
  ADD CONSTRAINT `sql_query_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `household_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
