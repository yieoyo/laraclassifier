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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

trait HasUploadFields
{
	/*
	|--------------------------------------------------------------------------
	| Methods for storing uploaded files (used in CRUD).
	|--------------------------------------------------------------------------
	*/
	
	/**
	 * Handle file upload and DB storage for a file:
	 * - on CREATE
	 *     - stores the file at the destination path
	 *     - generates a name
	 *     - stores the full path in the DB;
	 * - on UPDATE
	 *     - if the value is null, deletes the file and sets null in the DB
	 *     - if the value is different, stores the different file and updates DB value.
	 *
	 * @param $value - Value for that column sent from the input.
	 * @param $attributeName - Model attribute name (and column in the db).
	 * @param $disk - Filesystem disk used to store files.
	 * @param $destinationPath - Path in disk where to store the files.
	 * @return string|null
	 * @throws \Exception
	 */
	public function uploadFileToDisk($value, $attributeName, $disk, $destinationPath): ?string
	{
		// If a new file is uploaded, delete the file from the disk
		if (
			request()->hasFile($attributeName)
			&& $this->{$attributeName}
			&& $this->{$attributeName} != null
		) {
			Storage::disk($disk)->delete($this->{$attributeName});
			
			return null;
		}
		
		// If the file input is empty, delete the file from the disk
		if (is_null($value) && $this->{$attributeName} != null) {
			Storage::disk($disk)->delete($this->{$attributeName});
			
			return null;
		}
		
		// If a new file is uploaded, store it on disk and its filename in the database
		if (request()->hasFile($attributeName) && request()->file($attributeName)->isValid()) {
			
			// 1. Generate a new file name
			$file = request()->file($attributeName);
			$newFileName = md5($file->getClientOriginalName() . random_int(1, 9999) . time()) . '.' . $file->getClientOriginalExtension();
			
			// 2. Move the new file to the correct path
			$filePath = $file->storeAs($destinationPath, $newFileName, $disk);
			
			// 3. Save the complete path to the database
			return is_string($filePath) ? $filePath : null;
		}
		
		return null;
	}
	
	/**
	 * Customized - Handle file upload and DB storage for a file
	 * NOTE: Used in JobClass
	 *
	 * @param $value
	 * @param $fieldName
	 * @param $attributeName
	 * @param $disk
	 * @param $destinationPath
	 * @return string|null
	 * @throws \Exception
	 */
	public function uploadFileToDiskCustom($value, $fieldName, $attributeName, $disk, $destinationPath): ?string
	{
		// If a new file is uploaded, delete the file from the disk
		if (
			request()->hasFile($fieldName)
			&& $this->{$attributeName}
			&& $this->{$attributeName} != null
		) {
			Storage::disk($disk)->delete($this->{$attributeName});
			$this->attributes[$attributeName] = null;
			
			return null;
		}
		
		// If the file input is empty, delete the file from the disk
		if (is_null($value) && $this->{$attributeName} != null) {
			Storage::disk($disk)->delete($this->{$attributeName});
			$this->attributes[$attributeName] = null;
			
			return null;
		}
		
		// If a new file is uploaded, store it on disk and its filename in the database
		if (request()->hasFile($fieldName) && request()->file($fieldName)->isValid()) {
			
			// 1. Generate a new file name
			$file = request()->file($fieldName);
			$newFileName = md5($file->getClientOriginalName() . random_int(1, 9999) . time()) . '.' . $file->getClientOriginalExtension();
			
			// 2. Move the new file to the correct path
			$filePath = $file->storeAs($destinationPath, $newFileName, $disk);
			$filePath = is_string($filePath) ? $filePath : null;
			
			// 3. Save the complete path to the database
			$this->attributes[$attributeName] = $filePath;
			
			return $filePath;
		}
		
		$this->attributes[$attributeName] = null;
		
		return null;
	}
	
	/**
	 * Handle multiple file upload and DB storage:
	 * - if files are sent
	 *     - stores the files at the destination path
	 *     - generates random names
	 *     - stores the full path in the DB, as JSON array;
	 * - if a hidden input is sent to clear one or more files
	 *     - deletes the file
	 *     - removes that file from the DB.
	 *
	 * @param $value - Value for that column sent from the input.
	 * @param $attributeName - Model attribute name (and column in the db).
	 * @param $disk - Filesystem disk used to store files.
	 * @param $destinationPath - Path in disk where to store the files.
	 * @return string|null
	 * @throws \Exception
	 */
	public function uploadMultipleFilesToDisk($value, $attributeName, $disk, $destinationPath): ?string
	{
		if (!is_array($this->{$attributeName})) {
			$attributeValue = json_decode($this->{$attributeName}, true) ?? [];
		} else {
			$attributeValue = $this->{$attribute_name};
		}
		
		$filesToClear = request()->query('clear_' . $attributeName);
		
		// If a file has been marked for removal,
		// delete it from the disk and from the db
		if ($filesToClear) {
			foreach ($filesToClear as $key => $filename) {
				Storage::disk($disk)->delete($filename);
				$attributeValue = Arr::where($attributeValue, function ($value, $key) use ($filename) {
					return $value != $filename;
				});
			}
		}
		
		// If a new file is uploaded, store it on disk and its filename in the database
		if (request()->hasFile($attributeName)) {
			foreach (request()->file($attributeName) as $file) {
				if ($file->isValid()) {
					// 1. Generate a new file name
					$newFileName = md5($file->getClientOriginalName() . random_int(1, 9999) . time()) . '.' . $file->getClientOriginalExtension();
					
					// 2. Move the new file to the correct path
					$filePath = $file->storeAs($destinationPath, $newFileName, $disk);
					
					// 3. Add the public path to the database
					$attributeValue[] = $filePath;
				}
			}
		}
		
		$json = json_encode($attributeValue);
		$json = is_string($json) ? $json : null;
		
		$this->attributes[$attributeName] = $json;
		
		return $json;
	}
}
