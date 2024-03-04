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

class RedirectIfAuthenticated
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @param ...$guards
	 * @return \Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next, ...$guards)
	{
		$guards = empty($guards) ? [null] : $guards;
		
		foreach ($guards as $guard) {
			if (auth()->guard($guard)->check()) {
				if (isFromApi()) {
					return $next($request);
				}
				
				$url = isFromAdminPanel() ? admin_uri() : '/';
				$url .= '?login=success';
				
				return redirect()->to($url);
			}
		}
		
		return $next($request);
	}
}
