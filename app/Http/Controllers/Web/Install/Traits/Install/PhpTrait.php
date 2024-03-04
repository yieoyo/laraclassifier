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

namespace App\Http\Controllers\Web\Install\Traits\Install;

use App\Helpers\Number;

trait PhpTrait
{
	/**
	 * Get the composer.json required PHP version
	 *
	 * @return int|string|null
	 */
	protected function getComposerRequiredPhpVersion()
	{
		$version = null;
		
		$filePath = base_path('composer.json');
		
		try {
			$content = file_get_contents($filePath);
			$array = json_decode($content, true);
			
			if (isset($array['require']['php'])) {
				$version = $array['require']['php'];
			}
		} catch (\Throwable $e) {
		}
		
		if (empty($version)) {
			$version = config('version.php', '8.0');
		}
		
		return Number::getFloatRawFormat($version);
	}
	
	/**
	 * Get path of the PHP binary (PHP-cli) on the server
	 *
	 * @return false|string|null
	 */
	protected function getPhpBinaryPath()
	{
		$path = null;
		
		if (defined(PHP_BINARY)) {
			$path = PHP_BINARY;
		}
		
		if (empty($path)) {
			try {
				$path = exec('whereis php');
			} catch (\Throwable $e) {
			}
			
			if (empty($path)) {
				try {
					$path = exec('which php');
				} catch (\Throwable $e) {
				}
			}
		}
		
		if ($path == trim($path) && str_contains($path, ' ')) {
			$tmp = explode(' ', $path);
			if (isset($tmp[1])) {
				$path = $tmp[1];
			}
		}
		
		return $path;
	}
	
	/**
	 * @return string|null
	 */
	protected function getPhpBinaryVersion()
	{
		$version = null;
		
		$phpBinaryPath = $this->getPhpBinaryPath();
		if (!empty($phpBinaryPath)) {
			try {
				exec($phpBinaryPath . ' --version', $version);
			} catch (\Throwable $e) {
			}
		}
		
		if (is_array($version)) {
			$version = implode(' ', $version);
		}
		
		if (!empty($version) && is_string($version)) {
			$version = $this->parsePhpVersion($version);
		}
		
		return $version;
	}
	
	/**
	 * PHP: Extract version number for string
	 *
	 * @param $str
	 * @return string|null
	 */
	protected function parsePhpVersion($str): ?string
	{
		preg_match("/(?:PHP|version|)\s*((?:\d+\.?)+)/i", $str, $matches);
		$value = $matches[1] ?? null;
		
		return is_string($value) ? $value : null;
	}
}
