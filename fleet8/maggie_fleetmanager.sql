-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 14, 2025 at 09:47 AM
-- Server version: 10.11.5-MariaDB-1:10.11.5+maria~ubu1804
-- PHP Version: 8.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maggie_fleetmanager`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`maggie_kabito`@`localhost` PROCEDURE `GetCategoryFuelStats` (IN `category_id` INT, IN `section_id` INT, IN `report_year` INT, IN `report_month` INT)   BEGIN
    SELECT 
        COUNT(DISTINCT fl.vehicle_id) as total_vehicles,
        COUNT(fl.id) as total_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        AVG(fl.cost / fl.fuel_quantity) as avg_cost_per_liter
    FROM fuel_logs fl
    JOIN vehicles v ON fl.vehicle_id = v.id
    JOIN departments d ON v.department_id = d.id
    WHERE v.category_id = category_id
    AND (section_id IS NULL OR d.section_id = section_id)
    AND YEAR(fl.date) = report_year 
    AND MONTH(fl.date) = report_month;
END$$

CREATE DEFINER=`maggie_kabito`@`localhost` PROCEDURE `GetMonthlyFuelStats` (IN `report_year` INT, IN `report_month` INT)   BEGIN
    SELECT 
        COUNT(DISTINCT fl.vehicle_id) as total_vehicles,
        COUNT(fl.id) as total_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        AVG(fl.cost / fl.fuel_quantity) as avg_cost_per_liter
    FROM fuel_logs fl
    WHERE YEAR(fl.date) = report_year 
    AND MONTH(fl.date) = report_month;
END$$

CREATE DEFINER=`maggie_kabito`@`localhost` PROCEDURE `GetSectionFuelStats` (IN `section_id` INT, IN `report_year` INT, IN `report_month` INT)   BEGIN
    SELECT 
        COUNT(DISTINCT fl.vehicle_id) as total_vehicles,
        COUNT(fl.id) as total_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        AVG(fl.cost / fl.fuel_quantity) as avg_cost_per_liter
    FROM fuel_logs fl
    JOIN vehicles v ON fl.vehicle_id = v.id
    JOIN departments d ON v.department_id = d.id
    WHERE d.section_id = section_id
    AND YEAR(fl.date) = report_year 
    AND MONTH(fl.date) = report_month;
END$$

CREATE DEFINER=`maggie_kabito`@`localhost` PROCEDURE `GetVehicleEfficiency` (IN `vehicle_id` INT, IN `days_back` INT)   BEGIN
    SELECT 
        v.registration_number,
        v.make,
        v.model,
        COUNT(fl.id) as fuel_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        MAX(fl.mileage) - MIN(fl.mileage) as distance_covered,
        CASE 
            WHEN SUM(fl.fuel_quantity) > 0 THEN 
                ROUND((MAX(fl.mileage) - MIN(fl.mileage)) / SUM(fl.fuel_quantity), 2)
            ELSE 0 
        END as efficiency_km_per_liter
    FROM vehicles v
    JOIN fuel_logs fl ON v.id = fl.vehicle_id
    WHERE v.id = vehicle_id 
    AND fl.date >= DATE_SUB(CURDATE(), INTERVAL days_back DAY)
    GROUP BY v.id, v.registration_number, v.make, v.model;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `office_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `office_id`, `created_at`) VALUES
