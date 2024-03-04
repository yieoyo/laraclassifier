-- phpMyAdmin SQL Dump
-- version 4.4.10
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2017 at 10:12 AM
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


-- home_sections
UPDATE `__PREFIX__home_sections` SET `name` = 'Search Form Area' WHERE `method` = 'getSearchForm';
UPDATE `__PREFIX__home_sections` SET `name` = 'Advertising #2' WHERE `method` = 'getBottomAdvertising';
UPDATE `__PREFIX__home_sections` SET `options` = '{"max_items":null,"cache_expiration":null}' WHERE `method` = 'getCategories';
INSERT INTO `__PREFIX__home_sections` (`name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES('Advertising #1', 'getTopAdvertising', NULL, 'layouts.inc.advertising.top', 0, 11, 12, 1, 0);


-- pages
ALTER TABLE `__PREFIX__pages` ADD `external_link` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `content`;
ALTER TABLE `__PREFIX__pages` ADD `target_blank` TINYINT(1) UNSIGNED NULL DEFAULT '0' AFTER `title_color`;


-- settings
UPDATE `__PREFIX__settings` SET `field` = '{"name":"value","label":"Required","type":"checkbox","hint":"By enabling this option you have to add this entry: <strong>DISABLE_EMAIL=false</strong> in the /.env file."}' WHERE `key` = 'email_verification';
UPDATE `__PREFIX__settings` SET `field` = '{"name":"value","label":"Required","type":"checkbox","hint":"By enabling this option you have to add this entry: <strong>DISABLE_PHONE=false</strong> in the /.env file."}' WHERE `key` = 'phone_verification';



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;