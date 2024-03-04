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

use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Payment\Promotion\MultiStepsPayment;
use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Web\Public\Payment\HasPaymentRedirection;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Http\Requests\Front\PackageRequest;
use App\Models\PaymentMethod;
use App\Models\Post;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class PaymentController extends FrontController
{
	use HasPaymentReferrers;
	use WizardTrait;
	use MultiStepsPayment, HasPaymentRedirection;
	
	public $request;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
			$this->request = $request;
			$this->commonQueries();
			
			return $next($request);
		});
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		$this->getPaymentReferrersData();
		$this->setPaymentSettingsForPromotion();
	}
	
	/**
	 * Show the form
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
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Get auth user
		$authUser = auth()->user();
		
		// Get Post
		$post = null;
		if (!empty($authUser)) {
			$post = Post::query()
				->inCountry()
				->with([
					'possiblePayment' => fn ($q) => $q->with('package'),
					'paymentEndingLater',
				])
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $postId)
				->first();
		}
		
		if (empty($post)) {
			abort(404, t('post_not_found'));
		}
		
		view()->share('post', $post);
		$this->shareWizardMenu($request, $post);
		
		// Share the post's current active payment info (If exists)
		$this->getCurrentActivePaymentInfo($post);
		
		// Meta Tags
		MetaTag::set('title', t('update_my_listing'));
		MetaTag::set('description', t('update_my_listing'));
		
		return appView('post.createOrEdit.multiSteps.packages.edit');
	}
	
	/**
	 * Submit the form
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm($postId, PackageRequest $request)
	{
		// Add required data in the request for API
		$inputArray = [
			'payable_type' => 'Post',
			'payable_id'   => $postId,
		];
		$request->merge($inputArray);
		
		// Check if the payment process has been triggered
		// NOTE: Payment bypass email or phone verification
		// ===| Make|send payment (if needed) |==============
		
		$post = $this->retrievePayableModel($request, $postId);
		abort_if(empty($post), 404, t('post_not_found'));
		
		$payResult = $this->isPaymentRequested($request, $post);
		if (data_get($payResult, 'success')) {
			return $this->sendPayment($request, $post);
		}
		if (data_get($payResult, 'failure')) {
			flash(data_get($payResult, 'message'))->error();
		}
		
		// ===| If no payment is made (continue) |===========
		
		$isOfflinePayment = PaymentMethod::query()
			->where('name', 'offlinepayment')
			->where('id', $request->input('payment_method_id'))
			->exists();
		
		// Notification Message
		if (!$isOfflinePayment) {
			flash(t('no_payment_is_made'))->info();
		}
		
		// Get the next URL & Notification
		$nextUrl = UrlGen::post($post);
		
		return redirect()->to($nextUrl);
	}
}
