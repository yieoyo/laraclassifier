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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Traits\Charts\ChartjsTrait;
use App\Http\Controllers\Web\Admin\Traits\Charts\MorrisTrait;
use App\Models\Post;
use App\Models\Country;
use App\Models\User;
use App\Http\Controllers\Web\Admin\Panel\PanelController;

class DashboardController extends PanelController
{
	use MorrisTrait, ChartjsTrait;
	
	public $data = [];
	
	protected int $countCountries = 0;
	
	/**
	 * Create a new controller instance.
	 */
	public function __construct()
	{
		$this->middleware('admin');
		
		parent::__construct();
		
		// Get the Mini Stats data
		try {
			$countActivatedPosts = Post::verified()->count();
			$countUnactivatedPosts = Post::unverified()->count();
			$countActivatedUsers = User::doesntHave('permissions')->verified()->count();
			$countUnactivatedUsers = User::doesntHave('permissions')->unverified()->count();
			$countUsers = User::doesntHave('permissions')->count();
			$this->countCountries = Country::where('active', 1)->count();
		} catch (\Throwable $e) {
		}
		
		view()->share('countActivatedPosts', $countActivatedPosts ?? 0);
		view()->share('countUnactivatedPosts', $countUnactivatedPosts ?? 0);
		view()->share('countActivatedUsers', $countActivatedUsers ?? 0);
		view()->share('countUnactivatedUsers', $countUnactivatedUsers ?? 0);
		view()->share('countUsers', $countUsers ?? 0);
		view()->share('countCountries', $this->countCountries ?? 0);
	}
	
	/**
	 * Show the admin dashboard.
	 *
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function dashboard()
	{
		// Dashboard's Latest Entries Chart: 'bar' or 'line'
		$tmp = @explode('_', config('settings.app.vector_charts_type'));
		$chartProvider = data_get($tmp, '0');
		$chartType = data_get($tmp, '1');
		
		$this->data['chartsType'] = [
			'provider' => !empty($chartProvider) ? $chartProvider : 'morris',
			'type'     => !empty($chartType) ? $chartType : 'bar',
		];
		
		// Limit latest entries
		$latestEntriesLimit = config('settings.app.latest_entries_limit', 5);
		
		// -----
		
		// Get latest Ads
		$this->data['latestPosts'] = Post::with([
			'payment',
			'payment.package',
			'user',
			'country',
		])->take($latestEntriesLimit)->orderByDesc('created_at')->get();
		
		// Get latest Users
		$this->data['latestUsers'] = User::with(['country'])
			->take($latestEntriesLimit)
			->orderByDesc('created_at')->get();
		
		// Get latest entries charts
		$statsDaysNumber = 30;
		
		$getLatestListingsChartMethod = 'getLatestListingsFor' . ucfirst($this->data['chartsType']['provider']);
		if (method_exists($this, $getLatestListingsChartMethod)) {
			$this->data['latestPostsChart'] = $this->$getLatestListingsChartMethod($statsDaysNumber);
		}
		
		$getLatestUsersChartMethod = 'getLatestUsersFor' . ucfirst($this->data['chartsType']['provider']);
		if (method_exists($this, $getLatestUsersChartMethod)) {
			$this->data['latestUsersChart'] = $this->$getLatestUsersChartMethod($statsDaysNumber);
		}
		
		// Get entries per country charts
		if (config('settings.app.show_countries_charts')) {
			$countriesLimit = 10;
			$this->data['postsPerCountry'] = $this->getPostsPerCountryForChartjs($countriesLimit);
			$this->data['usersPerCountry'] = $this->getUsersPerCountryForChartjs($countriesLimit);
		}
		
		// -----
		
		// Page Title
		$this->data['title'] = trans('admin.dashboard');
		
		return view('admin.dashboard.index', $this->data);
	}
	
	/**
	 * Redirect to the dashboard.
	 *
	 * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
	 */
	public function redirect()
	{
		// The '/admin' route is not to be used as a page, because it breaks the menu's active state.
		return redirect(admin_uri('dashboard'));
	}
}
