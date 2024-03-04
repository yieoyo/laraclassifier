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
use App\Rules\UsernameIsAllowedRule;
use App\Rules\UsernameIsValidRule;
use Illuminate\Validation\Rule;

class UserRequest extends Request
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize(): bool
	{
		if (in_array($this->method(), ['POST', 'CREATE'])) {
			return true;
		} else {
			$guard = isFromApi() ? 'sanctum' : null;
			
			return auth($guard)->check();
		}
	}
	
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		// Don't apply this to the Admin Panel
		if (isAdminPanel()) {
			return;
		}
		
		$input = $this->all();
		
		// name
		if ($this->filled('name')) {
			$input['name'] = str_cleaner($this->input('name'));
			$input['name'] = prevent_str_containing_only_digit_chars($input['name']);
		}
		
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
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		$authFields = array_keys(getAuthFields());
		
		// CREATE
		if (in_array($this->method(), ['POST', 'CREATE'])) {
			$rules = $this->storeRules($authFields);
		}
		
		// UPDATE
		if (in_array($this->method(), ['PUT', 'PATCH', 'UPDATE'])) {
			$rules = $this->updateRules($authFields);
		}
		
		// photo
		if ($this->hasFile('photo')) {
			$rules['photo'] = [
				'required',
				'image',
				'mimes:' . getUploadFileTypes('image'),
				'min:' . (int)config('settings.upload.min_image_size', 0),
				'max:' . (int)config('settings.upload.max_image_size', 1000),
			];
		}
		
		return $rules;
	}
	
	/**
	 * @param array $authFields
	 * @return array
	 */
	private function storeRules(array $authFields): array
	{
		$rules = [
			'name'          => ['required', new BetweenRule(2, 200)],
			'country_code'  => ['sometimes', 'required', 'not_in:0'],
			'auth_field'    => ['required', Rule::in($authFields)],
			'phone'         => ['max:30'],
			'phone_country' => ['required_with:phone'],
			'password'      => ['required', 'confirmed'],
			'accept_terms'  => ['accepted'],
		];
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		if ($this->filled('email')) {
			$rules['email'][] = 'unique:users,email';
		}
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		if ($this->filled('phone')) {
			$rules['phone'][] = 'unique:users,phone';
		}
		
		// username
		$usernameIsEnabled = !config('larapen.core.disable.username');
		if ($usernameIsEnabled) {
			if ($this->filled('username')) {
				$rules['username'] = [
					'between:3,50',
					'unique:users,username',
					new UsernameIsValidRule(),
					new UsernameIsAllowedRule(),
				];
			}
		}
		
		// password
		$rules = $this->validPasswordRules('password', $rules);
		
		return $this->captchaRules($rules);
	}
	
	/**
	 * @param array $authFields
	 * @return array
	 */
	private function updateRules(array $authFields): array
	{
		$guard = isFromApi() ? 'sanctum' : null;
		$user = auth($guard)->user();
		
		$rules = [
			'name'          => ['required', 'max:100'],
			'auth_field'    => ['required', Rule::in($authFields)],
			'phone'         => ['max:30'],
			'phone_country' => ['required_with:phone'],
			'username'      => [new UsernameIsValidRule(), new UsernameIsAllowedRule()],
		];
		
		// Check if these fields have changed
		$emailChanged = ($this->filled('email') && $this->input('email') != $user->email);
		$phoneChanged = ($this->filled('phone') && $this->input('phone') != $user->phone);
		$usernameChanged = ($this->filled('username') && $this->input('username') != $user->username);
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		if ($emailChanged) {
			$rules['email'][] = 'unique:users,email';
		}
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		if ($phoneChanged) {
			$rules['phone'][] = 'unique:users,phone';
		}
		
		// username
		if ($this->filled('username')) {
			$rules['username'][] = 'between:3,50';
		}
		if ($usernameChanged) {
			$rules['username'][] = 'required';
			$rules['username'][] = 'unique:users,username';
		}
		
		// password
		$rules = $this->validPasswordRules('password', $rules);
		if ($this->filled('password')) {
			$rules['password'][] = 'confirmed';
		}
		
		if ($this->filled('user_accept_terms') && $this->input('user_accept_terms') != 1) {
			$rules['accept_terms'] = ['accepted'];
		}
		
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
		
		if ($this->hasFile('photo')) {
			$attributes['photo'] = strtolower(t('Photo'));
		}
		
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
		
		if ($this->hasFile('photo')) {
			// uploaded
			$maxSize = (int)config('settings.upload.max_image_size', 1000); // In KB
			$maxSize = $maxSize * 1024;                                     // Convert KB to Bytes
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
		}
		
		return $messages;
	}
}
