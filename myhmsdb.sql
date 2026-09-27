CREATE DATABASE IF NOT EXISTS `myhmsdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `myhmsdb`;

DROP TABLE IF EXISTS `appointment_table`;
DROP TABLE IF EXISTS `patient_registration`;
DROP TABLE IF EXISTS `doctor_table`;
DROP TABLE IF EXISTS `admin_table`;

CREATE TABLE `admin_table` (
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin_table` (`username`, `password`) VALUES
('admin', 'admin123');

CREATE TABLE `doctor_table` (
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `email` varchar(80) NOT NULL,
  `spec` varchar(80) NOT NULL,
  `docFees` int(10) NOT NULL DEFAULT 0,
  `available_days` varchar(30) NOT NULL DEFAULT '1,2,3,4,5',
  `available_start` time NOT NULL DEFAULT '09:00:00',
  `available_end` time NOT NULL DEFAULT '17:00:00',
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `doctor_table` (`username`, `password`, `email`, `spec`, `docFees`, `available_days`, `available_start`, `available_end`) VALUES
('ashok', 'ashok123', 'ashok@gmail.com', 'General', 500, '1,2,3,4,5', '09:00:00', '17:00:00'),
('arun', 'arun123', 'arun@gmail.com', 'Cardiologist', 600, '1,3,5', '10:00:00', '16:00:00'),
('Dinesh', 'dinesh123', 'dinesh@gmail.com', 'General', 700, '0,2,4', '08:30:00', '14:30:00'),
('Ganesh', 'ganesh123', 'ganesh@gmail.com', 'Pediatrician', 550, '1,2,3,4,5,6', '09:30:00', '15:30:00');

CREATE TABLE `patient_registration` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(80) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `password` varchar(50) NOT NULL,
  `cpassword` varchar(50) NOT NULL,
  PRIMARY KEY (`pid`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `patient_registration` (`pid`, `fname`, `lname`, `gender`, `email`, `contact`, `password`, `cpassword`) VALUES
(1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', 'ram123', 'ram123'),
(2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', 'alia123', 'alia123');

CREATE TABLE `appointment_table` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(80) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `doctor` varchar(50) NOT NULL,
  `docFees` int(10) NOT NULL DEFAULT 0,
  `appdate` date NOT NULL,
  `apptime` time NOT NULL,
  `userStatus` tinyint(1) NOT NULL DEFAULT 1,
  `doctorStatus` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  KEY `pid` (`pid`),
  KEY `doctor` (`doctor`),
  KEY `doctor_slot_status` (`doctor`, `appdate`, `apptime`, `userStatus`, `doctorStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `appointment_table` (`ID`, `pid`, `fname`, `lname`, `gender`, `email`, `contact`, `doctor`, `docFees`, `appdate`, `apptime`, `userStatus`, `doctorStatus`) VALUES
(1, 1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', 'ashok', 500, '2026-07-20', '10:00:00', 1, 1),
(2, 2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', 'Ganesh', 550, '2026-07-21', '12:30:00', 1, 1);
