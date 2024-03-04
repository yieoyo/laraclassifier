-- phpMyAdmin SQL Dump
-- version 4.4.10
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2017 at 10:05 AM
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

-- advertising
ALTER TABLE `__PREFIX__advertising` ADD UNIQUE(`slug`);
ALTER TABLE `__PREFIX__advertising` ADD INDEX(`active`);


-- categories
ALTER TABLE `__PREFIX__categories` ADD INDEX(`slug`);


-- cities
ALTER TABLE `__PREFIX__cities` ADD INDEX(`subadmin1_code`);
ALTER TABLE `__PREFIX__cities` ADD INDEX(`subadmin2_code`);
ALTER TABLE `__PREFIX__cities` ADD INDEX(`active`);

UPDATE __PREFIX__cities SET subadmin1_code = IF(LENGTH(subadmin1_code) > 0, CONCAT(country_code, '.', subadmin1_code), NULL),
subadmin2_code = IF(LENGTH(subadmin2_code) > 0, CONCAT(IF(LENGTH(subadmin1_code) > 0, subadmin1_code, country_code), '.', subadmin2_code), NULL);


-- continents
ALTER TABLE `__PREFIX__continents` ADD INDEX(`active`);


-- countries
ALTER TABLE `__PREFIX__countries` ADD `admin_type` ENUM('0','1','2') NOT NULL DEFAULT '0' AFTER `equivalent_fips_code`;
ALTER TABLE `__PREFIX__countries` ADD `admin_field_active` TINYINT(1) UNSIGNED NULL DEFAULT '0' AFTER `admin_type`;
ALTER TABLE `__PREFIX__countries` ADD INDEX(`active`);


-- currencies
ALTER TABLE `__PREFIX__currencies` CHANGE `in_left` `in_left` TINYINT(1) UNSIGNED NULL DEFAULT '0';
INSERT INTO `__PREFIX__currencies` (`code`, `name`, `html_entity`, `font_arial`, `font_code2000`, `unicode_decimal`, `unicode_hex`, `in_left`, `decimal_places`, `decimal_separator`, `thousand_separator`, `created_at`, `updated_at`) VALUES ('XBT', 'Bitcoin', '฿', '฿', '฿', NULL, NULL, 1, 2, '.', ',', NULL, '2017-04-08 04:49:08');


-- languages
ALTER TABLE `__PREFIX__languages` ADD INDEX(`active`);
ALTER TABLE `__PREFIX__languages` ADD INDEX(`default`);


-- messages
ALTER TABLE `__PREFIX__messages` ADD `reply_sent` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `filename`;
ALTER TABLE `__PREFIX__messages` CHANGE `ad_id` `post_id` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__messages` ADD INDEX(`post_id`);


-- packages
ALTER TABLE `__PREFIX__packages` CHANGE `has_badge` `has_badge` TINYINT(1) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `__PREFIX__packages` CHANGE `active` `active` TINYINT(1) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `__PREFIX__packages` ADD INDEX(`active`);


-- pages
ALTER TABLE `__PREFIX__pages` CHANGE `excluded_from_footer` `excluded_from_footer` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `__PREFIX__pages` CHANGE `active` `active` TINYINT(1) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `__PREFIX__pages` ADD INDEX(`active`);


-- payments
ALTER TABLE `__PREFIX__payments` ADD `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `payment_method_id`;
ALTER TABLE `__PREFIX__payments` CHANGE `ad_id` `post_id` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__payments` ADD `transaction_id` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Transaction\'s ID at the Provider' AFTER `payment_method_id`;
ALTER TABLE `__PREFIX__payments` DROP INDEX `ad_id`;
ALTER TABLE `__PREFIX__payments` ADD INDEX(`post_id`);
ALTER TABLE `__PREFIX__payments` ADD INDEX(`active`);


-- payment_methods
ALTER TABLE `__PREFIX__payment_methods` ADD `countries` TEXT NULL DEFAULT NULL COMMENT 'Countries codes separated by comma.' AFTER `has_ccbox`;
ALTER TABLE `__PREFIX__payment_methods` ADD INDEX(`has_ccbox`);
ALTER TABLE `__PREFIX__payment_methods` ADD INDEX(`active`);


-- pictures
ALTER TABLE `__PREFIX__pictures` CHANGE `ad_id` `post_id` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__pictures` CHANGE `active` `active` TINYINT(1) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `__PREFIX__pictures` DROP INDEX `ad_id`;
ALTER TABLE `__PREFIX__pictures` ADD INDEX(`post_id`);
ALTER TABLE `__PREFIX__pictures` ADD INDEX(`active`);


