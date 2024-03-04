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
use Illuminate\Validation\Rule;

class SendMessageRequest extends Request
{
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
		
		// phone
		if ($this->filled('phone')) {
			$input['phone'] = phoneE164($this->input('phone'), getPhoneCountry());
			$input['phone_national'] = phoneNational($this->input('phone'), getPhoneCountry());
		} else {
			$input['phone'] = null;
			$input['phone_national'] = null;
		}
		
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
		$authFields = array_keys(getAuthFields());
		
		$rules = [
			'name'          => ['required', new BetweenRule(2, 200)],
			'auth_field'    => ['required', Rule::in($authFields)],
			'email'         => ['max:100'],
			'phone'         => ['max:30'],
			'phone_country' => ['required_with:phone'],
			'body'          => ['required', new BetweenRule(20, 500)],
			'post_id'       => ['required', 'numeric'],
		];
		
		// Check 'resume' is required
		if ($this->filled('catType') && $this->input('catType') == 'job-offer') {
			$rules['filename'] = [
				'required',
				'mimes:' . getUploadFileTypes('file'),
				'min:' . (int)config('settings.upload.min_file_size', 0),
				'max:' . (int)config('settings.upload.max_file_size', 1000),
			];
		}
		
		// email
		$emailIsRequired = ($this->input('auth_field') == 'email');
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		
		// phone
		$phoneNumberIsRequired = ($this->input('auth_field') == 'phone');
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		
		return $this->captchaRules($rules);
	}
}
