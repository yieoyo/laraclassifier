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

use App\Http\Middleware\Install\CheckInstallation;
use Closure;
use Illuminate\Http\Request;

class Install
{
	use CheckInstallation;
	
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\RedirectResponse|mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if ($this->isInstalled() && $this->installationIsNotInProgress()) {
			return redirect()->to('/');
		}
		
		return $next($request);
	}
}
