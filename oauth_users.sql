-- phpMyAdmin SQL Dump
-- version 4.2.12
-- http://www.phpmyadmin.net

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `oauth_users`
--

CREATE TABLE IF NOT EXISTS `oauth_users` (
  `id` int(10) unsigned NOT NULL,
  `provider` varchar(20) NOT NULL,
  `uid` varchar(100) NOT NULL,
  `display_name` text NOT NULL,
  `first_name` text,
  `last_name` text,
  `email` text,
  `contact` char(10) DEFAULT 'undefined'
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='Stores user data from OAuth providers (e.g. Twitter)';

--
-- Indexes for table `oauth_users`
--
ALTER TABLE `oauth_users`
 ADD PRIMARY KEY (`id`), ADD KEY `Provider+UID` (`provider`,`uid`);

--
-- AUTO_INCREMENT for table `oauth_users`
--
ALTER TABLE `oauth_users`
 MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
