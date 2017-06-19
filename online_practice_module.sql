-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2017 at 03:14 PM
-- Server version: 10.1.22-MariaDB
-- PHP Version: 7.1.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `online_practice_module`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `insertCompletion` (IN `student` VARCHAR(255), IN `assignment_UUID` CHAR(38))  BEGIN
    DECLARE
        assignID INT ;
    SELECT
        id
    INTO
        assignID
    FROM
        assignment
    WHERE
        UUID = assignment_UUID ;
    INSERT
INTO COMPLETION
    (student_email, assignment_id)
VALUES(student, assignID) ;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` int(11) NOT NULL,
  `uuid` char(38) NOT NULL COMMENT 'uuid string with dashes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `completion`
--

CREATE TABLE `completion` (
  `id` int(11) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `completed_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assignment_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `completionreport`
-- (See below for the actual view)
--
CREATE TABLE `completionreport` (
`Id` int(11)
,`student_email` varchar(255)
,`title` varchar(255)
,`completed_on` datetime
,`name` varchar(255)
,`assignment_id` char(38)
);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`id`, `name`) VALUES
(1, 'Honors Chemistry'),
(2, 'Honors Physics');

-- --------------------------------------------------------

--
-- Structure for view `completionreport`
--
DROP TABLE IF EXISTS `completionreport`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `completionreport`  AS  select `c`.`id` AS `Id`,`c`.`student_email` AS `student_email`,`a`.`title` AS `title`,`c`.`completed_on` AS `completed_on`,`s`.`name` AS `name`,`a`.`uuid` AS `assignment_id` from ((`completion` `c` left join `assignment` `a` on((`c`.`assignment_id` = `a`.`id`))) left join `subject` `s` on((`a`.`subject` = `s`.`id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject_index` (`subject`),
  ADD KEY `uuid_2` (`uuid`);

--
-- Indexes for table `completion`
--
ALTER TABLE `completion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_email` (`student_email`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `completion`
--
ALTER TABLE `completion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `Has_subject` FOREIGN KEY (`subject`) REFERENCES `subject` (`id`);

--
-- Constraints for table `completion`
--
ALTER TABLE `completion`
  ADD CONSTRAINT `Has_Assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
