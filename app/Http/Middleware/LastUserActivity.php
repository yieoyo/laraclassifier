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

use App\Helpers\Date;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LastUserActivity
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		// Exception for Install & Upgrade Routes
		if (str_contains(currentRouteAction(), 'Web\Install\\')) {
			return $next($request);
		}
		
		// Waiting time in minutes
		$waitingTime = 5;
		
		$guard = (isFromApi()) ? 'sanctum' : null;
		if (!auth($guard)->check()) {
			return $next($request);
		}
		
		$user = auth($guard)->user();
		
		if (config('settings.optimization.cache_driver') == 'array') {
			if (Schema::hasColumn('users', 'last_activity')) {
				return $next($request);
			}
			
			$timeAgoFromNow = Carbon::now(Date::getAppTimeZone())->subMinutes($waitingTime);
			if (
				empty($user->original_last_activity)
				|| (
					isset($user->last_activity)
					&& (
						($user->last_activity instanceof Carbon && $user->last_activity->lt($timeAgoFromNow))
						|| (is_string($user->last_activity) && $user->last_activity < $timeAgoFromNow->format('Y-m-d H:i:s'))
					)
				)
			) {
				$user->last_activity = new Carbon;
				$user->timestamps = false;
				$user->save();
			}
		} else {
			$expiresAt = Carbon::now(Date::getAppTimeZone())->addMinutes($waitingTime);
			cache()->store('file')->put('user-is-online-' . $user->id, true, $expiresAt);
		}
		
		return $next($request);
	}
}
