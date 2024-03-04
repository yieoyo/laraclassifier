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

namespace App\Observers\Traits;

use App\Helpers\Files\Storage\StorageDisk;
use App\Helpers\Files\Tools\FileStorage;

trait PictureTrait
{
	/**
	 * Remove Picture With Its Thumbnails
	 *
	 * @param $filePath
	 * @param null $defaultPicture
	 */
	public static function removePictureWithItsThumbs($filePath, $defaultPicture = null)
	{
		if (empty($filePath)) {
			return;
		}
		
		if (empty($defaultPicture)) {
			$defaultPicture = config('larapen.core.picture.default');
		}
		
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		if (str_starts_with($filePath, 'uploads' . DIRECTORY_SEPARATOR)) {
			$filePath = str_replace('uploads' . DIRECTORY_SEPARATOR, '', $filePath);
		}
		
		// Get the picture filename (without path)
		$filename = basename($filePath);
		
		// Get the picture's directory
		$fileDir = dirname($filePath);
		
		if (!empty($fileDir) && $disk->exists($fileDir)) {
			if ($disk->directoryExists($fileDir)) {
				// Get all the files in the picture's directory
				$files = $disk->files($fileDir);
				if (!empty($files)) {
					foreach ($files as $file) {
						// Don't delete the default picture
						if (str_contains($file, $defaultPicture)) {
							continue;
						}
						// Delete the picture with its thumbs (by making a search with the picture original filename)
						if (str_contains($file, $filename)) {
							$disk->delete($file);
						}
					}
				}
				
				if (!str_contains($filePath, $defaultPicture)) {
					FileStorage::removeEmptySubDirs($disk, $fileDir);
				}
			}
		}
	}
}
