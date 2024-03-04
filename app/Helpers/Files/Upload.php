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
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Http\File as HttpFile;

class Upload
{
	/**
	 * @param string|null $destPath
	 * @param $file
	 * @param string|array|null $param
	 * @param bool $withWatermark
	 * @return string|null
	 */
	public static function image(?string $destPath, $file, string|array|null $param = null, bool $withWatermark = false): ?string
	{
		if (empty($file)) {
			return null;
		}
		
		// Case #1: No file is uploaded
		if (!$file instanceof UploadedFile) {
			if (!is_string($file)) {
				return null;
			}
			
			if (str_contains($file, $destPath) && !str_starts_with($file, $destPath)) {
				$file = $destPath . last(explode($destPath, $file));
			}
			
			if (str_starts_with($file, url('storage'))) {
				$file = ltrim(str_replace(url('storage'), '', $file), '/');
			}
			
			// Never save in DB the default fallback picture path
			if (str_contains($file, config('larapen.core.picture.default'))) {
				$file = null;
			}
			
			return $file;
		}
		
		# Case #2: Upload the file
		$disk = StorageDisk::getDisk();
		
		try {
			// Init. Intervention
			$image = Image::make($file);
			
			// Case #2: File is uploaded
			// Get file extension
			$extension = $file->getClientOriginalExtension();
			if (empty($extension)) {
				$extension = 'jpg';
			}
			
			// Image quality
			$imageQuality = config('settings.upload.image_quality', 90);
			
			// Param(s)
			if (is_string($param) || empty($param)) {
				$type = (!empty($type)) ? $type . '_' : '';
				
				$width = (int)config('settings.upload.img_resize_' . $type . 'width', 1000);
				$height = (int)config('settings.upload.img_resize_' . $type . 'height', 1000);
				$ratio = config('settings.upload.img_resize_' . $type . 'ratio', '1');
				$upSize = config('settings.upload.img_resize_' . $type . 'upsize', '0');
				$prefix = null;
			} else {
				$imageQuality = $param['quality'] ?? $imageQuality;
				$width = $param['width'] ?? 1000;
				$height = $param['height'] ?? 1000;
				$ratio = $param['ratio'] ?? '1';
				$upSize = $param['upsize'] ?? '0';
				$prefix = $param['filename'] ?? null;
			}
			
			// Generate a filename
			if (!empty($prefix)) {
				$filename = uniqid($prefix);
			} else {
				$filename = md5($file->getClientOriginalName() . random_int(1, 9999) . time());
			}
			$filename = $filename . '.' . $extension;
			
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
			$image = $image->encode($extension, $imageQuality);
			
			// Is it with Watermark?
			if ($withWatermark) {
				// Check and load Watermark plugin
				$plugin = load_installed_plugin('watermark');
				if (!empty($plugin)) {
					$image = call_user_func($plugin->class . '::apply', $image);
					if (empty($image)) {
						return null;
					}
				}
			}
			
			// Get file path
			$filePath = $destPath . '/' . $filename;
			
			// Store the image on disk
			$disk->put($filePath, $image->stream()->__toString());
			
			// Save this path to the database
			return $filePath;
		} catch (\Throwable $e) {
			return self::showError($e);
		}
	}
	
	/**
	 * @param string|null $destPath
	 * @param $file
	 * @param string|null $diskName
	 * @return string|null
	 */
	public static function file(?string $destPath, $file, ?string $diskName = null): ?string
	{
		if (empty($file)) {
			return null;
		}
		
		if (!$file instanceof UploadedFile) {
			if (!is_string($file)) {
				return null;
			}
			
			if (str_contains($file, $destPath) && !str_starts_with($file, $destPath)) {
				$file = $destPath . last(explode($destPath, $file));
			}
			
			if (str_starts_with($file, url('storage'))) {
				$file = ltrim(str_replace(url('storage'), '', $file), '/');
			}
			
			return $file;
		}
		
		$disk = StorageDisk::getDisk($diskName);
		
		try {
			// Generate a filename
			$filename = md5($file->getClientOriginalName() . random_int(1, 9999) . time());
			$filename = $filename . '.' . $file->getClientOriginalExtension();
			
			// Get filepath
			$filePath = $destPath . '/' . $filename;
			
			// Store the file on disk
			$disk->put($filePath, File::get($file->getrealpath()));
			
			// Return the path (to the database later)
			return $filePath;
		} catch (\Throwable $e) {
			return self::showError($e);
		}
	}
	
	/**
	 * Create an UploadedFile object from base64 file content
	 *
	 * @param string|null $base64File
	 * @param bool $test
	 * @return false|\Illuminate\Http\UploadedFile
	 */
	public static function fromBase64(?string $base64File, bool $test = true): bool|UploadedFile
	{
		$isBase64File = (!empty($base64File) && str_starts_with($base64File, 'data:image'));
		
		if (!$isBase64File) {
			return false;
		}
		
		// Get file extension
		$matches = [];
		preg_match('#data:image/([^;]+);base64#', $base64File, $matches);
		$extension = !empty($matches[1]) ? $matches[1] : 'png';
		
		// Get file data base64 string
		// $fileData = preg_replace('#^data:image/\w+;base64,#i', '', $base64File);
		$fileData = last(explode(',', $base64File));
		
		// Decode the base64 file
		$fileData = base64_decode($fileData);
		
		// Save it to temporary dir first
		$tmpFilePath = sys_get_temp_dir() . '/' . Str::uuid()->toString();
		file_put_contents($tmpFilePath, $fileData);
		
		// This just to help us get file info
		$tmpFile = new HttpFile($tmpFilePath);
		
		$path = $tmpFile->getPathname();
		$originalName = $tmpFile->getFilename() . '.' . $extension;
		$mimeType = $tmpFile->getMimeType();
		$error = null;
		
		return new UploadedFile($path, $originalName, $mimeType, $error, $test);
	}
	
	/**
	 * Create an UploadedFile object from file's full path
	 *
	 * @param string $path
	 * @param bool $test
	 * @return false|\Illuminate\Http\UploadedFile
	 */
	public static function fromPath(string $path, bool $test = true): bool|UploadedFile
	{
		if (empty($path) || !Storage::exists($path)) {
			return false;
		}
		
		$path = Storage::path($path);
		
		$filesystem = new Filesystem();
		$originalName = $filesystem->name($path) . '.' . $filesystem->extension($path);
		$mimeType = $filesystem->mimeType($path);
		$error = null;
		
		return new UploadedFile($path, $originalName, $mimeType, $error, $test);
	}
	
	/**
	 * @param \Throwable $e
	 * @return null
	 */
	private static function showError(\Throwable $e)
	{
		if (!isFromApi()) {
			if (isFromAdminPanel()) {
				Alert::error($e->getMessage())->flash();
			} else {
				flash($e->getMessage())->error();
			}
		} else {
			abort(500, $e->getMessage());
		}
		
		return null;
	}
}
