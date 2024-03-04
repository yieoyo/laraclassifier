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

use App\Helpers\UrlGen;
use Closure;
use Illuminate\Http\Request;

class TipsMessages
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
		
		if (!config('settings.other.show_tips_messages')) {
			return $next($request);
		}
		
		// SHOW MESSAGE... (About Login) If user not logged
		if (
			!auth()->check()
			&& request()->segment(1) !== null
			&& !str_contains(currentRouteAction(), 'RegisterController')
			&& !str_contains(currentRouteAction(), 'LoginController')
			&& !str_contains(currentRouteAction(), 'ForgotPasswordController')
			&& !str_contains(currentRouteAction(), 'ResetPasswordController')
			&& !str_contains(currentRouteAction(), 'Post\CreateOrEdit\\')
			&& !str_contains(currentRouteAction(), 'Search\\')
			&& !str_contains(currentRouteAction(), 'SitemapController')
			&& !str_contains(currentRouteAction(), 'PageController@cms')
			&& !str_contains(currentRouteAction(), 'PageController@contact')
		) {
			$msg = 'login_for_faster_access_to_the_best_deals';
			$siteCountryInfo = t($msg, [
				'login_url'    => UrlGen::login(),
				'register_url' => UrlGen::register(),
			]);
			$paddingTopExists = true;
		}
		
		// SHOW MESSAGE... (About Location)
		// - If we know the user IP country
		// - and if user visiting another country's website
		// - and if Geolocation is activated
		$countryCode = config('country.code');
		$ipCountryCode = config('ipCountry.code');
		$ipCountryName = config('ipCountry.name');
		if (config('settings.localization.geoip_activation')) {
			if (!empty($ipCountryCode) && !empty($countryCode)) {
				if ($ipCountryCode != $countryCode) {
					$msg = 'app_is_also_available_in_your_country';
					$siteCountryInfo = t($msg, [
						'appName' => config('settings.app.name'),
						'country' => getColumnTranslation($ipCountryName),
						'url'     => dmUrl($ipCountryCode, '/', true, true),
					]);
					$paddingTopExists = true;
				}
			}
		}
		
		// Share vars to views
		if (isset($siteCountryInfo) && $siteCountryInfo != '') {
			view()->share('siteCountryInfo', $siteCountryInfo);
		}
		if (isset($paddingTopExists)) {
			// On search results page, the search form is always the first row
			if (str_contains(currentRouteAction(), 'Search\\')) {
				$paddingTopExists = false;
			}
			view()->share('paddingTopExists', $paddingTopExists);
		}
		
		return $next($request);
	}
}
