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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyAPIAccess
{
	/**
	 * Handle an incoming request.
	 *
	 * Prevent any other application to call the API
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if (
			!(app()->environment('local'))
			&& (
				!request()->hasHeader('X-AppApiToken')
				|| request()->header('X-AppApiToken') !== config('larapen.core.api.token')
			)
		) {
			$message = 'You don\'t have access to this API.';
			
			return apiResponse()->forbidden($message);
		}
		
		return $next($request);
	}
}
