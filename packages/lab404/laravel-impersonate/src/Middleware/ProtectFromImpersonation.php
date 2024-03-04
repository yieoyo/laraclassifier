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

namespace Larapen\Impersonate\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lab404\Impersonate\Services\ImpersonateManager;
use Prologue\Alerts\Facades\Alert;

class ProtectFromImpersonation
{
	/**
	 * Handle an incoming request.
	 *
	 * @param Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function handle(Request $request, Closure $next)
	{
		$impersonate_manager = app()->make(ImpersonateManager::class);
		
		if ($impersonate_manager->isImpersonating()) {
			$message = t('Can not be accessed by an impersonator');
			
			if ($request->ajax()) {
				// Add a specific json attributes for 'bootstrap-fileinput' plugin
				if (
					str_contains(Route::currentRouteAction(), 'EditController@updatePhoto')
					|| str_contains(Route::currentRouteAction(), 'EditController@deletePhoto')
				) {
					// NOTE: 'bootstrap-fileinput' need 'error' (text) element & the optional 'errorkeys' (array) element
					$result = ['error' => $message];
				} else {
					$result = [
						'success' => false,
						'msg'     => $message,
					];
				}
				
				return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
			} else {
				if ($request->segment(1) == admin_uri()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return redirect()->back();
			}
		}
		
		return $next($request);
	}
}
