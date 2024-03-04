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

namespace App\Http\Requests;

use App\Http\Requests\Traits\CommonRules;
use App\Http\Requests\Traits\ErrorOutputFormat;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

abstract class Request extends FormRequest
{
	use ErrorOutputFormat;
	use CommonRules;
	
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return true;
	}
	
	/**
	 * Handle a failed validation attempt.
	 *
	 * @param Validator $validator
	 * @throws ValidationException
	 */
	protected function failedValidation(Validator $validator)
	{
		if (isFromApi() || $this->ajax() || $this->wantsJson()) {
			// Get Errors
			$errors = (new ValidationException($validator))->errors();
			
			// Add a specific json attributes for 'bootstrap-fileinput' plugin
			$hasFileinputField = (
				str_contains(get_called_class(), 'PhotoRequest')
				|| str_contains(get_called_class(), 'AvatarRequest')
			);
			if ($hasFileinputField) {
				// NOTE: 'bootstrap-fileinput' need 'error' (text) element & the optional 'errorkeys' (array) element
				$data = [
					'error' => $this->fileinputFormatError($errors),
				];
			} else {
				if (isFromApi()) {
					$message = $this->apiFormatError($errors);
				} else {
					$isAjaxRequest = ($this->ajax() || $this->wantsJson());
					$message = $isAjaxRequest
						? $this->simpleFormatError($errors)
						: $this->webFormatError($errors);
				}
				
				$data = [
					'success' => false,
					'message' => $message,
					'errors'  => $errors,
				];
			}
			
			throw new HttpResponseException(response()->json($data, Response::HTTP_UNPROCESSABLE_ENTITY));
		}
		
		parent::failedValidation($validator);
	}
}
