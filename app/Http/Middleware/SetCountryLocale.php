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

class SetCountryLocale
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
		
		// Exception for Admin panel
		if (isAdminPanel()) {
			return $next($request);
		}
		
		// Get the User's Country info (by his IP address) \w the Country's language
		$country = config('country');
		if (!empty($country)) {
			// Check if the 'Website Country Language' detection option is activated
			if (config('settings.localization.auto_detect_language') == 'from_country') {
				// Check if the language is available in the system
				if (is_array($country) && !empty($country['lang'])) {
					$lang = collect($country['lang']);
					
					if ($lang->isNotEmpty() && $lang->has('code')) {
						// Config: Language (Updated)
						config()->set('lang.code', $lang->get('code'));
						config()->set('lang.locale', $lang->get('locale'));
						config()->set('lang.iso_locale', $lang->get('iso_locale'));
						config()->set('lang.tag', $lang->get('tag'));
						config()->set('lang.direction', $lang->get('direction'));
						config()->set('lang.russian_pluralization', $lang->get('russian_pluralization'));
						config()->set('lang.date_format', $lang->get('date_format'));
						config()->set('lang.datetime_format', $lang->get('datetime_format'));
						
						// Apply the country's language to the app
						// & to the system (if its locale is available on the server)
						if (isAvailableLang($lang->get('code'))) {
							app()->setLocale(config('lang.code'));
							systemLocale()->setLocale(config('lang.locale'));
						}
					}
				}
			}
		}
		
		return $next($request);
	}
}
