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

namespace App\Http\Controllers\Api\Payment\Subscription;

use App\Helpers\Payment as PaymentHelper;
use App\Http\Controllers\Api\Payment\HasPaymentTrigger;
use App\Http\Controllers\Api\Payment\RetrievePackageFeatures;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Resources\UserResource;
use App\Models\Package;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;

trait SubscriptionPayment
{
	use RetrievePackageFeatures;
	use HasPaymentTrigger;
	
	public array $apiMsg = [];
	public array $apiUri = [];
	public ?Package $selectedPackage = null;
	
	/**
	 * Set payment settings for subscription packages
	 *
	 * @return void
	 */
	protected function setPaymentSettingsForSubscription(): void
	{
		// Messages
		$this->apiMsg['payable']['success'] = t('your_subscription_is_updated');
		$this->apiMsg['checkout']['success'] = t('payment_received');
		$this->apiMsg['checkout']['cancel'] = t('payment_cancelled_text');
		$this->apiMsg['checkout']['error'] = t('payment_error_text');
		
		// Set URLs
		$this->apiUri['previousUrl'] = url('account/subscription');
		$this->apiUri['nextUrl'] = url('account');
		$this->apiUri['paymentCancelUrl'] = url('account/#entryId/payment/cancel');
		$this->apiUri['paymentReturnUrl'] = url('account/#entryId/payment/success');
		
		// Payment Helper init.
		PaymentHelper::$country = collect(config('country'));
		PaymentHelper::$lang = collect(config('lang'));
		PaymentHelper::$msg = $this->apiMsg;
		PaymentHelper::$uri = $this->apiUri;
		
		// Selected Package
		$this->selectedPackage = $this->getSelectedPackage();
		
		if (!isFromApi()) {
			view()->share('selectedPackage', $this->selectedPackage);
		}
	}
	
	/**
	 * Store a subscription payment
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\JsonResponse|mixed
	 */
	public function storeSubscriptionPayment(PackageRequest $request)
	{
		$userId = $request->input('payable_id');
		$authUser = auth('sanctum')->user();
		
		$user = null;
		if (!empty($userId) && !empty($authUser)) {
			$user = User::withoutGlobalScopes([VerifiedScope::class])
				->with('payment')
				->where('id', $authUser->getAuthIdentifier())
				->first();
		}
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		// ===| Make|send payment (if needed) |==============
		
		$payResult = $this->isPaymentRequested($request, $user);
		if (data_get($payResult, 'success')) {
			return $this->sendPayment($request, $user);
		}
		if (data_get($payResult, 'failure')) {
			return apiResponse()->error(data_get($payResult, 'message'));
		}
		
		// ===| If no payment is made (continue) |===========
		
		$data = [
			'success' => true,
			'message' => t('your_subscription_is_updated'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
}

