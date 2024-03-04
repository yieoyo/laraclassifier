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

namespace App\Exceptions\Traits\Plugin;

use App\Helpers\Arr;

trait OutToDatePlugin
{
	/**
	 * Move plugin files to the backup folder if a specific error found
	 * e.g.: When the error message contains "must be compatible"
	 *
	 * @param $message
	 * @return string|void
	 */
	public function tryToArchivePlugin($message)
	{
		if (empty($message)) {
			return;
		}
		
		// Get the broken plugin name
		$matches = [];
		preg_match('|extras\\\plugins\\\([^\\\]+)\\\|ui', $message, $matches);
		$brokenPluginName = $matches[1] ?? null;
		
		if (empty($brokenPluginName)) {
			return;
		}
		
		$pluginsBasePath = config('larapen.core.plugin.path');
		$destinationDirectory = __DIR__ . '/../../../../storage/framework/cache/plugins.backup/';
		
		$sourceDirectory = $pluginsBasePath . $brokenPluginName;
		
		$issueFixed = false;
		$isDirectoryLinkedToSystemFiles = (
			$brokenPluginName == 'paypal'
			|| str_ends_with($sourceDirectory, 'plugins' . DIRECTORY_SEPARATOR)
			|| str_ends_with($sourceDirectory, 'plugins')
		);
		if (!$isDirectoryLinkedToSystemFiles) {
			try {
				$issueFixed = $this->archiveThePlugin($sourceDirectory, $destinationDirectory, $brokenPluginName);
			} catch (\Throwable $e) {
			}
			
			// Remove the broken plugin event its archiving failed
			if (!$issueFixed) {
				$issueFixed = remove_directory($sourceDirectory);
			}
		}
		
		if ($issueFixed) {
			// Customize and Redirect to the previous URL
			$previousUrl = url()->previous();
			$baseUrl = url('/');
			
			// Check if redirection is allowed
			// That avoids infinite redirections and redirections to external URLs
			$isRedirectionAllowed = (
				request()->query('archivedPlugin') != $brokenPluginName
				&& str_starts_with($previousUrl, $baseUrl)
			);
			
			if ($isRedirectionAllowed) {
				// Get the previous URL without a query string
				$previousUrlWithoutQuery = getUrlWithoutQuery($previousUrl);
				
				// Build the new query string
				$queryString = '';
				$queryArray = getUrlQuery($previousUrl, 'archivedPlugin');
				$queryArray = array_merge($queryArray, ['archivedPlugin' => $brokenPluginName]);
				if (!empty($queryArray)) {
					$queryString = '?' . Arr::query($queryArray);
				}
				
				// Get the previous URL with new query string
				$previousUrl = $previousUrlWithoutQuery . $queryString;
				
				// Redirect
				redirectUrl($previousUrl, 301, config('larapen.core.noCacheHeaders'));
			} else {
				$errorMessage = 'The "<code>%s</code>" plugin was broken probably due to version compatibility with the core app.
				The script tried to backup the plugin\'s files in the <code>/storage/framework/cache/backup</code>...
				By refreshing this page, the error message should be disappeared, and you can try to re-install the newer version of the plugin.
				If it is not the case, please reread the documentation on the installation of this plugin, in order to fix the issue manually.';
				
				return sprintf($errorMessage, $brokenPluginName);
			}
		}
	}
	
	// PRIVATE
	
	/**
	 * Backup all the out-to-date plugins files
	 *
	 * @param string $sourceDir
	 * @param string $destinationDir
	 * @param string $zipFileName
	 * @return bool
	 */
	private function archiveThePlugin(string $sourceDir, string $destinationDir, string $zipFileName): bool
	{
		// Check if the source directory exists
		if (!is_dir($sourceDir)) {
			return false;
		}
		
		$zipFile = $destinationDir . $zipFileName . '.zip';
		
		// Remove any existing file
		if (file_exists($zipFile)) {
			remove_directory($zipFile);
		}
		
		// Zip the directory and its contents, then remove it
		$issueFixed = false;
		if (zip_directory($sourceDir, $zipFile)) {
			$issueFixed = remove_directory($sourceDir);
		}
		
		return $issueFixed;
	}
}
