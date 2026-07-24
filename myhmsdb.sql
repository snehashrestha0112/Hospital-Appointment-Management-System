CREATE DATABASE IF NOT EXISTS `myhmsdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `myhmsdb`;

DROP TABLE IF EXISTS `appointmenttb`;
DROP TABLE IF EXISTS `patreg`;
DROP TABLE IF EXISTS `doctb`;
DROP TABLE IF EXISTS `admintb`;

CREATE TABLE `admintb` (
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admintb` (`username`, `password`) VALUES
('admin', 'admin123');

CREATE TABLE `doctb` (
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `email` varchar(80) NOT NULL,
  `spec` varchar(80) NOT NULL,
  `docFees` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `doctb` (`username`, `password`, `email`, `spec`, `docFees`) VALUES
('ashok', 'ashok123', 'ashok@gmail.com', 'General', 500),
('arun', 'arun123', 'arun@gmail.com', 'Cardiologist', 600),
('Dinesh', 'dinesh123', 'dinesh@gmail.com', 'General', 700),
('Ganesh', 'ganesh123', 'ganesh@gmail.com', 'Pediatrician', 550);

CREATE TABLE `patreg` (
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

INSERT INTO `patreg` (`pid`, `fname`, `lname`, `gender`, `email`, `contact`, `password`, `cpassword`) VALUES
(1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', 'ram123', 'ram123'),
(2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', 'alia123', 'alia123');

CREATE TABLE `appointmenttb` (
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
  KEY `doctor` (`doctor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `appointmenttb` (`ID`, `pid`, `fname`, `lname`, `gender`, `email`, `contact`, `doctor`, `docFees`, `appdate`, `apptime`, `userStatus`, `doctorStatus`) VALUES
(1, 1, 'Ram', 'Kumar', 'Male', 'ram@gmail.com', '9876543210', 'ashok', 500, '2026-07-20', '10:00:00', 1, 1),
(2, 2, 'Alia', 'Bhatt', 'Female', 'alia@gmail.com', '8976897689', 'Ganesh', 550, '2026-07-21', '12:30:00', 1, 1);
