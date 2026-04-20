-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 04:53 AM
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
-- Database: `checkmate`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `system_name` varchar(255) DEFAULT NULL,
  `system_tagline` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `created_at`, `updated_at`, `system_name`, `system_tagline`) VALUES
(1, 'System', 'Administrator', 'admin@nbsc.edu.ph', '$2y$10$HHDPAd8sANHcZ.tCsz09MuUqEZ5WOQBVpn1L9BgbUA1IzKGKb6DDq', 'admin', '2026-04-06 03:23:46', '2026-04-18 10:39:46', 'Student Evaluation System', 'Empowering excellence in education through comprehensive student performance tracking, evaluation, and assessment reporting.');

-- --------------------------------------------------------

--
-- Table structure for table `admin_promotions`
--

CREATE TABLE `admin_promotions` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `promoted_to` varchar(50) NOT NULL COMMENT 'program_head or admin',
  `promoted_by` int(11) NOT NULL COMMENT 'admin user_id',
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_promotions`
--

INSERT INTO `admin_promotions` (`id`, `instructor_id`, `promoted_to`, `promoted_by`, `promotion_date`) VALUES
(6, 8, 'program_head', 1, '2026-04-06 08:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_sessions`
--

CREATE TABLE `evaluation_sessions` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL DEFAULT '2025-2026',
  `semester` varchar(20) NOT NULL DEFAULT '1st Semester',
  `session_status` enum('draft','submitted','finalized') DEFAULT 'draft',
  `gwa` decimal(4,2) DEFAULT NULL COMMENT 'General Weighted Average',
  `total_units_taken` decimal(5,1) DEFAULT 0.0,
  `total_units_passed` decimal(5,1) DEFAULT 0.0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_sessions`
--

INSERT INTO `evaluation_sessions` (`id`, `instructor_id`, `student_id`, `major_id`, `academic_year`, `semester`, `session_status`, `gwa`, `total_units_taken`, `total_units_passed`, `notes`, `created_at`, `updated_at`) VALUES
(1, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 2.80, 10.0, 10.0, '', '2026-04-15 14:48:08', '2026-04-15 14:48:08'),
(2, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 3.25, 6.0, 3.0, '', '2026-04-19 09:14:19', '2026-04-19 09:14:19'),
(3, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 3.25, 6.0, 3.0, '', '2026-04-19 09:14:51', '2026-04-19 09:14:51'),
(4, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 3.08, 30.0, 18.0, '', '2026-04-19 15:04:17', '2026-04-19 15:04:17'),
(5, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', NULL, 0.0, 0.0, '', '2026-04-19 17:57:38', '2026-04-19 17:57:38'),
(6, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 2.00, 24.0, 24.0, '', '2026-04-19 18:23:36', '2026-04-19 18:23:36'),
(7, 8, 5, 1, '2025-2026', '2nd Semester', 'finalized', 2.06, 48.0, 47.0, '', '2026-04-19 18:25:47', '2026-04-19 18:25:47'),
(8, 8, 5, 1, '2025-2026', '2nd Semester', 'finalized', 2.02, 48.0, 48.0, '', '2026-04-19 18:36:04', '2026-04-19 18:36:04'),
(9, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 1.65, 75.0, 75.0, '', '2026-04-19 18:37:33', '2026-04-19 18:37:33'),
(10, 8, 5, 1, '2025-2026', '1st Semester', 'finalized', 1.79, 123.0, 123.0, '', '2026-04-20 02:28:49', '2026-04-20 02:28:49');

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT 'Instructor',
  `phone` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `avatar_gradient_from` varchar(20) DEFAULT '#667eea',
  `avatar_gradient_to` varchar(20) DEFAULT '#764ba2',
  `status` enum('on duty','on leave','on travel') DEFAULT 'on duty',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `first_name`, `middle_name`, `last_name`, `department`, `suffix`, `email`, `password`, `position`, `phone`, `birthday`, `avatar`, `avatar_gradient_from`, `avatar_gradient_to`, `status`, `created_at`, `updated_at`) VALUES
(7, 'clifford', 'r', 'rule', NULL, 'V', 'rule@gmail.com', '$2y$10$ZNNfzpaqacE25/8Y1omcZevDvseTR/tq/0SXm.mqRTeMY5x.0wzSO', 'Instructor', NULL, NULL, '7.jpg', '#667eea', '#764ba2', 'on duty', '2026-04-06 07:00:12', '2026-04-18 10:23:32'),
(8, 'joshua', 'tesoro', 'quidit', NULL, 'V', 'opop@gmail.com', '$2y$10$09Bo1/tnZ6pGJk6C5TacDu858KUbeJT7iRiursh.d6qzbHbDU1V/G', 'Instructor', '0945673667', NULL, '8.jpg', '#667eea', '#764ba2', 'on duty', '2026-04-06 07:00:53', '2026-04-18 15:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `id` int(11) NOT NULL,
  `major_name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT 'fas fa-building',
  `gradient_from` varchar(20) DEFAULT '#d4a843',
  `gradient_to` varchar(20) DEFAULT '#e8c768',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`id`, `major_name`, `display_name`, `description`, `icon_class`, `gradient_from`, `gradient_to`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Operational Management', 'Operational Management', 'Focuses on business operations, processes, and management strategies to optimize organizational efficiency.', 'fas fa-cogs', '#d4a843', '#e8c768', 1, 1, '2026-04-06 03:23:46', '2026-04-06 03:23:46'),
