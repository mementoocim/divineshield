-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 06:21 AM
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
-- Database: `divineshield_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','staff','church_leader') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `feeding_program_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `status` enum('present','absent','excused') NOT NULL,
  `logged_via` enum('manual','rfid') DEFAULT 'manual',
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `feeding_program_id`, `child_id`, `status`, `logged_via`, `logged_at`) VALUES
(1, 1, 1, 'present', 'manual', '2026-06-16 17:53:07'),
(2, 2, 2, 'present', 'manual', '2026-06-17 02:51:51');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 5, 'USER_REGISTER', 'Pastor El Nicos Lumayno registered church site: Saint Nicos (Pending approval)', '::1', '2026-06-16 12:42:40'),
(2, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:43:33'),
(3, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:43:38'),
(4, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:44:53'),
(5, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:44:57'),
(6, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:45:07'),
(7, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:45:14'),
(8, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:45:50'),
(9, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:45:54'),
(10, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 12:46:02'),
(11, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 12:46:56'),
(12, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 12:47:52'),
(13, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 12:52:33'),
(14, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 12:52:38'),
(15, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 12:52:45'),
(16, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 12:52:48'),
(17, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 13:49:17'),
(18, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 13:49:20'),
(19, 6, 'USER_REGISTER', 'Pastor Rina wax registered church site: Saint Rina (Pending approval)', '::1', '2026-06-16 13:53:39'),
(20, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 13:53:48'),
(21, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 13:53:51'),
(22, 1, 'ACCOUNT_DEACTIVATED', 'Rejected and disabled church leader: @rina123 (ID: 6)', '::1', '2026-06-16 14:02:56'),
(23, 1, 'ACCOUNT_ACTIVATED', 'Manually approved and activated church leader: @nicos1 (ID: 5)', '::1', '2026-06-16 14:13:45'),
(24, 5, 'LOGIN_FAILED', 'Failed login attempt for username: nicos1', '::1', '2026-06-16 14:13:56'),
(25, 5, 'LOGIN_FAILED', 'Failed login attempt for username: nicos1', '::1', '2026-06-16 14:14:02'),
(26, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 14:14:49'),
(27, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 14:14:51'),
(28, 1, 'ACCOUNT_ACTIVATED', 'Manually approved and activated church leader: @rina123 (ID: 6)', '::1', '2026-06-16 14:14:55'),
(29, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-16 14:15:16'),
(30, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 14:19:07'),
(31, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 14:19:11'),
(32, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '192.168.1.41', '2026-06-16 14:19:47'),
(33, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '192.168.1.41', '2026-06-16 14:19:51'),
(34, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 14:20:11'),
(35, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 14:20:13'),
(36, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 14:28:08'),
(37, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 14:28:10'),
(38, 1, 'ADMIN_AVATAR_UPLOADED', 'Administrator uploaded a new profile image.', '::1', '2026-06-16 14:29:19'),
(39, 1, 'ADMIN_AVATAR_UPLOADED', 'Administrator uploaded a new profile image.', '::1', '2026-06-16 14:29:57'),
(40, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 15:10:45'),
(41, 1, 'LOGIN_FAILED', 'Failed login attempt for username: admin', '::1', '2026-06-16 15:15:59'),
(42, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 15:16:02'),
(43, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 15:16:04'),
(44, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-16 15:23:53'),
(45, 6, 'CHILD_SUBMITTED', 'Pastor submitted beneficiary request: Nicos Lumayno (ID: 1) for Site ID: 3', '::1', '2026-06-16 15:25:00'),
(46, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 15:25:10'),
(47, 2, 'Approve Submission', 'Approved submission ID 1 and registered child ID 1', '::1', '2026-06-16 15:27:08'),
(48, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-16 16:32:08'),
(49, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 16:40:07'),
(50, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 16:40:10'),
(51, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 16:41:26'),
(52, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-16 16:45:23'),
(53, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 16:56:10'),
(54, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 16:56:12'),
(55, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 16:56:23'),
(56, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-16 16:56:34'),
(57, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 16:56:47'),
(58, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 16:56:49'),
(59, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 16:58:57'),
(60, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 16:59:09'),
(61, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 16:59:12'),
(62, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 17:00:37'),
(63, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 17:11:22'),
(64, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 17:15:07'),
(65, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 17:15:09'),
(66, 1, 'ACCOUNT_DEACTIVATED', 'Rejected and disabled church leader: @pastor_pedro (ID: 4)', '::1', '2026-06-16 17:18:45'),
(67, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 17:41:30'),
(68, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 17:41:31'),
(69, 1, 'FEEDING_PROGRAM_SCHEDULED', 'Scheduled new feeding program \'sdadsadsa\' (ID: 1) for Church Site ID: 3', '::1', '2026-06-16 17:46:59'),
(70, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-16 17:51:27'),
(71, 1, 'ATTENDANCE_RECORDED', 'Recorded/updated manual attendance list for program: \'sdadsadsa\' (ID: 1)', '::1', '2026-06-16 17:53:07'),
(72, 2, 'ATTENDANCE_RECORDED', 'Staff recorded/updated manual attendance for program: \'sdadsadsa\' (ID: 1)', '::1', '2026-06-16 17:59:21'),
(73, 7, 'USER_REGISTER', 'Pastor asdsa dasd registered church site: asdsadsad (Pending approval)', '::1', '2026-06-16 18:14:27'),
(74, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-16 18:14:30'),
(75, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-16 18:14:32'),
(76, 1, 'ACCOUNT_ACTIVATED', 'Manually approved and activated church leader: @asd123 (ID: 7)', '::1', '2026-06-16 18:17:01'),
(77, 8, 'USER_REGISTER', 'Pastor Kim Maramag registered church site: Kim Gospel Church (Pending approval)', '::1', '2026-06-17 02:04:02'),
(78, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:04:16'),
(79, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:04:18'),
(80, 1, 'ACCOUNT_ACTIVATED', 'Manually approved and activated church leader: @kim123 (ID: 8)', '::1', '2026-06-17 02:05:00'),
(81, 8, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-17 02:06:54'),
(82, 8, 'CHILD_SUBMITTED', 'Pastor submitted beneficiary request: Juan three (ID: 2) for Site ID: 5', '::1', '2026-06-17 02:09:47'),
(83, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:09:55'),
(84, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:10:00'),
(85, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:10:48'),
(86, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:10:50'),
(87, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:12:17'),
(88, 2, 'Approve Submission', 'Approved submission ID 2 and registered child ID 2', '::1', '2026-06-17 02:12:26'),
(89, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:13:13'),
(90, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:13:14'),
(91, 1, 'FEEDING_PROGRAM_SCHEDULED', 'Scheduled new feeding program \'Testing\' (ID: 2) for Church Site ID: 5', '::1', '2026-06-17 02:14:27'),
(92, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:21:00'),
(93, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:22:54'),
(94, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:22:57'),
(95, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-17 02:23:46'),
(96, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:24:23'),
(97, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:24:26'),
(98, 6, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-17 02:26:30'),
(99, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:26:37'),
(100, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:28:12'),
(101, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:28:14'),
(102, 1, 'ADMIN_AVATAR_UPLOADED', 'Administrator uploaded a new profile image.', '::1', '2026-06-17 02:29:06'),
(103, NULL, 'LOGIN_FAILED', 'Failed login attempt for username: aawdadw', '192.168.1.41', '2026-06-17 02:34:20'),
(104, NULL, 'LOGIN_FAILED', 'Failed login attempt for username: awdawdawdawd', '192.168.1.41', '2026-06-17 02:34:30'),
(105, NULL, 'LOGIN_FAILED', 'Failed login attempt for username: ahahwd', '192.168.1.41', '2026-06-17 02:34:39'),
(106, NULL, 'LOGIN_FAILED', 'Failed login attempt for username: try@aw', '192.168.1.41', '2026-06-17 02:34:44'),
(107, NULL, 'LOGIN_FAILED', 'Failed login attempt for username: chat ai', '192.168.1.41', '2026-06-17 02:34:51'),
(108, 1, 'STAFF_STATUS_TOGGLED', 'Changed account status of staff encoder @encoder1 to \'inactive\'', '::1', '2026-06-17 02:37:29'),
(109, 2, 'LOGIN_FAILED', 'Failed login attempt for username: encoder1', '192.168.1.41', '2026-06-17 02:37:41'),
(110, 2, 'LOGIN_BLOCKED', 'Login attempt blocked: account status inactive', '192.168.1.41', '2026-06-17 02:37:46'),
(111, 2, 'LOGIN_FAILED', 'Failed login attempt for username: encoder1', '192.168.1.41', '2026-06-17 02:39:05'),
(112, 2, 'LOGIN_BLOCKED', 'Login attempt blocked: account status inactive', '192.168.1.41', '2026-06-17 02:39:09'),
(113, 2, 'LOGIN_BLOCKED', 'Login attempt blocked: account status inactive', '::1', '2026-06-17 02:44:14'),
(114, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:44:18'),
(115, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:44:20'),
(116, 1, 'STAFF_STATUS_TOGGLED', 'Changed account status of staff encoder @encoder1 to \'active\'', '::1', '2026-06-17 02:44:24'),
(117, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:44:30'),
(118, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 02:46:14'),
(119, 8, 'LOGIN_SUCCESS', 'User logged in with role: church_leader', '::1', '2026-06-17 02:47:44'),
(120, 8, 'RECORD_BMI', 'Pastor recorded nutritional assessment for child: Juan three (BMI: 15.42, Status: Underweight)', '::1', '2026-06-17 02:48:09'),
(121, 8, 'RECORD_BMI', 'Pastor recorded nutritional assessment for child: Juan three (BMI: 20.66, Status: Normal Weight)', '::1', '2026-06-17 02:48:41'),
(122, 8, 'ATTENDANCE_RECORDED', 'Pastor recorded/updated manual attendance for program: \'Testing\' (ID: 2)', '::1', '2026-06-17 02:51:51'),
(123, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '::1', '2026-06-17 02:52:12'),
(124, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '::1', '2026-06-17 02:52:14'),
(125, 1, 'LOGIN_STEP1_SUCCESS', 'Credentials correct. Displaying Admin PIN verification screen.', '192.168.1.41', '2026-06-17 02:54:58'),
(126, 1, 'LOGIN_SUCCESS', 'Administrator logged in successfully via Two-Step MFA', '192.168.1.41', '2026-06-17 02:55:03'),
(127, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:23'),
(128, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:28'),
(129, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:29'),
(130, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:29'),
(131, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:29'),
(132, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:58:30'),
(133, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 02:59:49'),
(134, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:00:09'),
(135, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:03:03'),
(136, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:03:07'),
(137, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:04:54'),
(138, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:05:38'),
(139, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:05:40'),
(140, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:06:42'),
(141, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:10:27'),
(142, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:11:46'),
(143, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '192.168.1.60', '2026-06-17 03:11:59'),
(144, 2, 'STAFF_CHECK_IN', 'Staff member @encoder1 checked in successfully via QR code.', '192.168.1.60', '2026-06-17 03:11:59'),
(145, 2, 'LOGIN_SUCCESS', 'User logged in with role: staff', '192.168.1.60', '2026-06-17 03:13:03'),
(146, 9, 'LOGIN_FAILED', 'Failed login attempt for username: encoder2', '::1', '2026-06-17 03:15:52'),
(147, 9, 'LOGIN_FAILED', 'Failed login attempt for username: encoder2', '::1', '2026-06-17 03:16:24'),
(148, 9, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 03:17:58'),
(149, 9, 'LOGIN_SUCCESS', 'User logged in with role: staff', '192.168.1.36', '2026-06-17 03:18:57'),
(150, 9, 'STAFF_CHECK_IN', 'Staff member @encoder2 checked in successfully via QR code.', '192.168.1.36', '2026-06-17 03:18:57'),
(151, 1, 'QR_ATTENDANCE_RENEWED', 'Administrator generated/renewed the active staff check-in QR code.', '::1', '2026-06-17 03:21:48'),
(152, 9, 'LOGIN_SUCCESS', 'User logged in with role: staff', '192.168.1.36', '2026-06-17 03:22:12'),
(153, 9, 'LOGIN_SUCCESS', 'User logged in with role: staff', '::1', '2026-06-17 03:22:36');

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) DEFAULT NULL,
  `church_site_id` int(11) NOT NULL,
  `rfid_tag` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `status` enum('active','graduated','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `submission_id`, `church_site_id`, `rfid_tag`, `first_name`, `last_name`, `middle_name`, `gender`, `birthdate`, `guardian_name`, `status`, `created_at`) VALUES
(1, 1, 3, NULL, 'Nicos', 'Lumayno', 'Katok', 'male', '2015-11-27', 'Maria Clara', 'active', '2026-06-16 15:27:08'),
(2, 2, 5, NULL, 'Juan', 'three', 'two', 'male', '2015-10-17', 'Kimberly Maramag', 'active', '2026-06-17 02:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `children_submissions`
--

CREATE TABLE `children_submissions` (
  `id` int(11) NOT NULL,
  `church_site_id` int(11) NOT NULL,
  `church_leader_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `birthdate` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `guardian_name` varchar(100) NOT NULL,
  `guardian_relationship` varchar(50) NOT NULL,
  `initial_weight` decimal(5,2) NOT NULL,
  `initial_height` decimal(5,2) NOT NULL,
  `initial_bmi` decimal(4,2) NOT NULL,
  `initial_bmi_status` varchar(50) NOT NULL,
  `suggested_status` enum('qualified','disqualified') NOT NULL,
  `submission_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `review_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `children_submissions`
--

INSERT INTO `children_submissions` (`id`, `church_site_id`, `church_leader_id`, `first_name`, `last_name`, `middle_name`, `gender`, `birthdate`, `guardian_name`, `guardian_relationship`, `initial_weight`, `initial_height`, `initial_bmi`, `initial_bmi_status`, `suggested_status`, `submission_status`, `review_notes`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(1, 3, 6, 'Nicos', 'Lumayno', 'Katok', 'male', '2015-11-27', 'Maria Clara', 'Mother', 10.00, 110.00, 8.26, 'Severely Underweight', 'qualified', 'approved', NULL, 2, '2026-06-16 15:27:08', '2026-06-16 15:25:00'),
(2, 5, 8, 'Juan', 'three', 'two', 'male', '2015-10-17', 'Kimberly Maramag', 'Mother earth', 10.00, 110.00, 8.26, 'Severely Underweight', 'qualified', 'approved', NULL, 2, '2026-06-17 02:12:26', '2026-06-17 02:09:47');

-- --------------------------------------------------------

--
-- Table structure for table `church_sites`
--

CREATE TABLE `church_sites` (
  `id` int(11) NOT NULL,
  `church_leader_id` int(11) NOT NULL,
  `church_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `region` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city_municipality` varchar(100) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `church_sites`
--

INSERT INTO `church_sites` (`id`, `church_leader_id`, `church_name`, `address`, `region`, `province`, `city_municipality`, `barangay`, `contact_number`, `created_at`) VALUES
(1, 3, 'Grace Born-Again Church', '123 Salvation St.', 'Metro Manila', 'Metro Manila', 'Quezon City', 'Batasan Hills', '09191112222', '2026-06-16 11:59:21'),
(2, 5, 'Saint Nicos', 'Alibagu, City of Ilagan, Cagayan Valley', 'Cagayan Valley', 'City of Ilagan', 'City of Ilagan', 'Alibagu', '09611351290', '2026-06-16 12:42:40'),
(3, 6, 'Saint Rina', 'Allinguigan 2nd, City of Ilagan, Cagayan Valley', 'Cagayan Valley', 'City of Ilagan', 'City of Ilagan', 'Allinguigan 2nd', '0912345678', '2026-06-16 13:53:39'),
(4, 7, 'asdsadsad', 'asdsadasd', 'SOCCSKSARGEN', 'asdsad', 'Esperanza', 'Numo', '9090909090', '2026-06-16 18:14:27'),
(5, 8, 'Kim Gospel Church', 'Maramag Residence', 'Cagayan Valley', 'Isabela', 'City of Ilagan', 'Mangcuram', '0912313213213', '2026-06-17 02:04:02');

-- --------------------------------------------------------

--
-- Table structure for table `feeding_programs`
--

CREATE TABLE `feeding_programs` (
  `id` int(11) NOT NULL,
  `church_site_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feeding_programs`
--

INSERT INTO `feeding_programs` (`id`, `church_site_id`, `title`, `scheduled_date`, `scheduled_time`, `status`, `created_at`) VALUES
(2, 5, 'Testing', '2026-06-17', '11:14:00', 'scheduled', '2026-06-17 02:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `nutritional_assessments`
--

CREATE TABLE `nutritional_assessments` (
  `id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `encoder_id` int(11) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `bmi` decimal(4,2) NOT NULL,
  `bmi_status` varchar(50) NOT NULL,
  `assessment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nutritional_assessments`
--

INSERT INTO `nutritional_assessments` (`id`, `child_id`, `encoder_id`, `weight`, `height`, `bmi`, `bmi_status`, `assessment_date`, `notes`, `created_at`) VALUES
(1, 1, 2, 10.00, 110.00, 8.26, 'Severely Underweight', '2026-06-16', 'Initial assessment from registration submission', '2026-06-16 15:27:08'),
(2, 2, 2, 10.00, 110.00, 8.26, 'Severely Underweight', '2026-06-17', 'Initial assessment from registration submission', '2026-06-17 02:12:26'),
(3, 2, 8, 19.00, 111.00, 15.42, 'Underweight', '2026-06-17', 'Magaling na', '2026-06-17 02:48:09'),
(4, 2, 8, 25.00, 110.00, 20.66, 'Normal Weight', '2026-06-17', '', '2026-06-17 02:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `check_out_time` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_attendance`
--

INSERT INTO `staff_attendance` (`id`, `user_id`, `check_in_time`, `ip_address`) VALUES
(1, 2, '2026-06-17 03:11:59', '192.168.1.60'),
(2, 9, '2026-06-17 03:18:57', '192.168.1.36');

-- --------------------------------------------------------

--
-- Table structure for table `staff_qr_tokens`
--

CREATE TABLE `staff_qr_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_qr_tokens`
--

INSERT INTO `staff_qr_tokens` (`id`, `token`, `created_at`, `expires_at`) VALUES
(17, '2978b5eb6818d264655322ece8da9295', '2026-06-17 03:21:48', '2026-06-18 05:21:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff','church_leader') NOT NULL,
  `position_title` varchar(100) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `admin_pin` varchar(4) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `admin_message` text DEFAULT NULL,
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `position_title`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `admin_pin`, `profile_picture`, `admin_message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$a3joGnFOelSWOZGqAcXT7.uxhYYVjG76G865tcpbAfNJdgpXB6.AK', 'admin', NULL, 'DivineShield', NULL, 'Admin', 'admin@mainpi.org', '09171234567', '1234', 'assets/uploads/profile_pics/profile_1_1781663346.jpg', NULL, 'active', '2026-06-16 12:36:27', '2026-06-17 02:29:06'),
(2, 'encoder1', '$2y$10$oLAOpO0LYQcKPehQEdqmWux/MhoYep5iTmNaGfFrOep6H3Wwt3bRq', 'staff', NULL, 'Maria', NULL, 'Santos', 'maria.encoder@mainpi.org', '09187654321', NULL, NULL, NULL, 'active', '2026-06-16 12:36:27', '2026-06-17 03:17:21'),
(3, 'pastor_juan', '$2y$10$a3joGnFOelSWOZGqAcXT7.uxhYYVjG76G865tcpbAfNJdgpXB6.AK', 'church_leader', NULL, 'Juan', NULL, 'Dela Cruz', 'juan.delacruz@church.org', '09191112222', NULL, NULL, NULL, 'active', '2026-06-16 12:36:27', '2026-06-16 12:46:23'),
(4, 'pastor_pedro', '$2y$10$a3joGnFOelSWOZGqAcXT7.uxhYYVjG76G865tcpbAfNJdgpXB6.AK', 'church_leader', NULL, 'Pedro', NULL, 'Penduko', 'pedro.penduko@church.org', '09193334444', NULL, NULL, NULL, 'inactive', '2026-06-16 12:36:27', '2026-06-16 17:18:45'),
(5, 'nicos1', '$2y$10$a3joGnFOelSWOZGqAcXT7.uxhYYVjG76G865tcpbAfNJdgpXB6.AK', 'church_leader', 'Lead Pastor', 'El Nicos', 'Eslier', 'Lumayno', 'nicos@gmail.com', '09611351290', NULL, NULL, 'Hello Admin', 'active', '2026-06-16 12:42:40', '2026-06-16 14:13:45'),
(6, 'rina123', '$2y$10$5wYVAMKcXKKwdlJE1gg1H.wJ.BJhTLB6tIEt8qysg1r0AmljtE39a', 'church_leader', 'Lead Pastor', 'Rina', 'flor', 'wax', 'rina@gmail.com', '0912345678', NULL, NULL, 'hello', 'active', '2026-06-16 13:53:39', '2026-06-16 14:14:55'),
(7, 'asd123', '$2y$10$oR9jUflfvhn05ZlOjzG4Ve40Zt0E..3SRh/eW1uURmhCfI8bI3xgK', 'church_leader', 'Lead Pastor', 'asdsa', 'dsadsa', 'dasd', 'you@mail.com', '9090909090', NULL, NULL, '', 'active', '2026-06-16 18:14:27', '2026-06-16 18:17:01'),
(8, 'kim123', '$2y$10$t2btO.NCKk1HsiphCGVY3ux467G7461YfE3cGotczFbdmxjL.Mo/y', 'church_leader', 'Lead Pastor', 'Kim', 'Berly', 'Maramag', 'kmaramag15@gmail.com', '0912313213213', NULL, NULL, 'hello po -gm', 'active', '2026-06-17 02:04:02', '2026-06-17 02:05:00'),
(9, 'encoder2', '$2y$10$oLAOpO0LYQcKPehQEdqmWux/MhoYep5iTmNaGfFrOep6H3Wwt3bRq', 'staff', NULL, 'Jose', NULL, 'Reyes', 'jose.encoder@mainpi.org', '09181234001', NULL, NULL, NULL, 'active', '2026-06-17 03:15:20', '2026-06-17 03:17:21'),
(10, 'encoder3', '$2y$10$oLAOpO0LYQcKPehQEdqmWux/MhoYep5iTmNaGfFrOep6H3Wwt3bRq', 'staff', NULL, 'Ana', NULL, 'Cruz', 'ana.encoder@mainpi.org', '09181234002', NULL, NULL, NULL, 'active', '2026-06-17 03:15:20', '2026-06-17 03:17:21'),
(11, 'encoder4', '$2y$10$oLAOpO0LYQcKPehQEdqmWux/MhoYep5iTmNaGfFrOep6H3Wwt3bRq', 'staff', NULL, 'Carlo', NULL, 'Bautista', 'carlo.encoder@mainpi.org', '09181234003', NULL, NULL, NULL, 'active', '2026-06-17 03:15:20', '2026-06-17 03:17:21'),
(12, 'encoder5', '$2y$10$oLAOpO0LYQcKPehQEdqmWux/MhoYep5iTmNaGfFrOep6H3Wwt3bRq', 'staff', NULL, 'Liza', NULL, 'Garcia', 'liza.encoder@mainpi.org', '09181234004', NULL, NULL, NULL, 'active', '2026-06-17 03:15:20', '2026-06-17 03:17:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `idx_feeding_attendance` (`feeding_program_id`,`child_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `submission_id` (`submission_id`),
  ADD UNIQUE KEY `rfid_tag` (`rfid_tag`),
  ADD KEY `church_site_id` (`church_site_id`),
  ADD KEY `idx_rfid_tag` (`rfid_tag`),
  ADD KEY `idx_child_status` (`status`);

--
-- Indexes for table `children_submissions`
--
ALTER TABLE `children_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `church_site_id` (`church_site_id`),
  ADD KEY `church_leader_id` (`church_leader_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_submission_status` (`submission_status`);

--
-- Indexes for table `church_sites`
--
ALTER TABLE `church_sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `church_leader_id` (`church_leader_id`),
  ADD KEY `idx_region` (`region`);

--
-- Indexes for table `feeding_programs`
--
ALTER TABLE `feeding_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `church_site_id` (`church_site_id`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`);

--
-- Indexes for table `nutritional_assessments`
--
ALTER TABLE `nutritional_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `encoder_id` (`encoder_id`),
  ADD KEY `idx_assessment_date` (`assessment_date`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff_qr_tokens`
--
ALTER TABLE `staff_qr_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `children_submissions`
--
ALTER TABLE `children_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `church_sites`
--
ALTER TABLE `church_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `feeding_programs`
--
ALTER TABLE `feeding_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `nutritional_assessments`
--
ALTER TABLE `nutritional_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_qr_tokens`
--
ALTER TABLE `staff_qr_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`feeding_program_id`) REFERENCES `feeding_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `children_submissions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `children_ibfk_2` FOREIGN KEY (`church_site_id`) REFERENCES `church_sites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `children_submissions`
--
ALTER TABLE `children_submissions`
  ADD CONSTRAINT `children_submissions_ibfk_1` FOREIGN KEY (`church_site_id`) REFERENCES `church_sites` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `children_submissions_ibfk_2` FOREIGN KEY (`church_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `children_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `church_sites`
--
ALTER TABLE `church_sites`
  ADD CONSTRAINT `church_sites_ibfk_1` FOREIGN KEY (`church_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feeding_programs`
--
ALTER TABLE `feeding_programs`
  ADD CONSTRAINT `feeding_programs_ibfk_1` FOREIGN KEY (`church_site_id`) REFERENCES `church_sites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `nutritional_assessments`
--
ALTER TABLE `nutritional_assessments`
  ADD CONSTRAINT `nutritional_assessments_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nutritional_assessments_ibfk_2` FOREIGN KEY (`encoder_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `staff_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
