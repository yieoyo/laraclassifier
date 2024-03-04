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

namespace App\Http\Middleware\InputRequest;

use App\Helpers\Files\Upload;
use Illuminate\Http\Request;

trait UploadFile
{
	/**
	 * Apply Global Inputs to the API Calls
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Request
	 */
	protected function convertBase64FileToUploadedFile(Request $request): Request
	{
		if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
			return $request;
		}
		
		// Get all fields values
		$input = $request->all();
		
		// Remove all HTML tags in the fields values
		// Except fields: description
		array_walk_recursive($input, function (&$input, $key) use ($request) {
			if (
				!empty($input)
				&& is_string($input)
				&& str_starts_with($input, 'data:image')
			) {
				$input = Upload::fromBase64($input);
			}
		});
		
		// Replace the fields values
		$request->merge($input);
		
		// Clear convertedFiles array (set null).
		// This action forces $request->file() method to convert request files again
		// So when you use $request->file('file') or $request->allFiles() or something like this, our new 'file'
		// object will be available!
		// $request->convertedFiles = null;
		
		return $request;
	}
}