(2, 'Financial Management', 'Financial Management', 'Specializes in financial analysis, accounting, investment decisions, and corporate finance strategies.', 'fas fa-dollar-sign', '#3b82f6', '#60a5fa', 2, 1, '2026-04-06 03:23:46', '2026-04-06 03:23:46'),
(3, 'Marketing Management', 'Marketing Management', 'Covers marketing principles, consumer behavior, market research, and strategic marketing planning.', 'fas fa-chart-line', '#ec4899', '#f472b6', 3, 1, '2026-04-06 03:23:46', '2026-04-06 03:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `major_subjects`
--

CREATE TABLE `major_subjects` (
  `id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `year_level` varchar(20) DEFAULT '1st Year',
  `semester` varchar(20) DEFAULT '1st Semester',
  `is_required` tinyint(1) DEFAULT 1,
  `is_prerequisite` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `prerequisite` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `major_subjects`
--

INSERT INTO `major_subjects` (`id`, `major_id`, `subject_id`, `year_level`, `semester`, `is_required`, `is_prerequisite`, `sort_order`, `created_at`, `updated_at`, `prerequisite`) VALUES
(9, 2, 12, '3rd Year', '2nd Semester', 1, 1, 0, '2026-04-13 08:06:32', '2026-04-13 08:06:32', NULL),
(11, 3, 14, '1st Year', '1st Semester', 1, 1, 0, '2026-04-13 08:11:08', '2026-04-13 08:11:08', NULL),
(13, 1, 16, 'Bridging', '1st Semester', 1, 1, 0, '2026-04-13 08:54:47', '2026-04-13 08:54:47', NULL),
(14, 2, 16, 'Bridging', '1st Semester', 1, 1, 0, '2026-04-13 08:54:47', '2026-04-13 08:54:47', NULL),
(15, 3, 16, 'Bridging', '1st Semester', 1, 1, 0, '2026-04-13 08:54:47', '2026-04-13 08:54:47', NULL),
(17, 2, 17, '1st Year', '1st Semester', 1, 1, 0, '2026-04-13 23:53:36', '2026-04-13 23:53:36', NULL),
(18, 2, 18, '2nd Year', '2nd Semester', 1, 1, 0, '2026-04-15 09:52:46', '2026-04-15 09:52:46', NULL),
(19, 3, 18, '2nd Year', '2nd Semester', 1, 1, 0, '2026-04-15 09:52:46', '2026-04-15 09:52:46', NULL),
(23, 3, 20, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:16:52', '2026-04-16 00:16:52', NULL),
(24, 2, 20, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:16:52', '2026-04-16 00:16:52', NULL),
(26, 3, 21, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:18:12', '2026-04-16 00:18:12', NULL),
(27, 2, 21, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:18:12', '2026-04-16 00:18:12', NULL),
(29, 3, 22, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:19:55', '2026-04-16 00:19:55', NULL),
(30, 2, 22, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:19:55', '2026-04-16 00:19:55', NULL),
(31, 2, 23, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:21:54', '2026-04-16 00:21:54', NULL),
(33, 3, 23, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:21:54', '2026-04-16 00:21:54', NULL),
(35, 2, 24, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:23:41', '2026-04-16 00:23:41', NULL),
(36, 3, 24, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:23:41', '2026-04-16 00:23:41', NULL),
(37, 3, 25, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:24:12', '2026-04-16 00:24:12', NULL),
(39, 2, 25, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:24:12', '2026-04-16 00:24:12', NULL),
(41, 2, 26, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:25:11', '2026-04-16 00:25:11', NULL),
(42, 3, 26, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:25:11', '2026-04-16 00:25:11', NULL),
(43, 2, 27, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:26:23', '2026-04-16 00:26:23', NULL),
(44, 3, 27, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:26:23', '2026-04-16 00:26:23', NULL),
(46, 3, 28, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:27:09', '2026-04-16 00:27:09', NULL),
(48, 2, 28, '1st Year', '1st Semester', 1, 1, 0, '2026-04-16 00:27:09', '2026-04-16 00:27:09', NULL),
(52, 1, 32, '1st Year', '2nd Semester', 1, 1, 1, '2026-04-16 00:37:02', '2026-04-16 00:37:02', NULL),
(53, 1, 33, '1st Year', '2nd Semester', 1, 1, 2, '2026-04-16 00:37:56', '2026-04-16 00:37:56', NULL),
(54, 1, 34, '1st Year', '2nd Semester', 1, 1, 3, '2026-04-16 00:38:37', '2026-04-16 00:38:37', NULL),
(55, 1, 35, '1st Year', '2nd Semester', 1, 1, 4, '2026-04-16 00:40:09', '2026-04-16 00:40:09', NULL),
(56, 1, 36, '1st Year', '2nd Semester', 1, 1, 5, '2026-04-16 00:41:30', '2026-04-16 00:41:30', NULL),
(57, 1, 37, '1st Year', '2nd Semester', 1, 1, 6, '2026-04-16 00:42:09', '2026-04-16 00:42:09', NULL),
(58, 1, 38, '1st Year', '2nd Semester', 1, 1, 7, '2026-04-16 00:42:43', '2026-04-16 03:18:04', NULL),
(59, 1, 39, '1st Year', '2nd Semester', 1, 1, 8, '2026-04-16 00:44:48', '2026-04-16 03:18:04', NULL),
(60, 1, 40, '1st Year', '2nd Semester', 1, 1, 9, '2026-04-16 00:45:21', '2026-04-16 00:45:21', NULL),
(63, 1, 43, '1st Year', '1st Semester', 1, 1, 10, '2026-04-16 00:54:01', '2026-04-19 09:08:26', NULL),
(64, 2, 43, '1st Year', '1st Semester', 1, 1, 2, '2026-04-16 00:54:01', '2026-04-18 05:47:15', NULL),
(65, 1, 44, '1st Year', '1st Semester', 1, 1, 11, '2026-04-16 00:54:17', '2026-04-19 09:08:26', NULL),
(66, 2, 44, '1st Year', '1st Semester', 1, 1, 1, '2026-04-16 00:54:17', '2026-04-18 05:47:15', NULL),
(67, 1, 45, '1st Year', '1st Semester', 1, 1, 17, '2026-04-16 00:54:32', '2026-04-19 09:08:56', NULL),
(68, 2, 45, '1st Year', '1st Semester', 1, 1, 3, '2026-04-16 00:54:32', '2026-04-16 00:54:32', NULL),
(69, 2, 46, '1st Year', '1st Semester', 1, 1, 4, '2026-04-16 00:54:46', '2026-04-16 00:54:46', NULL),
(70, 1, 46, '1st Year', '1st Semester', 1, 1, 13, '2026-04-16 00:54:46', '2026-04-16 03:17:27', NULL),
(71, 2, 47, '1st Year', '1st Semester', 1, 1, 5, '2026-04-16 00:54:59', '2026-04-16 00:54:59', NULL),
(72, 1, 47, '1st Year', '1st Semester', 1, 1, 14, '2026-04-16 00:54:59', '2026-04-16 03:17:31', NULL),
(73, 1, 48, '1st Year', '1st Semester', 1, 1, 12, '2026-04-16 00:55:14', '2026-04-19 09:09:07', NULL),
(74, 2, 48, '1st Year', '1st Semester', 1, 1, 6, '2026-04-16 00:55:14', '2026-04-16 00:55:14', NULL),
(75, 1, 49, '1st Year', '1st Semester', 1, 1, 17, '2026-04-16 00:55:41', '2026-04-19 07:40:35', NULL),
(76, 2, 49, '1st Year', '1st Semester', 1, 1, 7, '2026-04-16 00:55:41', '2026-04-16 00:55:41', NULL),
(77, 1, 50, '1st Year', '1st Semester', 1, 1, 15, '2026-04-16 00:56:05', '2026-04-19 09:09:07', NULL),
(78, 2, 50, '1st Year', '1st Semester', 1, 1, 8, '2026-04-16 00:56:05', '2026-04-16 00:56:05', NULL),
(79, 1, 51, '1st Year', '1st Semester', 1, 1, 18, '2026-04-16 00:56:20', '2026-04-19 07:50:19', NULL),
(80, 2, 51, '1st Year', '1st Semester', 1, 1, 9, '2026-04-16 00:56:20', '2026-04-16 00:56:20', NULL),
(81, 2, 52, '2nd Year', '1st Semester', 1, 1, 10, '2026-04-16 00:58:58', '2026-04-16 00:58:58', NULL),
(82, 1, 52, '2nd Year', '1st Semester', 1, 1, 19, '2026-04-16 00:58:58', '2026-04-16 00:58:58', NULL),
(83, 1, 53, '2nd Year', '1st Semester', 1, 1, 20, '2026-04-16 00:59:25', '2026-04-16 00:59:25', NULL),
(84, 2, 53, '2nd Year', '1st Semester', 1, 1, 11, '2026-04-16 00:59:25', '2026-04-16 00:59:25', NULL),
(85, 1, 54, '2nd Year', '1st Semester', 1, 1, 21, '2026-04-16 01:00:10', '2026-04-16 01:00:10', NULL),
(86, 1, 55, '2nd Year', '1st Semester', 1, 1, 22, '2026-04-16 01:00:27', '2026-04-16 01:00:27', NULL),
(87, 1, 56, '2nd Year', '1st Semester', 1, 1, 23, '2026-04-16 01:00:48', '2026-04-16 01:00:48', NULL),
(88, 1, 57, '2nd Year', '1st Semester', 1, 1, 24, '2026-04-16 01:01:13', '2026-04-16 01:01:13', NULL),
(89, 1, 58, '2nd Year', '1st Semester', 1, 1, 25, '2026-04-16 01:01:33', '2026-04-16 01:01:33', NULL),
(90, 2, 59, '2nd Year', '1st Semester', 1, 1, 12, '2026-04-16 01:02:15', '2026-04-16 01:02:15', NULL),
(91, 1, 59, '2nd Year', '1st Semester', 1, 1, 27, '2026-04-16 01:02:15', '2026-04-16 03:18:38', NULL),
(92, 1, 60, '2nd Year', '1st Semester', 1, 1, 28, '2026-04-16 01:02:43', '2026-04-16 03:18:40', NULL),
(93, 2, 60, '2nd Year', '1st Semester', 1, 1, 13, '2026-04-16 01:02:43', '2026-04-16 01:02:43', NULL),
(94, 1, 61, '2nd Year', '1st Semester', 1, 1, 59, '2026-04-16 01:03:26', '2026-04-16 03:18:40', NULL),
(95, 2, 61, '2nd Year', '1st Semester', 1, 1, 14, '2026-04-16 01:03:26', '2026-04-16 01:03:26', NULL),
(96, 1, 62, '2nd Year', '2nd Semester', 1, 1, 29, '2026-04-16 01:05:57', '2026-04-16 01:05:57', NULL),
(97, 2, 62, '2nd Year', '2nd Semester', 1, 1, 15, '2026-04-16 01:05:57', '2026-04-16 01:05:57', NULL),
(98, 2, 63, '2nd Year', '2nd Semester', 1, 1, 16, '2026-04-16 01:06:25', '2026-04-16 01:06:25', NULL),
(99, 1, 63, '2nd Year', '2nd Semester', 1, 1, 30, '2026-04-16 01:06:25', '2026-04-16 01:06:25', NULL),
(102, 1, 65, '2nd Year', '2nd Semester', 1, 1, 31, '2026-04-16 01:12:18', '2026-04-16 01:12:18', NULL),
(103, 2, 65, '2nd Year', '2nd Semester', 1, 1, 17, '2026-04-16 01:12:18', '2026-04-16 01:12:18', NULL),
(104, 1, 66, '2nd Year', '2nd Semester', 1, 1, 32, '2026-04-16 01:12:50', '2026-04-16 01:12:50', NULL),
(105, 2, 66, '2nd Year', '2nd Semester', 1, 1, 18, '2026-04-16 01:12:50', '2026-04-16 01:12:50', NULL),
(106, 1, 67, '2nd Year', '2nd Semester', 1, 1, 33, '2026-04-16 01:13:18', '2026-04-16 01:13:18', NULL),
(107, 1, 68, '2nd Year', '2nd Semester', 1, 1, 34, '2026-04-16 01:13:39', '2026-04-16 01:13:39', NULL),
(108, 1, 69, '2nd Year', '2nd Semester', 1, 1, 35, '2026-04-16 01:14:01', '2026-04-16 01:14:01', NULL),
(109, 1, 70, '2nd Year', '2nd Semester', 1, 1, 36, '2026-04-16 01:14:33', '2026-04-16 01:14:33', NULL),
(110, 2, 70, '2nd Year', '2nd Semester', 1, 1, 19, '2026-04-16 01:14:33', '2026-04-16 01:14:33', NULL),
(111, 1, 71, '2nd Year', '2nd Semester', 1, 1, 37, '2026-04-16 01:14:52', '2026-04-16 01:14:52', NULL),
(112, 2, 71, '2nd Year', '2nd Semester', 1, 1, 20, '2026-04-16 01:14:52', '2026-04-16 01:14:52', NULL),
(113, 1, 72, '2nd Year', '2nd Semester', 1, 1, 38, '2026-04-16 01:15:16', '2026-04-16 01:15:16', NULL),
(114, 2, 72, '2nd Year', '2nd Semester', 1, 1, 21, '2026-04-16 01:15:16', '2026-04-16 01:15:16', NULL),
(115, 1, 73, '3rd Year', '1st Semester', 1, 1, 39, '2026-04-16 01:25:46', '2026-04-16 01:25:46', NULL),
(116, 2, 73, '3rd Year', '1st Semester', 1, 1, 22, '2026-04-16 01:25:46', '2026-04-16 01:25:46', NULL),
(117, 2, 74, '3rd Year', '1st Semester', 1, 1, 23, '2026-04-16 01:26:17', '2026-04-16 01:26:17', NULL),
(118, 1, 74, '3rd Year', '1st Semester', 1, 1, 40, '2026-04-16 01:26:17', '2026-04-16 01:26:17', NULL),
(119, 2, 75, '3rd Year', '1st Semester', 1, 1, 24, '2026-04-16 01:26:36', '2026-04-16 01:26:36', NULL),
(120, 1, 75, '3rd Year', '1st Semester', 1, 1, 41, '2026-04-16 01:26:36', '2026-04-16 01:26:36', NULL),
(121, 1, 76, '3rd Year', '1st Semester', 1, 1, 42, '2026-04-16 01:27:27', '2026-04-16 01:27:27', NULL),
(122, 2, 76, '3rd Year', '1st Semester', 1, 1, 25, '2026-04-16 01:27:27', '2026-04-16 01:27:27', NULL),
(123, 1, 77, '3rd Year', '1st Semester', 1, 1, 43, '2026-04-16 01:28:29', '2026-04-16 01:28:29', NULL),
(124, 1, 78, '3rd Year', '1st Semester', 1, 1, 44, '2026-04-16 01:28:56', '2026-04-16 01:28:56', NULL),
(125, 1, 79, '3rd Year', '1st Semester', 1, 1, 45, '2026-04-16 01:29:46', '2026-04-16 01:29:46', NULL),
(126, 2, 79, '3rd Year', '1st Semester', 1, 1, 26, '2026-04-16 01:29:46', '2026-04-16 01:29:46', NULL),
(127, 2, 80, '3rd Year', '1st Semester', 1, 1, 27, '2026-04-16 01:30:11', '2026-04-16 01:30:11', NULL),
(128, 1, 80, '3rd Year', '1st Semester', 1, 1, 46, '2026-04-16 01:30:11', '2026-04-16 01:30:11', NULL),
(129, 1, 81, '3rd Year', '2nd Semester', 1, 1, 47, '2026-04-16 01:34:06', '2026-04-16 01:34:06', NULL),
(130, 2, 81, '3rd Year', '2nd Semester', 1, 1, 28, '2026-04-16 01:34:06', '2026-04-16 01:34:06', NULL),
(131, 2, 82, '3rd Year', '2nd Semester', 1, 1, 29, '2026-04-16 01:34:25', '2026-04-16 01:34:25', NULL),
(132, 1, 82, '3rd Year', '2nd Semester', 1, 1, 48, '2026-04-16 01:34:25', '2026-04-16 01:34:25', NULL),
(133, 2, 83, '3rd Year', '2nd Semester', 1, 1, 30, '2026-04-16 01:34:55', '2026-04-16 01:34:55', NULL),
(134, 1, 83, '3rd Year', '2nd Semester', 1, 1, 49, '2026-04-16 01:34:55', '2026-04-16 01:34:55', NULL),
(135, 1, 84, '3rd Year', '2nd Semester', 1, 1, 50, '2026-04-16 01:35:26', '2026-04-16 01:35:26', NULL),
(136, 2, 84, '3rd Year', '2nd Semester', 1, 1, 31, '2026-04-16 01:35:26', '2026-04-16 01:35:26', NULL),
(137, 1, 85, '3rd Year', '2nd Semester', 1, 1, 51, '2026-04-16 01:35:50', '2026-04-16 01:35:50', NULL),
(138, 2, 85, '3rd Year', '2nd Semester', 1, 1, 32, '2026-04-16 01:35:50', '2026-04-16 01:35:50', NULL),
(139, 1, 86, '3rd Year', '2nd Semester', 1, 1, 52, '2026-04-16 01:36:23', '2026-04-16 01:36:23', NULL),
(140, 1, 87, '3rd Year', '2nd Semester', 1, 1, 53, '2026-04-16 01:37:00', '2026-04-16 01:37:00', NULL),
(141, 2, 87, '3rd Year', '2nd Semester', 1, 1, 33, '2026-04-16 01:37:00', '2026-04-16 01:37:00', NULL),
(142, 2, 88, '3rd Year', '2nd Semester', 1, 1, 34, '2026-04-16 01:37:30', '2026-04-16 01:37:30', NULL),
(143, 1, 88, '3rd Year', '2nd Semester', 1, 1, 54, '2026-04-16 01:37:30', '2026-04-16 01:37:30', NULL),
(144, 1, 89, '4th Year', '1st Semester', 1, 1, 55, '2026-04-16 01:38:47', '2026-04-16 01:38:47', NULL),
(145, 2, 89, '4th Year', '1st Semester', 1, 1, 35, '2026-04-16 01:38:47', '2026-04-16 01:38:47', NULL),
(146, 1, 90, '4th Year', '1st Semester', 1, 1, 56, '2026-04-16 01:39:19', '2026-04-16 01:39:19', NULL),
(147, 2, 90, '4th Year', '1st Semester', 1, 1, 36, '2026-04-16 01:39:19', '2026-04-16 01:39:19', NULL),
(148, 1, 91, '4th Year', '1st Semester', 1, 1, 57, '2026-04-16 01:39:53', '2026-04-16 01:39:53', NULL),
(149, 2, 91, '4th Year', '1st Semester', 1, 1, 37, '2026-04-16 01:39:53', '2026-04-16 01:39:53', NULL),
(150, 1, 92, '4th Year', '2nd Semester', 1, 1, 58, '2026-04-16 01:41:05', '2026-04-16 01:41:05', NULL),
(151, 1, 93, '2nd Year', '1st Semester', 1, 1, 26, '2026-04-16 01:43:40', '2026-04-16 03:18:32', NULL),
(153, 2, 94, '1st Year', '2nd Semester', 1, 1, 0, '2026-04-18 05:40:57', '2026-04-18 05:40:57', NULL),
(154, 3, 94, '1st Year', '2nd Semester', 1, 1, 0, '2026-04-18 05:40:57', '2026-04-18 05:40:57', NULL),
(155, 3, 95, 'Bridging', '1st Semester', 1, 1, 1, '2026-04-18 06:20:58', '2026-04-18 06:20:58', NULL),
(156, 2, 95, 'Bridging', '1st Semester', 1, 1, 38, '2026-04-18 06:20:58', '2026-04-18 06:20:58', NULL),
(158, 2, 96, 'Bridging', '1st Semester', 1, 1, 39, '2026-04-18 06:35:57', '2026-04-18 06:35:57', NULL),
(159, 1, 96, 'Bridging', '1st Semester', 1, 1, 60, '2026-04-18 06:35:57', '2026-04-18 06:35:57', NULL),
(160, 3, 96, 'Bridging', '1st Semester', 1, 1, 2, '2026-04-18 06:35:57', '2026-04-18 06:35:57', NULL),
(161, 1, 97, 'Bridging', '1st Semester', 1, 1, 61, '2026-04-18 06:49:11', '2026-04-18 06:49:11', NULL),
(162, 2, 97, 'Bridging', '1st Semester', 1, 1, 40, '2026-04-18 06:49:11', '2026-04-18 06:49:11', NULL),
(163, 3, 97, 'Bridging', '1st Semester', 1, 1, 3, '2026-04-18 06:49:11', '2026-04-18 06:49:11', NULL),
(164, 3, 98, 'Bridging', '1st Semester', 1, 1, 4, '2026-04-18 06:49:35', '2026-04-18 06:49:35', NULL),
(165, 1, 98, 'Bridging', '1st Semester', 1, 1, 62, '2026-04-18 06:49:35', '2026-04-18 06:49:35', NULL),
(166, 2, 98, 'Bridging', '1st Semester', 1, 1, 41, '2026-04-18 06:49:35', '2026-04-18 06:49:35', NULL),
(167, 2, 99, 'Bridging', '1st Semester', 1, 1, 42, '2026-04-18 06:50:00', '2026-04-18 06:50:00', NULL),
(168, 3, 99, 'Bridging', '1st Semester', 1, 1, 5, '2026-04-18 06:50:00', '2026-04-18 06:50:00', NULL),
(169, 1, 99, 'Bridging', '1st Semester', 1, 1, 63, '2026-04-18 06:50:00', '2026-04-18 06:50:00', NULL),
(170, 3, 100, 'Bridging', '1st Semester', 1, 1, 6, '2026-04-18 06:50:19', '2026-04-18 06:50:19', NULL),
(171, 1, 100, 'Bridging', '1st Semester', 1, 1, 64, '2026-04-18 06:50:19', '2026-04-18 06:50:19', NULL),
(172, 2, 100, 'Bridging', '1st Semester', 1, 1, 43, '2026-04-18 06:50:19', '2026-04-18 06:50:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mentees`
--

CREATE TABLE `mentees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by_id` int(11) DEFAULT NULL,
  `assigned_by_name` varchar(255) DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mentees`
--

INSERT INTO `mentees` (`id`, `student_id`, `first_name`, `last_name`, `email`, `mentor_id`, `created_at`, `assigned_by_id`, `assigned_by_name`, `assignment_notes`) VALUES
(15, 5, 'Tom', 'Brown', 'tom.b@student.edu', 8, '2026-04-18 08:39:05', NULL, 'Program Head', ''),
(16, 1, 'John', 'Doe', 'john.doe@student.edu', 8, '2026-04-18 08:39:05', NULL, 'Program Head', ''),
(17, 3, 'Mike', 'Johnson', 'mike.j@student.edu', 8, '2026-04-18 08:39:05', NULL, 'Program Head', ''),
(18, 7, 'nana', 'na', '2000@nbsc.du.ph', 7, '2026-04-18 08:39:19', NULL, 'Program Head', ''),
(19, 8, 'hi', 'sadas', 'dasdkj@gmail.com', 7, '2026-04-18 08:39:19', NULL, 'Program Head', ''),
(20, 4, 'Sarah', 'Williams', 'sarah.w@student.edu', 7, '2026-04-18 08:39:19', NULL, 'Program Head', '');

-- --------------------------------------------------------

--
-- Table structure for table `pending_instructors`
--

CREATE TABLE `pending_instructors` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pending_instructors`
--

INSERT INTO `pending_instructors` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `password`, `instructor_id`, `status`, `created_at`) VALUES
(3, 'Carol', 'M.', 'Williams', NULL, 'carol.williams@pending.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'approved', '2026-04-06 03:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `prerequisite_sets`
--

CREATE TABLE `prerequisite_sets` (
  `id` int(11) NOT NULL,
  `code` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `major_id` int(11) NOT NULL DEFAULT 0,
  `target_subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prerequisite_sets`
--

INSERT INTO `prerequisite_sets` (`id`, `code`, `created_at`, `major_id`, `target_subject_id`) VALUES
(6, 'RSS', '2026-04-18 07:10:03', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prerequisite_set_subjects`
--

CREATE TABLE `prerequisite_set_subjects` (
  `id` int(11) NOT NULL,
  `set_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prerequisite_set_subjects`
--

INSERT INTO `prerequisite_set_subjects` (`id`, `set_id`, `subject_id`) VALUES
(13, 6, 51),
(14, 6, 40),
(15, 6, 61),
(16, 6, 72),
(17, 6, 80),
(18, 6, 88),
(19, 6, 91);

-- --------------------------------------------------------

--
-- Table structure for table `program_heads`
--

CREATE TABLE `program_heads` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `position` varchar(100) DEFAULT 'Program Head',
  `office_location` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `program_heads`
--

INSERT INTO `program_heads` (`id`, `first_name`, `last_name`, `email`, `password`, `position`, `office_location`, `created_at`, `updated_at`) VALUES
(9, 'joshua', 'quidit', 'opop@gmail.com', '$2y$10$M4xgl87qNCwTSgckHxuZA.hX01JaG8BmmVl7PA4rkva6Nr84zZjqS', 'Program Head', NULL, '2026-04-06 08:46:39', '2026-04-06 08:46:39');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `report_description` varchar(500) DEFAULT NULL,
  `report_type` enum('pdf','excel') DEFAULT 'pdf',
  `icon_class` varchar(100) DEFAULT 'fas fa-file-pdf',
  `download_count` int(11) DEFAULT 0,
  `generated_by` varchar(50) DEFAULT 'instructor',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `report_name`, `report_description`, `report_type`, `icon_class`, `download_count`, `generated_by`, `created_at`) VALUES
(1, 'Evaluation Summary Report', 'Spring 2026 Semester', 'pdf', 'fas fa-file-pdf', 8, 'instructor', '2026-04-06 03:23:46'),
(2, 'Course Performance Report', 'All Courses - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 5, 'instructor', '2026-04-06 03:23:46'),
(3, 'Student Grades Export', 'Current Semester', 'excel', 'fas fa-file-excel', 6, 'instructor', '2026-04-06 03:23:46'),
(4, 'Feedback Analysis', 'All Courses - Comprehensive', 'pdf', 'fas fa-file-pdf', 4, 'instructor', '2026-04-06 03:23:46'),
(5, 'Department Performance Report', 'All Departments - Spring 2026', 'pdf', 'fas fa-file-pdf', 10, 'program_head', '2026-04-06 03:23:46'),
(6, 'Instructor Ranking Report', 'Top Performers - Academic Year 2025-2026', 'pdf', 'fas fa-file-pdf', 7, 'program_head', '2026-04-06 03:23:46'),
(7, 'Course Completion Report', 'Evaluation Completion Rates', 'excel', 'fas fa-file-excel', 3, 'program_head', '2026-04-06 03:23:46');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'program_head_settings', '{\"action\":\"save_settings\",\"deptName\":\"Business Management\",\"academicYear\":\"2025-2026\",\"deptDesc\":\"\",\"currentSemester\":\"1st Semester\",\"enrollmentStatus\":\"Open\",\"autoAssign\":\"false\",\"requireApproval\":\"true\",\"publicEval\":\"false\",\"emailNotif\":\"true\",\"ratingScale\":\"1-5 Stars\",\"minRating\":\"1\",\"ratingLabels\":\"Poor, Fair, Good, Very Good, Excellent\",\"minResponse\":\"75\",\"evalDeadline\":\"14\",\"allowLate\":\"no\",\"includeComments\":\"true\",\"showRankings\":\"false\",\"exportPdf\":\"true\",\"exportExcel\":\"true\",\"notifNewEval\":\"true\",\"notifReminders\":\"true\",\"notifWeekly\":\"false\",\"notifInstructor\":\"true\",\"notifEnrollment\":\"false\",\"reminderFreq\":\"Daily\",\"reminderTime\":\"8:00 AM\",\"schoolName\":\"Northern Bukidnon State College\",\"schoolAddress\":\"Manolo Fortich, Bukidnon\",\"instituteName\":\"Institute for Business Management\",\"degreeName\":\"Bachelor of Science in Business Administration\"}', '2026-04-18 10:15:35', '2026-04-18 10:15:36');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `major_id` int(11) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `avatar_initials` varchar(5) DEFAULT NULL,
  `avatar_gradient_from` varchar(20) DEFAULT '#3b82f6',
  `avatar_gradient_to` varchar(20) DEFAULT '#60a5fa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `student_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `major_id`, `year_level`, `avatar_initials`, `avatar_gradient_from`, `avatar_gradient_to`, `created_at`, `updated_at`, `student_id`) VALUES
(1, 'John', 'M.', 'Doe', NULL, 'john.doe@student.edu', 1, '3rd Year', 'JD', '#3b82f6', '#60a5fa', '2026-04-06 03:23:46', '2026-04-07 05:22:12', '2222'),
(2, 'Jane', 'A.', 'Wilson', NULL, 'jane.wilson@student.edu', 1, '2nd Year', 'JW', '#10b981', '#34d399', '2026-04-06 03:23:46', '2026-04-07 06:00:51', '09090990'),
(3, 'Mike', 'R.', 'Johnson', 'Jr.', 'mike.j@student.edu', 3, '3rd Year', 'MJ', '#8b5cf6', '#a78bfa', '2026-04-06 03:23:46', '2026-04-07 05:27:59', '34232'),
(4, 'Sarah', 'L.', 'Williams', 'Sr.', 'sarah.w@student.edu', 1, '4th Year', 'SW', '#f43f5e', '#fb7185', '2026-04-06 03:23:46', '2026-04-07 05:19:13', '202012'),
(5, 'Tom', 'B.', 'Brown', 'Sr.', 'tom.b@student.edu', 1, '2nd Year', 'TB', '#f59e0b', '#fbbf24', '2026-04-06 03:23:46', '2026-04-12 23:47:54', '202224'),
(6, 'opop', 'twewq', 'wqeqweq', 'IV', 'sdgb@gmail.com', 2, '1st Year', 'OW', '#3b82f6', '#60a5fa', '2026-04-07 05:49:21', '2026-04-07 05:49:21', '2321424'),
(7, 'nana', 'moly', 'na', NULL, '2000@nbsc.du.ph', 2, '1st Year', 'NN', '#3b82f6', '#60a5fa', '2026-04-08 04:59:24', '2026-04-08 04:59:24', '2029909'),
(8, 'hi', 'hellow', 'sadas', 'Sr.', 'dasdkj@gmail.com', 1, '1st Year', 'HS', '#3b82f6', '#60a5fa', '2026-04-15 23:43:14', '2026-04-15 23:43:14', '43423');

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `major_id` int(11) NOT NULL,
  `grade` decimal(4,2) DEFAULT NULL COMMENT '1.00 to 5.00 Philippine college scale',
  `grade_rounded` decimal(4,2) DEFAULT NULL COMMENT 'Rounded to nearest valid grade point',
  `status` enum('passed','failed','conditional','incomplete','not_taken') DEFAULT 'not_taken',
  `semester` varchar(20) DEFAULT '1st Semester',
  `year_level` varchar(20) DEFAULT '1st Year',
  `academic_year` varchar(20) DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL COMMENT 'instructor id',
  `graded_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`id`, `student_id`, `subject_id`, `major_id`, `grade`, `grade_rounded`, `status`, `semester`, `year_level`, `academic_year`, `graded_by`, `graded_at`, `remarks`, `created_at`, `updated_at`) VALUES
(43, 5, 51, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:19', '', '2026-04-19 18:23:19', '2026-04-19 18:23:19'),
(44, 5, 45, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:20', '', '2026-04-19 18:23:20', '2026-04-19 18:23:20'),
(45, 5, 49, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:22', '', '2026-04-19 18:23:22', '2026-04-19 18:23:22'),
(46, 5, 50, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:23', '', '2026-04-19 18:23:23', '2026-04-19 18:23:23'),
(47, 5, 47, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:24', '', '2026-04-19 18:23:24', '2026-04-19 18:23:24'),
(48, 5, 46, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:25', '', '2026-04-19 18:23:25', '2026-04-19 18:23:25'),
(49, 5, 48, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:26', '', '2026-04-19 18:23:26', '2026-04-19 18:23:26'),
(50, 5, 44, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:27', '', '2026-04-19 18:23:27', '2026-04-19 18:23:27'),
(51, 5, 43, 1, 2.00, 2.00, 'passed', '1st Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:23:28', '', '2026-04-19 18:23:28', '2026-04-19 18:23:28'),
(52, 5, 32, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:06', '', '2026-04-19 18:25:06', '2026-04-19 18:25:06'),
(53, 5, 33, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:08', '', '2026-04-19 18:25:08', '2026-04-19 18:25:08'),
(54, 5, 34, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:09', '', '2026-04-19 18:25:09', '2026-04-19 18:25:09'),
(55, 5, 35, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:10', '', '2026-04-19 18:25:10', '2026-04-19 18:25:10'),
(56, 5, 36, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:11', '', '2026-04-19 18:25:11', '2026-04-19 18:25:11'),
(57, 5, 37, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:12', '', '2026-04-19 18:25:12', '2026-04-19 18:25:12'),
(58, 5, 38, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:33', '', '2026-04-19 18:25:14', '2026-04-19 18:25:33'),
(59, 5, 39, 1, 2.00, 2.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:25:15', '', '2026-04-19 18:25:15', '2026-04-19 18:25:15'),
(60, 5, 40, 1, 3.00, 3.00, 'passed', '2nd Semester', '1st Year', '2025-2026', 8, '2026-04-19 18:35:53', '', '2026-04-19 18:25:16', '2026-04-19 18:35:53'),
(67, 5, 61, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:11', '', '2026-04-19 18:37:11', '2026-04-19 18:37:11'),
(68, 5, 60, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:12', '', '2026-04-19 18:37:12', '2026-04-19 18:37:12'),
(69, 5, 59, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:13', '', '2026-04-19 18:37:13', '2026-04-19 18:37:13'),
(70, 5, 93, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:14', '', '2026-04-19 18:37:14', '2026-04-19 18:37:14'),
(71, 5, 57, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:16', '', '2026-04-19 18:37:16', '2026-04-19 18:37:16'),
(72, 5, 56, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:18', '', '2026-04-19 18:37:18', '2026-04-19 18:37:18'),
(73, 5, 55, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:19', '', '2026-04-19 18:37:19', '2026-04-19 18:37:19'),
(74, 5, 54, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:20', '', '2026-04-19 18:37:20', '2026-04-19 18:37:20'),
(75, 5, 53, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:21', '', '2026-04-19 18:37:21', '2026-04-19 18:37:21'),
(76, 5, 52, 1, 1.00, 1.00, 'passed', '1st Semester', '2nd Year', '2025-2026', 8, '2026-04-19 18:37:23', '', '2026-04-19 18:37:23', '2026-04-19 18:37:23'),
(77, 5, 62, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:33', '', '2026-04-20 02:27:33', '2026-04-20 02:27:33'),
(78, 5, 63, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:34', '', '2026-04-20 02:27:34', '2026-04-20 02:27:34'),
(79, 5, 65, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:35', '', '2026-04-20 02:27:35', '2026-04-20 02:27:35'),
(80, 5, 66, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:36', '', '2026-04-20 02:27:36', '2026-04-20 02:27:36'),
(81, 5, 67, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:37', '', '2026-04-20 02:27:37', '2026-04-20 02:27:37'),
(82, 5, 68, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:39', '', '2026-04-20 02:27:39', '2026-04-20 02:27:39'),
(83, 5, 69, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:40', '', '2026-04-20 02:27:40', '2026-04-20 02:27:40'),
(84, 5, 70, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:41', '', '2026-04-20 02:27:41', '2026-04-20 02:27:41'),
(85, 5, 71, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:41', '', '2026-04-20 02:27:41', '2026-04-20 02:27:41'),
(87, 5, 72, 1, 2.00, 2.00, 'passed', '2nd Semester', '2nd Year', '2025-2026', 8, '2026-04-20 02:27:43', '', '2026-04-20 02:27:43', '2026-04-20 02:27:43'),
(88, 5, 73, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:39', '', '2026-04-20 02:28:39', '2026-04-20 02:28:39'),
(89, 5, 74, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:39', '', '2026-04-20 02:28:39', '2026-04-20 02:28:39'),
(90, 5, 75, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:40', '', '2026-04-20 02:28:40', '2026-04-20 02:28:40'),
(91, 5, 76, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:41', '', '2026-04-20 02:28:41', '2026-04-20 02:28:41'),
(92, 5, 77, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:43', '', '2026-04-20 02:28:43', '2026-04-20 02:28:43'),
(93, 5, 78, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:44', '', '2026-04-20 02:28:44', '2026-04-20 02:28:44'),
(94, 5, 79, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:44', '', '2026-04-20 02:28:44', '2026-04-20 02:28:44'),
(95, 5, 80, 1, 2.00, 2.00, 'passed', '1st Semester', '3rd Year', '2025-2026', 8, '2026-04-20 02:28:45', '', '2026-04-20 02:28:45', '2026-04-20 02:28:45');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `units` decimal(3,1) DEFAULT 3.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `default_year_level` varchar(20) DEFAULT '1st Year',
  `semester` varchar(20) DEFAULT '1st Semester',
  `prerequisite` varchar(255) DEFAULT NULL,
  `bridging_for` varchar(100) DEFAULT NULL,
  `lecture_hours` int(11) DEFAULT 2,
  `lab_hours` int(11) DEFAULT 0,
  `credit_type` varchar(20) DEFAULT 'lec',
  `default_semester` varchar(20) DEFAULT '1st Semester',
  `prerequisite_subject_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `units`, `created_at`, `updated_at`, `default_year_level`, `semester`, `prerequisite`, `bridging_for`, `lecture_hours`, `lab_hours`, `credit_type`, `default_semester`, `prerequisite_subject_code`) VALUES
(32, 'NSTP 2', 'Nat\'l Serv. Trng. Program', 3.0, '2026-04-16 00:37:02', '2026-04-16 00:37:02', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(33, 'PATH FIT 2', 'FITNESS TRAINING', 2.0, '2026-04-16 00:37:56', '2026-04-16 00:37:56', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(34, 'GE 2', 'READING IN THE PHILIPINES HISTORY', 3.0, '2026-04-16 00:38:37', '2026-04-16 00:38:37', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(35, 'BA Core 5', 'HUMAN RESOURCES MANAGEMENT', 3.0, '2026-04-16 00:40:09', '2026-04-16 00:40:09', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(36, 'BA Core 6', 'INTERNATIONAL BUSINESS AND TRADE', 3.0, '2026-04-16 00:41:30', '2026-04-16 00:41:30', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(37, 'TAX 2', 'BUSINESS TAX', 3.0, '2026-04-16 00:42:09', '2026-04-16 00:42:09', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(38, 'LAW 2', 'INSTRUMENTS', 3.0, '2026-04-16 00:42:43', '2026-04-16 00:42:43', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(39, 'CCS 2', 'PERSONAL DEVELOPMENT', 3.0, '2026-04-16 00:44:48', '2026-04-16 00:44:48', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(40, 'RSS 2', 'RETURN SERVICE SYTEM 2', 1.0, '2026-04-16 00:45:21', '2026-04-16 01:04:32', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(43, 'NSTP 1', 'Nat\'l Serv. Trng. Program', 3.0, '2026-04-16 00:54:01', '2026-04-16 00:54:01', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(44, 'PATH FIT 1', 'Movement  Competency', 2.0, '2026-04-16 00:54:17', '2026-04-16 00:54:17', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(45, 'GE 1', 'Understanding the Self', 3.0, '2026-04-16 00:54:32', '2026-04-16 00:54:32', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(46, 'BA Core 1', 'Basic Microeconomics(Eco)', 3.0, '2026-04-16 00:54:46', '2026-04-16 00:54:46', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(47, 'BA Core 2', 'Business Law (Obligations & Contracts)', 3.0, '2026-04-16 00:54:59', '2026-04-16 00:54:59', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(48, 'BA Core 3', 'Income Taxation', 3.0, '2026-04-16 00:55:14', '2026-04-16 00:55:14', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(49, 'BA Core 4', 'Good Governance & Social Responsibility', 3.0, '2026-04-16 00:55:41', '2026-04-16 00:55:41', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(50, 'CCS 1', 'Introduction to Collagiate Education', 3.0, '2026-04-16 00:56:05', '2026-04-16 00:56:05', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(51, 'RSS 1', 'Return Service System 1', 1.0, '2026-04-16 00:56:20', '2026-04-16 03:17:49', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(52, 'PATH FIT 3', 'DANCE', 2.0, '2026-04-16 00:58:58', '2026-04-16 00:58:58', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(53, 'GE 3', 'CONTEMPORARY WORD', 3.0, '2026-04-16 00:59:25', '2026-04-16 00:59:25', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(54, 'OM1', 'SPECIAL TOPICS IN OPERATION MANAGEMENT', 3.0, '2026-04-16 01:00:10', '2026-04-16 01:00:10', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(55, 'OM 2', 'PROJECT MANAGEMENT', 3.0, '2026-04-16 01:00:27', '2026-04-16 01:00:27', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(56, 'OM 3', 'FACILITIES MANAGEMENT', 3.0, '2026-04-16 01:00:48', '2026-04-16 01:00:48', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(57, 'OM 4', 'LOGISTICS MANAGEMENT', 3.0, '2026-04-16 01:01:13', '2026-04-16 01:01:13', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(59, 'ACCTG 2', 'ADVANCE ACCOUNTING', 3.0, '2026-04-16 01:02:15', '2026-04-16 01:02:15', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(60, 'CSS 3', 'CRITICAL LITERACY', 3.0, '2026-04-16 01:02:43', '2026-04-16 01:02:43', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(61, 'RSS 3', 'RETURN SERVICE SYSTEM 3', 1.0, '2026-04-16 01:03:26', '2026-04-16 01:03:26', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(62, 'PATH FIT 4', 'SPORTS', 2.0, '2026-04-16 01:05:57', '2026-04-16 01:05:57', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(63, 'GE 4', 'ARTS APPRECIATION', 3.0, '2026-04-16 01:06:25', '2026-04-16 01:06:25', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(65, 'GE 5', 'MATHEMATICS IN THE MODERN WORLD', 3.0, '2026-04-16 01:12:18', '2026-04-16 01:12:18', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(66, 'GE 6', 'ETHICS', 3.0, '2026-04-16 01:12:50', '2026-04-16 01:12:50', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(67, 'OM 6', 'PRODUCTIVITY AND QUALITY TOOLS', 3.0, '2026-04-16 01:13:18', '2026-04-16 01:13:18', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(68, 'OM 7', 'COSTING AND PRICING', 3.0, '2026-04-16 01:13:39', '2026-04-16 01:13:39', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(69, 'OM 8', 'ENVENTORY MANAGEMENT & CONTROL', 3.0, '2026-04-16 01:14:01', '2026-04-16 01:14:01', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(70, 'ELECTIVE 1 OM', 'MANAGERIAL ACCOUNTING', 3.0, '2026-04-16 01:14:33', '2026-04-16 01:14:33', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(71, 'CSS 4', 'ENVIRONMENTAL SCIENCE', 3.0, '2026-04-16 01:14:52', '2026-04-16 01:14:52', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(72, 'RSS 4', 'RETURN SERVICE SYSTEM 4', 1.0, '2026-04-16 01:15:16', '2026-04-16 01:15:16', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(73, 'GE 7', 'PURPOSIVE COMMUNICATION', 2.0, '2026-04-16 01:25:46', '2026-04-16 01:25:46', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(74, 'GE 8', 'SCIENCE, TECHNOLOGY & SOCIETY', 3.0, '2026-04-16 01:26:17', '2026-04-16 01:26:17', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(75, 'GE 9', 'LIFE, WORKS & WRITINGS OF RIZAL', 3.0, '2026-04-16 01:26:36', '2026-04-16 01:26:36', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(76, 'CBMEC 1', 'STRATEGIC MANAGEMENT', 3.0, '2026-04-16 01:27:27', '2026-04-16 01:27:27', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(77, 'ELECTIVE 2 OM', 'GLOBAL/INTERNALTIONAL TRADE', 3.0, '2026-04-16 01:28:29', '2026-04-16 01:28:29', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(78, 'ELECTIVE 3 OM', 'FINANCIAL MANAGEMENT', 3.0, '2026-04-16 01:28:56', '2026-04-16 01:28:56', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(79, 'CCS 5', 'LIVING IN THE IT ERA', 3.0, '2026-04-16 01:29:46', '2026-04-16 01:29:46', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(80, 'RSS 5', 'RETURN SERVICE SYSTEM 5', 1.0, '2026-04-16 01:30:11', '2026-04-16 01:30:11', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(81, 'GE 10', 'GENDER AND SOCIETY', 3.0, '2026-04-16 01:34:06', '2026-04-16 01:34:06', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(82, 'GE 11', 'PHIL INDIGENOUS COMMUNITIES& PEACE STUDIES', 3.0, '2026-04-16 01:34:25', '2026-04-16 01:34:25', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(83, 'GE 12', 'PEOPLE AND THE EARTH ECOSYSTEM', 3.0, '2026-04-16 01:34:55', '2026-04-16 01:34:55', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(84, 'CBMEC 2', 'OPERATIONS MANAGEMENT', 3.0, '2026-04-16 01:35:26', '2026-04-16 01:35:26', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(85, 'BA CORE 7', 'BUSINESS RESEARCH', 3.0, '2026-04-16 01:35:50', '2026-04-16 01:35:50', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(86, 'ELECTIVE 4 OM', 'MARKETTING MANAGEMENT', 3.0, '2026-04-16 01:36:23', '2026-04-16 01:36:23', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(87, 'CCS 6', 'INDIGENOUS CREATIVE CRAFTS', 3.0, '2026-04-16 01:37:00', '2026-04-16 01:37:00', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(88, 'RSS 6', 'RETURN SERVICE SYSTEM 6', 1.0, '2026-04-16 01:37:30', '2026-04-16 01:37:30', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(89, 'BA CORE 8', 'THESIS OR FEASIBILITY STUDY', 3.0, '2026-04-16 01:38:47', '2026-04-16 01:38:47', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(90, 'ELECTIVE 5 OM', 'MANAGEMENT INFORMATION SYSTEM', 3.0, '2026-04-16 01:39:19', '2026-04-16 01:39:19', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(91, 'RSS 7', 'RETURN SERVICE SYSTEM', 1.0, '2026-04-16 01:39:53', '2026-04-16 01:39:53', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(92, 'PRAC', 'PRACTICUM/ WORK INTEGRATED', 6.0, '2026-04-16 01:41:05', '2026-04-16 01:41:05', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(93, 'OM 5', 'INVENTORY MANAGEMENT & CONTROL', 3.0, '2026-04-16 01:43:40', '2026-04-16 01:43:40', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(94, 'asds', 'asdasd', 3.0, '2026-04-18 05:40:57', '2026-04-18 05:40:57', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(95, 'sdsa', 'dasdsadsa', 3.0, '2026-04-18 06:20:58', '2026-04-18 06:20:58', '1st Year', '1st Semester', '', NULL, 2, 0, 'lec', '1st Semester', NULL),
(96, 'ACCTG 1', 'FUNDAMENTALS OF ACCOUNTING', 3.0, '2026-04-18 06:35:57', '2026-04-18 06:35:57', 'Bridging', '1st Semester', '', 'SHS NON ABM', 2, 0, 'lec', '1st Semester', NULL),
(97, 'MKTG 1', 'PRINCIPLES OF MARKETING', 3.0, '2026-04-18 06:49:11', '2026-04-18 06:49:11', 'Bridging', '1st Semester', '', 'SHS NON ABM', 2, 0, 'lec', '1st Semester', NULL),
(98, 'MNGT 1', 'PRINCIPLES OF MANAGEMENT', 3.0, '2026-04-18 06:49:35', '2026-04-18 06:49:35', 'Bridging', '1st Semester', '', 'SHS NON ABM', 2, 0, 'lec', '1st Semester', NULL),
(99, 'ENG 1', 'STUDY AND THINKING SKILLS', 3.0, '2026-04-18 06:50:00', '2026-04-18 06:50:00', 'Bridging', '1st Semester', '', '', 2, 0, 'lec', '1st Semester', NULL),
(100, 'MATH 1', 'COLLEGE ALGEBRA', 3.0, '2026-04-18 06:50:19', '2026-04-18 06:50:19', 'Bridging', '1st Semester', '', '', 2, 0, 'lec', '1st Semester', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_advisements`
--

CREATE TABLE `subject_advisements` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `recommended_year_level` varchar(20) DEFAULT NULL,
  `recommended_semester` varchar(20) DEFAULT NULL,
  `reason` text DEFAULT NULL COMMENT 'why recommended or blocked',
  `status` enum('recommended','blocked','conditional') DEFAULT 'recommended',
  `session_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `instructor_id`, `priority`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(8, 'ddqew', 'sadasd', 8, 'medium', NULL, 'active', '2026-04-18 12:44:56', '2026-04-18 12:44:56'),
(9, 'sadasadas', 'dasd', 8, 'high', NULL, 'active', '2026-04-18 14:29:42', '2026-04-18 14:29:42'),
(10, 'awd', 'awdawd', 8, 'medium', NULL, 'active', '2026-04-18 14:30:22', '2026-04-18 14:30:22'),
(11, 'sada', 'dasdsad', 8, 'low', NULL, 'active', '2026-04-18 15:12:24', '2026-04-18 15:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `completion_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_assignments`
--

INSERT INTO `task_assignments` (`id`, `task_id`, `mentee_id`, `assigned_at`, `status`, `completion_date`, `notes`) VALUES
(11, 8, 15, '2026-04-18 12:44:56', 'completed', '2026-04-18', NULL),
(12, 9, 15, '2026-04-18 14:29:42', 'completed', '2026-04-18', NULL),
(13, 10, 15, '2026-04-18 14:30:22', 'completed', '2026-04-18', NULL),
(14, 10, 16, '2026-04-18 14:30:22', 'pending', NULL, NULL),
(15, 11, 15, '2026-04-18 15:12:24', 'completed', '2026-04-18', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `admin_promotions`
--
ALTER TABLE `admin_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`);

--
-- Indexes for table `evaluation_sessions`
--
ALTER TABLE `evaluation_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_session_status` (`session_status`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_major_name` (`major_name`),
  ADD KEY `idx_major_name` (`major_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `major_subjects`
--
ALTER TABLE `major_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_major_subject` (`major_id`,`subject_id`);

--
-- Indexes for table `mentees`
--
ALTER TABLE `mentees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mentor_id` (`mentor_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `pending_instructors`
--
ALTER TABLE `pending_instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `prerequisite_sets`
--
ALTER TABLE `prerequisite_sets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `prerequisite_set_subjects`
--
ALTER TABLE `prerequisite_set_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `set_id` (`set_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `program_heads`
--
ALTER TABLE `program_heads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generated_by` (`generated_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_major_id` (`major_id`),
  ADD KEY `idx_year_level` (`year_level`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_subject_year_sem` (`student_id`,`subject_id`,`academic_year`,`semester`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_major_id` (`major_id`),
  ADD KEY `idx_graded_by` (`graded_by`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_subject_code` (`subject_code`);

--
-- Indexes for table `subject_advisements`
--
ALTER TABLE `subject_advisements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_subject_id` (`subject_id`),
  ADD KEY `idx_session_id` (`session_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_task_mentee` (`task_id`,`mentee_id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_mentee_id` (`mentee_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_promotions`
--
ALTER TABLE `admin_promotions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `evaluation_sessions`
--
ALTER TABLE `evaluation_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `major_subjects`
--
ALTER TABLE `major_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `mentees`
--
ALTER TABLE `mentees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pending_instructors`
--
ALTER TABLE `pending_instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `prerequisite_sets`
--
ALTER TABLE `prerequisite_sets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `prerequisite_set_subjects`
--
ALTER TABLE `prerequisite_set_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `program_heads`
--
ALTER TABLE `program_heads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `subject_advisements`
--
ALTER TABLE `subject_advisements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_promotions`
--
ALTER TABLE `admin_promotions`
  ADD CONSTRAINT `admin_promotions_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluation_sessions`
--
ALTER TABLE `evaluation_sessions`
  ADD CONSTRAINT `es_instructor_fk` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `es_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mentees`
--
ALTER TABLE `mentees`
  ADD CONSTRAINT `mentees_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mentees_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pending_instructors`
--
ALTER TABLE `pending_instructors`
  ADD CONSTRAINT `pending_instructors_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `prerequisite_set_subjects`
--
ALTER TABLE `prerequisite_set_subjects`
  ADD CONSTRAINT `prerequisite_set_subjects_ibfk_1` FOREIGN KEY (`set_id`) REFERENCES `prerequisite_sets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prerequisite_set_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `sg_major_fk` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sg_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sg_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `subject_advisements`
--
ALTER TABLE `subject_advisements`
  ADD CONSTRAINT `sa_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sa_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `mentees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
