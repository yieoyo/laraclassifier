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

use Illuminate\Http\Request;

trait CheckboxToDatetime
{
	/**
	 * The following method loops through all request input and strips out all tags from
	 * the request. This to ensure that users are unable to set ANY HTML within the form
	 * submissions, but also cleans up input.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Request
	 */
	protected function applyCheckboxToDatetime(Request $request): Request
	{
		// Exception for Install & Upgrade Routes
		if (str_contains(currentRouteAction(), 'Web\Install\\')) {
			return $request;
		}
		
		// Get all fields values
		$inputs = $request->all();
		
		// Set the right value for datetime column (displayed as checkbox) in the fields values
		array_walk_recursive($inputs, function (&$value, $key) use ($request) {
			if (str_ends_with($key, '_at')) {
				if (!isValidDate($value)) {
					$value = ($value == 1 || $value == '1' || $value === true) ? now() : null;
				}
			}
		});
		
		// Replace the fields values
		$request->merge($inputs);
		
		return $request;
	}
}
