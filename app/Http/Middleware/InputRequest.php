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

use App\Http\Middleware\InputRequest\CheckboxToDatetime;
use App\Http\Middleware\InputRequest\UploadFile;
use App\Http\Middleware\InputRequest\XssProtection;
use Closure;
use Illuminate\Http\Request;

class InputRequest
{
	use CheckboxToDatetime, UploadFile, XssProtection;
	
	/**
	 * Apply Global Inputs to the API Calls
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		$request = $this->applyCheckboxToDatetime($request);
		$request = $this->convertBase64FileToUploadedFile($request);
		$request = $this->applyXssProtection($request);
		
		return $next($request);
	}
}
