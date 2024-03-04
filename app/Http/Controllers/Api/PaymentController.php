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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Payment\Promotion\MultiStepsPayment;
use App\Http\Controllers\Api\Payment\Subscription\SubscriptionPayment;
use App\Http\Requests\Front\PackageRequest;
use App\Models\Payment;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\PaymentResource;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;

/**
 * @group Payments
 */
class PaymentController extends BaseController
{
	use MultiStepsPayment;
	use SubscriptionPayment;
	
	/**
	 * List payments
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the payment relationships for Eager Loading - Possible values: payable,paymentMethod,package,currency. Example: null
	 * @queryParam valid boolean Allow getting the valid payment list. Possible value: 0 or 1. Example: 0
	 * @queryParam active boolean Allow getting the active payment list. Possible value: 0 or 1. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @param int|null $payableId
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(?int $payableId = null): \Illuminate\Http\JsonResponse
	{
		if (!auth('sanctum')->check()) {
			return apiResponse()->unauthorized();
		}
		
		$embed = explode(',', request()->query('embed'));
		$isValid = (request()->filled('valid') && request()->integer('valid') == 1);
		$isActive = (request()->filled('active') && request()->integer('active') == 1);
		
		$authUser = auth('sanctum')->user();
		
		$type = getRequestSegment(3);
		$isPromoting = ($type == 'promotion');
		$isSubscripting = ($type == 'subscription');
		
		$payments = Payment::query()
			->withoutGlobalScopes([ValidPeriodScope::class, StrictActiveScope::class]);
		
		if (!empty($payableId)) {
			$payments->$type()->where('payable_id', $payableId);
		}
		
		if ($isPromoting) {
			$payments->whereHasMorph('payable', Post::class, function ($query) use ($authUser) {
				$query->inCountry();
				$query->whereHas('user', fn ($q) => $q->where('user_id', $authUser->getAuthIdentifier()));
			});
		}
		if ($isSubscripting) {
			$payments->whereHasMorph('payable', User::class, function ($query) use ($authUser) {
				$query->where('id', $authUser->getAuthIdentifier());
			});
			if (in_array('posts', $embed)) {
				$postScopes = [VerifiedScope::class, ReviewedScope::class];
				$payments->with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]);
			}
		}
		
		if (in_array('payable', $embed)) {
			$payments->with('payable');
		}
		if (in_array('paymentMethod', $embed)) {
			$payments->with('paymentMethod');
		}
		if (in_array('package', $embed)) {
			if (in_array('currency', $embed)) {
				$payments->with(['package' => fn ($query) => $query->with('currency')]);
			} else {
				$payments->with('package');
			}
		}
		
		$payments->when($isValid, fn ($query) => $query->valid());
		$payments->when($isActive, fn ($query) => $query->active());
		
		// Sorting
		$payments = $this->applySorting($payments, ['created_at']);
		
		$payments = $payments->paginate($this->perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$payments = setPaginationBaseUrl($payments);
		
		$collection = new EntityCollection(class_basename($this), $payments);
		
		$message = ($payments->count() <= 0)
			? ($isSubscripting) ? t('no_subscriptions_found') : t('no_payments_found')
			: null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get payment
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam embed string Comma-separated list of the payment relationships for Eager Loading - Possible values: payable,paymentMethod,package,currency. Example: null
	 *
	 * @urlParam id int required The payment's ID. Example: 2
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		if (!auth('sanctum')->check()) {
			return apiResponse()->unauthorized();
		}
		
		$embed = explode(',', request()->query('embed'));
		
		$payment = Payment::query()->where('id', $id);
		
		if (in_array('payable', $embed)) {
			$payment->with('payable');
		}
		if (in_array('paymentMethod', $embed)) {
			$payment->with('paymentMethod');
		}
		if (in_array('package', $embed)) {
			if (in_array('currency', $embed)) {
				$payment->with(['package' => fn ($query) => $query->with('currency')]);
			} else {
				$payment->with('package');
			}
		}
		
		$payment = $payment->first();
		
		abort_if(empty($payment), 404, t('payment_not_found'));
		
		$resource = new PaymentResource($payment);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store payment
	 *
	 * Note: This endpoint is only available for the multi steps form edition.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam package int Selected package ID.
	 *
	 * @bodyParam country_code string required The code of the user's country. Example: US
	 * @bodyParam payable_id int required The payable's ID (ID of the listing or user). Example: 2
	 * @bodyParam payable_type string required The payable model's name - Possible values: Post,User. Example: Post
	 * @bodyParam package_id int required The package's ID (Auto filled when the query parameter 'package' is set).
	 * @bodyParam payment_method_id int The payment method's ID (required when the selected package's price is > 0). Example: 5
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(PackageRequest $request): \Illuminate\Http\JsonResponse
	{
		/*
		 * IMPORTANT: Du to possible payment gateways' redirections,
		 * the API payment storing's endpoint is not call from the app's web version.
		 */
		$payableType = $request->input('payable_type');
		$isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		
		// promotion
		if ($isPromoting) {
			/*
			 * Prevent developers to call the API payment endpoint to store payment
			 * from the web version of the app when the Single-Step Form is enabled.
			 */
			if (doesRequestIsFromWebApp()) {
				$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
				if ($isSingleStepFormEnabled) {
					$message = 'This endpoint cannot be called from the app\'s web version when the Single-Step Form is enabled.';
					abort(400, $message);
				}
			}
			
			/*
			 * The same way to store payment is use both API call and the Web Multi-Steps Form process
			 * i.e.: The payable ID and type are required
			 */
			$this->setPaymentSettingsForPromotion();
			
			return $this->multiStepsPaymentStore($request);
		}
		
		// subscription
		if ($isSubscripting) {
			$this->setPaymentSettingsForSubscription();
			
			return $this->storeSubscriptionPayment($request);
		}
		
		abort(400, 'Payable type not found.');
	}
}
