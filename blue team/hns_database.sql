-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2026 at 08:03 PM
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
-- Database: `hns_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `hns_caretaker_auth`
--

CREATE TABLE `hns_caretaker_auth` (
  `authorization_id` int(11) NOT NULL,
  `patient_user_id` int(11) NOT NULL,
  `authorization_code_hash` char(64) NOT NULL,
  `expiration_time` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hns_caretaker_patients`
--

CREATE TABLE `hns_caretaker_patients` (
  `connection_id` int(11) NOT NULL,
  `caretaker_user_id` int(11) NOT NULL,
  `patient_user_id` int(11) NOT NULL,
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Revoked') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hns_caretaker_patients`
--

INSERT INTO `hns_caretaker_patients` (`connection_id`, `caretaker_user_id`, `patient_user_id`, `connected_at`, `status`) VALUES
(1, 7, 1, '2026-11-17 18:16:33', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `hns_users`
--

CREATE TABLE `hns_users` (
  `HNS_user_id` int(11) NOT NULL,
  `HNS_username` varchar(50) NOT NULL,
  `HNS_password` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `HNS_user_type` enum('Caretaker','Patient') NOT NULL,
  `HNS_first_name` varchar(100) NOT NULL,
  `HNS_last_name` varchar(100) NOT NULL,
  `hospital_patient_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hns_users`
--

INSERT INTO `hns_users` (`HNS_user_id`, `HNS_username`, `HNS_password`, `Email`, `HNS_user_type`, `HNS_first_name`, `HNS_last_name`, `hospital_patient_id`) VALUES
(1, 'ThePatient67', 'J0hnnySilv3rh@nd', 'john.patient@Bmail.com', 'Patient', 'John', 'Patient', 9),
(3, 'Bravado', '12345678', 'Brochacho@yoohoo.mail', 'Patient', 'John', 'Bravo', NULL),
(6, 'user1', 'password1', 'fake@fakemail.fake', 'Patient', 'guy', 'person', NULL),
(7, 'TheCareTaker67', 'Pa$$w0rd', 'caretaker67@chess.com', 'Caretaker', 'Ramos', 'Cuidador', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hns_caretaker_auth`
--
ALTER TABLE `hns_caretaker_auth`
  ADD PRIMARY KEY (`authorization_id`),
  ADD UNIQUE KEY `authorization_code_hash` (`authorization_code_hash`),
  ADD KEY `idx_patient_user` (`patient_user_id`),
  ADD KEY `idx_expiration` (`expiration_time`);

--
-- Indexes for table `hns_caretaker_patients`
--
ALTER TABLE `hns_caretaker_patients`
  ADD PRIMARY KEY (`connection_id`),
  ADD UNIQUE KEY `unique_caretaker_patient` (`caretaker_user_id`,`patient_user_id`),
  ADD KEY `idx_patient_user` (`patient_user_id`),
  ADD KEY `idx_caretaker_user` (`caretaker_user_id`);

--
-- Indexes for table `hns_users`
--
ALTER TABLE `hns_users`
  ADD PRIMARY KEY (`HNS_user_id`),
  ADD UNIQUE KEY `HNS_username` (`HNS_username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hns_caretaker_auth`
--
ALTER TABLE `hns_caretaker_auth`
  MODIFY `authorization_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hns_caretaker_patients`
--
ALTER TABLE `hns_caretaker_patients`
  MODIFY `connection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hns_users`
--
ALTER TABLE `hns_users`
  MODIFY `HNS_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
