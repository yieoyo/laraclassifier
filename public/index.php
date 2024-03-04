<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

$valid = true;
$error = '';

// Server components verification to prevent error during the installation process,
// These verifications are always make, including during the installation process
if (!extension_loaded('json')) {
	$error .= "<strong>ERROR:</strong> The requested PHP extension json is missing from your system.<br />";
	$valid = false;
}
if ($valid) {
	$requiredPhpVersion = _getComposerRequiredPhpVersion();
	if (!version_compare(PHP_VERSION, $requiredPhpVersion, '>=')) {
		$error .= "<strong>ERROR:</strong> PHP " . $requiredPhpVersion . " or higher is required.<br />";
		$valid = false;
	}
}

if (!$valid) {
	echo '<pre>' . $error . '</pre>';
	exit();
}

// Remove the bootstrap/cache files before making upgrade
if (_updateIsAvailable()) {
	$cachedFiles = [
		realpath(__DIR__ . '/../bootstrap/cache/packages.php'),
		realpath(__DIR__ . '/../bootstrap/cache/services.php'),
	];
	foreach ($cachedFiles as $file) {
		if (file_exists($file)) {
			unlink($file);
		}
	}
}

// Remove unsupported bootstrap/cache files
$unsupportedCachedFiles = [
	realpath(__DIR__ . '/../bootstrap/cache/config.php'),
	realpath(__DIR__ . '/../bootstrap/cache/routes.php'),
];
foreach ($unsupportedCachedFiles as $file) {
	if (file_exists($file)) {
		unlink($file);
	}
}

// Load Laravel Framework
require 'main.php';


// ==========================================================================================
// THESE FUNCTIONS WILL RUN BEFORE LARAVEL LIBRARIES
// ==========================================================================================

/**
 * Get the composer.json required PHP version
 *
 * @return string
 */
function _getComposerRequiredPhpVersion(): string
{
	$defaultVersion = '8.0';
	$version = null;
	
	$filePath = realpath(__DIR__ . '/../composer.json');
	
	try {
		$content = file_get_contents($filePath);
		$array = json_decode($content, true);
		
		if (isset($array['require']['php'])) {
			$version = $array['require']['php'];
		}
	} catch (\Exception $e) {
	}
	
	if (empty($version)) {
		$version = _getRequiredPhpVersion($defaultVersion);
	}
	
	// String to Float
	$version = trim($version);
	$version = strtr($version, [' ' => '']);
	$version = preg_replace('/ +/', '', $version);
	$version = str_replace(',', '.', $version);
	$version = preg_replace('/[^\d.]/', '', $version);
	
	return is_string($version) ? $version : $defaultVersion;
}

/**
 * Get the required PHP version (from config/version.php)
 *
 * @param string|null $default
 * @return string|null
 */
function _getRequiredPhpVersion(?string $default = null): ?string
{
	return _getVersionValue('php', $default);
}

/**
 * Check if a new version is available
 *
 * @return bool
 */
function _updateIsAvailable(): bool
{
	$lastVersion = _getLatestVersion();
	$currentVersion = _getCurrentVersion();
	
	if (!empty($lastVersion) && !empty($currentVersion)) {
		if (version_compare($lastVersion, $currentVersion, '>')) {
			return true;
		}
	}
	
	return false;
}

/**
 * Get the current version value
 *
 * @return string
 */
function _getCurrentVersion(): string
{
	// Get the Current Version
	$version = _getDotEnvValue('APP_VERSION');
	
	return _checkAndUseSemVer($version);
}

/**
 * Get the latest version value
 *
 * @return string|null
 */
function _getLatestVersion(): ?string
{
	return _getVersionValue('app');
}

/**
 * Check and use semver version num format
 *
 * @param string|null $version
 * @return string
 */
function _checkAndUseSemVer(?string $version): string
{
	$defaultSemver = '0.0.0';
	
	if (empty($version)) {
		return $defaultSemver;
	}
	
	$semver = null;
	
	if (empty($semver)) {
		$numPattern = '([0-9]+)';
		$hasValidFormat = preg_match('#^' . $numPattern . '\.' . $numPattern . '\.' . $numPattern . '$#', $version);
		$semver = $hasValidFormat ? $version : $semver;
	}
	if (empty($semver)) {
		$hasValidFormat = preg_match('#^' . $numPattern . '\.' . $numPattern . '$#', $version);
		$semver = $hasValidFormat ? $version . '.0' : $semver;
	}
	if (empty($semver)) {
		$hasValidFormat = preg_match('#^' . $numPattern . '$#', $version);
		$semver = $hasValidFormat ? $version . '.0.0' : $semver;
	}
	if (empty($semver)) {
		$semver = $defaultSemver;
	}
	
	return $semver;
}

/**
 * Convert multidimensional array to array with keys with dot notation
 *
 * @param array|null $array
 * @param string|null $parentKey
 * @return array
 */
function _arrayToDotNotation(?array $array, ?string $parentKey = ''): array
{
	$result = [];
	
	if (empty($array)) {
		return $result;
	}
	
	foreach ($array as $key => $value) {
		$newKey = $parentKey ? $parentKey . '.' . $key : $key;
		if (is_array($value)) {
			$result += _arrayToDotNotation($value, $newKey);
		} else {
			$result[$newKey] = $value;
		}
	}
	
	return $result;
}

/**
 * Get a /.env file key's value
 *
 * @param $key
 * @return string|null
 */
function _getDotEnvValue($key): ?string
{
	if (empty($key)) return null;
	
	$value = null;
	
	$filePath = realpath(__DIR__ . '/../.env');
	if (file_exists($filePath)) {
		$content = file_get_contents($filePath);
		$matches = [];
		preg_match('/' . $key . '=(.*)[^\n]*/', $content, $matches);
		$value = $matches[1] ?? null;
		$value = is_string($value) ? trim($value) : null;
	}
	
	return $value;
}

/**
 * Get entity's version (from config/version.php)
 * Supports dot notation keys
 *
 * @param string $key
 * @param string|null $default
 * @return string|null
 */
function _getVersionValue(string $key, ?string $default = null): ?string
{
	$versionFilePath = realpath(__DIR__ . '/../config/version.php');
	
	$version = null;
	if (file_exists($versionFilePath)) {
		$array = include($versionFilePath);
		$array = _arrayToDotNotation($array);
		if (isset($array[$key])) {
			$version = (is_string($array[$key])) ? $array[$key] : null;
			$version = _checkAndUseSemVer($version);
		}
	}
	
	if (empty($version) && !empty($default)) {
		$version = $default;
	}
	
	return $version;
}

/**
 * Check if the app's installation files exist
 *
 * @return bool
 */
function _appInstallFilesExist(): bool
{
	$envFile = realpath(__DIR__ . '/../.env');
	$installedFile = realpath(__DIR__ . '/../storage/installed');
	
	return (file_exists($envFile) && file_exists($installedFile));
}

// ==========================================================================================
