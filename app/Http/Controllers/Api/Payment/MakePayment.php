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

namespace App\Http\Controllers\Api\Payment;

use App\Helpers\Payment\PaymentUrlsTrait;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\PaymentMethod;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Post;

trait MakePayment
{
	use PaymentUrlsTrait;
	
	/**
	 * Send Payment
	 * Note: Used by API and Web calls
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 */
	public function sendPayment(Request $request, Post|User $payable)
	{
		// Get the payable full name with namespace
		$payableType = get_class($payable);
		
		$isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		$isPayableTypeFound = ($isPromoting || $isSubscripting);
		
		// Replace patterns in URLs
		$this->apiUri = self::replacePatternsInUrls($payable, $this->apiUri);
		
		if ($isPayableTypeFound) {
			// Result Data Structure
			$resData = [
				'success' => true,
				'message' => $this->apiMsg['payable']['success'],
				'result'  => $isPromoting ? new PostResource($payable) : new UserResource($payable),
				'extra'   => [
					'payment'          => [
						'success' => false,
						'message' => $this->apiMsg['checkout']['error'],
						'result'  => null,
					],
					'previousUrl'      => $this->apiUri['previousUrl'],
					'nextUrl'          => $this->apiUri['nextUrl'],
					'paymentCancelUrl' => $this->apiUri['paymentCancelUrl'],
					'paymentReturnUrl' => $this->apiUri['paymentReturnUrl'],
				],
			];
			
			// Get Payment Method
			// NOTE: If an API call detected, only API compatible gateway maybe fetched
			// Check the /app/Models/Scopes/CompatibleApiScope.php file for more information
			$paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
			
			if (!empty($paymentMethod)) {
				// Load Payment Plugin
				$plugin = load_installed_plugin(strtolower($paymentMethod->name));
				
				// Payment using the selected Payment Method
				if (!empty($plugin)) {
					try {
						
						// Send the Payment
						// return $plugin->class::{'sendPayment'}($request, $payable, $resData);
						return call_user_func($plugin->class . '::sendPayment', $request, $payable, $resData);
						
					} catch (\Throwable $e) {
						$resData['extra']['payment']['message'] = $e->getMessage();
						$resData['extra']['previousUrl'] = $this->apiUri['previousUrl'] . '?error=pluginLoading';
						
						return apiResponse()->json($resData, 400);
					}
				} else {
					$resData['extra']['payment']['message'] = t('plugin_not_found');
					$resData['extra']['previousUrl'] = $this->apiUri['previousUrl'] . '?error=pluginNotFound';
				}
			} else {
				$resData['extra']['payment']['message'] = t('payment_method_not_found');
				$resData['extra']['previousUrl'] = $this->apiUri['previousUrl'] . '?error=paymentMethodNotFound';
			}
		} else {
			$resData['extra']['payment']['message'] = t('package_type_not_found');
			$resData['extra']['previousUrl'] = $this->apiUri['previousUrl'] . '?error=packageTypeNotFound';
		}
		
		if (isFromApi()) {
			return apiResponse()->json($resData);
		} else {
			$errorMessage = data_get($resData, 'extra.payment.message', 'Unknown Error.');
			$previousUrl = data_get($resData, 'extra.previousUrl', '/');
			
			flash($errorMessage)->error();
			
			return redirect()->to($previousUrl);
		}
	}
	
	/**
	 * Payment Confirmation
	 * Note: Only used by Web calls
	 *
	 * URL: /posts/{id}/payment/success
	 * - Success URL when Credit Card is used
	 * - Payment Process URL when no Credit Card is used
	 *
	 * @param $payableId
	 * @return \Illuminate\Http\RedirectResponse|mixed
	 */
	public function paymentConfirmation($payableId = null)
	{
		// Get session parameters
		$params = session('params');
		$params = is_array($params) ? $params : [];
		
		if (empty($params)) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentSessionNotFound');
		}
		
		// Check if the payable ID can be retrieved
		if (!isset($params['payable']['id']) || !isset($params['package']['type'])) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentParametersNotFound');
		}
		
		$packageType = $params['package']['type'];
		$isPromoting = ($packageType == 'promotion');
		$isSubscripting = ($packageType == 'subscription');
		
		// Get the entry
		$payable = null;
		if ($isPromoting) {
			$payable = Post::withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('id', $params['payable']['id'])
				->first();
		}
		if ($isSubscripting) {
			$payable = User::withoutGlobalScopes([VerifiedScope::class])
				->where('id', $params['payable']['id'])
				->first();
		}
		
		if (empty($payable)) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentEntryNotFound');
		}
		
		// GO TO PAYMENT METHODS
		
		// Check if the payment method ID can be retrieved
		if (!isset($params['paymentMethod']['id'])) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentMethodParameterNotFound');
		}
		
		// Get Payment Method
		$paymentMethod = PaymentMethod::find($params['paymentMethod']['id']);
		if (empty($paymentMethod)) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentMethodEntryNotFound');
		}
		
		// Load Payment Plugin
		$plugin = load_installed_plugin(strtolower($paymentMethod->name));
		
		// Check if the Payment Method exists
		if (empty($plugin)) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentMethodPluginNotFound');
		}
		
		// Payment using the selected Payment Method
		try {
			// return $plugin->class::{'paymentConfirmation'}($payable, $params);
			return call_user_func($plugin->class . '::paymentConfirmation', $payable, $params);
		} catch (\Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to('/?error=paymentMethodPluginError');
		}
	}
	
	/**
	 * Payment Cancel
	 * Note: Only used by Web calls
	 *
	 * URL: /posts/{id}/payment/cancel
	 *
	 * @param $payableId
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function paymentCancel($payableId = null)
	{
		// Set the error message
		flash($this->apiMsg['checkout']['cancel'])->error();
		
		// Get session parameters
		$params = session('params');
		$params = is_array($params) ? $params : [];
		
		if (empty($params)) {
			return redirect()->to('/?error=paymentCancelled&params=empty');
		}
		
		// Check if the payable ID can be retrieved
		if (!isset($params['payable']['id']) || !isset($params['package']['type'])) {
			flash($this->apiMsg['checkout']['error'])->error();
			
			return redirect()->to('/?error=paymentParametersNotFound');
		}
		
		$packageType = $params['package']['type'];
		$isPromoting = ($packageType == 'promotion');
		$isSubscripting = ($packageType == 'subscription');
		
		// Get the entry
		$payable = null;
		if ($isPromoting) {
			$payable = Post::withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('id', $params['payable']['id'])
				->first();
		}
		if ($isSubscripting) {
			$payable = User::withoutGlobalScopes([VerifiedScope::class])
				->where('id', $params['payable']['id'])
				->first();
		}
		
		if (empty($payable)) {
			return redirect()->to('/?error=paymentCancelled&post=empty');
		}
		
		// Delete new entries when payment canceled (Or not)
		if (session()->has('message')) {
			if (config('settings.single.remove_new_entries_when_payment_cancelled')) {
				$payable->delete();
			} else {
				flash(session('message'))->success();
			}
			session()->forget('message');
		}
		
		// Replace patterns in URLs
		$this->apiUri = self::replacePatternsInUrls($payable, $this->apiUri);
		
		return redirect()->to($this->apiUri['previousUrl'] . '?error=paymentCancelled')->withInput();
	}
}
