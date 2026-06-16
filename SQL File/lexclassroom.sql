-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 07:26 PM
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
-- Database: `lexclassroom`
--

-- --------------------------------------------------------

--
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` int(11) NOT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `batch_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batches`
--

INSERT INTO `batches` (`id`, `CourseID`, `batch_name`) VALUES
(3, 1, 'Sem 1 - Sec A'),
(4, 3, 'Sem 1 - Sec A'),
(5, 3, 'Sem 1 - Sec B'),
(6, 3, 'Sem 1 - Sec C');

-- --------------------------------------------------------

--
-- Table structure for table `classrooms`
--

CREATE TABLE `classrooms` (
  `id` int(11) NOT NULL,
  `room_name_or_number` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `CourseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classrooms`
--

INSERT INTO `classrooms` (`id`, `room_name_or_number`, `capacity`, `CourseID`) VALUES
(5, '401', 60, 3);

-- --------------------------------------------------------

--
-- Table structure for table `class_attendance`
--

CREATE TABLE `class_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `scan_time` time NOT NULL,
  `status` varchar(20) DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_attendance`
--

INSERT INTO `class_attendance` (`id`, `student_id`, `timetable_id`, `attendance_date`, `scan_time`, `status`) VALUES
(1, 3, 135, '2026-06-10', '11:27:52', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `lab_records`
--

CREATE TABLE `lab_records` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) DEFAULT NULL,
  `subject` varchar(50) DEFAULT NULL,
  `lab_topic` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'COMPLETED',
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_records`
--

