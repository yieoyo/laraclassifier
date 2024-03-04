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

use App\Models\User;
use App\Rules\UsernameIsAllowedRule;
use App\Rules\UsernameIsValidRule;
use Illuminate\Validation\Rule;

class UserRequest extends Request
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
		$authFields = array_keys(getAuthFields());
		
		$rules = [];
		
		// CREATE
		if (in_array($this->method(), ['POST', 'CREATE'])) {
			$rules = $this->storeRules($authFields);
		}
		
		// UPDATE
		if (in_array($this->method(), ['PUT', 'PATCH', 'UPDATE'])) {
			$rules = $this->updateRules($authFields);
		}
		
		return $rules;
	}
	
	/**
	 * @param array $authFields
	 * @return array
	 */
	public function storeRules(array $authFields): array
	{
		$rules = [
			'name'          => ['required', 'min:2', 'max:100'],
			'country_code'  => ['sometimes', 'required', 'not_in:0'],
			'auth_field'    => ['required', Rule::in($authFields)],
			'phone'         => ['max:30'],
			'phone_country' => ['required_with:phone'],
			'password'      => ['required'],
		];
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		$usersTable = config('permission.table_names.users', 'users');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		if ($this->filled('email')) {
			$rules['email'][] = 'unique:' . $usersTable . ',email';
		}
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		if ($this->filled('phone')) {
			$rules['phone'][] = 'unique:' . $usersTable . ',phone';
		}
		
		// username
		if ($this->filled('username')) {
			$rules['username'] = [
				'between:3,50',
				'unique:' . $usersTable . ',username',
				new UsernameIsValidRule(),
				new UsernameIsAllowedRule(),
			];
		}
		
		return $this->validPasswordRules('password', $rules);
	}
	
	/**
	 * @param array $authFields
	 * @return array
	 */
	public function updateRules(array $authFields): array
	{
		$rules = [
			'name'          => ['required', 'max:100'],
			'country_code'  => ['sometimes', 'required', 'not_in:0'],
			'auth_field'    => ['required', Rule::in($authFields)],
			'phone'         => ['max:30'],
			'phone_country' => ['required_with:phone'],
			'username'      => [new UsernameIsValidRule(), new UsernameIsAllowedRule()],
		];
		
		$emailChanged = false;
		$phoneChanged = false;
		$usernameChanged = false;
		
		$user = null;
		$userId = request()->segment(3);
		if (!empty($userId) && is_numeric($userId)) {
			$user = User::find($userId);
		}
		
		if (isFromAdminPanel() && request()->segment(2) == 'account') {
			$user = auth()->check() ? auth()->user() : null;
		}
		
		// Check if these fields has changed
		if (!empty($user)) {
			$emailChanged = ($this->filled('email') && $this->input('email') != $user->email);
			$phoneChanged = ($this->filled('phone') && $this->input('phone') != $user->phone);
			$usernameChanged = ($this->filled('username') && $this->input('username') != $user->username);
		}
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		$usersTable = config('permission.table_names.users', 'users');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		if ($emailChanged) {
			$rules['email'][] = 'unique:' . $usersTable . ',email';
		}
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		if ($phoneChanged) {
			$rules['phone'][] = 'unique:' . $usersTable . ',phone';
		}
		
		// username
		if ($this->filled('username')) {
			$rules['username'][] = 'between:3,50';
		}
		if ($usernameChanged) {
			$rules['username'][] = 'required';
			$rules['username'][] = 'unique:' . $usersTable . ',username';
		}
		
		return $this->validPasswordRules('password', $rules);
	}
}
