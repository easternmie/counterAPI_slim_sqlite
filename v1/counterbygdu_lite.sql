-- phpMyAdmin SQL Dump
-- version 4.5.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2016 at 01:52 AM
-- Server version: 5.7.9
-- PHP Version: 7.0.0



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: counterbygdu
--

-- --------------------------------------------------------

--
-- Table structure for table livefeed
--

DROP TABLE IF EXISTS livefeed;
CREATE TABLE IF NOT EXISTS livefeed (
  `id_livefeed` int(11) NOT NULL AUTO_INCREMENT,
  `currenttimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_location` int(11) NOT NULL,
  `event` int(11) NOT NULL,
  PRIMARY KEY (`id_livefeed`),
  KEY `fk_livefeed_location_idx` (`id_location`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table location
--

DROP TABLE IF EXISTS location;
CREATE TABLE IF NOT EXISTS location (
  `id_location` int(11) NOT NULL AUTO_INCREMENT,
  `loc_name` varchar(255) NOT NULL,
  `loc_description` varchar(255) NOT NULL,
  PRIMARY KEY (`id_location`)
) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table livefeed
--
ALTER TABLE livefeed
  ADD CONSTRAINT `fk_livefeed_location` FOREIGN KEY (`id_location`) REFERENCES location (`id_location`) ON DELETE NO ACTION ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
