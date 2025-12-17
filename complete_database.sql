/*
SQL Dump for CCsearch Research Repository System
Generated for SQLyog import
Database: research_repository
*/

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `research_repository` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `research_repository`;

-- Set SQL mode and disable foreign key checks for import
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `student_information`
--
DROP TABLE IF EXISTS `student_information`;
CREATE TABLE `student_information` (
  `studentID` varchar(10) NOT NULL,
  `studentName` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  PRIMARY KEY (`studentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `student_information`
--
INSERT INTO `student_information` (`studentID`, `studentName`, `address`) VALUES
('2023-00001', 'Juan Dela Cruz', 'Quezon City'),
('2023-00002', 'Maria Santos', 'Makati City'),
('2023-00003', 'Pedro Reyes', 'Pasig City'),
('2023-00004', 'Ana Dizon', 'Manila City'),
('2023-00005', 'Mark Villanueva', 'Cebu City'),
('2023-00006', 'Liza Corpuz', 'Davao City'),
('2023-00007', 'James Tan', 'Caloocan City'),
('2023-00008', 'Carla Mendoza', 'Taguig City'),
('2023-00009', 'Rico Bautista', 'Pasay City'),
('2023-00010', 'Ella Soriano', 'Mandaluyong City'),
('2023-00011', 'Kevin Chua', 'San Juan City'),
('2023-00012', 'Jasmine Ramos', 'Valenzuela City'),
('2023-00013', 'Daniel Cruz', 'Quezon City'),
('2023-00014', 'Rose Hernandez', 'Las Piñas City'),
('2023-00015', 'Miguel Ortega', 'Muntinlupa City'),
('2023-00016', 'Patricia Lim', 'Baguio City'),
('2023-00017', 'Joseph Bautista', 'Iloilo City'),
('2023-00018', 'Angela Robles', 'Cavite'),
('2023-00019', 'Francis Uy', 'Laguna'),
('2023-00020', 'Nika Villareal', 'Batangas');

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--
DROP TABLE IF EXISTS `registration`;
CREATE TABLE `registration` (
  `studentID` varchar(10) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `contactNumber` varchar(15) NOT NULL,
  `currentAddress` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `emailAddress` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`studentID`),
  UNIQUE KEY `emailAddress` (`emailAddress`),
  CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_information` (`studentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `registration`
--
INSERT INTO `registration` (`studentID`, `firstName`, `lastName`, `contactNumber`, `currentAddress`, `department`, `emailAddress`, `password`) VALUES
('2023-00001', 'Nasser', 'Maxion', '09151686616', 'SAN NICOLAS', 'BSIT', 'nassermaxion21@gmail.com', '$2y$10$pPb6uEXMqHt.e6/wgoQRL.Cb8M6QR4rPK64ziwSkg.onidPWxtxc2'),
('2023-00002', 'Maxi', 'Mill', '09123465789', 'San Juan CSFP', 'BSIT', 'Maxi@gmail.com', '$2y$10$qPx5KgFdHihO.VSWu5deNecRa4D6LnM5WEgxhSB//PTBzG1/HI0wq');

-- --------------------------------------------------------

--
-- Table structure for table `publications`
--
DROP TABLE IF EXISTS `publications`;
CREATE TABLE `publications` (
  `publicationID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `published_datetime` datetime NOT NULL,
  `authors` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `abstract` text,
  `file_path` varchar(255) DEFAULT NULL,
  `bg_image` varchar(255) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `views` int(11) DEFAULT '0',
  PRIMARY KEY (`publicationID`),
  KEY `studentID` (`studentID`),
  CONSTRAINT `publications_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `registration` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `library`
--
DROP TABLE IF EXISTS `library`;
CREATE TABLE `library` (
  `libraryID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `publicationID` int(11) NOT NULL,
  `added_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`libraryID`),
  KEY `studentID` (`studentID`),
  KEY `publicationID` (`publicationID`),
  CONSTRAINT `library_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_information` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `library_ibfk_2` FOREIGN KEY (`publicationID`) REFERENCES `publications` (`publicationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `saved_publications`
--
DROP TABLE IF EXISTS `saved_publications`;
CREATE TABLE `saved_publications` (
  `savedID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `publicationID` int(11) NOT NULL,
  `saved_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`savedID`),
  KEY `studentID` (`studentID`),
  KEY `publicationID` (`publicationID`),
  CONSTRAINT `saved_publications_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `student_information` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `saved_publications_ibfk_2` FOREIGN KEY (`publicationID`) REFERENCES `publications` (`publicationID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--
DROP TABLE IF EXISTS `user_profiles`;
CREATE TABLE `user_profiles` (
  `profileID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` varchar(20) NOT NULL,
  `firstName` varchar(50) NOT NULL,
  `lastName` varchar(50) NOT NULL,
  `contactNumber` varchar(11) NOT NULL,
  `emailAddress` varchar(100) NOT NULL,
  `currentAddress` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `profileImage` varchar(255) DEFAULT 'uploads/profile.png',
  `theme_preference` varchar(10) DEFAULT 'light',
  `is_public` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`profileID`),
  UNIQUE KEY `studentID` (`studentID`),
  KEY `idx_studentID` (`studentID`),
  KEY `idx_is_public` (`is_public`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `registration` (`studentID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_profiles`
--
INSERT INTO `user_profiles` (`profileID`, `studentID`, `firstName`, `lastName`, `contactNumber`, `emailAddress`, `currentAddress`, `department`, `profileImage`, `theme_preference`, `is_public`, `created_at`, `updated_at`) VALUES
(3, '2023-00001', 'Nasser', 'Maxiondwadwa', '09151686616', 'nassermaxion21@gmail.com', 'SAN NICOLAS', 'BSIT', 'uploads/profiles/2023-00001_profile_1765023170.png', 'light', 0, '2025-12-06 19:39:52', '2025-12-06 20:15:26'),
(4, '2023-00002', 'Maxi', 'Milldwadwa', '09123465789', 'Maxi@gmail.com', 'San Juan CSFPDWA', 'BSIT', 'uploads/profiles/2023-00002_profile_1765023354.png', 'light', 0, '2025-12-06 20:01:18', '2025-12-06 20:15:54');

-- Commit the transaction
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;







