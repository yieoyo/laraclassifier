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

namespace App\Http\Controllers\Api\Payment\Promotion;

use App\Helpers\Payment as PaymentHelper;
use App\Http\Controllers\Api\Payment\HasPaymentTrigger;
use App\Http\Controllers\Api\Payment\RetrievePackageFeatures;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Resources\PostResource;
use App\Models\Package;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;

trait MultiStepsPayment
{
	use RetrievePackageFeatures;
	use HasPaymentTrigger;
	
	public array $apiMsg = [];
	public array $apiUri = [];
	public ?Package $selectedPackage = null;
	
	/**
	 * Set payment settings for promotion packages (Multi-Steps Form)
	 *
	 * @return void
	 */
	protected function setPaymentSettingsForPromotion(): void
	{
		// Messages
		$this->apiMsg['payable']['success'] = t('your_listing_is_updated');
		$this->apiMsg['checkout']['success'] = t('payment_received');
		$this->apiMsg['checkout']['cancel'] = t('payment_cancelled_text');
		$this->apiMsg['checkout']['error'] = t('payment_error_text');
		
		// Set URLs
		$this->apiUri['previousUrl'] = url('posts/#entryId/payment');
		$nextUrl = str_replace(
			['{slug}', '{hashableId}', '{id}'],
			['#entrySlug', '#entryId', '#entryId'],
			(config('routes.post') ?? '#entrySlug/#entryId')
		);
		$this->apiUri['nextUrl'] = url($nextUrl);
		$this->apiUri['paymentCancelUrl'] = url('posts/#entryId/payment/cancel');
		$this->apiUri['paymentReturnUrl'] = url('posts/#entryId/payment/success');
		
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
	 * Store a promotion payment (Multi-Steps Form)
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\JsonResponse|mixed
	 */
	public function multiStepsPaymentStore(PackageRequest $request)
	{
		$postId = $request->input('payable_id');
		$authUser = auth('sanctum')->user();
		
		$post = null;
		if (!empty($postId) && !empty($authUser)) {
			$post = Post::withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->with('payment')
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $postId)
				->first();
		}
		
		if (empty($post)) {
			return apiResponse()->notFound(t('post_not_found'));
		}
		
		// ===| Make|send payment (if needed) |==============
		
		$payResult = $this->isPaymentRequested($request, $post);
		if (data_get($payResult, 'success')) {
			return $this->sendPayment($request, $post);
		}
		if (data_get($payResult, 'failure')) {
			return apiResponse()->error(data_get($payResult, 'message'));
		}
		
		// ===| If no payment is made (continue) |===========
		
		$data = [
			'success' => true,
			'message' => t('your_listing_is_updated'),
			'result'  => (new PostResource($post))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
}
