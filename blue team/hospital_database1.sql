-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2026 at 01:25 PM
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
-- Database: `hospital_database1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admissions`
--

CREATE TABLE `admissions` (
  `admission_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `bed_id` int(11) NOT NULL,
  `admitting_encounter_id` int(11) DEFAULT NULL,
  `admission_date` datetime NOT NULL,
  `discharge_date` datetime DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `appointment_time` datetime NOT NULL,
  `status` enum('Scheduled','Checked In','Completed','Cancelled','No Show') DEFAULT 'Scheduled',
  `reason` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `patient_id`, `staff_id`, `department_id`, `appointment_time`, `status`, `reason`) VALUES
(3, 9, 7, 5, '2026-09-13 10:00:00', 'Scheduled', 'Office Visit');

-- --------------------------------------------------------

--
-- Table structure for table `beds`
--

CREATE TABLE `beds` (
  `bed_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `bed_label` varchar(10) NOT NULL,
  `bed_status` enum('Available','Occupied','Maintenance') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(80) NOT NULL,
  `floor_number` int(11) DEFAULT NULL,
  `extension` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `floor_number`, `extension`) VALUES
(1, 'General Medicine', 1, '101'),
(2, 'Pediatrics', 2, '102'),
(3, 'Emergency Medicine', 1, '103'),
(4, 'Neurology', 3, '104'),
(5, 'Cardiology', 3, '105');

-- --------------------------------------------------------

--
-- Table structure for table `diagnoses`
--

