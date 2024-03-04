-- phpMyAdmin SQL Dump
-- version 4.4.10
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Jul 6, 2018 at 04:15 PM
-- Server version: 5.5.42
-- PHP Version: 5.6.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `laraclassified`
--


-- user_types
TRUNCATE TABLE `__PREFIX__user_types`;
INSERT INTO `__PREFIX__user_types` (`id`, `name`, `active`) VALUES
(1, 'Professional', 1),
(2, 'Individual', 1);


-- users
UPDATE `__PREFIX__users` SET `user_type_id` = null WHERE `user_type_id` = '1';
UPDATE `__PREFIX__users` SET `user_type_id` = '1' WHERE `user_type_id` = '2';
UPDATE `__PREFIX__users` SET `user_type_id` = '2' WHERE `user_type_id` = '3';


-- --------------------------------------------------------

--
-- Table structure for table `__PREFIX__permissions`
--

DROP TABLE IF EXISTS `__PREFIX__permissions`;
CREATE TABLE `__PREFIX__permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `__PREFIX__permissions`
--
ALTER TABLE `__PREFIX__permissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `__PREFIX__permissions`
--
ALTER TABLE `__PREFIX__permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;


-- --------------------------------------------------------

--
-- Table structure for table `__PREFIX__roles`
--

DROP TABLE IF EXISTS `__PREFIX__roles`;
CREATE TABLE `__PREFIX__roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `__PREFIX__roles`
--
ALTER TABLE `__PREFIX__roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for table `__PREFIX__roles`
--
ALTER TABLE `__PREFIX__roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;


-- --------------------------------------------------------

--
-- Table structure for table `__PREFIX__model_has_permissions`
--

DROP TABLE IF EXISTS `__PREFIX__model_has_permissions`;
CREATE TABLE `__PREFIX__model_has_permissions` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `__PREFIX__model_has_permissions`
--
ALTER TABLE `__PREFIX__model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`);

--
-- Constraints for table `__PREFIX__model_has_permissions`
--
ALTER TABLE `__PREFIX__model_has_permissions`
  ADD CONSTRAINT `__PREFIX__model_has_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `__PREFIX__permissions` (`id`) ON DELETE CASCADE;


-- --------------------------------------------------------

--
-- Table structure for table `__PREFIX__model_has_roles`
--

DROP TABLE IF EXISTS `__PREFIX__model_has_roles`;
CREATE TABLE `__PREFIX__model_has_roles` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `__PREFIX__model_has_roles`
--
ALTER TABLE `__PREFIX__model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`);

--
-- Constraints for table `__PREFIX__model_has_roles`
--
ALTER TABLE `__PREFIX__model_has_roles`
  ADD CONSTRAINT `__PREFIX__model_has_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `__PREFIX__roles` (`id`) ON DELETE CASCADE;


-- --------------------------------------------------------

--
-- Table structure for table `__PREFIX__role_has_permissions`
--

DROP TABLE IF EXISTS `__PREFIX__role_has_permissions`;
CREATE TABLE `__PREFIX__role_has_permissions` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `__PREFIX__role_has_permissions`
--
ALTER TABLE `__PREFIX__role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Constraints for table `__PREFIX__role_has_permissions`
--
ALTER TABLE `__PREFIX__role_has_permissions`
  ADD CONSTRAINT `__PREFIX__role_has_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `__PREFIX__permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `__PREFIX__role_has_permissions_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `__PREFIX__roles` (`id`) ON DELETE CASCADE;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;