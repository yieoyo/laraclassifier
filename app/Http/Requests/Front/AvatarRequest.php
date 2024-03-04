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

namespace App\Http\Requests\Front;

use App\Http\Requests\Request;

class AvatarRequest extends Request
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		$guard = isFromApi() ? 'sanctum' : null;
		
		return auth($guard)->check();
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		$rules['photo'] = [
			'required',
			'image',
			'mimes:' . getUploadFileTypes('image'),
			'min:' . (int)config('settings.upload.min_image_size', 0),
			'max:' . (int)config('settings.upload.max_image_size', 1000),
		];
		
		return $rules;
	}
	
	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [];
		
		$attributes['photo'] = strtolower(t('Photo'));
		
		return $attributes;
	}
	
	/**
	 * Get custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		// uploaded
		$maxSize = (int)config('settings.upload.max_image_size', 1000); // In KB
		$maxSize = $maxSize * 1024; // Convert KB to Bytes
		$msg = t('large_file_uploaded_error', [
			'field'   => strtolower(t('Photo')),
			'maxSize' => readableBytes($maxSize),
		]);
		
		$uploadMaxFilesizeStr = @ini_get('upload_max_filesize');
		$postMaxSizeStr = @ini_get('post_max_size');
		if (!empty($uploadMaxFilesizeStr) && !empty($postMaxSizeStr)) {
			$uploadMaxFilesize = (int)strToDigit($uploadMaxFilesizeStr);
			$postMaxSize = (int)strToDigit($postMaxSizeStr);
			
			$serverMaxSize = min($uploadMaxFilesize, $postMaxSize);
			$serverMaxSize = $serverMaxSize * 1024 * 1024; // Convert MB to KB to Bytes
			if ($serverMaxSize < $maxSize) {
				$msg = t('large_file_uploaded_error_system', [
					'field'   => strtolower(t('Photo')),
					'maxSize' => readableBytes($serverMaxSize),
				]);
			}
		}
		
		$messages['photo.uploaded'] = $msg;
		
		return $messages;
	}
}
