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
use App\Rules\BetweenRule;

class ReplyMessageRequest extends Request
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
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		$input = $this->all();
		
		// auth_field
		$input['auth_field'] = getAuthField();
		
		// body
		if ($this->filled('body')) {
			$string = $this->input('body');
			
			$string = strip_tags($string);
			$string = html_entity_decode($string);
			$string = strip_tags($string);
			
			$input['body'] = $string;
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
		
		if ($this->hasFile('filename')) {
			$rules['body'] = [new BetweenRule(1, 500)];
			$rules['filename'] = [
				'mimes:' . getUploadFileTypes('file'),
				'min:' . (int)config('settings.upload.min_file_size', 0),
				'max:' . (int)config('settings.upload.max_file_size', 1000),
			];
		} else {
			$rules['body'] = ['required', new BetweenRule(1, 500)];
		}
		
		return $rules;
	}
}