(1, 'Transport', 'Main transport and logistics department', 1, '2025-07-13 20:35:25'),
(2, 'Operations', 'Field operations and utility services', 1, '2025-07-13 20:35:25'),
(3, 'Administration', 'Administrative and management functions', 1, '2025-07-13 20:35:25'),
(4, 'Maintenance', 'Vehicle and equipment maintenance', 1, '2025-07-13 20:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `office_id` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `phone`, `department`, `office_id`, `created_at`) VALUES
(1, 'John Smith', 'john.smith@waterutility.com', '+254-234-567-8901', 'Transport', 1, '2025-07-13 20:35:25'),
(2, 'Maria Garcia', 'maria.garcia@waterutility.com', '+254-234-567-8902', 'Transport', 1, '2025-07-13 20:35:25'),
(3, 'David Johnson', 'david.johnson@waterutility.com', '+254-234-567-8903', 'Transport', 1, '2025-07-13 20:35:25'),
(4, 'Sarah Wilson', 'sarah.wilson@waterutility.com', '+254-234-567-8904', 'Transport', 1, '2025-07-13 20:35:25'),
(5, 'Michael Brown', 'michael.brown@waterutility.com', '+254-234-567-8905', 'Transport', 1, '2025-07-13 20:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE `fuel_logs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `mileage` int(11) NOT NULL,
  `fuel_quantity` decimal(8,2) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_logs`
--

INSERT INTO `fuel_logs` (`id`, `vehicle_id`, `date`, `mileage`, `fuel_quantity`, `cost`, `notes`, `created_at`) VALUES
(1, 1, '2024-12-15', 45000, 45.50, 7280.00, 'Regular refuel at Shell Station', '2025-07-13 20:35:25'),
(2, 2, '2024-12-14', 12500, 8.20, 1312.00, 'Motorcycle fuel top-up', '2025-07-13 20:35:25'),
(3, 3, '2024-12-13', 67000, 38.00, 6080.00, 'Monthly fuel for administration vehicle', '2025-07-13 20:35:25');

-- --------------------------------------------------------

--
-- Stand-in structure for view `fuel_log_details`
-- (See below for the actual view)
--
CREATE TABLE `fuel_log_details` (
`id` int(11)
,`vehicle_id` int(11)
,`date` date
,`mileage` int(11)
,`fuel_quantity` decimal(8,2)
,`cost` decimal(10,2)
,`notes` text
,`created_at` timestamp
,`registration_number` varchar(20)
,`make` varchar(100)
,`model` varchar(100)
,`year` int(11)
,`vehicle_department` varchar(100)
,`category_name` varchar(100)
,`employee_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `fuel_log_details_with_office`
-- (See below for the actual view)
--
CREATE TABLE `fuel_log_details_with_office` (
`id` int(11)
,`vehicle_id` int(11)
,`date` date
,`mileage` int(11)
,`fuel_quantity` decimal(8,2)
,`cost` decimal(10,2)
,`notes` text
,`created_at` timestamp
,`registration_number` varchar(20)
,`make` varchar(100)
,`model` varchar(100)
,`year` int(11)
,`vehicle_department` varchar(100)
,`category_name` varchar(100)
,`employee_name` varchar(255)
,`office_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `description`, `address`, `phone`, `status`, `created_at`) VALUES
(1, 'HQ', 'Headquarters Office', 'Main office location', '+254-XXX-XXX-XXX', 'active', '2025-07-13 20:35:56'),
(2, 'Maragua', 'Maragua Office', 'Maragua branch office', '+254-XXX-XXX-XXX', 'active', '2025-07-13 20:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `created_at`) VALUES
(1, 'Super Admin', 'Full system access', '{\"vehicles_view\": true, \"vehicles_edit\": true, \"vehicles_delete\": true, \"fuel_logs_view\": true, \"fuel_logs_edit\": true, \"fuel_logs_delete\": true, \"employees_view\": true, \"employees_edit\": true, \"employees_delete\": true, \"departments_view\": true, \"departments_edit\": true, \"departments_delete\": true, \"users_view\": true, \"users_edit\": true, \"users_delete\": true, \"reports_view\": true, \"system_settings\": true}', '2025-07-13 20:35:56'),
(2, 'Admin', 'Administrative access with some restrictions', '{\"vehicles_view\": true, \"vehicles_edit\": true, \"vehicles_delete\": false, \"fuel_logs_view\": true, \"fuel_logs_edit\": true, \"fuel_logs_delete\": false, \"employees_view\": true, \"employees_edit\": true, \"employees_delete\": false, \"departments_view\": true, \"departments_edit\": true, \"departments_delete\": false, \"users_view\": true, \"users_edit\": false, \"users_delete\": false, \"reports_view\": true, \"system_settings\": false}', '2025-07-13 20:35:56'),
(3, 'User', 'Basic user access - view and add fuel logs only', '{\"vehicles_view\": true, \"vehicles_edit\": false, \"vehicles_delete\": false, \"fuel_logs_view\": true, \"fuel_logs_edit\": true, \"fuel_logs_delete\": false, \"employees_view\": true, \"employees_edit\": false, \"employees_delete\": false, \"departments_view\": true, \"departments_edit\": false, \"departments_delete\": false, \"users_view\": false, \"users_edit\": false, \"users_delete\": false, \"reports_view\": true, \"system_settings\": false}', '2025-07-13 20:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_id`, `office_id`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1, 1, 'active', '2025-07-14 06:39:08', '2025-07-13 20:35:56'),
(3, 'Administrator', 'administrator@gmail.com', '$2y$10$bJm9pKWYlorbUJba/Rd1r.mK0A0YsRzRx5skzGQrKAPlF/HiFJZa6', 'administrator', 2, 1, 'active', '2025-07-14 06:32:15', '2025-07-14 06:30:10'),
(4, 'User', 'user@gmail.com', '$2y$10$hqj82g42bviz.NZ2GmNUNOthny6qDRf.c3on.hGnQIVjWps1zf16i', 'user', 3, 1, 'active', '2025-07-14 06:40:39', '2025-07-14 06:40:02');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `make` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `year` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `assigned_employee_id` int(11) DEFAULT NULL,
  `department` varchar(100) NOT NULL DEFAULT 'Transport',
  `office_id` int(11) NOT NULL DEFAULT 1,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `current_mileage` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `registration_number`, `make`, `model`, `year`, `category_id`, `assigned_employee_id`, `department`, `office_id`, `status`, `current_mileage`, `created_at`) VALUES
(1, 'KCA-001A', 'Toyota', 'Hilux', 2020, 3, 1, 'Transport', 1, 'active', 45000, '2025-07-13 20:35:25'),
(2, 'KBA-002B', 'Honda', 'CB125', 2021, 2, 2, 'Operations', 1, 'active', 12500, '2025-07-13 20:35:25'),
(3, 'KAA-003C', 'Toyota', 'Corolla', 2019, 1, 3, 'Administration', 1, 'active', 67000, '2025-07-13 20:35:25');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_categories`
--

CREATE TABLE `vehicle_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_categories`
--

INSERT INTO `vehicle_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Car', 'Passenger cars and sedans', '2025-07-13 20:35:25'),
(2, 'Motorcycle', 'Motorcycles and scooters', '2025-07-13 20:35:25'),
(3, 'Truck', 'Heavy trucks and commercial vehicles', '2025-07-13 20:35:25'),
(4, 'Personal Car', 'Personal vehicles assigned to staff', '2025-07-13 20:35:56'),
(5, 'Van', 'Utility vans and light commercial vehicles', '2025-07-13 20:35:56');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vehicle_details`
-- (See below for the actual view)
--
CREATE TABLE `vehicle_details` (
`id` int(11)
,`registration_number` varchar(20)
,`make` varchar(100)
,`model` varchar(100)
,`year` int(11)
,`category_id` int(11)
,`assigned_employee_id` int(11)
,`department` varchar(100)
,`status` enum('active','inactive','maintenance')
,`current_mileage` int(11)
,`created_at` timestamp
,`category_name` varchar(100)
,`category_description` text
,`employee_name` varchar(255)
,`employee_email` varchar(255)
,`employee_department` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vehicle_details_with_office`
-- (See below for the actual view)
--
CREATE TABLE `vehicle_details_with_office` (
`id` int(11)
,`registration_number` varchar(20)
,`make` varchar(100)
,`model` varchar(100)
,`year` int(11)
,`category_id` int(11)
,`assigned_employee_id` int(11)
,`department` varchar(100)
,`office_id` int(11)
,`status` enum('active','inactive','maintenance')
,`current_mileage` int(11)
,`created_at` timestamp
,`category_name` varchar(100)
,`category_description` text
,`employee_name` varchar(255)
,`employee_email` varchar(255)
,`employee_department` varchar(100)
,`office_name` varchar(100)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_departments_name` (`name`),
  ADD KEY `idx_departments_office` (`office_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_employees_department` (`department`),
  ADD KEY `idx_employees_office` (`office_id`);

--
-- Indexes for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fuel_logs_vehicle` (`vehicle_id`),
  ADD KEY `idx_fuel_logs_date` (`date`),
  ADD KEY `idx_fuel_logs_vehicle_date` (`vehicle_id`,`date`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_office` (`office_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_vehicles_category` (`category_id`),
  ADD KEY `idx_vehicles_employee` (`assigned_employee_id`),
  ADD KEY `idx_vehicles_department` (`department`),
  ADD KEY `idx_vehicles_office` (`office_id`);

--
-- Indexes for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_categories`
--
ALTER TABLE `vehicle_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

-- --------------------------------------------------------

--
-- Structure for view `fuel_log_details`
--
DROP TABLE IF EXISTS `fuel_log_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`maggie_kabito`@`localhost` SQL SECURITY DEFINER VIEW `fuel_log_details`  AS SELECT `fl`.`id` AS `id`, `fl`.`vehicle_id` AS `vehicle_id`, `fl`.`date` AS `date`, `fl`.`mileage` AS `mileage`, `fl`.`fuel_quantity` AS `fuel_quantity`, `fl`.`cost` AS `cost`, `fl`.`notes` AS `notes`, `fl`.`created_at` AS `created_at`, `v`.`registration_number` AS `registration_number`, `v`.`make` AS `make`, `v`.`model` AS `model`, `v`.`year` AS `year`, `v`.`department` AS `vehicle_department`, `vc`.`name` AS `category_name`, `e`.`name` AS `employee_name` FROM (((`fuel_logs` `fl` join `vehicles` `v` on(`fl`.`vehicle_id` = `v`.`id`)) join `vehicle_categories` `vc` on(`v`.`category_id` = `vc`.`id`)) left join `employees` `e` on(`v`.`assigned_employee_id` = `e`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `fuel_log_details_with_office`
--
DROP TABLE IF EXISTS `fuel_log_details_with_office`;

CREATE ALGORITHM=UNDEFINED DEFINER=`maggie_kabito`@`localhost` SQL SECURITY DEFINER VIEW `fuel_log_details_with_office`  AS SELECT `fl`.`id` AS `id`, `fl`.`vehicle_id` AS `vehicle_id`, `fl`.`date` AS `date`, `fl`.`mileage` AS `mileage`, `fl`.`fuel_quantity` AS `fuel_quantity`, `fl`.`cost` AS `cost`, `fl`.`notes` AS `notes`, `fl`.`created_at` AS `created_at`, `v`.`registration_number` AS `registration_number`, `v`.`make` AS `make`, `v`.`model` AS `model`, `v`.`year` AS `year`, `v`.`department` AS `vehicle_department`, `vc`.`name` AS `category_name`, `e`.`name` AS `employee_name`, `o`.`name` AS `office_name` FROM ((((`fuel_logs` `fl` join `vehicles` `v` on(`fl`.`vehicle_id` = `v`.`id`)) join `vehicle_categories` `vc` on(`v`.`category_id` = `vc`.`id`)) left join `employees` `e` on(`v`.`assigned_employee_id` = `e`.`id`)) left join `offices` `o` on(`v`.`office_id` = `o`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vehicle_details`
--
DROP TABLE IF EXISTS `vehicle_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`maggie_kabito`@`localhost` SQL SECURITY DEFINER VIEW `vehicle_details`  AS SELECT `v`.`id` AS `id`, `v`.`registration_number` AS `registration_number`, `v`.`make` AS `make`, `v`.`model` AS `model`, `v`.`year` AS `year`, `v`.`category_id` AS `category_id`, `v`.`assigned_employee_id` AS `assigned_employee_id`, `v`.`department` AS `department`, `v`.`status` AS `status`, `v`.`current_mileage` AS `current_mileage`, `v`.`created_at` AS `created_at`, `vc`.`name` AS `category_name`, `vc`.`description` AS `category_description`, `e`.`name` AS `employee_name`, `e`.`email` AS `employee_email`, `e`.`department` AS `employee_department` FROM ((`vehicles` `v` left join `vehicle_categories` `vc` on(`v`.`category_id` = `vc`.`id`)) left join `employees` `e` on(`v`.`assigned_employee_id` = `e`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vehicle_details_with_office`
--
DROP TABLE IF EXISTS `vehicle_details_with_office`;

CREATE ALGORITHM=UNDEFINED DEFINER=`maggie_kabito`@`localhost` SQL SECURITY DEFINER VIEW `vehicle_details_with_office`  AS SELECT `v`.`id` AS `id`, `v`.`registration_number` AS `registration_number`, `v`.`make` AS `make`, `v`.`model` AS `model`, `v`.`year` AS `year`, `v`.`category_id` AS `category_id`, `v`.`assigned_employee_id` AS `assigned_employee_id`, `v`.`department` AS `department`, `v`.`office_id` AS `office_id`, `v`.`status` AS `status`, `v`.`current_mileage` AS `current_mileage`, `v`.`created_at` AS `created_at`, `vc`.`name` AS `category_name`, `vc`.`description` AS `category_description`, `e`.`name` AS `employee_name`, `e`.`email` AS `employee_email`, `e`.`department` AS `employee_department`, `o`.`name` AS `office_name` FROM (((`vehicles` `v` left join `vehicle_categories` `vc` on(`v`.`category_id` = `vc`.`id`)) left join `employees` `e` on(`v`.`assigned_employee_id` = `e`.`id`)) left join `offices` `o` on(`v`.`office_id` = `o`.`id`)) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD CONSTRAINT `fuel_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`id`),
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicles_ibfk_3` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
