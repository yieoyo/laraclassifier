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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../../../Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	$configForUpload = true;
	include_once $iniConfigFile;
}

use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Api\Payment\RetrievePackageFeatures;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits\PricingPageUrlTrait;
use App\Http\Requests\Front\PhotoRequest;
use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PhotoController extends FrontController
{
	use HasPaymentReferrers;
	use WizardTrait;
	use RetrievePackageFeatures;
	use PricingPageUrlTrait;
	
	public $data = [];
	public $package = null;
	
	/**
	 * PhotoController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
			$this->commonQueries();
			
			return $next($request);
		});
		
		$this->middleware('only.ajax')->only('delete');
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		// $isNewEntry = isPostCreationRequest();
		
		// $this->setPaymentSettingsForPromotion($isNewEntry);
		$this->getPaymentReferrersData();
		
		// Selected Package
		$this->package = $this->getSelectedPackage();
		view()->share('selectedPackage', $this->package);
		
		// Set the Package's pictures limit
		$this->getCurrentActivePaymentInfo(null, $this->package);
	}
	
	/**
	 * Show the form the create a new listing post.
	 *
	 * @param $postId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getForm($postId, Request $request)
	{
		// Check if the form type is 'Single-Step Form' and make redirection to it (permanently).
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			$url = url('edit/' . $postId);
			$url = qsUrl($url, request()->only(['package']), null, false);
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$data = [];
		
		// Get Post
		$post = null;
		if (auth()->check()) {
			// Get post - Call API endpoint
			$endpoint = '/posts/' . $postId;
			$queryParams = [
				'embed'               => 'pictures',
				'countryCode'         => config('country.code'),
				'unactivatedIncluded' => 1,
				'belongLoggedUser'    => 1, // Logged user required
				'noCache'             => 1,
			];
			$queryParams = array_merge(request()->all(), $queryParams);
			$data = makeApiRequest('get', $endpoint, $queryParams);
			
			$apiMessage = $this->handleHttpError($data);
			$post = data_get($data, 'result');
		}
		
		if (empty($post)) {
			abort(404, t('post_not_found'));
		}
		
		view()->share('post', $post);
		$this->shareWizardMenu($request, $post);
		
		// Set the Package's pictures limit
		if (!empty($this->package)) {
			$this->getCurrentActivePaymentInfo(null, $this->package);
		} else {
			// Share the post's current active payment info (If exists)
			// & Set the Package's pictures limit
			$this->getCurrentActivePaymentInfo($post);
		}
		
		// Get the Next URL
		if (
			$this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			$nextUrl = 'posts/' . $postId . '/payment';
			$nextStepLabel = t('Next');
		} else {
			$nextUrl = UrlGen::postUri($post);
			$nextStepLabel = t('Finish');
		}
		view()->share('nextStepUrl', $nextUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		
		// Meta Tags
		MetaTag::set('title', t('update_my_listing'));
		MetaTag::set('description', t('update_my_listing'));
		
		return appView('post.createOrEdit.multiSteps.photos.edit', $data);
	}
	
	/**
	 * Store a new listing post.
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function postForm($postId, PhotoRequest $request)
	{
		// Add required data in the request for API
		$inputArray = [
			'count_packages'        => $this->countPackages ?? 0,
			'count_payment_methods' => $this->countPaymentMethods ?? 0,
			'post_id'               => $postId,
		];
		request()->merge($inputArray);
		
		// Call API endpoint
		$endpoint = '/pictures';
		$data = makeApiRequest('post', $endpoint, request()->all(), [], true);
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			// AJAX Response
			if (request()->ajax()) {
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			// AJAX Response
			if (request()->ajax()) {
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			flash($message)->error();
		}
		
		$post = data_get($data, 'extra.post.result');
		
		// Get Next URL
		if (data_get($data, 'extra.steps.payment')) {
			$nextUrl = url('posts/' . $postId . '/payment');
		} else {
			$nextUrl = UrlGen::post($post);
		}
		$nextStepLabel = data_get($data, 'extra.nextStepLabel');
		
		view()->share('nextStepUrl', $nextUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		// AJAX Response
		if (request()->ajax()) {
			$data = data_get($data, 'extra.fileInput');
			
			return ajaxResponse()->json($data);
		}
		
		// Non AJAX Response
		return redirect()->to($nextUrl);
	}
	
	/**
	 * Delete picture
	 *
	 * @param $postId
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function delete($postId, $id)
	{
		// Add required data in the request for API
		$inputArray = ['post_id' => $postId];
		request()->merge($inputArray);
		
		// Call API endpoint
		$endpoint = '/pictures/' . $id;
		$data = makeApiRequest('delete', $endpoint, request()->all());
		
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		$result = ['status' => 0];
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			if (request()->ajax()) {
				$result['error'] = $message;
				
				return ajaxResponse()->json($result, $status);
			}
			
			return redirect()->back();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			if (request()->ajax()) {
				$result['status'] = 1;
				$result['message'] = $message;
				
				return ajaxResponse()->json($result);
			} else {
				flash($message)->success();
			}
		} else {
			if (request()->ajax()) {
				$result['error'] = $message;
				
				return ajaxResponse()->json($result, $status);
			} else {
				flash($message)->error();
			}
		}
		
		return redirect()->back();
	}
	
	/**
	 * Reorder pictures
	 *
	 * @param $postId
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorder($postId, Request $request): \Illuminate\Http\JsonResponse
	{
		$httpStatus = 200;
		$result = ['status' => 0, 'message' => null];
		
		$params = $request->input('params');
		
		if (
			isset($params['stack'])
			&& is_array($params['stack'])
			&& count($params['stack']) > 0
		) {
			$body = [];
			foreach ($params['stack'] as $position => $item) {
				if (array_key_exists('key', $item) && $item['key'] != '') {
					$body[] = [
						'id'       => $item['key'],
						'position' => $position,
					];
				}
			}
			
			if (!empty($body)) {
				$inputArray = [
					'post_id' => $postId,
					'body'    => json_encode($body),
				];
				request()->merge($inputArray);
				
				// Call API endpoint
				$endpoint = '/pictures/reorder';
				$headers = ['X-Action' => 'bulk'];
				$data = makeApiRequest('post', $endpoint, $request->all(), $headers);
				
				// Parsing the API response
				$httpStatus = (int)data_get($data, 'status');
				$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
				
				if (data_get($data, 'isSuccessful') && data_get($data, 'success')) {
					$result = [
						'status'  => 1,
						'message' => $message,
					];
				} else {
					$result['error'] = $message;
				}
			}
		}
		
		return ajaxResponse()->json($result, $httpStatus);
	}
}
