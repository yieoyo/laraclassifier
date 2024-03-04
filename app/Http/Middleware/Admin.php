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

use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Prologue\Alerts\Facades\Alert;

class Admin
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @param $guard
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
	 */
	public function handle(Request $request, Closure $next, $guard = null)
	{
		if (!auth()->check()) {
			// Block access if user is guest (not logged in)
			if ($request->ajax() || $request->wantsJson()) {
				return response(trans('admin.unauthorized'), Response::HTTP_UNAUTHORIZED);
			} else {
				if ($request->path() != admin_uri('login')) {
					Alert::error(trans('admin.unauthorized'))->flash();
					
					return redirect()->guest(admin_uri('login'));
				}
			}
		} else {
			try {
				$aclTableNames = config('permission.table_names');
				if (isset($aclTableNames['permissions'])) {
					if (!Schema::hasTable($aclTableNames['permissions'])) {
						return $next($request);
					}
				}
			} catch (\Throwable $e) {
				return $next($request);
			}
			
			$user = User::query()->count();
			if (!($user == 1)) {
				// If user does //not have this permission
				if (!auth()->guard($guard)->user()->can(Permission::getStaffPermissions())) {
					if ($request->ajax() || $request->wantsJson()) {
						return response(trans('admin.unauthorized'), Response::HTTP_UNAUTHORIZED);
					} else {
						auth()->logout();
						Alert::error(trans('admin.unauthorized'))->flash();
						
						return redirect()->guest(admin_uri('login'));
					}
				}
			}
		}
		
		return $next($request);
	}
}
