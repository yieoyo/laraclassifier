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

namespace App\Http\Requests\Admin;

class PictureRequest extends Request
{
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		$input = $this->all();
		
		// filename
		if ($this->filled('filename')) {
			$input['filename'] = $this->input('filename');
			
			if (str_contains($input['filename'], config('larapen.core.picture.default'))) {
				$input['filename'] = null;
			}
		}
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = [];
		
		// Require 'pictures' if exists
		if ($this->file('filename')) {
			$rules['filename'] = [
				'image',
				'mimes:' . getUploadFileTypes('image'),
				'min:' . (int)config('settings.upload.min_image_size', 0),
				'max:' . (int)config('settings.upload.max_image_size', 1000),
			];
		} else {
			$rules['filename'][] = 'required';
		}
		
		return $rules;
	}
	
	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function attributes()
	{
		$attributes = [];
		
		$attributes['filename'] = trans('validation.attributes.picture');
		
		return $attributes;
	}
	
	/**
	 * Get custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages()
	{
		$messages = [];
		
		if ($this->file('filename')) {
			// $files = $this->file('filename');
			// uploaded
			$maxSize = (int)config('settings.upload.max_image_size', 1000); // In KB
			$maxSize = $maxSize * 1024; // Convert KB to Bytes
			$msg = t('large_file_uploaded_error', [
				'field'   => t('picture'),
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
						'field'   => t('picture'),
						'maxSize' => readableBytes($serverMaxSize),
					]);
				}
			}
			
			$messages['filename.uploaded'] = $msg;
		}
		
		return $messages;
	}
}
