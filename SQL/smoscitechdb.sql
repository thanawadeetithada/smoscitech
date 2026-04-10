-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 11, 2026 at 12:24 AM
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
-- Database: `smoscitechdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `hours_count` int(11) DEFAULT 0 COMMENT 'จำนวนชั่วโมงกิจกรรม (สำหรับ Transcript กยศ.)',
  `cover_image` varchar(255) DEFAULT NULL,
  `status` enum('open','closed','completed') NOT NULL DEFAULT 'open',
  `created_by` int(11) NOT NULL COMMENT 'Link to users.user_id (ผู้สร้างกิจกรรม)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allowed_year_level` text DEFAULT NULL COMMENT 'ชั้นปีที่อนุญาตให้เข้าร่วม',
  `allowed_academic_year` text DEFAULT NULL COMMENT 'ปีการศึกษาที่อนุญาต',
  `allowed_department` text DEFAULT NULL COMMENT 'สาขาวิชาที่อนุญาต'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`activity_id`, `title`, `description`, `location`, `start_date`, `end_date`, `hours_count`, `cover_image`, `status`, `created_by`, `created_at`, `updated_at`, `allowed_year_level`, `allowed_academic_year`, `allowed_department`) VALUES
(11, 'ค่ายอาสา', 'รายละเอียดกิจกรรม วันที่ 1-5', 'หอประชุม', '2026-04-07 17:04:00', '2026-04-30 17:04:00', 10, 'cover_1775556284_69d4d6bc7b479.jpg', 'open', 19, '2026-04-07 10:04:44', '2026-04-07 10:04:44', 'ชั้นปีที่ 1', '2569', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_evidences`
--

CREATE TABLE `activity_evidences` (
  `evidence_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL COMMENT 'เชื่อมโยงว่ารูปนี้เป็นของใคร ในกิจกรรมไหน',
  `image_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL COMMENT 'รายละเอียดคำบรรยาย',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_evidences`
--

INSERT INTO `activity_evidences` (`evidence_id`, `registration_id`, `image_path`, `description`, `uploaded_at`) VALUES
(6, 15, 'user_18_ev_1775557462.jpg', 'sssssss', '2026-04-07 10:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `activity_registrations`
--

CREATE TABLE `activity_registrations` (
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL COMMENT 'หน้าที่ที่เลือก (ถ้ามี)',
  `registration_status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `participation_status` enum('waiting','passed','not_passed') DEFAULT 'waiting' COMMENT 'สถานะผลการเข้าร่วม (ผ่าน/ไม่ผ่าน) ตามข้อ 5.4',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_registrations`
--

INSERT INTO `activity_registrations` (`registration_id`, `user_id`, `activity_id`, `task_id`, `registration_status`, `participation_status`, `registered_at`) VALUES
(15, 18, 11, 46, 'approved', 'passed', '2026-04-07 10:11:11');

-- --------------------------------------------------------

--
-- Table structure for table `activity_tasks`
--

CREATE TABLE `activity_tasks` (
  `task_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `task_name` varchar(100) NOT NULL,
  `task_detail` text DEFAULT NULL,
  `capacity` int(11) DEFAULT 0 COMMENT 'จำนวนที่รับสมัครในตำแหน่งนี้'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_tasks`
--

INSERT INTO `activity_tasks` (`task_id`, `activity_id`, `task_name`, `task_detail`, `capacity`) VALUES
(46, 11, 'ดูแล', 'รายละเอียด (ระบุได้)', 5);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT 'default.png',
  `idstudent` varchar(191) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `about_me` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `academic_year` varchar(4) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `userrole` enum('executive','academic_officer','club_president','club_member') DEFAULT NULL,
  `membership_status` enum('member','no_member') NOT NULL DEFAULT 'no_member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `hard_skill_computer` varchar(255) DEFAULT NULL,
  `comp_level` varchar(50) DEFAULT NULL,
  `hard_skill_lang` varchar(255) DEFAULT NULL,
  `lang_listen` varchar(50) DEFAULT NULL,
  `lang_speak` varchar(50) DEFAULT NULL,
  `lang_read` varchar(50) DEFAULT NULL,
  `lang_write` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `profile_image`, `idstudent`, `email`, `phone`, `about_me`, `password`, `academic_year`, `year_level`, `department`, `reset_token`, `reset_expiry`, `status`, `userrole`, `membership_status`, `created_at`, `deleted_at`, `hard_skill_computer`, `comp_level`, `hard_skill_lang`, `lang_listen`, `lang_speak`, `lang_read`, `lang_write`) VALUES
(15, 'ผู้บริหาร', 'executive', '1_1771686035.png', '4', 'test4@gmail.com', '585หห', NULL, '$2y$10$QZOZZ/mojFmDBGZLKTOJDeEbggwRxS5wLpjNSaCPJipjmYIRh.A.i', '2569', 'ชั้นปีที่ 4', 'วิทยาการคอมพิวเตอร์', NULL, NULL, 'active', 'executive', 'member', '2026-02-13 07:42:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'สมาชิกสโมร', 'club_member', 'user_18_1775857659.jpg', '1', 'test1@gmail.com', '585', 'กกกกกแนะนำตัวเอง (About Me)แนะนำตัวเอง (About Me)แนะนำตัวเอง (About Me)แนะนำตัวเอง (About Me)', '$2y$10$9b1Owjhi11P2XwGsIQR8J.j7AxZiH3obQjT3VcX3aL6TMufBfvf2C', '2569', 'ชั้นปีที่ 1', 'วิทยาการคอมพิวเตอร์', NULL, NULL, 'active', 'club_member', 'member', '2026-02-21 15:00:45', NULL, 'Python', 'ดีเยี่ยม', '', 'Array', 'Array', 'Array', 'Array'),
(19, 'นายก/รองนายกสโมสรนักศึกษา', 'club_president', 'user_19_1775856658.png', '2', 'test2@gmail.com', '585', NULL, '$2y$10$e3aya9edCqrJs2.uvU.yau82w3vYQ7t1WVOknHDyRx26QlEVPuNza', '2569', 'ชั้นปีที่ 1', 'เทคโนโลยีสารสนเทศ', NULL, NULL, 'active', 'club_president', 'member', '2026-02-21 15:06:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'นักวิชาการศึกษา', 'academic_officer', '1_1771686035.png', '3', 'test3@gmail.com', '585', NULL, '$2y$10$TTihFVVIj/UJSyb88XBxWewetOoMvnMFWgrH5dECRuNfj0gtfgP6G', '2569', 'ชั้นปีที่ 2', 'ชีววิทยา', NULL, NULL, 'active', 'academic_officer', 'member', '2026-02-22 03:23:18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, '5', '5', 'default.png', '5', 'thanawadeetit@gmail.com', '1111111111', NULL, '$2y$10$ltUTjqWczhdIxwNdZ4is/O63mBpI/P/Dxipl/6/o.e3nlcg5E9Ove', '2569', 'ชั้นปีที่ 1', 'วิทยาการคอมพิวเตอร์', NULL, NULL, 'active', 'club_member', 'no_member', '2026-04-10 21:48:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_custom_activities`
--

CREATE TABLE `user_custom_activities` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_hard_skills`
--

CREATE TABLE `user_hard_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_hard_skills`
--

INSERT INTO `user_hard_skills` (`id`, `user_id`, `skill_name`, `skill_level`) VALUES
(2, 18, 'Photosho', 'ดีเยี่ยม');

-- --------------------------------------------------------

--
-- Table structure for table `user_languages`
--

CREATE TABLE `user_languages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lang_name` varchar(255) NOT NULL,
  `lang_listen` varchar(50) DEFAULT NULL,
  `lang_speak` varchar(50) DEFAULT NULL,
  `lang_read` varchar(50) DEFAULT NULL,
  `lang_write` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_skills`
--

CREATE TABLE `user_skills` (
  `skill_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL COMMENT 'เช่น การทำงานเป็นทีม, การสื่อสาร',
  `skill_level` int(1) NOT NULL DEFAULT 1 COMMENT 'ระดับคะแนน (เช่น 1-5)',
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_skills`
--

INSERT INTO `user_skills` (`skill_id`, `user_id`, `skill_name`, `skill_level`, `evaluated_at`) VALUES
(61, 18, 'การสื่อสารที่ดี', 5, '2026-04-10 22:23:27'),
(62, 18, 'การทำงานเป็นทีม', 5, '2026-04-10 22:23:27'),
(63, 18, 'การแก้ปัญหาเฉพาะหน้า', 5, '2026-04-10 22:23:27'),
(64, 18, 'การคิดวิเคราะห์', 5, '2026-04-10 22:23:27'),
(65, 18, 'การบริหารเวลา', 5, '2026-04-10 22:23:27'),
(66, 18, 'ความรับผิดชอบต่อหน้าที่', 5, '2026-04-10 22:23:28'),
(67, 18, 'ความคิดสร้างสรรค์', 5, '2026-04-10 22:23:28'),
(68, 18, 'การปรับตัวเข้ากับสถานการณ์', 5, '2026-04-10 22:23:28'),
(69, 18, 'ภาวะผู้นำ', 5, '2026-04-10 22:23:28'),
(70, 18, 'การจัดการความเครียด', 5, '2026-04-10 22:23:28'),
(71, 18, 'การมีมนุษยสัมพันธ์ที่ดี', 5, '2026-04-10 22:23:28'),
(72, 18, 'ความละเอียดรอบคอบ', 5, '2026-04-10 22:23:28'),
(73, 18, 'ความมีวินัย', 5, '2026-04-10 22:23:28'),
(74, 18, 'การรับฟังความคิดเห็นผู้อื่น', 5, '2026-04-10 22:23:28'),
(75, 18, 'การตัดสินใจอย่างมีเหตุผล', 5, '2026-04-10 22:23:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `activity_evidences`
--
ALTER TABLE `activity_evidences`
  ADD PRIMARY KEY (`evidence_id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `activity_registrations`
--
ALTER TABLE `activity_registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `activity_tasks`
--
ALTER TABLE `activity_tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_custom_activities`
--
ALTER TABLE `user_custom_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_hard_skills`
--
ALTER TABLE `user_hard_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_languages`
--
ALTER TABLE `user_languages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `activity_evidences`
--
ALTER TABLE `activity_evidences`
  MODIFY `evidence_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `activity_registrations`
--
ALTER TABLE `activity_registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `activity_tasks`
--
ALTER TABLE `activity_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `user_custom_activities`
--
ALTER TABLE `user_custom_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_hard_skills`
--
ALTER TABLE `user_hard_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_languages`
--
ALTER TABLE `user_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_skills`
--
ALTER TABLE `user_skills`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `fk_activity_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_evidences`
--
ALTER TABLE `activity_evidences`
  ADD CONSTRAINT `fk_evidence_reg` FOREIGN KEY (`registration_id`) REFERENCES `activity_registrations` (`registration_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_registrations`
--
ALTER TABLE `activity_registrations`
  ADD CONSTRAINT `fk_reg_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reg_task` FOREIGN KEY (`task_id`) REFERENCES `activity_tasks` (`task_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_tasks`
--
ALTER TABLE `activity_tasks`
  ADD CONSTRAINT `fk_task_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_custom_activities`
--
ALTER TABLE `user_custom_activities`
  ADD CONSTRAINT `fk_custom_act_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_hard_skills`
--
ALTER TABLE `user_hard_skills`
  ADD CONSTRAINT `fk_hardskill_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_languages`
--
ALTER TABLE `user_languages`
  ADD CONSTRAINT `fk_lang_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skills`
--
ALTER TABLE `user_skills`
  ADD CONSTRAINT `fk_skill_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