CREATE TABLE `diagnoses` (
  `diagnosis_id` int(11) NOT NULL,
  `diagnosis_code` varchar(15) NOT NULL,
  `diagnosis_name` varchar(120) NOT NULL,
  `category` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnoses`
--

INSERT INTO `diagnoses` (`diagnosis_id`, `diagnosis_code`, `diagnosis_name`, `category`) VALUES
(1, 'DX-001', 'Hypertension', 'Cardiovascular'),
(2, 'DX-002', 'Migraine', 'Neurological'),
(3, 'DX-003', 'Seasonal Allergies', 'Immunological'),
(4, 'DX-004', 'Type 2 Diabetes', 'Endocrine'),
(5, 'DX-005', 'Asthma', 'Respiratory'),
(6, 'DX-006', 'Sprained Ankle', 'Musculoskeletal'),
(7, 'DX-007', 'Iron Deficiency Anemia', 'Hematological'),
(8, 'DX-008', 'Viral Infection', 'Infectious');

-- --------------------------------------------------------

--
-- Table structure for table `encounters`
--

CREATE TABLE `encounters` (
  `encounter_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `attending_staff_id` int(11) NOT NULL,
  `encounter_time` datetime NOT NULL,
  `encounter_type` enum('Outpatient','Emergency','Inpatient','Follow-up') NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `encounter_diagnoses`
--

CREATE TABLE `encounter_diagnoses` (
  `encounter_id` int(11) NOT NULL,
  `diagnosis_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `diagnosis_notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_providers`
--

CREATE TABLE `insurance_providers` (
  `provider_id` int(11) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_providers`
--

INSERT INTO `insurance_providers` (`provider_id`, `provider_name`, `phone`, `website`) VALUES
(1, 'Northstar Health', '555-0301', 'https://northstar.example'),
(2, 'Lakeside Insurance', '555-0302', 'https://lakeside.example'),
(3, 'Community Health Plan', '555-0303', 'https://community.example'),
(4, 'Pine Valley Coverage', '555-0304', 'https://pinevalley.example');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_staff`
--

CREATE TABLE `insurance_staff` (
  `provider_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `accepted_from` date NOT NULL,
  `accepted_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `insurance_staff`
--

INSERT INTO `insurance_staff` (`provider_id`, `staff_id`, `accepted_from`, `accepted_until`) VALUES
(1, 3, '2026-11-15', NULL),
(1, 7, '2026-11-15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `issued_at` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Issued','Paid','Overdue','Cancelled') DEFAULT 'Draft'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `medications`
--

CREATE TABLE `medications` (
  `medication_id` int(11) NOT NULL,
  `medication_name` varchar(100) NOT NULL,
  `strength` varchar(40) DEFAULT NULL,
  `dosage_form` varchar(40) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1
) ;

--
-- Dumping data for table `medications`
--

INSERT INTO `medications` (`medication_id`, `medication_name`, `strength`, `dosage_form`, `unit_price`, `active`) VALUES
(1, 'Example Medicine A', '10 mg', 'Tablet', 4.50, 1),
(2, 'Example Medicine B', '250 mg', 'Capsule', 8.25, 1),
(3, 'Example Medicine C', '5 mg', 'Tablet', 3.75, 1),
(4, 'Example Medicine D', '100 mg', 'Tablet', 6.00, 1),
(5, 'Example Medicine E', '20 mg/mL', 'Liquid', 9.50, 1),
(6, 'Example Medicine F', '50 mcg', 'Inhaler', 15.00, 1),
(7, 'Example Medicine G', '500 mg', 'Tablet', 7.25, 1),
(8, 'Example Medicine H', '25 mg', 'Tablet', 5.50, 1);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `medical_record_no` varchar(15) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_type` varchar(3) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `medical_record_no`, `first_name`, `last_name`, `date_of_birth`, `blood_type`, `phone`, `created_at`) VALUES
(1, 'MR-1000', 'Arthur', 'Morgan', '1863-06-22', 'O+', '555-0101', '2026-11-06 01:30:16'),
(2, 'MR-1001', 'Biddy', 'Blais', '2000-03-04', 'A+', '555-0102', '2026-11-06 01:30:16'),
(3, 'MR-1002', 'Jackie', 'Welles', '2006-05-26', 'B-', '555-0103', '2026-11-06 01:30:16'),
(4, 'MR-1003', 'Gordon', 'Freeman', '1973-03-14', 'AB+', '555-0104', '2026-11-06 01:30:16'),
(5, 'MR-1004', 'Peter', 'Parker', '2001-08-10', 'A-', '555-0105', '2026-11-06 01:30:16'),
(6, 'MR-1005', 'Leon', 'Kennedy', '1977-07-31', 'A-', '555-0106', '2026-11-06 01:30:16'),
(7, 'MR-1006', 'Cave', 'Johnson', '1922-04-19', 'B+', '555-0107', '2026-11-06 01:30:16'),
(8, 'MR-1007', 'Baby', 'Prunes', '1868-12-05', 'O+', '555-0108', '2026-11-06 01:30:16'),
(9, 'MR-1008', 'John', 'Patient', '1957-11-07', 'O+', '555-0109', '2026-11-06 01:59:47');

-- --------------------------------------------------------

--
-- Table structure for table `patient_insurance`
--

CREATE TABLE `patient_insurance` (
  `insurance_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `policy_number` varchar(50) NOT NULL,
  `group_number` varchar(50) DEFAULT NULL,
  `insurance_type` enum('Private','Public','Employer','Other') NOT NULL DEFAULT 'Other',
  `coverage_start` date NOT NULL,
  `coverage_end` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `patient_insurance`
--

INSERT INTO `patient_insurance` (`insurance_id`, `patient_id`, `provider_id`, `policy_number`, `group_number`, `insurance_type`, `coverage_start`, `coverage_end`, `is_primary`) VALUES
(1, 9, 1, 'NS-JP-2026-001', 'NS-100', 'Private', '2026-11-15', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int(11) NOT NULL,
  `encounter_id` int(11) NOT NULL,
  `prescribing_staff_id` int(11) NOT NULL,
  `prescribed_at` datetime NOT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `prescription_id` int(11) NOT NULL,
  `medication_id` int(11) NOT NULL,
  `dosage_instructions` varchar(200) NOT NULL,
  `frequency` varchar(60) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type` enum('Standard','Private','ICU') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `employee_no` varchar(15) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `job_title` varchar(60) NOT NULL,
  `hire_date` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `employee_no`, `first_name`, `last_name`, `job_title`, `hire_date`, `active`) VALUES
(1, 'EMP-001', 'Nick', 'Riviera', 'Physician', '2018-03-12', 1),
(2, 'EMP-002', 'Casey', 'Wright', 'Pediatrician', '2020-06-15', 1),
(3, 'EMP-003', 'Leonard', 'McCoy', 'Emergency Physician', '2019-09-01', 1),
(4, 'EMP-004', 'Gregory', 'House', 'Neurologist', '2000-02-20', 1),
(5, 'EMP-005', 'Drew', 'Collins', 'Nurse', '2021-04-08', 1),
(6, 'EMP-006', 'Leo', 'Spaceman', 'Cardiologist', '2016-11-10', 1),
(7, 'EMP-007', 'Victor', 'von Doom', 'Cardiologist', '2026-11-15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_department_assignments`
--

CREATE TABLE `staff_department_assignments` (
  `assignment_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ;

--
-- Dumping data for table `staff_department_assignments`
--

INSERT INTO `staff_department_assignments` (`assignment_id`, `staff_id`, `department_id`, `start_date`, `end_date`, `is_primary`) VALUES
(2, 1, 1, '2018-03-12', NULL, 1),
(3, 2, 2, '2020-06-15', NULL, 1),
(4, 3, 3, '2019-09-01', NULL, 1),
(5, 4, 4, '2000-02-20', NULL, 1),
(6, 6, 5, '2016-11-10', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `ward_id` int(11) NOT NULL,
  `ward_name` varchar(80) NOT NULL,
  `ward_type` varchar(50) DEFAULT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admissions`
--
ALTER TABLE `admissions`
  ADD PRIMARY KEY (`admission_id`),
  ADD KEY `bed_id` (`bed_id`),
  ADD KEY `admitting_encounter_id` (`admitting_encounter_id`),
  ADD KEY `idx_admission_patient_dates` (`patient_id`,`admission_date`,`discharge_date`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `idx_appointment_time` (`appointment_time`,`status`);

--
-- Indexes for table `beds`
--
ALTER TABLE `beds`
  ADD PRIMARY KEY (`bed_id`),
  ADD UNIQUE KEY `room_id` (`room_id`,`bed_label`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_name` (`department_name`);

--
-- Indexes for table `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD UNIQUE KEY `diagnosis_code` (`diagnosis_code`);

--
-- Indexes for table `encounters`
--
ALTER TABLE `encounters`
  ADD PRIMARY KEY (`encounter_id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `attending_staff_id` (`attending_staff_id`),
  ADD KEY `idx_encounter_patient_time` (`patient_id`,`encounter_time`);

--
-- Indexes for table `encounter_diagnoses`
--
ALTER TABLE `encounter_diagnoses`
  ADD PRIMARY KEY (`encounter_id`,`diagnosis_id`),
  ADD KEY `diagnosis_id` (`diagnosis_id`);

--
-- Indexes for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  ADD PRIMARY KEY (`provider_id`),
  ADD UNIQUE KEY `provider_name` (`provider_name`);

--
-- Indexes for table `insurance_staff`
--
ALTER TABLE `insurance_staff`
  ADD PRIMARY KEY (`provider_id`,`staff_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `encounter_id` (`encounter_id`),
  ADD KEY `idx_invoice_patient` (`patient_id`,`status`);

--
-- Indexes for table `medications`
--
ALTER TABLE `medications`
  ADD PRIMARY KEY (`medication_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `medical_record_no` (`medical_record_no`),
  ADD KEY `idx_patient_name` (`last_name`,`first_name`);

--
-- Indexes for table `patient_insurance`
--
ALTER TABLE `patient_insurance`
  ADD PRIMARY KEY (`insurance_id`),
  ADD UNIQUE KEY `provider_id` (`provider_id`,`policy_number`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `encounter_id` (`encounter_id`),
  ADD KEY `prescribing_staff_id` (`prescribing_staff_id`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`prescription_id`,`medication_id`),
  ADD KEY `medication_id` (`medication_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `ward_id` (`ward_id`,`room_number`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`);

--
-- Indexes for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`,`department_id`,`start_date`),
  ADD KEY `idx_assignment_department` (`department_id`,`staff_id`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`ward_id`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admissions`
--
ALTER TABLE `admissions`
  MODIFY `admission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `beds`
--
ALTER TABLE `beds`
  MODIFY `bed_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `diagnoses`
--
ALTER TABLE `diagnoses`
  MODIFY `diagnosis_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `encounters`
--
ALTER TABLE `encounters`
  MODIFY `encounter_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurance_providers`
--
ALTER TABLE `insurance_providers`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medications`
--
ALTER TABLE `medications`
  MODIFY `medication_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `patient_insurance`
--
ALTER TABLE `patient_insurance`
  MODIFY `insurance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admissions`
--
ALTER TABLE `admissions`
  ADD CONSTRAINT `admissions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `admissions_ibfk_2` FOREIGN KEY (`bed_id`) REFERENCES `beds` (`bed_id`),
  ADD CONSTRAINT `admissions_ibfk_3` FOREIGN KEY (`admitting_encounter_id`) REFERENCES `encounters` (`encounter_id`);

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `beds`
--
ALTER TABLE `beds`
  ADD CONSTRAINT `beds_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `encounters`
--
ALTER TABLE `encounters`
  ADD CONSTRAINT `encounters_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `encounters_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`),
  ADD CONSTRAINT `encounters_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `encounters_ibfk_4` FOREIGN KEY (`attending_staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `encounter_diagnoses`
--
ALTER TABLE `encounter_diagnoses`
  ADD CONSTRAINT `encounter_diagnoses_ibfk_1` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`encounter_id`),
  ADD CONSTRAINT `encounter_diagnoses_ibfk_2` FOREIGN KEY (`diagnosis_id`) REFERENCES `diagnoses` (`diagnosis_id`);

--
-- Constraints for table `insurance_staff`
--
ALTER TABLE `insurance_staff`
  ADD CONSTRAINT `insurance_staff_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `insurance_providers` (`provider_id`),
  ADD CONSTRAINT `insurance_staff_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`encounter_id`);

--
-- Constraints for table `patient_insurance`
--
ALTER TABLE `patient_insurance`
  ADD CONSTRAINT `patient_insurance_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `patient_insurance_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `insurance_providers` (`provider_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`encounter_id`) REFERENCES `encounters` (`encounter_id`),
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`prescribing_staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `prescription_items_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`),
  ADD CONSTRAINT `prescription_items_ibfk_2` FOREIGN KEY (`medication_id`) REFERENCES `medications` (`medication_id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`ward_id`);

--
-- Constraints for table `staff_department_assignments`
--
ALTER TABLE `staff_department_assignments`
  ADD CONSTRAINT `staff_department_assignments_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `staff_department_assignments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `wards`
--
ALTER TABLE `wards`
  ADD CONSTRAINT `wards_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
