-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2026 at 01:55 PM
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
-- Database: `emssion2`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('greencrm_cache_spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:10:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"create-role\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:9:\"edit-role\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:11:\"delete-role\";s:1:\"c\";s:3:\"web\";}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:11:\"create-user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:9:\"edit-user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:11:\"delete-user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:11:\"client-card\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:13:\"proposal-card\";s:1:\"c\";s:3:\"web\";}i:8;a:3:{s:1:\"a\";i:9;s:1:\"b\";s:20:\"proposal-amount-card\";s:1:\"c\";s:3:\"web\";}i:9;a:3:{s:1:\"a\";i:10;s:1:\"b\";s:27:\"singed-proposal-amount-card\";s:1:\"c\";s:3:\"web\";}}s:5:\"roles\";a:1:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"Admin\";s:1:\"c\";s:3:\"web\";}}}', 1769514697);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) DEFAULT NULL COMMENT 'Company code/identifier',
  `industry_type` enum('manufacturing','energy','transportation','agriculture','construction','retail','healthcare','education','technology','finance','hospitality','mining','chemical','textile','food_beverage','other') DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `size` enum('small','medium','large','enterprise') DEFAULT NULL,
  `employee_count` int(11) DEFAULT NULL,
  `annual_revenue` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `fiscal_year_start` varchar(10) DEFAULT NULL COMMENT 'MM-DD format',
  `reporting_standards` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'GHG Protocol, ISO 14064, etc.' CHECK (json_valid(`reporting_standards`)),
  `scopes_enabled` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Which scopes are enabled [1,2,3]' CHECK (json_valid(`scopes_enabled`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `subscription_expires_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `code`, `industry_type`, `tax_id`, `registration_number`, `country`, `address`, `contact_person`, `email`, `phone`, `website`, `logo`, `size`, `employee_count`, `annual_revenue`, `currency`, `timezone`, `fiscal_year_start`, `reporting_standards`, `scopes_enabled`, `is_active`, `subscription_expires_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Altayaboon Technlogies', 'AC', 'other', NULL, NULL, 'Pakistan', 'islamabad', 'Naveed khan', 'superadmin@qrs.ae', '+9211111111111', NULL, NULL, 'medium', 150, 25000.00, 'USD', 'UTC', NULL, '[\"GHG Protocol\",\"ISO 14064\",\"CDP\"]', '[\"1\",\"2\",\"3\"]', 1, NULL, NULL, '2026-01-07 08:55:02', '2026-01-07 08:55:02'),
