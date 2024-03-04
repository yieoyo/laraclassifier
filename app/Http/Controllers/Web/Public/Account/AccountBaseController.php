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

namespace App\Http\Controllers\Web\Public\Account;

use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Support\Collection;

abstract class AccountBaseController extends FrontController
{
	/**
	 * AccountBaseController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
			if (auth()->check()) {
				$this->leftMenuInfo();
			}
			
			return $next($request);
		});
		
		// Get Page Current Path
		$pagePath = (request()->segment(1) == 'account') ? (request()->segment(3) ?? '') : '';
		view()->share('pagePath', $pagePath);
	}
	
	public function leftMenuInfo(): void
	{
		$authUser = auth()->user();
		if (empty($authUser)) return;
		
		// Get user's stats - Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier() . '/stats';
		$data = makeApiRequest('get', $endpoint);
		
		// Retrieve the stats
		$stats = data_get($data, 'result');
		
		// Format the account's sidebar menu
		$accountMenu = collect();
		if (isset($this->userMenu)) {
			$accountMenu = $this->userMenu->groupBy('group');
			$accountMenu = $accountMenu->map(function ($group, $k) use ($stats) {
				return $group->map(function ($item, $key) use ($stats) {
					$isActive = (isset($item['isActive']) && $item['isActive']);
					$countVar = isset($item['countVar']) ? data_get($stats, $item['countVar']) : null;
					$cssClass = !empty($item['countCustomClass']) ? $item['countCustomClass'] . ' hide' : '';
					
					$item['isActive'] = $isActive;
					$item['countVar'] = $countVar;
					$item['cssClass'] = $cssClass;
					
					return $item;
				})->reject(function ($item, $key) {
					return (is_numeric($item['countVar']) && $item['countVar'] < 0);
				});
			})->reject(function ($group, $k) {
				return ($group instanceof Collection) ? $group->isEmpty() : empty($group);
			});
		}
		
		// Export data to views
		view()->share('stats', $stats);
		view()->share('accountMenu', $accountMenu);
	}
}