-- posts
RENAME TABLE `__PREFIX__ads` TO `__PREFIX__posts`;
ALTER TABLE `__PREFIX__posts` DROP `package_id`, DROP `resume`, DROP `new`, DROP `brand`;
ALTER TABLE `__PREFIX__posts` ADD `tmp_token` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `activation_token`;
ALTER TABLE `__PREFIX__posts` CHANGE `ad_type_id` `post_type_id` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__posts` CHANGE `activation_token` `email_token` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__posts` ADD `phone_token` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `email_token`;
ALTER TABLE `__PREFIX__posts` CHANGE `active` `verified_email` TINYINT(1) UNSIGNED NULL DEFAULT '0';
ALTER TABLE `__PREFIX__posts` ADD `verified_phone` TINYINT(1) UNSIGNED NULL DEFAULT '1' AFTER `verified_email`;
ALTER TABLE `__PREFIX__posts`
	CHANGE `seller_name` `contact_name` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
	CHANGE `seller_email` `email` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
	CHANGE `seller_phone` `phone` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
	CHANGE `seller_phone_hidden` `phone_hidden` TINYINT(1) NULL DEFAULT '0';
ALTER TABLE `__PREFIX__posts`
	CHANGE `country_code` `country_code` VARCHAR(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
	CHANGE `category_id` `category_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	CHANGE `city_id` `city_id` INT(10) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `__PREFIX__posts` DROP INDEX `ad_type_id`;
ALTER TABLE `__PREFIX__posts` ADD INDEX(`post_type_id`);
ALTER TABLE `__PREFIX__posts` DROP INDEX `seller_name`;
ALTER TABLE `__PREFIX__posts` ADD INDEX(`contact_name`);
ALTER TABLE `__PREFIX__posts` DROP INDEX `active`;
ALTER TABLE `__PREFIX__posts` ADD INDEX(`verified_email`);
ALTER TABLE `__PREFIX__posts` ADD INDEX(`verified_phone`);
UPDATE `__PREFIX__posts` SET `verified_phone` = 1;



-- post_types
RENAME TABLE `__PREFIX__ad_type` TO `__PREFIX__post_types`;
ALTER TABLE `__PREFIX__post_types` CHANGE `active` `active` TINYINT(1) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `__PREFIX__post_types` ADD INDEX(`active`);


-- report_type
RENAME TABLE `__PREFIX__report_type` TO `__PREFIX__report_types`;


-- roles
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `__PREFIX__permissions`;
DROP TABLE IF EXISTS `__PREFIX__permission_role`;
DROP TABLE IF EXISTS `__PREFIX__roles`;
DROP TABLE IF EXISTS `__PREFIX__role_users`;
SET FOREIGN_KEY_CHECKS=1;


-- saved_posts
RENAME TABLE `__PREFIX__saved_ads` TO `__PREFIX__saved_posts`;
ALTER TABLE `__PREFIX__saved_posts` CHANGE `ad_id` `post_id` INT(10) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__saved_posts` DROP INDEX `ad_id`;
ALTER TABLE `__PREFIX__saved_posts` ADD INDEX(`post_id`);


-- settings
ALTER TABLE `__PREFIX__settings` ADD INDEX(`active`);

UPDATE `__PREFIX__settings`
	SET `key` = 'email_verification',
		`name` = 'Email verification required',
		`description` = 'Email verification required'
	WHERE `key` = 'require_users_activation';

UPDATE `__PREFIX__settings`
	SET `key` = 'phone_verification',
		`name` = 'Phone verification required',
		`description` = 'Phone verification required'
	WHERE `key` = 'require_ads_activation';

UPDATE `__PREFIX__settings`
	SET `key` = 'app_cache_expiration',
		`name` = 'Cache Expiration Time',
		`description` = 'Cache Expiration Time (in minutes)'
	WHERE `key` = 'app_cache_expire';

UPDATE `__PREFIX__settings`
	SET `key` = 'app_cookie_expiration',
		`name` = 'Cookie Expiration Time',
		`description` = 'Cookie Expiration Time (in secondes)'
	WHERE `key` = 'app_cookie_expire';

UPDATE `__PREFIX__settings`
	SET `key` = 'app_skin',
		`name` = 'Front Skin',
		`value` = IF(LENGTH(`value`) > 0, CONCAT('skin-', `value`), NULL),
		`field` = '{"name":"value","label":"Value","type":"select_from_array","options":{"skin-default":"Default","skin-blue":"Blue","skin-yellow":"Yellow","skin-green":"Green","skin-red":"Red"}}'
	WHERE `key` = 'app_theme';

UPDATE `__PREFIX__settings`
	SET `key` = 'admin_skin',
		`name` = 'Admin Skin',
		`description` = 'Admin Panel Skin'
	WHERE `key` = 'admin_theme';

UPDATE `__PREFIX__settings` SET `active`=1 WHERE `key`='sparkpost_secret';

UPDATE `__PREFIX__settings`
	SET `description`='e.g. smtp, mailgun, mandrill, ses, sparkpost, mail, sendmail',
		`field`='{"name":"value","label":"Value","type":"select_from_array","options":{"smtp":"SMTP","mailgun":"Mailgun","mandrill":"Mandrill","ses":"Amazon SES","sparkpost":"Sparkpost","mail":"PHP Mail","sendmail":"Sendmail"}}'
	WHERE `key`='mail_driver';

UPDATE `__PREFIX__settings`
	SET `description`='Before enabling this option you need to download the Maxmind database by following the documentation: https://laraclassifier.com/doc/geo-location/'
	WHERE `key`='activation_geolocation';

UPDATE `__PREFIX__settings` SET `key` = 'show_post_on_googlemap' WHERE `key` = 'show_ad_on_googlemap';
UPDATE `__PREFIX__settings` SET `key` = 'unactivated_listings_expiration' WHERE `key` = 'unactivated_ads_expiration';
UPDATE `__PREFIX__settings` SET `key` = 'activated_listings_expiration' WHERE `key` = 'activated_ads_expiration';
UPDATE `__PREFIX__settings` SET `key` = 'archived_listings_expiration' WHERE `key` = 'archived_ads_expiration';
UPDATE `__PREFIX__settings` SET `key` = 'posts_per_page' WHERE `key` = 'ads_per_page';
UPDATE `__PREFIX__settings` SET `key` = 'posts_pictures_number' WHERE `key` = 'ads_pictures_number';
UPDATE `__PREFIX__settings` SET `key` = 'posts_review_activation' WHERE `key` = 'ads_review_activation';

DELETE FROM `__PREFIX__settings` WHERE `key`='meta_description';
DELETE FROM `__PREFIX__settings` WHERE `key`='activation_home_stats';
DELETE FROM `__PREFIX__settings` WHERE `key`='facebook_page_fans';
DELETE FROM `__PREFIX__settings` WHERE `key`='show_country_svgmap';

INSERT INTO `__PREFIX__settings` (`key`, `name`, `value`, `description`, `field`, `parent_id`, `lft`, `rgt`, `depth`, `active`, `created_at`, `updated_at`)
VALUES
  ('sms_driver', 'SMS driver', 'nexmo', 'e.g. nexmo, twilio', '{"name":"value","label":"Value","type":"select_from_array","options":{"nexmo":"Nexmo","twilio":"Twilio"}}', 0, 86, 86, 1, 1, NULL, '2017-04-12 13:06:19'),
  ('sms_message_activation', 'SMS Message Activation', '0', 'Users can contact the author by SMS. Note: You need to set the "SMS driver" setting.', '{"name":"value","label":"Activation","type":"checkbox"}', 0, 87, 87, 1, 1, NULL, '2017-06-17 13:06:19');


-- subadmin1
ALTER TABLE `__PREFIX__subadmin1` ADD INDEX(`active`);
ALTER TABLE `__PREFIX__subadmin1` ADD `country_code` VARCHAR(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `code`;
ALTER TABLE `__PREFIX__subadmin1` ADD INDEX(`country_code`);
UPDATE __PREFIX__subadmin1 SET country_code = SUBSTRING(code, 1, 2);


-- subadmin2
ALTER TABLE `__PREFIX__subadmin2` ADD INDEX(`active`);
ALTER TABLE `__PREFIX__subadmin2` ADD `country_code` VARCHAR(2) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `code`;
ALTER TABLE `__PREFIX__subadmin2` ADD `subadmin1_code` VARCHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `country_code`;
ALTER TABLE `__PREFIX__subadmin2` ADD INDEX(`country_code`);
ALTER TABLE `__PREFIX__subadmin2` ADD INDEX(`subadmin1_code`);
UPDATE __PREFIX__subadmin2 SET country_code = SUBSTRING(code, 1, 2);
UPDATE __PREFIX__subadmin2 SET subadmin1_code = SUBSTRING_INDEX(code, '.', 2);


-- users
ALTER TABLE `__PREFIX__users` CHANGE `activation_token` `email_token` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `__PREFIX__users` ADD `phone_token` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `email_token`;

ALTER TABLE `__PREFIX__users` CHANGE `active` `verified_email` TINYINT(1) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `__PREFIX__users` ADD `verified_phone` TINYINT(1) UNSIGNED NULL DEFAULT '1' AFTER `verified_email`;
ALTER TABLE `__PREFIX__users` ADD INDEX(`verified_email`);
ALTER TABLE `__PREFIX__users` ADD INDEX(`verified_phone`);

ALTER TABLE `__PREFIX__users` ADD `username` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL AFTER `phone_hidden`;

ALTER TABLE `__PREFIX__users` ADD INDEX(`username`);
ALTER TABLE `__PREFIX__users` ADD INDEX(`phone`);
ALTER TABLE `__PREFIX__users` ADD INDEX(`email`);

ALTER TABLE `__PREFIX__users`
	CHANGE `phone_hidden` `phone_hidden` TINYINT(1) UNSIGNED NULL DEFAULT '0',
	CHANGE `is_admin` `is_admin` TINYINT(1) UNSIGNED NULL DEFAULT '0',
	CHANGE `disable_comments` `disable_comments` TINYINT(1) UNSIGNED NULL DEFAULT '0',
	CHANGE `receive_newsletter` `receive_newsletter` TINYINT(1) UNSIGNED NULL DEFAULT '1',
	CHANGE `receive_advice` `receive_advice` TINYINT(1) UNSIGNED NULL DEFAULT '1',
	CHANGE `verified_email` `verified_email` TINYINT(1) UNSIGNED NULL DEFAULT '1',
	CHANGE `blocked` `blocked` TINYINT(1) UNSIGNED NULL DEFAULT '0',
	CHANGE `closed` `closed` TINYINT(1) UNSIGNED NULL DEFAULT '0';

UPDATE `__PREFIX__users` SET `verified_phone` = 1;

-- user_types
RENAME TABLE `__PREFIX__user_type` TO `__PREFIX__user_types`;
ALTER TABLE `__PREFIX__user_types` CHANGE `id` `id` TINYINT(1) UNSIGNED NOT NULL;
ALTER TABLE `__PREFIX__user_types` CHANGE `active` `active` TINYINT(1) UNSIGNED NULL DEFAULT '1';
ALTER TABLE `__PREFIX__user_types` ADD INDEX(`active`);





-- home_sections
DROP TABLE IF EXISTS `__PREFIX__home_sections`;
CREATE TABLE `__PREFIX__home_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `method` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `options` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `view` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `lft` int(10) UNSIGNED DEFAULT NULL,
  `rgt` int(10) UNSIGNED DEFAULT NULL,
  `depth` int(10) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(1, 'Locations & SVG Map', 'getLocations', '{\"max_items\":\"14\",\"show_map\":\"1\",\"map_background_color\":null,\"map_border\":null,\"map_hover_border\":null,\"map_border_width\":null,\"map_color\":null,\"map_hover\":null,\"map_width\":\"300px\",\"map_height\":\"300px\",\"cache_expiration\":null}', 'home.inc.locations', 0, 2, 3, 1, 1);
INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(2, 'Premium Listings', 'getPremiumListings', '{\"max_items\":\"20\",\"autoplay\":\"1\",\"autoplay_timeout\":null,\"cache_expiration\":null}', 'home.inc.featured', 0, 4, 5, 1, 1);
INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(3, 'Latest Listings', 'getLatestListings', '{\"max_items\":\"4\",\"show_view_more_btn\":\"1\",\"cache_expiration\":null}', 'home.inc.latest', 0, 8, 9, 1, 1);
INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(4, 'Categories', 'getCategories', '{\"cache_expiration\":null}', 'home.inc.categories', 0, 6, 7, 1, 1);
INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(5, 'Mini stats', 'getStats', NULL, 'home.inc.stats', 0, 10, 11, 1, 1);
INSERT INTO `__PREFIX__home_sections` (`id`, `name`, `method`, `options`, `view`, `parent_id`, `lft`, `rgt`, `depth`, `active`) VALUES(6, 'Bottom advertising', 'getBottomAdvertising', NULL, 'layouts.inc.advertising.bottom', 0, 12, 13, 1, 0);

ALTER TABLE `__PREFIX__home_sections` ADD PRIMARY KEY (`id`);
ALTER TABLE `__PREFIX__home_sections` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;


-- meta_tags
DROP TABLE IF EXISTS `__PREFIX__meta_tags`;
CREATE TABLE `__PREFIX__meta_tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `translation_lang` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `translation_of` int(10) UNSIGNED NOT NULL,
  `page` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '',
  `description` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '',
  `keywords` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '',
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(1, 'en', 1, 'home', '{app.name} - Hello, World!', 'Sell and Buy products and services on {app.name} in Minutes {country.name}. Free ads in {country.name}. Looking for a product or service - {country.name}', '{app.name}, {country.name}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(2, 'en', 2, 'register', 'Sign Up - {app.name}', 'Sign Up on {app.name}', '{app.name}, {country.name}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(3, 'en', 3, 'login', 'Login - {app.name}', 'Log in to {app.name}', '{app.name}, {country.name}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(4, 'en', 4, 'create', 'Post Free Ads', 'Post Free Ads - {country.name}.', '{app.name}, {country.name}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(5, 'en', 5, 'countries', 'Free Local Classified Ads in the World', 'Welcome to {app.name} : 100% Free Ads Classified. Sell and buy near you. Simple, fast and efficient.', '{app.name}, {country.name}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(6, 'en', 6, 'contact', 'Contact Us - {app.name}', 'Contact Us - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(7, 'en', 7, 'sitemap', 'Sitemap {app.name} - {country}', 'Sitemap {app.name} - {country}. 100% Free Ads Classified', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(8, 'fr', 1, 'home', '{app.name} - CMS d\'annonces classées et géolocalisées', 'Vendre et acheter des produits et services en quelques minutes sur {app.name} {country}. Petites annonces - {country}. Recherchez un produit ou un service - {country}', '{app.name}, {country}, annonces, classées, gratuites, script, app, annonces premium', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(9, 'es', 1, 'home', '{app.name} - Hello, World!', 'Sell and Buy products and services on {app.name} in Minutes {country}. Free ads in {country}. Looking for a product or service - {country}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(10, 'fr', 2, 'register', 'S\'inscrire - {app.name}', 'S\'inscrire sur {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(11, 'es', 2, 'register', 'Sign Up - {app.name}', 'Sign Up on {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(12, 'fr', 3, 'login', 'S\'identifier - {app.name}', 'S\'identifier sur {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(13, 'es', 3, 'login', 'Login - {app.name}', 'Log in to {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(14, 'fr', 4, 'create', 'Publiez une annonce gratuite', 'Publiez une annonce gratuite - {country}.', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(15, 'es', 4, 'create', 'Post a Free Ads', 'Post a Free Ads - {country}.', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(16, 'fr', 5, 'countries', 'Petites annonces classées dans le monde', 'Bienvenue sur {app.name} : Site de petites annonces 100% gratuit. Vendez et achetez près de chez vous. Simple, rapide et efficace.', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(17, 'es', 5, 'countries', 'Free Local Classified Ads in the World', 'Welcome to {app.name} : 100% Free Ads Classified. Sell and buy near you. Simple, fast and efficient.', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(18, 'fr', 6, 'contact', 'Nous contacter - {app.name}', 'Nous contacter - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(19, 'es', 6, 'contact', 'Contact Us - {app.name}', 'Contact Us - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(20, 'fr', 7, 'sitemap', 'Plan du site {app.name} - {country}', 'Plan du site {app.name} - {country}. Site de petites annonces 100% gratuit dans le Monde. Vendez et achetez près de chez vous. Simple, rapide et efficace.', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(21, 'es', 7, 'sitemap', 'Sitemap {app.name} - {country}', 'Sitemap {app.name} - {country}. 100% Free Ads Classified', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(22, 'en', 22, 'password', 'Lost your password? - {app.name}', 'Lost your password? - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(23, 'fr', 22, 'password', 'Mot de passe oublié? - {app.name}', 'Mot de passe oublié? - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);
INSERT INTO `__PREFIX__meta_tags` (`id`, `translation_lang`, `translation_of`, `page`, `title`, `description`, `keywords`, `active`) VALUES(24, 'es', 22, 'password', '¿Perdiste tu contraseña? - {app.name}', '¿Perdiste tu contraseña? - {app.name}', '{app.name}, {country}, free ads, classified, ads, script, app, premium ads', 1);

ALTER TABLE `__PREFIX__meta_tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `translation_lang` (`translation_lang`),
  ADD KEY `translation_of` (`translation_of`);

ALTER TABLE `__PREFIX__meta_tags` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;


-- fields
CREATE TABLE `__PREFIX__fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `belongs_to` enum('post','user') COLLATE utf8_unicode_ci NOT NULL,
  `translation_lang` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `translation_of` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` enum('text','textarea','checkbox','checkbox_multiple','select','radio','file') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `max` int(10) UNSIGNED DEFAULT '255',
  `default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `required` tinyint(1) UNSIGNED DEFAULT NULL,
  `help` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `__PREFIX__fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `translation_lang` (`translation_lang`),
  ADD KEY `translation_of` (`translation_of`),
  ADD KEY `belongs_to` (`belongs_to`);

ALTER TABLE `__PREFIX__fields` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;


-- fields_options
CREATE TABLE `__PREFIX__fields_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED DEFAULT NULL,
  `translation_lang` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `translation_of` int(10) UNSIGNED DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `lft` int(10) UNSIGNED DEFAULT NULL,
  `rgt` int(10) UNSIGNED DEFAULT NULL,
  `depth` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `__PREFIX__fields_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `translation_lang` (`translation_lang`),
  ADD KEY `translation_of` (`translation_of`);

ALTER TABLE `__PREFIX__fields_options` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=615;


-- category_field
CREATE TABLE `__PREFIX__category_field` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `field_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `lft` int(10) UNSIGNED DEFAULT NULL,
  `rgt` int(10) UNSIGNED DEFAULT NULL,
  `depth` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `__PREFIX__category_field`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_id` (`category_id`,`field_id`);

ALTER TABLE `__PREFIX__category_field` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;


-- post_values
CREATE TABLE `__PREFIX__post_values` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED DEFAULT NULL,
  `field_id` int(10) UNSIGNED DEFAULT NULL,
  `option_id` int(10) UNSIGNED DEFAULT NULL,
  `value` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `__PREFIX__post_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `option_id` (`option_id`);

ALTER TABLE `__PREFIX__post_values` MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
