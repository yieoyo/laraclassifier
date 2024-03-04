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

use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Api\Payment\Subscription\SubscriptionPayment;
use App\Http\Controllers\Web\Public\Payment\HasPaymentRedirection;
use App\Http\Requests\Front\PackageRequest;
use App\Models\PaymentMethod;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class SubscriptionController extends AccountBaseController
{
	use HasPaymentReferrers;
	use SubscriptionPayment, HasPaymentRedirection;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
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
		$this->setPaymentSettingsForSubscription();
	}
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function getForm(Request $request)
	{
		// Get auth user
		$authUser = auth()->user();
		
		// Get User
		$user = null;
		if (!empty($authUser)) {
			$user = User::query()
				->with([
					'possiblePayment' => fn ($q) => $q->with('package'),
					'paymentEndingLater',
				])
				->withoutGlobalScopes([VerifiedScope::class])
				->where('id', $authUser->getAuthIdentifier())
				->first();
		}
		
		if (empty($user)) {
			abort(404, t('user_not_found'));
		}
		
		view()->share('user', $user);
		
		// Share the post's current active payment info (If exists)
		$this->getCurrentActivePaymentInfo($user);
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('update_my_subscription') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('update_my_subscription'));
		
		return appView('account.subscription');
	}
	
	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm(PackageRequest $request)
	{
		// Get auth user
		$authUser = auth()->user();
		abort_if(empty($authUser), 404, t('user_not_found'));
		
		$userId = $authUser->getAuthIdentifier();
		
		// Add required data in the request for API
		$inputArray = [
			'payable_type' => 'User',
			'payable_id'   => $userId,
		];
		$request->merge($inputArray);
		
		// Check if the payment process has been triggered
		// ===| Make|send payment (if needed) |==============
		
		$user = $this->retrievePayableModel($request, $userId);
		abort_if(empty($user), 404, t('user_not_found'));
		
		$payResult = $this->isPaymentRequested($request, $user);
		if (data_get($payResult, 'success')) {
			$this->sendPayment($request, $user);
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
		$nextUrl = url('account');
		
		return redirect()->to($nextUrl);
	}
}