(2, 'Green Cre', 'GC', 'energy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'medium', 500, 500000.00, 'USD', 'UTC', NULL, NULL, '[\"1\",\"2\",\"3\"]', 1, NULL, NULL, '2026-01-16 08:27:49', '2026-01-16 08:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'string' COMMENT 'string, integer, boolean, json',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'UAE', 'United Arab Emirates', 1, '2026-01-29 03:24:42', '2026-01-29 03:24:42'),
(2, 'US', 'United States', 1, '2026-01-29 03:24:42', '2026-01-29 03:24:42'),
(3, 'UK', 'United Kingdom', 1, '2026-01-29 03:24:42', '2026-01-29 03:24:42'),
(4, 'CA', 'Canada', 1, '2026-01-29 03:24:42', '2026-01-29 03:24:42'),
(5, 'AU', 'Australia', 1, '2026-01-29 03:24:42', '2026-01-29 03:24:42');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `facility_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `company_id`, `facility_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Accounting', 'test', '2025-12-31 02:24:03', '2026-01-16 12:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `eio_factors`
--

CREATE TABLE `eio_factors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sector_code` varchar(255) DEFAULT NULL,
  `sector_name` varchar(255) NOT NULL,
  `country` varchar(3) NOT NULL DEFAULT 'USA',
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `emission_factor` decimal(15,6) NOT NULL,
  `factor_unit` varchar(255) NOT NULL DEFAULT 'kg_CO2e_per_USD',
  `data_source` varchar(255) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emission_factors`
--

CREATE TABLE `emission_factors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` bigint(20) UNSIGNED NOT NULL,
  `organization_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED DEFAULT NULL,
  `unit` varchar(255) NOT NULL,
  `factor_value` decimal(10,6) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `emission_factors`
--

INSERT INTO `emission_factors` (`id`, `emission_source_id`, `organization_id`, `country_id`, `unit`, `factor_value`, `region`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, NULL, 'litter', 1.800000, NULL, '2026-01-20 07:42:12', '2026-01-20 07:42:12'),
(2, 1, NULL, NULL, 'm³', 0.001900, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(3, 14, NULL, NULL, 'liters', 0.002680, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(4, 15, NULL, NULL, 'liters', 0.002310, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(5, 16, NULL, NULL, 'liters', 0.001510, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(6, 17, NULL, NULL, 'liters', 0.003170, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(7, 18, NULL, NULL, 'kg', 0.002420, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(8, 19, NULL, NULL, 'kg', 0.000000, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(9, 20, NULL, NULL, 'liters', 0.002310, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(10, 21, NULL, NULL, 'liters', 0.002680, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(11, 22, NULL, NULL, 'm³', 0.002000, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(12, 23, NULL, NULL, 'kg', 1.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(13, 24, NULL, NULL, 'kg', 1.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(14, 25, NULL, NULL, 'kWh', 0.000500, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(15, 26, NULL, NULL, 'kWh', 0.000400, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(16, 6, NULL, NULL, 'MJ', 0.000070, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(17, 7, NULL, NULL, 'kWh', 0.000250, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(18, 8, NULL, NULL, 'kWh', 0.000180, 'default', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(19, 27, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(20, 28, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(21, 29, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(22, 30, NULL, NULL, 'ton-km', 0.000060, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(23, 31, NULL, NULL, 'kg', 0.000500, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(24, 32, NULL, NULL, 'km', 0.000180, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(25, 33, NULL, NULL, 'km', 0.000120, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(26, 34, NULL, NULL, 'm²', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(27, 35, NULL, NULL, 'ton-km', 0.000060, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(28, 36, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(29, 37, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(30, 38, NULL, NULL, 'kg', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(31, 39, NULL, NULL, 'm²', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(32, 40, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(33, 41, NULL, NULL, 'unit', 0.000000, 'placeholder', '2026-01-20 04:02:36', '2026-01-20 04:02:36'),
(34, 1, 1, NULL, 'm³', 0.001900, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(35, 14, 1, NULL, 'liters', 0.002680, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(36, 15, 1, NULL, 'liters', 0.002310, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(37, 16, 1, NULL, 'liters', 0.001510, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(38, 17, 1, NULL, 'liters', 0.003170, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(39, 18, 1, NULL, 'kg', 0.002420, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(40, 19, 1, NULL, 'kg', 0.000000, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(41, 20, 1, NULL, 'liters', 0.002310, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(42, 21, 1, NULL, 'liters', 0.002680, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(43, 22, 1, NULL, 'm³', 0.002000, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(44, 23, 1, NULL, 'kg', 1.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(45, 24, 1, NULL, 'kg', 1.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(46, 25, 1, NULL, 'kWh', 0.000500, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(47, 26, 1, NULL, 'kWh', 0.000400, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(48, 6, 1, NULL, 'MJ', 0.000070, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(49, 7, 1, NULL, 'kWh', 0.000250, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(50, 8, 1, NULL, 'kWh', 0.000180, 'default', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(51, 27, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(52, 28, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(53, 29, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(54, 30, 1, NULL, 'ton-km', 0.000060, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(55, 31, 1, NULL, 'kg', 0.000500, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(56, 32, 1, NULL, 'km', 0.000180, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(57, 33, 1, NULL, 'km', 0.000120, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(58, 34, 1, NULL, 'm²', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(59, 35, 1, NULL, 'ton-km', 0.000060, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(60, 36, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(61, 37, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(62, 38, 1, NULL, 'kg', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(63, 39, 1, NULL, 'm²', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(64, 40, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(65, 41, 1, NULL, 'unit', 0.000000, 'placeholder', '2026-01-28 06:13:22', '2026-01-29 02:19:51'),
(66, 18, 2, NULL, 'kg', 2.500000, 'duabi', '2026-01-28 07:18:44', '2026-01-28 07:18:44'),
(67, 18, 1, NULL, 'kg', 2.600000, 'duabi', '2026-01-28 07:19:11', '2026-01-28 07:19:32'),
(68, 18, 3, NULL, 'kg', 2.700000, 'duabi', '2026-01-28 07:19:49', '2026-01-28 07:19:49'),
(69, 1, 1, NULL, 'm³', 0.001900, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(70, 14, 1, NULL, 'liters', 0.002680, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(71, 15, 1, NULL, 'liters', 0.002310, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(72, 16, 1, NULL, 'liters', 0.001510, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(73, 17, 1, NULL, 'liters', 0.003170, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(74, 20, 1, NULL, 'liters', 0.002310, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(75, 21, 1, NULL, 'liters', 0.002680, 'UAE', '2026-01-29 02:19:51', '2026-01-29 02:19:51'),
(76, 18, 4, 1, 'kg', 2.220000, NULL, '2026-01-29 03:42:06', '2026-01-29 03:42:06');

-- --------------------------------------------------------

--
-- Table structure for table `emission_records`
--

CREATE TABLE `emission_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `site_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entry_date` date NOT NULL,
  `facility` varchar(50) NOT NULL,
  `scope` tinyint(4) NOT NULL COMMENT '1, 2, or 3',
  `scope3_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `emission_source` varchar(100) NOT NULL,
  `activity_data` decimal(10,2) DEFAULT NULL,
  `spend_amount` decimal(15,2) DEFAULT NULL,
  `spend_currency` varchar(3) NOT NULL DEFAULT 'USD',
  `emission_factor` decimal(10,4) DEFAULT NULL,
  `factor_organization_id` bigint(20) UNSIGNED DEFAULT NULL,
  `calculation_method` enum('activity-based','spend-based','hybrid') DEFAULT NULL,
  `data_quality` enum('primary','secondary','estimated') NOT NULL DEFAULT 'estimated',
  `co2e_value` decimal(12,2) NOT NULL,
  `confidence_level` enum('low','medium','high') NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `data_source` enum('manual','import','api') NOT NULL DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `supporting_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_documents`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','draft') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emission_records`
--

INSERT INTO `emission_records` (`id`, `company_id`, `site_id`, `entry_date`, `facility`, `scope`, `scope3_category_id`, `supplier_id`, `emission_source`, `activity_data`, `spend_amount`, `spend_currency`, `emission_factor`, `factor_organization_id`, `calculation_method`, `data_quality`, `co2e_value`, `confidence_level`, `department`, `data_source`, `notes`, `supporting_documents`, `created_by`, `created_at`, `updated_at`, `status`) VALUES
(3, 1, NULL, '2025-12-25', 'hq', 1, NULL, NULL, 'diesel', 140.00, NULL, 'USD', 2.5000, NULL, NULL, 'estimated', 350.00, 'medium', 'operations', 'manual', 'notest', NULL, 1, '2025-12-25 03:20:00', '2026-01-16 12:42:10', 'active'),
(4, 1, NULL, '2025-12-25', 'plant-b', 1, NULL, NULL, 'diesel', 240.00, NULL, 'USD', 1.4000, NULL, NULL, 'estimated', 336.00, 'medium', 'operations', 'manual', 'notes', NULL, 1, '2025-12-25 06:17:58', '2026-01-16 12:42:46', 'active'),
(5, 1, NULL, '2025-12-25', 'plant-a', 1, NULL, NULL, 'electricity', NULL, NULL, 'USD', NULL, NULL, NULL, 'estimated', 240.00, 'medium', NULL, 'manual', 'test', NULL, 1, '2025-12-25 06:39:22', '2026-01-16 12:42:46', 'active'),
(6, 1, NULL, '2025-12-26', 'plant-a', 3, NULL, NULL, 'vehicle', NULL, NULL, 'USD', NULL, NULL, NULL, 'estimated', 250.00, 'medium', NULL, 'manual', 'test', NULL, 1, '2025-12-25 06:39:22', '2026-01-16 12:42:46', 'active'),
(7, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'diesel', 200.00, NULL, 'USD', 2.4000, NULL, NULL, 'estimated', 480.00, 'medium', '1', 'manual', 'this is testing', NULL, 1, '2025-12-31 03:28:25', '2026-01-16 12:42:46', 'active'),
(8, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'natural-gas', NULL, NULL, 'USD', NULL, NULL, NULL, 'estimated', 2500.00, 'medium', NULL, 'manual', NULL, NULL, 1, '2025-12-31 03:29:55', '2026-01-16 12:42:46', 'active'),
(9, 1, NULL, '2025-12-31', '3', 1, NULL, NULL, 'natural-gas', NULL, NULL, 'USD', NULL, NULL, NULL, 'estimated', 255.00, 'medium', NULL, 'manual', NULL, NULL, 1, '2025-12-31 03:29:55', '2026-01-16 12:42:46', 'active'),
(10, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'Electricity', 1200.00, NULL, 'USD', 0.5270, NULL, NULL, 'estimated', 632.40, 'high', '1', 'import', 'Monthly electricity consumption', NULL, NULL, '2026-01-02 02:45:04', '2026-01-16 12:42:46', 'active'),
(11, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Natural Gas', 500.00, NULL, 'USD', 2.0000, NULL, NULL, 'estimated', 1000.00, 'medium', '1', 'import', 'Heating gas usage', NULL, NULL, '2026-01-02 02:45:04', '2026-01-16 12:42:46', 'active'),
(12, 1, NULL, '2025-12-31', '2', 3, NULL, NULL, 'Diesel', 300.00, NULL, 'USD', 2.6800, NULL, NULL, 'estimated', 804.00, 'low', '1', 'import', 'Backup generator usage', NULL, NULL, '2026-01-02 02:45:04', '2026-01-16 12:42:46', 'active'),
(13, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'Electricity', 1200.00, NULL, 'USD', 0.5270, NULL, NULL, 'estimated', 632.40, 'high', '1', 'import', 'Monthly electricity consumption', NULL, NULL, '2026-01-02 02:45:36', '2026-01-16 12:42:46', 'active'),
(14, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Natural Gas', 500.00, NULL, 'USD', 2.0000, NULL, NULL, 'estimated', 1000.00, 'medium', '1', 'import', 'Heating gas usage', NULL, NULL, '2026-01-02 02:45:36', '2026-01-16 12:42:46', 'active'),
(15, 1, NULL, '2025-12-31', '2', 3, NULL, NULL, 'Diesel', 300.00, NULL, 'USD', 2.6800, NULL, NULL, 'estimated', 804.00, 'low', '1', 'import', 'Backup generator usage', NULL, NULL, '2026-01-02 02:45:36', '2026-01-16 12:42:46', 'active'),
(16, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'Electricity', 1200.00, NULL, 'USD', 0.5270, NULL, NULL, 'estimated', 632.40, 'high', '1', 'import', 'Monthly electricity consumption', NULL, NULL, '2026-01-02 02:49:08', '2026-01-16 12:42:46', 'active'),
(17, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Natural Gas', 500.00, NULL, 'USD', 2.0000, NULL, NULL, 'estimated', 1000.00, 'medium', '1', 'import', 'Heating gas usage', NULL, NULL, '2026-01-02 02:49:08', '2026-01-16 12:42:46', 'active'),
(18, 1, NULL, '2025-12-31', '2', 3, NULL, NULL, 'Diesel', 300.00, NULL, 'USD', 2.6800, NULL, NULL, 'estimated', 804.00, 'low', '1', 'import', 'Backup generator usage', NULL, NULL, '2026-01-02 02:49:08', '2026-01-16 12:42:46', 'active'),
(19, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Electricity', 1200.00, NULL, 'USD', 0.5270, NULL, NULL, 'estimated', 632.40, 'high', '1', 'import', 'Monthly electricity consumption', NULL, NULL, '2026-01-02 02:49:51', '2026-01-16 12:42:46', 'active'),
(20, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Natural Gas', 500.00, NULL, 'USD', 2.0000, NULL, NULL, 'estimated', 1000.00, 'medium', '1', 'import', 'Heating gas usage', NULL, NULL, '2026-01-02 02:49:51', '2026-01-16 12:42:46', 'active'),
(21, 1, NULL, '2025-12-31', '2', 3, NULL, NULL, 'Diesel', 300.00, NULL, 'USD', 2.6800, NULL, NULL, 'estimated', 804.00, 'low', '1', 'import', 'Backup generator usage', NULL, NULL, '2026-01-02 02:49:51', '2026-01-16 12:42:46', 'active'),
(22, 1, NULL, '2025-12-31', '2', 1, NULL, NULL, 'Electricity', 1200.00, NULL, 'USD', 0.5270, NULL, NULL, 'estimated', 632.40, 'high', '1', 'import', 'Monthly electricity consumption', NULL, NULL, '2026-01-02 02:52:32', '2026-01-16 12:42:46', 'draft'),
(23, 1, NULL, '2025-12-31', '2', 2, NULL, NULL, 'Natural Gas', 500.00, NULL, 'USD', 2.0000, NULL, NULL, 'estimated', 1000.00, 'medium', '1', 'import', 'Heating gas usage', NULL, NULL, '2026-01-02 02:52:32', '2026-01-16 12:42:46', 'active'),
(24, 1, NULL, '2025-12-31', '2', 3, NULL, NULL, 'Diesel', 300.00, NULL, 'USD', 2.6800, NULL, NULL, 'estimated', 804.00, 'low', '1', 'import', 'Backup generator usage', NULL, NULL, '2026-01-02 02:52:32', '2026-01-16 12:42:46', 'active'),
(25, 1, NULL, '2022-10-28', 'WareHouse G13', 2, NULL, NULL, 'Purchased Electricity', 1946.00, NULL, 'USD', 0.0005, NULL, NULL, 'estimated', 1.03, 'high', NULL, 'api', 'Extracted from electricity bill via OCR. Supplier: Unknown', NULL, 1, '2026-01-05 04:37:40', '2026-01-16 12:42:46', 'draft'),
(26, 1, NULL, '2026-01-06', 'warehouse 1', 1, NULL, NULL, 'diesel', 200.00, NULL, 'USD', 0.0027, NULL, NULL, 'estimated', 0.54, 'medium', 'Accounting', 'manual', NULL, NULL, NULL, '2026-01-06 02:52:25', '2026-01-16 12:42:46', 'active'),
(27, 1, NULL, '2026-01-07', 'WareHouse G13', 2, NULL, NULL, 'steam', 200.00, NULL, 'USD', 2.9000, NULL, NULL, 'estimated', 580.00, 'medium', 'Accounting', 'manual', NULL, NULL, 1, '2026-01-07 07:20:25', '2026-01-16 12:42:46', 'active'),
(28, 1, NULL, '2026-01-16', '2', 1, NULL, NULL, 'steam', 200.00, NULL, 'USD', 1.5000, NULL, NULL, 'estimated', 300.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-16 07:15:24', '2026-01-16 12:42:46', 'active'),
(29, 1, NULL, '2026-01-16', '3', 1, NULL, NULL, 'diesel', 200.00, NULL, 'USD', 1.5000, NULL, NULL, 'estimated', 300.00, 'medium', '1', 'manual', 'this manula entry', NULL, 1, '2026-01-16 07:21:32', '2026-01-16 12:42:46', 'active'),
(30, 1, NULL, '2026-01-16', '2', 1, NULL, NULL, 'gasoline', 80.00, NULL, 'USD', 1.5000, NULL, NULL, 'estimated', 120.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-16 07:27:20', '2026-01-16 12:42:46', 'active'),
(31, 1, NULL, '2026-01-16', '3', 1, NULL, NULL, 'diesel', 70.00, NULL, 'USD', 1.0000, NULL, NULL, 'estimated', 70.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-16 07:28:23', '2026-01-16 12:42:46', 'active'),
(32, 1, NULL, '2026-01-16', '3', 2, NULL, NULL, 'electricity', 210.00, NULL, 'USD', 2.1000, NULL, NULL, 'estimated', 441.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-16 07:31:17', '2026-01-16 12:42:46', 'active'),
(33, 1, NULL, '2026-01-19', 'WareHouse G13', 3, NULL, NULL, '1. Purchased Goods & Services', 200.00, NULL, 'USD', 1.5000, NULL, NULL, 'estimated', 300.00, 'medium', 'Accounting', 'manual', NULL, NULL, 1, '2026-01-19 03:12:11', '2026-01-19 03:12:11', 'active'),
(34, 1, NULL, '2026-01-20', '2', 1, NULL, NULL, 'Diesel Fuel Combustion', 200.00, NULL, 'USD', 1.5000, NULL, NULL, 'estimated', 300.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-20 03:08:59', '2026-01-20 03:08:59', 'active'),
(35, 1, NULL, '2026-01-20', '3', 1, NULL, NULL, 'Diesel Fuel Combustion', 200.00, NULL, 'USD', 1.2000, NULL, NULL, 'estimated', 240.00, 'medium', '1', 'manual', NULL, NULL, 1, '2026-01-20 03:45:51', '2026-01-20 03:45:51', 'active'),
(36, 1, NULL, '2026-01-29', '2', 1, NULL, NULL, 'Coal Combustion', 100.00, NULL, 'USD', 0.0024, 1, NULL, 'estimated', 0.24, 'medium', '1', 'manual', 'test', NULL, 1, '2026-01-29 02:59:02', '2026-01-29 02:59:02', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `emission_sources`
--

CREATE TABLE `emission_sources` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `scope` tinyint(4) DEFAULT NULL COMMENT '1, 2, or 3',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `emission_sources`
--

INSERT INTO `emission_sources` (`id`, `name`, `scope`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Natural Gas Combustion', 1, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(2, 'Diesel Fuel Combustion', 1, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(3, 'Gasoline (Company Vehicles)', 1, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(4, 'Refrigerants (F-gases)', 1, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(5, 'Purchased Electricity', 2, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(6, 'Purchased Steam', 2, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(7, 'District Heating', 2, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(8, 'District Cooling', 2, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(9, 'Business Travel', 3, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(10, 'Employee Commuting', 3, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(11, 'Waste Generated in Operations', 3, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(12, 'Upstream Transportation & Distribution', 3, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(13, 'Purchased Goods & Services', 3, NULL, '2026-01-20 03:07:24', '2026-01-20 03:07:24'),
(14, 'Diesel (Stationary Combustion)', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(15, 'Gasoline (Stationary Combustion)', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(16, 'LPG / Propane Combustion', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(17, 'Fuel Oil (Heating Oil) Combustion', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(18, 'Coal Combustion', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(19, 'Biomass Combustion', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(20, 'Company Fleet - Gasoline', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(21, 'Company Fleet - Diesel', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(22, 'Company Fleet - CNG', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(23, 'Refrigerant Leakage (HFCs)', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(24, 'Fire Suppression (HFCs)', 1, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(25, 'Purchased Electricity (Location-based)', 2, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(26, 'Purchased Electricity (Market-based)', 2, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(27, 'Scope 3 - 1. Purchased Goods & Services', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(28, 'Scope 3 - 2. Capital Goods', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(29, 'Scope 3 - 3. Fuel & Energy Related Activities', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(30, 'Scope 3 - 4. Upstream Transportation & Distribution', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(31, 'Scope 3 - 5. Waste Generated in Operations', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(32, 'Scope 3 - 6. Business Travel', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(33, 'Scope 3 - 7. Employee Commuting', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(34, 'Scope 3 - 8. Upstream Leased Assets', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(35, 'Scope 3 - 9. Downstream Transportation & Distribution', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(36, 'Scope 3 - 10. Processing of Sold Products', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(37, 'Scope 3 - 11. Use of Sold Products', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(38, 'Scope 3 - 12. End-of-Life Treatment of Sold Products', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(39, 'Scope 3 - 13. Downstream Leased Assets', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(40, 'Scope 3 - 14. Franchises', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32'),
(41, 'Scope 3 - 15. Investments', 3, NULL, '2026-01-20 04:02:32', '2026-01-20 04:02:32');

-- --------------------------------------------------------

--
-- Table structure for table `export_jobs`
--

CREATE TABLE `export_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `format` enum('pdf','excel','csv','pptx','png') NOT NULL DEFAULT 'excel',
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(255) DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `error_message` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `id` int(10) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`id`, `company_id`, `name`, `description`, `address`, `city`, `state`, `country`, `created_at`, `updated_at`) VALUES
(2, 1, 'WareHouse 2', 'This is testing website.', 'islamabad', 'islamabad', 'punjab', 'Pakistan', '2025-12-31 02:00:58', '2026-01-24 04:39:59'),
(3, 1, 'warehouse 1', 'THIS US', 'islamabad', 'islamabad', 'punjab', 'Pakistan', '2025-12-31 02:01:26', '2026-01-16 12:30:27');

-- --------------------------------------------------------

--
-- Table structure for table `factor_organizations`
--

CREATE TABLE `factor_organizations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `factor_organizations`
--

INSERT INTO `factor_organizations` (`id`, `code`, `name`, `url`, `created_at`, `updated_at`) VALUES
(1, 'IPCC', 'IPCC (Intergovernmental Panel on Climate Change)', 'https://www.ipcc.ch/', '2026-01-28 06:13:14', '2026-01-28 06:13:14'),
(2, 'DEFRA', 'DEFRA (UK Government GHG Conversion Factors)', 'https://www.gov.uk/government/collections/government-conversion-factors-for-company-reporting', '2026-01-28 06:13:14', '2026-01-28 06:13:14'),
(3, 'EPA', 'US EPA (Environmental Protection Agency)', 'https://www.epa.gov/', '2026-01-28 06:13:14', '2026-01-28 06:13:14'),
(4, 'COUNTRY', 'Country-specific (select country below)', '', '2026-01-29 03:33:34', '2026-01-29 03:33:34');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `import_history`
--

CREATE TABLE `import_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `import_id` varchar(50) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `import_type` enum('csv','excel','api','manual','scheduled') NOT NULL DEFAULT 'csv',
  `status` enum('queued','processing','completed','failed','partial') NOT NULL DEFAULT 'queued',
  `total_records` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `successful_records` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_records` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `warning_records` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processing_time` decimal(8,2) DEFAULT NULL,
  `logs` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `industry_emission_templates`
--

CREATE TABLE `industry_emission_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `industry_type` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `scope` tinyint(4) NOT NULL COMMENT '1, 2, or 3',
  `emission_source` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `default_factor` decimal(12,6) NOT NULL,
  `region` varchar(50) DEFAULT NULL COMMENT 'Country/region for region-specific factors',
  `source_reference` varchar(255) DEFAULT NULL COMMENT 'Reference to emission factor source',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_06_26_052804_create_permission_tables', 1),
(5, '2025_10_23_060523_create_companies_table', 1),
(7, '2025_10_23_062256_create_emission_sources_table', 1),
(8, '2025_10_23_062339_create_emission_factors_table', 1),
(9, '2025_10_23_062416_create_emission_records_table', 1),
(10, '2025_10_23_062441_create_reports_table', 1),
(11, '2025_10_23_062513_create_audit_logs_table', 1),
(12, '2025_10_23_062533_create_settings_table', 1),
(13, '2025_10_23_084843_add_fields_to_companies_table', 2),
(14, '2025_10_23_060612_create_sites_table', 3),
(15, '2025_10_28_055900_create_utility_bills_table', 4),
(16, '2025_11_13_124240_create_car_registrations_table', 5),
(17, '2025_11_13_192130_create_car_assets_table', 6),
(18, '2026_01_02_075713_create_cache_table', 0),
(19, '2026_01_02_075713_create_cache_locks_table', 0),
(20, '2026_01_02_075713_create_departments_table', 0),
(21, '2026_01_02_075713_create_emission_records_table', 0),
(22, '2026_01_02_075713_create_facilities_table', 0),
(23, '2026_01_02_075713_create_failed_jobs_table', 0),
(24, '2026_01_02_075713_create_job_batches_table', 0),
(25, '2026_01_02_075713_create_jobs_table', 0),
(26, '2026_01_02_075713_create_model_has_permissions_table', 0),
(27, '2026_01_02_075713_create_model_has_roles_table', 0),
(28, '2026_01_02_075713_create_password_reset_tokens_table', 0),
(29, '2026_01_02_075713_create_permissions_table', 0),
(30, '2026_01_02_075713_create_role_has_permissions_table', 0),
(31, '2026_01_02_075713_create_roles_table', 0),
(32, '2026_01_02_075713_create_sessions_table', 0),
(33, '2026_01_02_075713_create_settings_table', 0),
(34, '2026_01_02_075713_create_users_table', 0),
(35, '2026_01_02_075716_add_foreign_keys_to_departments_table', 0),
(36, '2026_01_02_075716_add_foreign_keys_to_emission_records_table', 0),
(37, '2026_01_02_075716_add_foreign_keys_to_model_has_permissions_table', 0),
(38, '2026_01_02_075716_add_foreign_keys_to_model_has_roles_table', 0),
(39, '2026_01_02_075716_add_foreign_keys_to_role_has_permissions_table', 0),
(40, '2026_01_03_000000_create_utility_bills_table', 7),
(41, '2026_01_03_000001_create_emission_sources_table', 8),
(42, '2026_01_03_000002_create_emission_factors_table', 8),
(43, '2026_01_03_000003_create_companies_table', 9),
(44, '2026_01_03_000004_create_sites_table', 9),
(45, '2026_01_03_083738_create_import_history_table', 10),
(46, '2026_01_07_000001_create_targets_table', 10),
(47, '2026_01_07_083556_create_reports_table', 11),
(48, '2026_01_07_084003_change_reports_to_use_facilities_and_departments', 12),
(49, '2026_01_07_090000_add_views_to_reports_table', 13),
(50, '2026_01_07_090100_create_report_templates_table', 13),
(51, '2026_01_07_090200_create_scheduled_reports_table', 14),
(52, '2026_01_07_090300_create_export_jobs_table', 14),
(53, '2026_01_07_114651_add_company_id_to_users_table', 15),
(54, '2026_01_07_114749_add_company_id_to_emission_records_table', 15),
(55, '2026_01_07_114754_enhance_companies_table_for_multi_industry', 15),
(56, '2026_01_07_114800_create_company_settings_table', 15),
(57, '2026_01_07_114805_create_industry_emission_templates_table', 15),
(58, '2026_01_16_122214_add_company_id_to_facilities_and_departments_tables', 16),
(59, '2026_01_17_100000_create_scope3_categories_table', 17),
(60, '2026_01_17_100001_create_suppliers_table', 17),
(61, '2026_01_17_100002_add_scope3_fields_to_emission_records_table', 17),
(62, '2026_01_17_100005_add_public_portal_fields_to_supplier_surveys_table', 18),
(63, '2026_01_28_000001_create_factor_organizations_table', 19),
(64, '2026_01_28_000002_add_organization_id_to_emission_factors_table', 20),
(65, '2026_01_28_000003_add_factor_organization_id_to_emission_records_table', 21),
(66, '2026_01_17_100003_create_supplier_surveys_table', 22),
(67, '2026_01_17_100004_create_eio_factors_table', 22),
(68, '2026_01_29_000001_create_countries_table', 22),
(69, '2026_01_29_000002_add_country_id_to_emission_factors_table', 22);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'create-role', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(2, 'edit-role', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(3, 'delete-role', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(4, 'create-user', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(5, 'edit-user', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(6, 'delete-user', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(7, 'client-card', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(8, 'proposal-card', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(9, 'proposal-amount-card', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(10, 'singed-proposal-amount-card', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `facility_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `report_name` varchar(255) NOT NULL,
  `period` varchar(50) NOT NULL,
  `generated_at` date NOT NULL,
  `status` enum('published','draft','scheduled','archived') NOT NULL DEFAULT 'draft',
  `type` enum('executive','regulatory','internal','public') NOT NULL DEFAULT 'internal',
  `views_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `last_viewed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `facility_id`, `department_id`, `report_name`, `period`, `generated_at`, `status`, `type`, `views_count`, `last_viewed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 'TEST REPORT', '2025-1', '2026-01-07', 'published', 'internal', 0, NULL, 1, '2026-01-07 04:52:37', '2026-01-07 06:24:18');

-- --------------------------------------------------------

--
-- Table structure for table `report_templates`
--

CREATE TABLE `report_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'executive',
  `formats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`formats`)),
  `sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sections`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(2, 'Admin', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(3, 'Product Manager', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52'),
(4, 'User', 'web', '2025-10-23 02:51:52', '2025-10-23 02:51:52');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(4, 2),
(5, 2),
(6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_reports`
--

CREATE TABLE `scheduled_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `report_template_id` bigint(20) UNSIGNED DEFAULT NULL,
  `facility_id` int(10) UNSIGNED DEFAULT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
  `schedule_time` time NOT NULL DEFAULT '08:00:00',
  `next_run_date` date DEFAULT NULL,
  `last_run_date` date DEFAULT NULL,
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipients`)),
  `formats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`formats`)),
  `status` enum('active','paused','completed') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scope3_categories`
--

CREATE TABLE `scope3_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_type` enum('upstream','downstream') NOT NULL DEFAULT 'upstream',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scope3_categories`
--

INSERT INTO `scope3_categories` (`id`, `code`, `name`, `description`, `category_type`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, '3.1', 'Purchased Goods and Services', 'Emissions from the production of goods and services purchased by the company. This includes raw materials, office supplies, IT equipment, and professional services.', 'upstream', 1, 1, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(2, '3.2', 'Capital Goods', 'Emissions from the production of capital goods (long-term assets) purchased by the company. This includes buildings, machinery, vehicles, and IT infrastructure.', 'upstream', 1, 2, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(3, '3.3', 'Fuel- and Energy-Related Activities', 'Emissions from the extraction, production, and transportation of fuels and energy purchased by the company, not included in Scope 1 or 2. Includes transmission and distribution losses.', 'upstream', 1, 3, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(4, '3.4', 'Upstream Transportation and Distribution', 'Emissions from transportation and distribution of products purchased by the company (incoming logistics). Includes shipping, freight, and third-party logistics.', 'upstream', 1, 4, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(5, '3.5', 'Waste Generated in Operations', 'Emissions from disposal and treatment of waste generated in operations. Includes solid waste, wastewater, hazardous waste, recycling, and incineration.', 'upstream', 1, 5, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(6, '3.6', 'Business Travel', 'Emissions from transportation of employees for business purposes. Includes air travel, car rentals, train travel, and hotel stays.', 'upstream', 1, 6, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(7, '3.7', 'Employee Commuting', 'Emissions from transportation of employees between their homes and workplaces. Includes personal vehicles, public transportation, and remote work considerations.', 'upstream', 1, 7, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(8, '3.8', 'Upstream Leased Assets', 'Emissions from operation of assets leased by the company (not included in Scope 1 or 2). Includes leased vehicles, buildings, and equipment.', 'upstream', 1, 8, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(9, '3.9', 'Downstream Transportation and Distribution', 'Emissions from transportation and distribution of products sold by the company (outgoing logistics). Includes shipping to customers, retail distribution, and e-commerce fulfillment.', 'downstream', 1, 9, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(10, '3.10', 'Processing of Sold Products', 'Emissions from processing of intermediate products sold to other companies. Includes chemical processing and manufacturing components that are further processed.', 'downstream', 1, 10, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(11, '3.11', 'Use of Sold Products', 'Emissions from use of products sold by the company. Includes fuel consumption of vehicles, electricity consumption of electronics, and energy use during product operation.', 'downstream', 1, 11, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(12, '3.12', 'End-of-Life Treatment of Sold Products', 'Emissions from disposal and treatment of products sold by the company at end of life. Includes landfill disposal, incineration, recycling, and product take-back programs.', 'downstream', 1, 12, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(13, '3.13', 'Downstream Leased Assets', 'Emissions from operation of assets owned by the company but leased to others. Includes leased vehicles, buildings, and equipment (finance leases).', 'downstream', 1, 13, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(14, '3.14', 'Franchises', 'Emissions from operation of franchises. Includes fast-food franchises, retail franchises, and service franchises.', 'downstream', 1, 14, '2026-01-19 03:13:07', '2026-01-19 03:13:07'),
(15, '3.15', 'Investments', 'Emissions associated with investments (for financial institutions). Includes equity investments, debt investments, project finance, and managed investments.', 'downstream', 1, 15, '2026-01-19 03:13:07', '2026-01-19 03:13:07');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('HmOz1eLJfT0O0hoLtrmZVnvqyxH8TnLBy9Yu6smL', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiaU1VbXFlMFIwbzR1dGtBNzY2dlQxa1ZNRU1IY2JaQWdSZ0xiSmtPWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9lbWlzc2lvbi1yZWNvcmRzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czo0OiJhdXRoIjthOjE6e3M6MjE6InBhc3N3b3JkX2NvbmZpcm1lZF9hdCI7aToxNzY5NjkwNzcyO319', 1769690774),
('pEyeoS9UegbfI9g79qGCXLY4PdS6ZRnM5Iz1Vhk9', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', 'YTo2OntzOjY6Il90b2tlbiI7czo0MDoiUHVManRjZEcxZndKbE5YTjRMMWdMM0tMbWg2NVJXS2k0aGNyMGNGdiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzg6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9lbWlzc2lvbi1yZWNvcmRzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjQ6ImF1dGgiO2E6MTp7czoyMToicGFzc3dvcmRfY29uZmlybWVkX2F0IjtpOjE3Njk2NjU3MTE7fXM6MTg6ImN1cnJlbnRfY29tcGFueV9pZCI7aToxO30=', 1769673436);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sites`
--

CREATE TABLE `sites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `data_quality` enum('primary','secondary','estimated') NOT NULL DEFAULT 'estimated',
  `emission_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emission_factors`)),
  `status` enum('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `last_data_submission` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_id`, `name`, `email`, `contact_person`, `phone`, `address`, `city`, `state`, `country`, `industry`, `data_quality`, `emission_factors`, `status`, `last_data_submission`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Naveed khan', 'engr.naveedkhan3@gmail.com', 'Fawad', '0501371042', 'Ka', 'Abu dhabi', 'Abu dhabi', 'UAE', 'Factory', 'estimated', NULL, 'active', '2026-01-19 04:08:46', NULL, '2026-01-19 03:08:46', '2026-01-19 04:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_surveys`
--

CREATE TABLE `supplier_surveys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `survey_type` varchar(255) NOT NULL DEFAULT 'emissions_data',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`questions`)),
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`responses`)),
  `status` enum('draft','sent','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `sent_at` timestamp NULL DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `reminder_count` int(11) NOT NULL DEFAULT 0,
  `public_token` varchar(64) DEFAULT NULL,
  `public_token_expires_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplier_surveys`
--

INSERT INTO `supplier_surveys` (`id`, `company_id`, `supplier_id`, `survey_type`, `title`, `description`, `questions`, `responses`, `status`, `sent_at`, `due_date`, `completed_at`, `reminder_sent_at`, `reminder_count`, `public_token`, `public_token_expires_at`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 'emissions_data', '2025', 'THIS IS TEST', '[{\"question\":\"what is the emssion factor of your travels\",\"type\":\"text\"},{\"question\":\"what is the emssion factor value\",\"type\":\"number\"},{\"question\":\"How many factor you use\",\"type\":\"text\"}]', '[\"1.9\",\"2.5\",\"3\"]', 'completed', '2026-01-19 04:07:24', '2026-01-22 20:00:00', '2026-01-19 04:08:46', NULL, 0, '083264815ca89bb2dc74531227bc913c613f864a1ff2eaf5f8e66e2bad139115', '2026-02-18 04:07:24', NULL, 1, '2026-01-19 04:05:42', '2026-01-19 04:08:46');

-- --------------------------------------------------------

--
-- Table structure for table `targets`
--

CREATE TABLE `targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `site_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('sbt','net-zero','carbon-neutral','regulatory','internal') NOT NULL DEFAULT 'internal',
  `scope` varchar(10) NOT NULL DEFAULT 'all',
  `baseline_year` smallint(5) UNSIGNED DEFAULT NULL,
  `baseline_emissions` decimal(12,2) DEFAULT NULL,
  `target_year` smallint(5) UNSIGNED NOT NULL,
  `target_emissions` decimal(12,2) DEFAULT NULL,
  `reduction_percent` decimal(5,2) DEFAULT NULL,
  `strategy` varchar(255) DEFAULT NULL,
  `review_frequency` enum('monthly','quarterly','biannual','annual') NOT NULL DEFAULT 'quarterly',
  `responsible_person` varchar(255) DEFAULT NULL,
  `status` enum('on-track','at-risk','off-track','completed') NOT NULL DEFAULT 'on-track',
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `targets`
--

INSERT INTO `targets` (`id`, `company_id`, `site_id`, `name`, `type`, `scope`, `baseline_year`, `baseline_emissions`, `target_year`, `target_emissions`, `reduction_percent`, `strategy`, `review_frequency`, `responsible_person`, `status`, `description`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'New Zero 2030', 'net-zero', '1-2', 2020, 5000.00, 2030, 3000.00, 10.00, 'energy-efficiency', 'quarterly', NULL, 'on-track', NULL, 1, '2026-01-07 02:25:42', '2026-01-07 02:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `company_access` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of company IDs user can access' CHECK (json_valid(`company_access`)),
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `company_id`, `is_super_admin`, `company_access`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'Naveed khan', 'engr.naveedkhan3@gmail.com', NULL, '$2y$12$aVQMX2g19yiFae1j4s4ST.keslEjHh7vzD5uDYvUcsNAQkO/FXdp2', NULL, '2025-10-23 02:51:53', '2026-01-16 08:39:30'),
(2, NULL, 0, NULL, 'fawad', 'fawad@gmail.com', NULL, '$2y$12$fzL2Kb.IYL1Dg6Ps6e4xeuWkxTSjbCIB6G1AhL7lZFS8ziPbEfJIm', NULL, '2025-10-23 02:51:54', '2025-10-23 02:51:54'),
(3, NULL, 0, NULL, 'zaheer', 'zaheer@gmail.com', NULL, '$2y$12$MKo2y3DRDYBn1VRNKryTou1TxULihUGeT1jMyOYEuPUpvD.Ug.3he', NULL, '2025-10-23 02:51:54', '2025-10-23 02:51:54'),
(4, NULL, 0, NULL, 'waqer', 'waqer@gmail.com', NULL, '$2y$12$MBxuHipbJ4/qn9K7S4aMmux863ZO5YxcLro6NY2Ye.Uj51NNGAOvC', NULL, '2025-10-23 02:51:54', '2025-10-23 02:51:54');

-- --------------------------------------------------------

--
-- Table structure for table `utility_bills`
--

CREATE TABLE `utility_bills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `site_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `bill_type` varchar(255) NOT NULL DEFAULT 'electricity',
  `supplier_name` varchar(255) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `consumption` decimal(12,2) DEFAULT NULL,
  `consumption_unit` varchar(255) DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT NULL,
  `raw_text` text DEFAULT NULL,
  `raw_response` text DEFAULT NULL,
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extracted_data`)),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `emission_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utility_bills`
--

INSERT INTO `utility_bills` (`id`, `company_id`, `site_id`, `file_path`, `bill_type`, `supplier_name`, `bill_date`, `consumption`, `consumption_unit`, `cost`, `raw_text`, `raw_response`, `extracted_data`, `created_by`, `emission_record_id`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'utility_bills/mQiMFsd1D3zvEEEuC47FvwhScGlF3LAoflDIiZFM.png', 'electricity', NULL, '2022-10-28', 1946.00, 'kWh', NULL, 'GOVERNMENT OF DUBAI\r\nGreen\r\nTax Invoice\r\nBill\r\nInvoice: 100131695513\r\nIssue Date:28/10/2022\r\nMonth: October 2022\r\nPeriod: 26/09/2022 to 25/10/2022\r\nDEWA VAT No. : 100027620200003\r\nKilowatt Hours(kWh)\r\n1,946\r\nElectricity\r\nFuel Surcharge\r\n01409\r\nDubai Electricity & Water Authority\r\nPage 2 of 3\r\nAccount Number\r\n2041445190\r\nMeter number(s): 1198115T\r\nCurrent reading:\r\n105229\r\nPrevious reading: 103283\r\nO\r\nElectricity\r\nCarbon\r\nFootprint\r\nConsumption\r\nKg C02e\r\n779\r\nThe Carbon Footprint\r\nindicator measures how your\r\nenergy usage impacts the\r\nenvironment. Help us fight\r\nglobal warming by reducing\r\nyour monthly consumption.\r\nCarbon emissions in Kg C02e\r\nabove 2, 000\r\nupto 2,000\r\nupto 1,250\r\nupto 500\r\nLearn how to conserve and\r\nsave the environment.\r\nwww.dewa.gov.ae\r\n1,946kWh\r\nO kWh\r\nO kWh\r\nO kWh\r\no\r\nConsumption\r\n1,946 kWh\r\nRate\r\n0.230AED\r\nO.OOOAED\r\n0.000 AED\r\nO.OOOAED\r\nRate\r\nO.060AED\r\nMeter service charge\r\nSub total\r\nVAT\r\n5% VAT applicable on total amount of 570.34\r\nElectricity total\r\nAED\r\n447.58\r\n0.00\r\n0.00\r\n0.00\r\nAED\r\n116.76\r\n6.00\r\n570.34\r\nAED\r\n28.52\r\n598.86\r\n24/7 Customer care 04 601 9999 po Box 564, Dubai, UAE.\r\ndewa .gov .ae\r\ndewa.ae/smart\r\nA globally leading sustainable innovative corporation\r\nFor Generations to Come\r\nDubai Electricity & Water Authority (PJSC)\r\n', NULL, '{\"bill_date\":\"2022-10-28\",\"consumption\":1946,\"consumption_unit\":\"kWh\",\"cost\":null,\"supplier_name\":null,\"confidence\":\"high\",\"ocr_method\":\"ocr_space\"}', 1, 25, '2026-01-05 04:37:40', '2026-01-05 04:37:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_code_unique` (`code`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_settings_company_id_key_unique` (`company_id`,`key`),
  ADD KEY `company_settings_company_id_index` (`company_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `countries_code_unique` (`code`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_departments_facility` (`facility_id`),
  ADD KEY `departments_company_id_index` (`company_id`);

--
-- Indexes for table `eio_factors`
--
ALTER TABLE `eio_factors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `eio_factors_sector_code_country_year_unique` (`sector_code`,`country`,`year`),
  ADD KEY `eio_factors_sector_code_index` (`sector_code`),
  ADD KEY `eio_factors_country_index` (`country`),
  ADD KEY `eio_factors_is_active_index` (`is_active`);

--
-- Indexes for table `emission_factors`
--
ALTER TABLE `emission_factors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emission_factors_source_org_idx` (`emission_source_id`,`organization_id`),
  ADD KEY `emission_factors_organization_id_foreign` (`organization_id`),
  ADD KEY `emission_factors_source_org_country_idx` (`emission_source_id`,`organization_id`,`country_id`),
  ADD KEY `emission_factors_country_id_foreign` (`country_id`);

--
-- Indexes for table `emission_records`
--
ALTER TABLE `emission_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_emission_records_user` (`created_by`),
  ADD KEY `emission_records_site_id_foreign` (`site_id`),
  ADD KEY `emission_records_company_id_entry_date_index` (`company_id`,`entry_date`),
  ADD KEY `emission_records_company_id_scope_index` (`company_id`,`scope`),
  ADD KEY `emission_records_scope3_category_id_index` (`scope3_category_id`),
  ADD KEY `emission_records_supplier_id_index` (`supplier_id`),
  ADD KEY `emission_records_calculation_method_index` (`calculation_method`),
  ADD KEY `emission_records_data_quality_index` (`data_quality`),
  ADD KEY `emission_records_factor_organization_id_foreign` (`factor_organization_id`);

--
-- Indexes for table `emission_sources`
--
ALTER TABLE `emission_sources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `export_jobs`
--
ALTER TABLE `export_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `export_jobs_created_by_foreign` (`created_by`),
  ADD KEY `export_jobs_status_index` (`status`),
  ADD KEY `export_jobs_created_at_index` (`created_at`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `facilities_company_id_index` (`company_id`);

--
-- Indexes for table `factor_organizations`
--
ALTER TABLE `factor_organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `factor_organizations_code_unique` (`code`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `import_history`
--
ALTER TABLE `import_history`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `import_history_import_id_unique` (`import_id`),
  ADD KEY `import_history_user_id_foreign` (`user_id`),
  ADD KEY `import_history_status_index` (`status`),
  ADD KEY `import_history_import_type_index` (`import_type`),
  ADD KEY `import_history_created_at_index` (`created_at`);

--
-- Indexes for table `industry_emission_templates`
--
ALTER TABLE `industry_emission_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `industry_emission_templates_industry_type_scope_index` (`industry_type`,`scope`),
  ADD KEY `industry_emission_templates_industry_type_is_active_index` (`industry_type`,`is_active`),
  ADD KEY `industry_emission_templates_industry_type_index` (`industry_type`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_created_by_foreign` (`created_by`),
  ADD KEY `reports_status_index` (`status`),
  ADD KEY `reports_type_index` (`type`),
  ADD KEY `reports_generated_at_index` (`generated_at`),
  ADD KEY `reports_facility_id_index` (`facility_id`),
  ADD KEY `reports_department_id_index` (`department_id`);

--
-- Indexes for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_templates_created_by_foreign` (`created_by`),
  ADD KEY `report_templates_category_index` (`category`),
  ADD KEY `report_templates_is_active_index` (`is_active`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_reports_report_template_id_foreign` (`report_template_id`),
  ADD KEY `scheduled_reports_facility_id_foreign` (`facility_id`),
  ADD KEY `scheduled_reports_department_id_foreign` (`department_id`),
  ADD KEY `scheduled_reports_created_by_foreign` (`created_by`),
  ADD KEY `scheduled_reports_status_index` (`status`),
  ADD KEY `scheduled_reports_next_run_date_index` (`next_run_date`);

--
-- Indexes for table `scope3_categories`
--
ALTER TABLE `scope3_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scope3_categories_code_unique` (`code`),
  ADD KEY `scope3_categories_category_type_index` (`category_type`),
  ADD KEY `scope3_categories_is_active_index` (`is_active`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sites_company_id_foreign` (`company_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_company_id_index` (`company_id`),
  ADD KEY `suppliers_status_index` (`status`),
  ADD KEY `suppliers_data_quality_index` (`data_quality`);

--
-- Indexes for table `supplier_surveys`
--
ALTER TABLE `supplier_surveys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `supplier_surveys_public_token_unique` (`public_token`),
  ADD KEY `supplier_surveys_company_id_foreign` (`company_id`),
  ADD KEY `supplier_surveys_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `targets`
--
ALTER TABLE `targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `targets_company_id_index` (`company_id`),
  ADD KEY `targets_site_id_index` (`site_id`),
  ADD KEY `targets_status_index` (`status`),
  ADD KEY `targets_created_by_index` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_company_id_foreign` (`company_id`);

--
-- Indexes for table `utility_bills`
--
ALTER TABLE `utility_bills`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `eio_factors`
--
ALTER TABLE `eio_factors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emission_factors`
--
ALTER TABLE `emission_factors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `emission_records`
--
ALTER TABLE `emission_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `emission_sources`
--
ALTER TABLE `emission_sources`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `export_jobs`
--
ALTER TABLE `export_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `factor_organizations`
--
ALTER TABLE `factor_organizations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `import_history`
--
ALTER TABLE `import_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `industry_emission_templates`
--
ALTER TABLE `industry_emission_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report_templates`
--
ALTER TABLE `report_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scope3_categories`
--
ALTER TABLE `scope3_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sites`
--
ALTER TABLE `sites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supplier_surveys`
--
ALTER TABLE `supplier_surveys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `targets`
--
ALTER TABLE `targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `utility_bills`
--
ALTER TABLE `utility_bills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD CONSTRAINT `company_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_departments_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emission_factors`
--
ALTER TABLE `emission_factors`
  ADD CONSTRAINT `emission_factors_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emission_factors_emission_source_id_foreign` FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emission_factors_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `factor_organizations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `emission_records`
--
ALTER TABLE `emission_records`
  ADD CONSTRAINT `emission_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `emission_records_factor_organization_id_foreign` FOREIGN KEY (`factor_organization_id`) REFERENCES `factor_organizations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emission_records_scope3_category_id_foreign` FOREIGN KEY (`scope3_category_id`) REFERENCES `scope3_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emission_records_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `emission_records_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_emission_records_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `export_jobs`
--
ALTER TABLE `export_jobs`
  ADD CONSTRAINT `export_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `facilities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_history`
--
ALTER TABLE `import_history`
  ADD CONSTRAINT `import_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_templates`
--
ALTER TABLE `report_templates`
  ADD CONSTRAINT `report_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scheduled_reports`
--
ALTER TABLE `scheduled_reports`
  ADD CONSTRAINT `scheduled_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_reports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_reports_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_reports_report_template_id_foreign` FOREIGN KEY (`report_template_id`) REFERENCES `report_templates` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sites`
--
ALTER TABLE `sites`
  ADD CONSTRAINT `sites_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_surveys`
--
ALTER TABLE `supplier_surveys`
  ADD CONSTRAINT `supplier_surveys_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_surveys_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
