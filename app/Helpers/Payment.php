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

namespace App\Helpers;

use App\Helpers\Payment\PaymentTrait;
use App\Http\Resources\PaymentResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Package;
use App\Models\Payment as PaymentModel;
use App\Notifications\PaymentNotification;
use App\Notifications\PaymentSent;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use App\Notifications\SubscriptionPurchased;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class Payment
{
	use PaymentTrait;
	
	public static Collection $country;
	public static Collection $lang;
	public static array $msg = [];
	public static array $uri = [];
	
	/**
	 * Apply actions after successful Payment
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $params
	 * @param array $resData
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public static function paymentConfirmationActions(Post|User $payable, array $params, array $resData = [])
	{
		// Save the Payment in database
		$resData = self::register($payable, $params, $resData);
		
		if (isFromApi()) {
			
			return apiResponse()->json($resData);
			
		} else {
			
			if (data_get($resData, 'extra.payment.success')) {
				flash(data_get($resData, 'extra.payment.message'))->success();
			} else {
				flash(data_get($resData, 'extra.payment.message'))->error();
			}
			
			if (data_get($resData, 'success')) {
				session()->flash('message', data_get($resData, 'message'));
				
				return redirect()->to(self::$uri['nextUrl']);
			} else {
				// Maybe never called
				return redirect()->to(self::$uri['nextUrl'])->withErrors(['error' => data_get($resData, 'message')]);
			}
			
		}
	}
	
	/**
	 * Apply actions when Payment failed
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param string|array|null $errorMessage
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public static function paymentFailureActions(Post|User $payable, string|null|array $errorMessage = null)
	{
		// Remove the entry
		self::removeEntry($payable);
		
		$errorMessage = (is_string($errorMessage) || empty($errorMessage))
			? $errorMessage
			: 'Unexplained Error (Issue in the language files).';
		
		// Return to Form
		$message = self::$msg['checkout']['error'];
		if (!empty($errorMessage)) {
			$message .= '<br>' . $errorMessage;
		}
		
		if (isFromApi()) {
			$data = [
				'success' => false,
				'result'  => null,
				'message' => $message,
				'extra'   => [
					'previousUrl' => self::$uri['previousUrl'] . '?error=payment',
				],
			];
			
			return apiResponse()->json($data);
		} else {
			flash($message)->error();
			
			// Redirect
			return redirect()->to(self::$uri['previousUrl'] . '?error=payment')->withInput();
		}
	}
	
	/**
	 * Apply actions when API failed
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param \Throwable|null $e
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public static function paymentApiErrorActions(Post|User $payable, ?\Throwable $e, ?string $message = null)
	{
		// Remove the entry
		self::removeEntry($payable);
		
		$message = ($e instanceof \Throwable) ? $e->getMessage() : $message;
		
		if (isFromApi()) {
			$data = [
				'success' => false,
				'result'  => null,
				'message' => $message,
				'extra'   => [
					'previousUrl' => self::$uri['previousUrl'] . '?error=paymentApi',
				],
			];
			
			return apiResponse()->json($data);
		} else {
			// Remove local parameters into the session (if exists)
			if (session()->has('params')) {
				session()->forget('params');
			}
			
			// Return to Form
			flash($message)->error();
			
			// Redirect
			return redirect()->to(self::$uri['previousUrl'] . '?error=paymentApi')->withInput();
		}
	}
	
	/**
	 * Save the payment and Send payment confirmation email
	 * NOTE: Used by the OfflinePayment plugin (and must be compatible with its version)
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $params
	 * @param array $resData
	 * @return array
	 */
	public static function register(Post|User $payable, array $params, array $resData = [])
	{
		// Don't save payment if selected Package is not compatible with payable (Post|User)
		if (!self::isPayableCompatibleWithPackageArray($payable, $params)) {
			return $resData;
		}
		
		$request = request();
		
		// Get the payable full name with namespace
		$payableType = get_class($payable);
		
		$isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		
		// Update the payable (Post|User)
		if ($isPromoting) {
			$payable->reviewed_at = now();
		}
		$payable->featured = 1;
		$payable->save();
		
		// Get the payment info
		$paymentArray = [
			'payable_id'        => $payable->id,
			'payable_type'      => $payableType,
			'package_id'        => data_get($params, 'package.id'),
			'payment_method_id' => data_get($params, 'paymentMethod.id'),
			'transaction_id'    => data_get($params, 'transaction_id'),
			'amount'            => data_get($params, 'package.price', 0),
			'period_start'      => data_get($params, 'package.period_start', now()->startOfDay()),
			'period_end'        => data_get($params, 'package.period_end'),
		];
		
		// Check if the 'currency_code' column is available in the Payment model
		$cacheId = 'currencyCodeColumnIsAvailablePaymentTable';
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400) * 5;
		$currencyCodeColumnIsAvailable = cache()->remember($cacheId, $cacheExpiration, function () {
			return Schema::hasColumn((new PaymentModel())->getTable(), 'currency_code');
		});
		
		if ($currencyCodeColumnIsAvailable) {
			$currencyCode = data_get($params, 'package.currency_code');
			if (empty($currencyCode)) {
				$package = Package::find(data_get($params, 'package.id'));
				$currencyCode = (!empty($package) && isset($package->currency_code)) ? $package->currency_code : null;
			}
			$paymentArray['currency_code'] = $currencyCode;
		}
		
		// Check the uniqueness of the payment
		$payment = PaymentModel::query()
			->whereMorphedTo('payable', $payable)
			->where('package_id', $paymentArray['package_id'])
			->where('payment_method_id', $paymentArray['payment_method_id'])
			->where('period_start', $paymentArray['period_start'])
			->where('period_end', $paymentArray['period_end'])
			->first();
		
		if (!empty($payment)) {
			$resData['extra']['payment']['success'] = true;
			$resData['extra']['payment']['message'] = self::$msg['checkout']['success'];
			$resData['extra']['payment']['result'] = $payment = (new PaymentResource($payment))->toArray($request);
			
			return $resData;
		}
		
		// Save the payment
		$payment = new PaymentModel($paymentArray);
		$payment->save();
		
		$resData['extra']['payment']['success'] = true;
		$resData['extra']['payment']['message'] = self::$msg['checkout']['success'];
		$resData['extra']['payment']['result'] = (new PaymentResource($payment))->toArray($request);
		
		// SEND EMAILS
		
		// Get all admin users
		$admins = User::permission(Permission::getStaffPermissions())->get();
		
		// Send Payment Email Notifications
		if (config('settings.mail.payment_notification') == 1) {
			// Send Confirmation Email
			try {
				if ($isPromoting) {
					$payable->notify(new PaymentSent($payment, $payable));
				}
				if ($isSubscripting) {
					$payable->notify(new SubscriptionPurchased($payment, $payable));
				}
			} catch (\Throwable $e) {
				// Not Necessary To Notify
			}
			
			// Send to Admin the Payment Notification Email
			try {
				if ($admins->count() > 0) {
					if ($isPromoting) {
						Notification::send($admins, new PaymentNotification($payment, $payable));
					}
					if ($isSubscripting) {
						Notification::send($admins, new SubscriptionNotification($payment, $payable));
					}
				}
			} catch (\Throwable $e) {
				// Not Necessary To Notify
			}
		}
		
		return $resData;
	}
	
	/**
	 * Remove the listing for public - If there are no free packages
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @return bool
	 */
	public static function removeEntry(Post|User $payable): bool
	{
		// Get the payable full name with namespace
		$payableType = get_class($payable);
		
		// $isPromoting = (str_ends_with($payableType, 'Post'));
		$isSubscripting = (str_ends_with($payableType, 'User'));
		
		// For User
		// Don't delete user during the subscription process
		if ($isSubscripting) {
			return false;
		}
		
		// For Post
		// Don't delete the listing when a user tries to UPGRADE her listings
		if (empty($payable->tmp_token)) {
			return false;
		}
		
		$guard = isFromApi() ? 'sanctum' : null;
		
		if (auth($guard)->check()) {
			// Delete the listing if user is logged in and there is no free package
			$countPackages = Package::promotion()->where('price', 0)->count();
			if ($countPackages == 0) {
				// But! User can access to the listing from her area to UPGRADE it!
				// You can UNCOMMENT the line below if you don't want the feature above.
				// $payable->delete();
			}
		} else {
			// Delete the listing if user is a guest
			$payable->delete();
		}
		
		return true;
	}
}
