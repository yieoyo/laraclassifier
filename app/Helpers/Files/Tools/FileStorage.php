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

namespace App\Helpers\Files\Tools;

use Illuminate\Filesystem\FilesystemAdapter;

class FileStorage
{
	/**
	 * Remove matched pattern recursively
	 *
	 * @param $disk
	 * @param string $path
	 * @param string $pattern
	 * @return bool
	 */
	public static function removeMatchedFilesRecursive($disk, string $path, string $pattern): bool
	{
		if (empty($path) || !$disk instanceof FilesystemAdapter) {
			return false;
		}
		
		if (!$disk->exists($path)) {
			return false;
		}
		
		if ($disk->directoryExists($path)) {
			// Get all files and all hidden files
			$files = $disk->allfiles($path);
			if (!empty($files)) {
				foreach ($files as $file) {
					self::removeMatchedFilesRecursive($disk, $file, $pattern);
				}
			}
			
			return true;
		} else {
			if (preg_match($pattern, $path)) {
				try {
					$disk->delete($path);
				} catch (\Throwable $e) {
					return false;
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Remove all empty directories recursively
	 *
	 * @param $disk
	 * @param string $path
	 * @return bool
	 */
	public static function removeEmptySubDirs($disk, string $path): bool
	{
		if (empty($path) || !$disk instanceof FilesystemAdapter) {
			return false;
		}
		
		$empty = true;
		
		if (!$disk->exists($path)) {
			return false;
		}
		
		if (!$disk->directoryExists($path)) return true;
		
		// Remove all unwanted files
		self::removeUnwantedFiles($disk, $path);
		
		// Get all subdirectories recursively.
		$directories = $disk->allDirectories($path);
		if (!empty($directories)) {
			foreach ($directories as $directory) {
				if (empty($directory) || !$disk->exists($directory)) {
					continue;
				}
				
				if ($disk->directoryExists($directory)) {
					if (!self::removeEmptySubDirs($disk, $directory)) {
						$empty = false;
					}
				} else {
					$empty = false;
				}
			}
		}
		
		$files = $disk->files($path);
		if (!empty($files)) {
			$empty = false;
		}
		
		if ($empty) {
			try {
				$disk->deleteDirectory($path);
			} catch (\Throwable $e) {
			}
		}
		
		return $empty;
	}
	
	/**
	 * Remove all unwanted files from a directory recursively
	 *
	 * @param $disk
	 * @param string $path
	 * @param array $filenames
	 * @return bool|void
	 */
	public static function removeUnwantedFiles($disk, string $path, array $filenames = [])
	{
		if (empty($path) || !$disk instanceof FilesystemAdapter) {
			return false;
		}
		
		if (empty($filenames)) {
			// Default unwanted filenames
			$filenames = [
				'.DS_Store',
				'.localized',
				'Thumbs.db',
				'error_log',
			];
		}
		
		if (!$disk->exists($path)) {
			return;
		}
		
		// Get all files and all hidden files
		$files = $disk->allfiles($path);
		foreach ($files as $file) {
			if ($disk->directoryExists($file)) {
				continue;
			}
			
			if (in_array(basename($file), $filenames)) {
				try {
					$disk->delete($file);
				} catch (\Throwable $e) {
				}
			}
		}
	}
	
	/**
	 * Get a file (or directory)'s type
	 * IMPORTANT: No longer works since Laravel 9
	 *
	 * @param $disk
	 * @param string $path
	 * @return string|null
	 */
	public static function mimeType($disk, string $path): ?string
	{
		if (empty($path) || !$disk instanceof FilesystemAdapter) {
			return null;
		}
		
		if (!$disk->exists($path)) {
			return null;
		}
		
		$fileType = null;
		
		try {
			$fileType = ($disk->mimeType($path) === 'directory') ? 'directory' : 'file';
		} catch (\Throwable $e) {
			// dd($e->getMessage() . ' - ' . $path); // debug!
		}
		
		if (empty($fileType)) {
			try {
				// Check only non-empty file
				$size = $disk->size($path);
				$fileType = (is_numeric($size) && $size > 0) ? 'file' : null;
			} catch (\Throwable $e) {
				// The $disk->size(...) method provide fatal error for directories
				$fileType = 'directory';
			}
			
			// Checking for empty file
			// (For performance concern, make sure that checks only empty files)
			if (empty($fileType)) {
				$isDir = ($disk->get($path) == '' && is_array($disk->files($path)));
				$fileType = $isDir ? 'directory' : 'file';
			}
		}
		
		return $fileType;
	}
	
	/**
	 * Get the file full path on the storage
	 *
	 * @param $disk
	 * @param string $filePath
	 * @return string
	 */
	public static function fullFilePath($disk, string $filePath): string
	{
		$rootPath = config('filesystems.disks.' . config('filesystems.default') . '.root');
		
		if (!str_ends_with($rootPath, DIRECTORY_SEPARATOR)) {
			$rootPath .= DIRECTORY_SEPARATOR;
		}
		
		return $rootPath . $filePath;
	}
}
