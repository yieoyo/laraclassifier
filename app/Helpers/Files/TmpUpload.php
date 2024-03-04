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

namespace App\Helpers\Files;

use App\Helpers\Files\Storage\StorageDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class TmpUpload
{
	/**
	 * @param $tmpUploadDir
	 * @param $file
	 * @return string|null
	 */
	public static function image($tmpUploadDir, $file): ?string
	{
		if (!$file instanceof UploadedFile) {
			return null;
		}
		
		$disk = StorageDisk::getDisk();
		
		try {
			// Get file original infos
			$fileOrigName = $file->getClientOriginalName();
			$fileOrigExtension = $file->getClientOriginalExtension();
			if (empty($fileOrigExtension)) {
				$fileOrigExtension = 'jpg';
			}
			
			// Image quality
			$imageQuality = config('settings.upload.image_quality', 90);
			
			// Image default dimensions
			$width = (int)config('settings.upload.img_resize_width', 1000);
			$height = (int)config('settings.upload.img_resize_height', 1000);
			
			// Other parameters
			$ratio = config('settings.upload.img_resize_ratio', '1');
			$upSize = config('settings.upload.img_resize_upsize', '0');
			
			// Init. Intervention
			$image = Image::make($file);
			
			// Fix the Image Orientation
			if (exifExtIsEnabled()) {
				$image = $image->orientate();
			}
			
			// If the original dimensions are higher than the resize dimensions
			// OR the 'upsize' option is enable, then resize the image
			if ($image->width() > $width || $image->height() > $height || $upSize == '1') {
				// Resize
				$image = $image->resize($width, $height, function ($constraint) use ($ratio, $upSize) {
					if ($ratio == '1') {
						$constraint->aspectRatio();
					}
					if ($upSize == '1') {
						$constraint->upsize();
					}
				});
			}
			
			// Encode the Image!
			$image = $image->encode($fileOrigExtension, $imageQuality);
			
			// Generate a filename
			$filename = md5($fileOrigName . time()) . '.' . $fileOrigExtension;
			
			// Get filepath
			$filePath = $tmpUploadDir . '/' . $filename;
			
			// Store the image on disk
			$disk->put($filePath, $image->stream()->__toString());
			
			// Return the path (to the database later)
			return $filePath;
		} catch (\Throwable $e) {
			abort(500, $e->getMessage());
		}
	}
	
	/**
	 * @param $tmpUploadDir
	 * @param $file
	 * @return string|null
	 */
	public static function file($tmpUploadDir, $file): ?string
	{
		if (!$file instanceof UploadedFile) {
			return null;
		}
		
		$disk = StorageDisk::getDisk();
		
		try {
			// Get file original infos
			$fileOrigName = $file->getClientOriginalName();
			$fileOrigExtension = $file->getClientOriginalExtension();
			
			// Generate a filename
			$filename = md5($fileOrigName . time()) . '.' . $fileOrigExtension;
			
			// Get filepath
			$filePath = $tmpUploadDir . '/' . $filename;
			
			// Store the file on disk
			$disk->put($filePath, File::get($file->getrealpath()));
			
			// Return the path (to the database later)
			return $filePath;
		} catch (\Throwable $e) {
		}
		
		return null;
	}
}