INSERT INTO `lab_records` (`id`, `student_name`, `subject`, `lab_topic`, `status`, `timestamp`) VALUES
(1, 'Rahul Sharma', 'Physics', 'Solar System VR', 'COMPLETED', '2026-01-25 17:55:45'),
(2, 'Rahul Sharma', 'Biology', 'DNA Structure', 'COMPLETED', '2026-01-25 17:56:04'),
(3, 'Rahul Sharma', 'Engineering', 'Engine Mechanics', 'COMPLETED', '2026-01-25 17:56:13'),
(4, 'Rahul Sharma', 'Physics', 'Solar System', 'COMPLETED', '2026-01-25 17:58:38'),
(5, 'Rahul Sharma', 'Biology', 'DNA Analysis', 'COMPLETED', '2026-01-25 17:59:25'),
(6, 'Rahul Sharma', 'Engineering', 'Robotics Core', 'COMPLETED', '2026-01-25 17:59:38'),
(7, 'Rahul Sharma', 'Chemistry', 'Chemical Bonding', 'COMPLETED', '2026-01-25 17:59:49'),
(8, 'Rahul Sharma', 'physics', 'ASTRO-PHYSICS', 'COMPLETED', '2026-01-25 23:30:27'),
(9, 'Rahul Sharma', 'bio', 'MOLECULAR BIOLOGY', 'COMPLETED', '2026-01-25 23:31:20'),
(10, 'Rahul Sharma', 'mech', 'MECH-ENGINEERING', 'COMPLETED', '2026-01-25 23:31:36'),
(11, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'WATER (H2O) + ENERGY', '2026-01-25 23:34:40'),
(12, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'SODIUM CHLORIDE (SAL', '2026-01-25 23:35:04'),
(13, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'UNKNOWN SLUDGE', '2026-01-25 23:35:29'),
(14, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'UNKNOWN SLUDGE', '2026-01-25 23:36:04'),
(15, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'UNKNOWN SLUDGE', '2026-01-25 23:36:13'),
(16, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'WATER (H2O) + ENERGY', '2026-01-25 23:40:47'),
(17, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'CARBON DIOXIDE (CO2)', '2026-01-25 23:41:12'),
(18, 'Rahul Sharma', 'Chemistry', 'Chemistry Mix', 'CARBON DIOXIDE (CO2)', '2026-01-25 23:41:14'),
(19, 'Rahul Sharma', 'Chemistry', 'Experiment 12', 'NO REACTION', '2026-01-25 23:46:07'),
(20, 'Rahul Sharma', 'Chemistry', 'Experiment 7', 'NO REACTION', '2026-01-25 23:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `lab_users`
--

CREATE TABLE `lab_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_users`
--

INSERT INTO `lab_users` (`id`, `username`, `password`, `role`, `full_name`) VALUES
(1, 'student1', '123', 'student', 'Rahul Sharma'),
(2, 'student2', '123', 'student', 'Priya Singh'),
(3, 'teacher1', '123', 'teacher', 'Dr. A. P. J. Abdul'),
(4, 'admin', '123', 'admin', 'System Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('teacher','student') NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `user_type`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 'teacher', 'Reminder: Your <strong>OPREATING SYSTEM</strong> class for batch <strong>Sem 1 - Sec A</strong> in Room <strong>401</strong> starts at 12:00 PM.', 0, '2026-06-10 05:42:55'),
(2, 3, 'student', 'Upcoming: <strong>OPREATING SYSTEM</strong> with Prof. Kaushik Ghosh in Room <strong>401</strong> at 12:00 PM.', 1, '2026-06-10 05:42:55'),
(3, 2, 'teacher', 'Reminder: Your <strong>OPREATING SYSTEM</strong> class for batch <strong>Sem 1 - Sec B</strong> in Room <strong>401</strong> starts at 12:00 PM.', 0, '2026-06-10 05:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` varchar(20) DEFAULT 'absent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `student_id`, `attendance_date`, `check_in_time`, `check_out_time`, `status`) VALUES
(1, 3, '2026-06-10', '11:27:04', '11:29:44', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('checkin_end', '09:30'),
('checkin_start', '00:00'),
('checkout_end', '18:00'),
('checkout_start', '16:00'),
('enforce_time_windows', '0'),
('maintenance_mode', '0'),
('stu_checkin_end', '10:00'),
('stu_checkin_start', '08:00'),
('stu_checkout_end', '17:00'),
('stu_checkout_start', '15:00'),
('stu_enforce_mode', 'open');

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `ID` int(10) NOT NULL,
  `AdminName` varchar(200) DEFAULT NULL,
  `UserName` varchar(200) DEFAULT NULL,
  `Email` varchar(200) NOT NULL DEFAULT 'admin@vidyaverse.com',
  `MobileNumber` varchar(15) NOT NULL DEFAULT '0000000000',
  `Password` varchar(200) DEFAULT NULL,
  `AdminRegdate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `Email`, `MobileNumber`, `Password`, `AdminRegdate`) VALUES
(1, 'Kaushik Ghosh', 'admin', 'kaushik3389@gmail.com', '8249812808', '21232f297a57a5a743894a0e4a801fc3', '2025-12-06 18:28:21');

-- --------------------------------------------------------

--
-- Table structure for table `tblassigment`
--

CREATE TABLE `tblassigment` (
  `ID` int(10) NOT NULL,
  `Tid` int(5) DEFAULT NULL,
  `Cid` int(5) DEFAULT NULL,
  `Sid` int(50) DEFAULT NULL,
  `AssignmentNumber` varchar(200) DEFAULT NULL,
  `AssignmenttTitle` varchar(200) DEFAULT NULL,
  `AssignmentDescription` mediumtext DEFAULT NULL,
  `SubmissionDate` date DEFAULT NULL,
  `AssigmentMarks` int(5) DEFAULT NULL,
  `AssignmentFile` varchar(200) DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblassigment`
--

INSERT INTO `tblassigment` (`ID`, `Tid`, `Cid`, `Sid`, `AssignmentNumber`, `AssignmenttTitle`, `AssignmentDescription`, `SubmissionDate`, `AssigmentMarks`, `AssignmentFile`, `CreationDate`) VALUES
(1, 2, 1, 2, 'PCCCS209-16230', 'WAP TO PRINT HELO WORLD', 'hi', '2025-12-25', 25, '', '2025-12-16 05:06:22'),
(2, 2, 1, 2, 'PCCCS209-66089', '1', 'test', '2025-12-21', 20, '', '2025-12-21 18:22:27'),
(3, 2, 1, 2, 'PCCCS209-16749', 'WAP TO PRINT HELO WORLD', 'rjmnm', '2025-12-24', 25, '', '2025-12-22 06:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `tblaudit_logs`
--

CREATE TABLE `tblaudit_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblaudit_logs`
--

INSERT INTO `tblaudit_logs` (`id`, `user_id`, `action`, `ip_address`, `timestamp`) VALUES
(1, 'admin', 'login_success', '::1', '2026-05-24 21:40:37'),
(2, 'admin', 'login_success', '::1', '2026-05-30 01:01:34'),
(3, 'admin', 'login_success', '::1', '2026-06-07 23:01:12'),
(4, 'admin', 'login_success', '::1', '2026-06-07 23:02:43'),
(5, 'admin', 'login_success', '::1', '2026-06-10 01:08:35'),
(6, 'admin', 'login_success', '::1', '2026-06-10 11:23:38');

-- --------------------------------------------------------

--
-- Table structure for table `tblcourse`
--

CREATE TABLE `tblcourse` (
  `ID` int(10) NOT NULL,
  `BranchName` varchar(200) DEFAULT NULL,
  `CourseName` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcourse`
--

INSERT INTO `tblcourse` (`ID`, `BranchName`, `CourseName`) VALUES
(3, 'CSE', 'B.Tech'),
(4, 'IT', 'B.Tech'),
(5, 'ECE', 'B.Tech');

-- --------------------------------------------------------

--
-- Table structure for table `tblexams`
--

CREATE TABLE `tblexams` (
  `ID` int(11) NOT NULL,
  `ExamTitle` varchar(255) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `BatchID` int(11) NOT NULL,
  `ExamDate` datetime NOT NULL,
  `Duration` int(11) NOT NULL COMMENT 'In Minutes',
  `TotalMarks` int(11) NOT NULL DEFAULT 0,
  `TotalQuestions` int(11) NOT NULL DEFAULT 0,
  `CreationDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblexam_answers`
--

CREATE TABLE `tblexam_answers` (
  `ID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `QuestionID` int(11) NOT NULL,
  `SelectedOption` varchar(1) NOT NULL,
  `StudentAnswer` text DEFAULT NULL,
  `IsCorrect` int(11) DEFAULT 0,
  `MarksObtained` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblexam_answers`
--

INSERT INTO `tblexam_answers` (`ID`, `SessionID`, `QuestionID`, `SelectedOption`, `StudentAnswer`, `IsCorrect`, `MarksObtained`) VALUES
(1, 1, 4, 'r', NULL, 0, 0),
(2, 1, 1, 'D', NULL, 0, 0),
(3, 1, 2, 'b', NULL, 0, 0),
(4, 1, 3, 'h', NULL, 0, 0),
(5, 2, 3, '', NULL, 0, 0),
(6, 2, 4, '', NULL, 0, 0),
(7, 2, 2, '', NULL, 0, 0),
(8, 5, 4, '', 'gvghbn', NULL, 0),
(9, 5, 3, '', 'jhbn', NULL, 0),
(10, 5, 2, '', 'rghb', NULL, 0),
(11, 5, 1, '', 'D', 1, 1),
(12, 5, 1, '', 'D', 1, 1),
(13, 5, 2, '', 'mnm', NULL, 0),
(14, 5, 4, '', 'mnnm', NULL, 0),
(15, 5, 3, '', 'nmm', NULL, 0),
(16, 5, 4, '', 'mnmn', NULL, 0),
(17, 5, 3, '', 'kmn', NULL, 0),
(18, 5, 1, '', 'D', 1, 1),
(19, 5, 2, '', 'nmn', NULL, 0),
(20, 6, 1, '', 'D', 1, 1),
(21, 6, 4, '', 'vb', NULL, 0),
(22, 6, 2, '', 'jnbn', NULL, 0),
(23, 6, 3, '', 'bnb', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblexam_questions`
--

CREATE TABLE `tblexam_questions` (
  `ID` int(11) NOT NULL,
  `ExamID` int(11) NOT NULL,
  `QuestionID` int(11) NOT NULL,
  `SectionName` varchar(50) DEFAULT NULL,
  `QuestionMarks` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblexam_sessions`
--

CREATE TABLE `tblexam_sessions` (
  `ID` int(11) NOT NULL,
  `ExamID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `StartTime` datetime DEFAULT current_timestamp(),
  `EndTime` datetime DEFAULT NULL,
  `Score` int(11) DEFAULT 0,
  `Status` enum('Ongoing','Completed','Terminated') DEFAULT 'Ongoing',
  `TabSwitchCount` int(11) DEFAULT 0,
  `LastSnapshot` varchar(255) DEFAULT NULL,
  `MovementWarnings` int(11) DEFAULT 0,
  `TeacherWarningMsg` varchar(255) DEFAULT NULL,
  `IsReportedByTeacher` int(11) DEFAULT 0,
  `LiveSnapshot` longtext DEFAULT NULL,
  `AdminMessage` varchar(255) DEFAULT NULL,
  `LastHeartbeat` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblexam_sessions`
--

INSERT INTO `tblexam_sessions` (`ID`, `ExamID`, `StudentID`, `StartTime`, `EndTime`, `Score`, `Status`, `TabSwitchCount`, `LastSnapshot`, `MovementWarnings`, `TeacherWarningMsg`, `IsReportedByTeacher`, `LiveSnapshot`, `AdminMessage`, `LastHeartbeat`) VALUES
(1, 2, 3, '2025-12-15 20:46:43', '2025-12-15 20:53:02', 1, 'Completed', 4, NULL, NULL, NULL, 0, NULL, NULL, NULL),
(2, 1, 3, '2025-12-15 21:01:20', '2025-12-15 21:11:42', 3, 'Terminated', 5, NULL, NULL, NULL, 0, NULL, 'Admin Action: Disqualified due to suspicious behavior.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblfeedback`
--

CREATE TABLE `tblfeedback` (
  `ID` int(11) NOT NULL,
  `FullName` varchar(100) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblfeedback`
--

INSERT INTO `tblfeedback` (`ID`, `FullName`, `Email`, `Role`, `Message`, `CreationDate`) VALUES
(1, 'k', 'kaushik3389@gmail.com', 'Prospective Student', 'ws', '2026-06-07 16:14:00');

-- --------------------------------------------------------

--
-- Table structure for table `tblliveinteraction`
--

CREATE TABLE `tblliveinteraction` (
  `ID` int(11) NOT NULL,
  `SessionID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `Type` varchar(20) NOT NULL,
  `Message` text DEFAULT NULL,
  `IsActive` int(1) DEFAULT 1,
  `Timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblliveinteraction`
--

INSERT INTO `tblliveinteraction` (`ID`, `SessionID`, `StudentID`, `Type`, `Message`, `IsActive`, `Timestamp`) VALUES
(1, 1, 3, 'Doubt', 'hlo mam', 1, '2025-12-16 15:45:32'),
(2, 1, 3, 'Doubt', 'hii', 1, '2025-12-16 15:53:56'),
(3, 1, 3, 'Doubt', 'hloo', 1, '2025-12-16 15:54:09'),
(4, 4, 3, 'Doubt', 'nbvghb', 0, '2025-12-16 15:57:29'),
(5, 4, 3, 'Break', NULL, 0, '2025-12-16 16:05:08'),
(6, 4, 3, 'Doubt', 'bnn', 0, '2025-12-16 16:05:18'),
(7, 7, 3, 'Doubt', 'hlo', 0, '2025-12-16 18:08:13'),
(8, 7, 3, 'Break', NULL, 0, '2025-12-16 18:08:26'),
(9, 7, 3, 'Doubt', 'mam', 0, '2025-12-16 18:08:37'),
(10, 7, 3, 'Doubt', 'hlo\n', 1, '2025-12-16 18:13:14'),
(11, 8, 3, 'Doubt', 'hi', 0, '2025-12-16 18:13:32'),
(12, 8, 3, 'Doubt', 'bnb', 0, '2025-12-16 18:13:50'),
(13, 8, 3, 'Break', NULL, 0, '2025-12-16 23:40:14'),
(14, 9, 3, 'Doubt', 'What is the Full Form of ACID? Sir can you Explain it once Again. I haven\'t able to understand', 1, '2025-12-17 00:03:26'),
(15, 9, 3, 'Break', NULL, 0, '2025-12-17 00:03:37'),
(16, 10, 3, 'Doubt', 'my doubt', 1, '2025-12-21 23:49:50'),
(17, 11, 3, 'Doubt', ' what is my name', 0, '2025-12-22 11:29:51'),
(18, 11, 3, 'Break', NULL, 1, '2025-12-22 11:30:13');

-- --------------------------------------------------------

--
-- Table structure for table `tbllivesession`
--

CREATE TABLE `tbllivesession` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `StartTime` datetime DEFAULT current_timestamp(),
  `Status` varchar(20) DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbllivesession`
--

INSERT INTO `tbllivesession` (`ID`, `TeacherID`, `CourseID`, `StartTime`, `Status`) VALUES
(1, 2, 0, '2025-12-16 15:42:53', 'Ended'),
(2, 2, 0, '2025-12-16 15:54:24', 'Ended'),
(3, 2, 0, '2025-12-16 15:54:39', 'Ended'),
(4, 2, 0, '2025-12-16 15:57:12', 'Ended'),
(5, 2, 1, '2025-12-16 16:56:31', 'Ended'),
(6, 2, 1, '2025-12-16 17:49:48', 'Ended'),
(7, 2, 0, '2025-12-16 18:07:54', 'Ended'),
(8, 2, 1, '2025-12-16 18:09:29', 'Ended'),
(9, 2, 0, '2025-12-17 00:00:12', 'Ended'),
(10, 2, 0, '2025-12-21 23:49:24', 'Ended'),
(11, 2, 1, '2025-12-22 11:29:26', 'Ended'),
(12, 2, 0, '2026-06-07 22:19:30', 'Ended');

-- --------------------------------------------------------

--
-- Table structure for table `tblnews`
--

CREATE TABLE `tblnews` (
  `ID` int(10) NOT NULL,
  `Title` varchar(200) DEFAULT NULL,
  `Description` mediumtext DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblnews`
--

INSERT INTO `tblnews` (`ID`, `Title`, `Description`, `CreationDate`) VALUES
(1, 'SIH GRAND FINALE', 'SIH Grand Finale to be held from 20th December 2025 - 23rd December 2025', '2025-12-15 17:15:05'),
(2, 'CA4 EXAMS', 'TO BE HELD FROM 4TH MARCH', '2026-02-10 20:12:07'),
(4, 'TEKATHON 2K26', 'TEKATHON 2K26 Grand Finale to be held on 13th May 2026', '2026-05-29 20:09:59');

-- --------------------------------------------------------

--
-- Table structure for table `tblnewsbyteacher`
--

CREATE TABLE `tblnewsbyteacher` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Description` mediumtext DEFAULT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblquestions`
--

CREATE TABLE `tblquestions` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `SubjectID` int(11) DEFAULT NULL,
  `QuestionType` varchar(20) DEFAULT 'Long',
  `QuestionText` text NOT NULL,
  `OptionA` varchar(255) NOT NULL,
  `OptionB` varchar(255) NOT NULL,
  `OptionC` varchar(255) NOT NULL,
  `OptionD` varchar(255) NOT NULL,
  `CorrectAnswer` enum('A','B','C','D') NOT NULL,
  `IsApproved` tinyint(1) DEFAULT 0,
  `CreatedDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblquestions`
--

INSERT INTO `tblquestions` (`ID`, `TeacherID`, `CourseID`, `SubjectID`, `QuestionType`, `QuestionText`, `OptionA`, `OptionB`, `OptionC`, `OptionD`, `CorrectAnswer`, `IsApproved`, `CreatedDate`) VALUES
(1, 2, 1, 2, 'MCQ', 'hi', 'h', 'b', 'df', 'df', 'D', 0, '2025-12-15 13:31:17'),
(2, 2, 1, 2, 'Short', 'ihbjb', 'N/A', 'N/A', 'N/A', 'N/A', '', 0, '2025-12-15 13:44:56'),
(3, 2, 1, 2, 'Long', 'dfg', 'N/A', 'N/A', 'N/A', 'N/A', '', 0, '2025-12-15 13:54:32'),
(4, 0, 1, 2, 'Long', 'df', 'N/A', 'N/A', 'N/A', 'N/A', '', 1, '2025-12-15 13:55:28'),
(5, 2, 1, 2, 'MCQ', 'test1', '1', '2', '3', '4', 'D', 0, '2025-12-21 18:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `tblreset_requests`
--

CREATE TABLE `tblreset_requests` (
  `ID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RequestDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblsubject`
--

CREATE TABLE `tblsubject` (
  `ID` int(5) NOT NULL,
  `CourseID` int(5) DEFAULT NULL,
  `Semester` varchar(50) NOT NULL,
  `SubjectFullname` varchar(200) DEFAULT NULL,
  `SubjectShortname` varchar(200) NOT NULL,
  `SubjectCode` varchar(200) DEFAULT NULL,
  `HoursPerWeek` int(3) NOT NULL DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsubject`
--

INSERT INTO `tblsubject` (`ID`, `CourseID`, `Semester`, `SubjectFullname`, `SubjectShortname`, `SubjectCode`, `HoursPerWeek`) VALUES
(2, 1, '1', 'OPREATING SYSTEM', 'OS', 'PCCCS209', 4),
(3, 3, '1', 'Image Processing', 'IP', 'CS201', 4),
(4, 3, '1', 'C Programming', 'C', 'CS202', 4),
(5, 3, '1', 'OPREATING SYSTEM', 'OS', 'CS203', 4);

-- --------------------------------------------------------

--
-- Table structure for table `tblsurveys`
--

CREATE TABLE `tblsurveys` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `CourseID` int(11) NOT NULL,
  `IsActive` int(1) DEFAULT 1,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsurveys`
--

INSERT INTO `tblsurveys` (`ID`, `TeacherID`, `CourseID`, `IsActive`, `CreatedAt`) VALUES
(1, 2, 1, 0, '2025-12-16 18:18:41'),
(2, 2, 1, 0, '2025-12-16 18:22:52'),
(3, 2, 1, 0, '2025-12-16 18:23:00'),
(4, 2, 1, 0, '2025-12-16 18:23:18'),
(5, 2, 1, 0, '2025-12-16 18:27:04'),
(6, 2, 1, 0, '2025-12-16 18:27:09'),
(7, 2, 1, 0, '2025-12-16 18:27:09'),
(8, 2, 1, 0, '2025-12-16 18:27:09'),
(9, 2, 1, 0, '2025-12-16 18:28:39'),
(10, 2, 1, 0, '2025-12-16 18:37:25'),
(11, 2, 1, 0, '2025-12-17 00:55:31'),
(12, 2, 1, 0, '2025-12-17 16:50:14'),
(13, 2, 2, 1, '2025-12-28 18:06:07'),
(14, 2, 1, 1, '2026-02-11 01:45:49'),
(15, 2, 3, 1, '2026-06-07 22:15:47');

-- --------------------------------------------------------

--
-- Table structure for table `tblsurvey_responses`
--

CREATE TABLE `tblsurvey_responses` (
  `ID` int(11) NOT NULL,
  `SurveyID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `Rating` int(1) NOT NULL,
  `Feedback` text DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblsurvey_responses`
--

INSERT INTO `tblsurvey_responses` (`ID`, `SurveyID`, `StudentID`, `Rating`, `Feedback`, `Timestamp`) VALUES
(1, 1, 3, 5, 'good', '2025-12-16 18:20:33'),
(2, 9, 3, 2, 'not good', '2025-12-16 18:29:59'),
(3, 11, 3, 5, 'Best Classes', '2025-12-17 00:57:05'),
(4, 12, 3, 3, 'ctfguiyhj', '2025-12-17 16:50:31'),
(5, 14, 3, 5, '', '2026-02-11 01:48:59');

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher`
--

CREATE TABLE `tblteacher` (
  `ID` int(10) NOT NULL,
  `TeacherName` varchar(250) DEFAULT NULL,
  `EmpID` varchar(50) DEFAULT NULL,
  `qr_code_identifier` varchar(255) DEFAULT NULL,
  `FirstName` varchar(200) DEFAULT NULL,
  `LastName` varchar(200) DEFAULT NULL,
  `MobileNumber` varchar(15) DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `Gender` varchar(50) DEFAULT NULL,
  `Dob` varchar(200) DEFAULT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `ProfilePic` varchar(200) DEFAULT NULL,
  `CourseID` int(5) DEFAULT NULL,
  `Religion` varchar(200) DEFAULT NULL,
  `Address` mediumtext DEFAULT NULL,
  `RegDate` date DEFAULT current_timestamp(),
  `Cid` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblteacher`
--

INSERT INTO `tblteacher` (`ID`, `TeacherName`, `EmpID`, `qr_code_identifier`, `FirstName`, `LastName`, `MobileNumber`, `Email`, `Gender`, `Dob`, `Password`, `ProfilePic`, `CourseID`, `Religion`, `Address`, `RegDate`, `Cid`) VALUES
(2, 'Kaushik Ghosh', 'KG01', 'TCHR_5741a37001da02df', 'Kaushik', 'Ghosh', '8249812808', 'kaushik3389@gmail.com', 'Male', '2004-08-04', 'e807f1fcf82d132f9bb018ca6738a19f', 'c050b276e665d46d5fb3b641a8e97abf1765030496.jpg', 3, 'Hindu', 'MAHANADI GARDENS BLOCK-C ROOM NO.-12, JODA WEST, JODA, KEONJHAR', '2025-12-06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblteachers`
--

CREATE TABLE `tblteachers` (
  `ID` int(11) NOT NULL,
  `TeacherName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `AssignedSubjectID` int(11) NOT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher_reset_requests`
--

CREATE TABLE `tblteacher_reset_requests` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `RequestDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher_subjects`
--

CREATE TABLE `tblteacher_subjects` (
  `ID` int(11) NOT NULL,
  `TeacherID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblteacher_subjects`
--

INSERT INTO `tblteacher_subjects` (`ID`, `TeacherID`, `SubjectID`) VALUES
(13, 2, 5),
(14, 2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `tbluploadass`
--

CREATE TABLE `tbluploadass` (
  `ID` int(10) NOT NULL,
  `UserID` int(5) DEFAULT NULL,
  `AssId` int(5) DEFAULT NULL,
  `AssDes` mediumtext DEFAULT NULL,
  `AnswerFile` varchar(200) NOT NULL,
  `SubmitDate` timestamp NULL DEFAULT current_timestamp(),
  `Marks` decimal(10,2) DEFAULT NULL,
  `Remarks` varchar(200) DEFAULT NULL,
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluploadass`
--

INSERT INTO `tbluploadass` (`ID`, `UserID`, `AssId`, `AssDes`, `AnswerFile`, `SubmitDate`, `Marks`, `Remarks`, `UpdationDate`) VALUES
(1, 3, 1, '', 'c4d2aa2d842decc600fce20d749733d51765862156.docx', '2025-12-16 05:15:56', 24.00, 'good', '2025-12-16 05:38:40'),
(2, 3, 2, '', 'd2c4b8dc5cd7439596889387b811ec451766341376.pdf', '2025-12-21 18:22:56', 20.00, '', '2025-12-21 18:24:15'),
(3, 3, 3, '', '6848967b77b80d37ae6d3d4d20c00e181766383336.pdf', '2025-12-22 06:02:16', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

CREATE TABLE `tbluser` (
  `ID` int(10) NOT NULL,
  `FullName` varchar(200) DEFAULT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `Cid` int(5) DEFAULT NULL,
  `RollNumber` varchar(50) DEFAULT NULL,
  `qr_code_identifier` varchar(255) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `FaceEncoding` text DEFAULT NULL,
  `UserImage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser` (`ID`, `FullName`, `MobileNumber`, `DOB`, `Email`, `Cid`, `RollNumber`, `qr_code_identifier`, `batch_id`, `Password`, `RegDate`, `FaceEncoding`, `UserImage`) VALUES
(3, 'Kaushik Ghosh', 1234567890, NULL, 'myselfsambhunath@gmail.com', 1, '0987654321', 'STU_bf35f814c58423e8', 4, 'e807f1fcf82d132f9bb018ca6738a19f', '2025-11-06 11:58:50', '[-0.1508849561214447, 0.009320472367107868, 0.08256363868713379, -0.0006000746507197618, -0.013923399150371552, -0.07118219137191772, 0.017245490103960037, -0.07920938730239868, 0.13529087603092194, -0.08205574750900269, 0.19456937909126282, -0.004788772203028202, -0.16734905540943146, -0.09395479410886765, 0.03170356526970863, 0.06462079286575317, -0.1027112752199173, -0.10582851618528366, -0.018844977021217346, -0.10443690419197083, 0.04856906086206436, 0.020321227610111237, 0.01823398470878601, 0.03691716492176056, -0.12646722793579102, -0.34743744134902954, -0.07982786744832993, -0.1478746384382248, 0.0307206679135561, -0.034699615091085434, 0.0849185511469841, 0.03518126904964447, -0.20098964869976044, -0.02968953549861908, -0.03106454201042652, 0.06128799170255661, 0.0071403998881578445, 0.0008564358577132225, 0.20922133326530457, -0.022637829184532166, -0.13413219153881073, -0.09448569267988205, -0.008936405181884766, 0.24376356601715088, 0.07237054407596588, 0.046863045543432236, 0.029262054711580276, -0.030765298753976822, 0.04242820292711258, -0.23331396281719208, 0.1123916432261467, 0.12288947403430939, 0.07781144231557846, 0.012411529198288918, 0.03482609987258911, -0.09706644713878632, 0.03843851387500763, 0.012222547084093094, -0.1927202045917511, 0.08357428759336472, 0.08091223239898682, -0.04865426570177078, -0.06994614005088806, 0.017050916329026222, 0.26161864399909973, 0.09403079003095627, -0.09420685470104218, -0.06587833166122437, 0.13813067972660065, -0.1462693214416504, -0.022205397486686707, 0.06426962465047836, -0.10699522495269775, -0.1734459102153778, -0.2761518955230713, 0.08344543725252151, 0.43411552906036377, 0.13378052413463593, -0.18710258603096008, 0.012312844395637512, -0.1810290515422821, 0.035757772624492645, 0.16902007162570953, 0.03145172446966171, -0.035725187510252, -0.01088645774871111, -0.10430818051099777, 0.05946583300828934, 0.1521613895893097, 0.0027198679745197296, -0.11955620348453522, 0.15272516012191772, 0.019597480073571205, 0.046771518886089325, 0.010585903190076351, 0.00551711255684495, -0.033945243805646896, -0.028052575886249542, -0.10164355486631393, -0.021393585950136185, 0.10965092480182648, 0.05378110706806183, 0.015989184379577637, 0.12687379121780396, -0.1574660837650299, 0.1566983014345169, 0.03524959087371826, -0.055043723434209824, 0.06899215281009674, 0.07157100737094879, -0.15693296492099762, -0.10717277228832245, 0.10857164114713669, -0.19470973312854767, 0.17310135066509247, 0.1250462830066681, -0.011245634406805038, 0.17524544894695282, 0.0510169118642807, 0.08281503617763519, -0.024961885064840317, -0.042278505861759186, -0.16253487765789032, -0.0361114926636219, 0.07068263739347458, 0.029890701174736023, 0.0810069739818573, 0.013441303744912148]', 'face_3_1767784791.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kiosk_live`
--

CREATE TABLE `tbl_kiosk_live` (
  `id` int(11) NOT NULL,
  `StudentName` varchar(255) DEFAULT NULL,
  `UserImage` varchar(255) DEFAULT NULL,
  `ScanTime` datetime DEFAULT NULL,
  `Mode` varchar(50) DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_live_attendance`
--

CREATE TABLE `tbl_live_attendance` (
  `StudentID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `SlotID` int(11) NOT NULL,
  `FirstSeen` time DEFAULT NULL,
  `LastSeen` time DEFAULT NULL,
  `Status` enum('Present','Absent','Left Early') DEFAULT 'Absent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_live_attendance`
--

INSERT INTO `tbl_live_attendance` (`StudentID`, `Date`, `SlotID`, `FirstSeen`, `LastSeen`, `Status`) VALUES
(2, '2025-12-28', 1, '10:03:53', '10:36:29', 'Present'),
(2, '2025-12-28', 4, '02:21:47', '02:51:34', 'Present'),
(2, '2025-12-28', 999, '15:18:53', '15:18:53', 'Present'),
(3, '2025-12-28', 999, '17:09:54', '17:09:54', 'Present'),
(3, '2025-12-29', 999, '15:08:36', '15:08:36', 'Present'),
(3, '2025-12-31', 999, '00:07:39', '00:07:39', 'Present'),
(3, '2026-01-07', 999, '16:50:36', '16:50:36', 'Present'),
(3, '2026-01-08', 999, '01:11:29', '01:11:29', 'Present'),
(3, '2026-01-11', 999, '02:00:28', '02:00:28', 'Present'),
(3, '2026-01-15', 999, '00:05:57', '00:05:57', 'Present'),
(3, '2026-02-07', 999, '00:18:26', '00:18:26', 'Present'),
(3, '2026-02-11', 999, '01:39:53', '01:39:53', 'Present'),
(3, '2026-05-24', 999, '17:53:33', '17:53:33', 'Present'),
(3, '2026-05-30', 999, '01:25:04', '01:25:04', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_timetable_slots`
--

CREATE TABLE `tbl_timetable_slots` (
  `ID` int(11) NOT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `SlotType` enum('Class','Break') DEFAULT 'Class',
  `SubjectName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_timetable_slots`
--

INSERT INTO `tbl_timetable_slots` (`ID`, `StartTime`, `EndTime`, `SlotType`, `SubjectName`) VALUES
(1, '10:00:00', '11:00:00', 'Class', 'Maths'),
(2, '11:00:00', '11:15:00', 'Break', 'Tea Break'),
(3, '11:15:00', '12:15:00', 'Class', 'Physics'),
(4, '01:00:00', '03:00:00', 'Class', 'Late Night Testing');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` enum('present','absent') NOT NULL DEFAULT 'absent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `attendance_date`, `check_in_time`, `check_out_time`, `status`) VALUES
(1, 2, '2026-06-10', '01:34:27', '01:34:38', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_schedule`
--

CREATE TABLE `timetable_schedule` (
  `id` int(11) NOT NULL,
  `version_id` int(11) DEFAULT NULL,
  `batch_id` int(11) NOT NULL,
  `day_of_week` varchar(10) NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `classroom_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_schedule`
--

INSERT INTO `timetable_schedule` (`id`, `version_id`, `batch_id`, `day_of_week`, `start_time`, `end_time`, `subject_id`, `teacher_id`, `classroom_id`) VALUES
(61, NULL, 3, 'Monday', '09:00:00', '10:00:00', 2, 2, 5),
(62, NULL, 3, 'Monday', '10:00:00', '11:00:00', 2, 2, 1),
(63, NULL, 3, 'Monday', '11:00:00', '12:00:00', 2, 2, 1),
(64, NULL, 3, 'Monday', '12:00:00', '13:00:00', 2, 2, 1),
(65, NULL, 3, 'Monday', '14:00:00', '15:00:00', 2, 2, 1),
(66, NULL, 3, 'Monday', '15:00:00', '16:00:00', 2, 2, 1),
(67, NULL, 3, 'Tuesday', '09:00:00', '10:00:00', 2, 2, 1),
(68, NULL, 3, 'Tuesday', '10:00:00', '11:00:00', 2, 2, 1),
(69, NULL, 3, 'Tuesday', '11:00:00', '12:00:00', 2, 2, 1),
(70, NULL, 3, 'Tuesday', '12:00:00', '13:00:00', 2, 2, 1),
(71, NULL, 3, 'Tuesday', '14:00:00', '15:00:00', 2, 2, 1),
(72, NULL, 3, 'Tuesday', '15:00:00', '16:00:00', 2, 2, 1),
(73, NULL, 3, 'Wednesday', '09:00:00', '10:00:00', 2, 2, 1),
(74, NULL, 3, 'Wednesday', '10:00:00', '11:00:00', 2, 2, 1),
(75, NULL, 3, 'Wednesday', '11:00:00', '12:00:00', 2, 2, 1),
(76, NULL, 3, 'Wednesday', '12:00:00', '13:00:00', 2, 2, 1),
(77, NULL, 3, 'Wednesday', '14:00:00', '15:00:00', 2, 2, 1),
(78, NULL, 3, 'Wednesday', '15:00:00', '16:00:00', 2, 2, 1),
(79, NULL, 3, 'Thursday', '09:00:00', '10:00:00', 2, 2, 1),
(80, NULL, 3, 'Thursday', '10:00:00', '11:00:00', 2, 2, 1),
(81, NULL, 3, 'Thursday', '11:00:00', '12:00:00', 2, 2, 1),
(82, NULL, 3, 'Thursday', '12:00:00', '13:00:00', 2, 2, 1),
(83, NULL, 3, 'Thursday', '14:00:00', '15:00:00', 2, 2, 1),
(84, NULL, 3, 'Thursday', '15:00:00', '16:00:00', 2, 2, 1),
(85, NULL, 3, 'Friday', '09:00:00', '10:00:00', 2, 2, 1),
(86, NULL, 3, 'Friday', '10:00:00', '11:00:00', 2, 2, 1),
(87, NULL, 3, 'Friday', '11:00:00', '12:00:00', 2, 2, 1),
(88, NULL, 3, 'Friday', '12:00:00', '13:00:00', 2, 2, 1),
(89, NULL, 3, 'Friday', '14:00:00', '15:00:00', 2, 2, 1),
(90, NULL, 3, 'Friday', '15:00:00', '16:00:00', 2, 2, 1),
(121, NULL, 4, 'Monday', '09:00:00', '10:00:00', 4, 2, 5),
(122, NULL, 4, 'Monday', '10:00:00', '11:00:00', 5, 2, 5),
(123, NULL, 4, 'Monday', '11:00:00', '12:00:00', 4, 2, 5),
(124, NULL, 4, 'Monday', '12:00:00', '13:00:00', 5, 2, 5),
(125, NULL, 4, 'Monday', '14:00:00', '15:00:00', 4, 2, 5),
(126, NULL, 4, 'Monday', '15:00:00', '16:00:00', 5, 2, 5),
(127, NULL, 4, 'Tuesday', '09:00:00', '10:00:00', 4, 2, 5),
(128, NULL, 4, 'Tuesday', '10:00:00', '11:00:00', 5, 2, 5),
(129, NULL, 4, 'Tuesday', '11:00:00', '12:00:00', 4, 2, 5),
(130, NULL, 4, 'Tuesday', '12:00:00', '13:00:00', 5, 2, 5),
(131, NULL, 4, 'Tuesday', '14:00:00', '15:00:00', 4, 2, 5),
(132, NULL, 4, 'Tuesday', '15:00:00', '16:00:00', 5, 2, 5),
(133, NULL, 4, 'Wednesday', '09:00:00', '10:00:00', 4, 2, 5),
(134, NULL, 4, 'Wednesday', '10:00:00', '11:00:00', 5, 2, 5),
(135, NULL, 4, 'Wednesday', '11:00:00', '12:00:00', 4, 2, 5),
(136, NULL, 4, 'Wednesday', '12:00:00', '13:00:00', 5, 2, 5),
(137, NULL, 4, 'Wednesday', '14:00:00', '15:00:00', 4, 2, 5),
(138, NULL, 4, 'Wednesday', '15:00:00', '16:00:00', 5, 2, 5),
(139, NULL, 4, 'Thursday', '09:00:00', '10:00:00', 4, 2, 5),
(140, NULL, 4, 'Thursday', '10:00:00', '11:00:00', 5, 2, 5),
(141, NULL, 4, 'Thursday', '11:00:00', '12:00:00', 4, 2, 5),
(142, NULL, 4, 'Thursday', '12:00:00', '13:00:00', 5, 2, 5),
(143, NULL, 4, 'Thursday', '14:00:00', '15:00:00', 4, 2, 5),
(144, NULL, 4, 'Thursday', '15:00:00', '16:00:00', 5, 2, 5),
(145, NULL, 4, 'Friday', '09:00:00', '10:00:00', 4, 2, 5),
(146, NULL, 4, 'Friday', '10:00:00', '11:00:00', 5, 2, 5),
(147, NULL, 4, 'Friday', '11:00:00', '12:00:00', 4, 2, 5),
(148, NULL, 4, 'Friday', '12:00:00', '13:00:00', 5, 2, 5),
(149, NULL, 4, 'Friday', '14:00:00', '15:00:00', 4, 2, 5),
(150, NULL, 4, 'Friday', '15:00:00', '16:00:00', 5, 2, 5),
(151, NULL, 5, 'Monday', '09:00:00', '10:00:00', 4, 2, 5),
(152, NULL, 5, 'Monday', '10:00:00', '11:00:00', 5, 2, 5),
(153, NULL, 5, 'Monday', '11:00:00', '12:00:00', 4, 2, 5),
(154, NULL, 5, 'Monday', '12:00:00', '13:00:00', 5, 2, 5),
(155, NULL, 5, 'Monday', '14:00:00', '15:00:00', 4, 2, 5),
(156, NULL, 5, 'Monday', '15:00:00', '16:00:00', 5, 2, 5),
(157, NULL, 5, 'Tuesday', '09:00:00', '10:00:00', 4, 2, 5),
(158, NULL, 5, 'Tuesday', '10:00:00', '11:00:00', 5, 2, 5),
(159, NULL, 5, 'Tuesday', '11:00:00', '12:00:00', 4, 2, 5),
(160, NULL, 5, 'Tuesday', '12:00:00', '13:00:00', 5, 2, 5),
(161, NULL, 5, 'Tuesday', '14:00:00', '15:00:00', 4, 2, 5),
(162, NULL, 5, 'Tuesday', '15:00:00', '16:00:00', 5, 2, 5),
(163, NULL, 5, 'Wednesday', '09:00:00', '10:00:00', 4, 2, 5),
(164, NULL, 5, 'Wednesday', '10:00:00', '11:00:00', 5, 2, 5),
(165, NULL, 5, 'Wednesday', '11:00:00', '12:00:00', 4, 2, 5),
(166, NULL, 5, 'Wednesday', '12:00:00', '13:00:00', 5, 2, 5),
(167, NULL, 5, 'Wednesday', '14:00:00', '15:00:00', 4, 2, 5),
(168, NULL, 5, 'Wednesday', '15:00:00', '16:00:00', 5, 2, 5),
(169, NULL, 5, 'Thursday', '09:00:00', '10:00:00', 4, 2, 5),
(170, NULL, 5, 'Thursday', '10:00:00', '11:00:00', 5, 2, 5),
(171, NULL, 5, 'Thursday', '11:00:00', '12:00:00', 4, 2, 5),
(172, NULL, 5, 'Thursday', '12:00:00', '13:00:00', 5, 2, 5),
(173, NULL, 5, 'Thursday', '14:00:00', '15:00:00', 4, 2, 5),
(174, NULL, 5, 'Thursday', '15:00:00', '16:00:00', 5, 2, 5),
(175, NULL, 5, 'Friday', '09:00:00', '10:00:00', 4, 2, 5),
(176, NULL, 5, 'Friday', '10:00:00', '11:00:00', 5, 2, 5),
(177, NULL, 5, 'Friday', '11:00:00', '12:00:00', 4, 2, 5),
(178, NULL, 5, 'Friday', '12:00:00', '13:00:00', 5, 2, 5),
(179, NULL, 5, 'Friday', '14:00:00', '15:00:00', 4, 2, 5),
(180, NULL, 5, 'Friday', '15:00:00', '16:00:00', 5, 2, 5),
(181, NULL, 3, 'Monday', '13:00:00', '14:00:00', 4, 2, 0),
(182, NULL, 3, 'Thursday', '13:00:00', '14:00:00', 4, 2, 5),
(183, NULL, 3, 'Wednesday', '13:00:00', '14:00:00', 4, 2, 5),
(184, NULL, 3, 'Friday', '13:00:00', '14:00:00', 4, 2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_versions`
--

CREATE TABLE `timetable_versions` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classrooms`
--
ALTER TABLE `classrooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_attendance`
--
ALTER TABLE `class_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_records`
--
ALTER TABLE `lab_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_users`
--
ALTER TABLE `lab_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblassigment`
--
ALTER TABLE `tblassigment`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblaudit_logs`
--
ALTER TABLE `tblaudit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcourse`
--
ALTER TABLE `tblcourse`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblexams`
--
ALTER TABLE `tblexams`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblexam_answers`
--
ALTER TABLE `tblexam_answers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblexam_questions`
--
ALTER TABLE `tblexam_questions`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblexam_sessions`
--
ALTER TABLE `tblexam_sessions`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblfeedback`
--
ALTER TABLE `tblfeedback`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblliveinteraction`
--
ALTER TABLE `tblliveinteraction`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbllivesession`
--
ALTER TABLE `tbllivesession`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblnews`
--
ALTER TABLE `tblnews`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblnewsbyteacher`
--
ALTER TABLE `tblnewsbyteacher`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblquestions`
--
ALTER TABLE `tblquestions`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblreset_requests`
--
ALTER TABLE `tblreset_requests`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblsubject`
--
ALTER TABLE `tblsubject`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblsurveys`
--
ALTER TABLE `tblsurveys`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblsurvey_responses`
--
ALTER TABLE `tblsurvey_responses`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblteacher`
--
ALTER TABLE `tblteacher`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `qr_code_identifier` (`qr_code_identifier`);

--
-- Indexes for table `tblteachers`
--
ALTER TABLE `tblteachers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblteacher_reset_requests`
--
ALTER TABLE `tblteacher_reset_requests`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblteacher_subjects`
--
ALTER TABLE `tblteacher_subjects`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbluploadass`
--
ALTER TABLE `tbluploadass`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tbluser`
--
ALTER TABLE `tbluser`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `qr_code_identifier` (`qr_code_identifier`);

--
-- Indexes for table `tbl_kiosk_live`
--
ALTER TABLE `tbl_kiosk_live`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_live_attendance`
--
ALTER TABLE `tbl_live_attendance`
  ADD PRIMARY KEY (`StudentID`,`Date`,`SlotID`);

--
-- Indexes for table `tbl_timetable_slots`
--
ALTER TABLE `tbl_timetable_slots`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_date` (`teacher_id`,`attendance_date`);

--
-- Indexes for table `timetable_schedule`
--
ALTER TABLE `timetable_schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable_versions`
--
ALTER TABLE `timetable_versions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `classrooms`
--
ALTER TABLE `classrooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_attendance`
--
ALTER TABLE `class_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lab_records`
--
ALTER TABLE `lab_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lab_users`
--
ALTER TABLE `lab_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbladmin`
--
ALTER TABLE `tbladmin`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblassigment`
--
ALTER TABLE `tblassigment`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblaudit_logs`
--
ALTER TABLE `tblaudit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tblcourse`
--
ALTER TABLE `tblcourse`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblexams`
--
ALTER TABLE `tblexams`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tblexam_answers`
--
ALTER TABLE `tblexam_answers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tblexam_questions`
--
ALTER TABLE `tblexam_questions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tblexam_sessions`
--
ALTER TABLE `tblexam_sessions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tblfeedback`
--
ALTER TABLE `tblfeedback`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblliveinteraction`
--
ALTER TABLE `tblliveinteraction`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbllivesession`
--
ALTER TABLE `tbllivesession`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tblnews`
--
ALTER TABLE `tblnews`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tblnewsbyteacher`
--
ALTER TABLE `tblnewsbyteacher`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblquestions`
--
ALTER TABLE `tblquestions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblreset_requests`
--
ALTER TABLE `tblreset_requests`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblsubject`
--
ALTER TABLE `tblsubject`
  MODIFY `ID` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblsurveys`
--
ALTER TABLE `tblsurveys`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tblsurvey_responses`
--
ALTER TABLE `tblsurvey_responses`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tblteacher`
--
ALTER TABLE `tblteacher`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblteachers`
--
ALTER TABLE `tblteachers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblteacher_reset_requests`
--
ALTER TABLE `tblteacher_reset_requests`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblteacher_subjects`
--
ALTER TABLE `tblteacher_subjects`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tbluploadass`
--
ALTER TABLE `tbluploadass`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbluser`
--
ALTER TABLE `tbluser`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_timetable_slots`
--
ALTER TABLE `tbl_timetable_slots`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable_schedule`
--
ALTER TABLE `timetable_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `timetable_versions`
--
ALTER TABLE `timetable_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
