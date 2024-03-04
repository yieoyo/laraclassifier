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

namespace App\Http\Controllers\Web\Public\Post;

use App\Helpers\UrlGen;
use App\Http\Requests\Front\ReportRequest;
use App\Models\Post;
use App\Models\ReportType;
use App\Http\Controllers\Web\Public\FrontController;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ReportController extends FrontController
{
	/**
	 * ReportController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
			$this->commonQueries();
			
			return $next($request);
		});
		
		if (config('settings.single.auth_required_to_report_abuse')) {
			$this->middleware('auth')->only(['showReportForm', 'sendReport']);
		}
	}
	
	/**
	 * Common Queries
	 */
	public function commonQueries()
	{
		// Get Report abuse types
		$reportTypes = ReportType::query()->get();
		view()->share('reportTypes', $reportTypes);
	}
	
	public function showReportForm($postId)
	{
		// Get Post
		$postId = hashId($postId, true) ?? $postId;
		$post = Post::findOrFail($postId);
		
		// Meta Tags
		$title = t('Report for', ['title' => mb_ucfirst($post->title)]);
		$description = t('Send a report for', ['title' => mb_ucfirst($post->title)]);
		
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		
		// Open Graph
		$this->og->title($title)->description($description);
		view()->share('og', $this->og);
		
		// SEO: noindex
		$noIndexListingsReportPages = (
			config('settings.seo.no_index_listing_report')
			&& currentRouteActionContains('Post\ReportController')
		);
		
		return appView('post.report', compact('title', 'post', 'noIndexListingsReportPages'));
	}
	
	/**
	 * @param $postId
	 * @param \App\Http\Requests\Front\ReportRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function sendReport($postId, ReportRequest $request)
	{
		// Call API endpoint
		$postId = hashId($postId, true) ?? $postId;
		$endpoint = '/posts/' . $postId . '/report';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		$post = data_get($data, 'extra.post');
		
		if (!empty($post)) {
			return redirect()->to(UrlGen::postUri($post));
		} else {
			return redirect()->to('/');
		}
	}
}
