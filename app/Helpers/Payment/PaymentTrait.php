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

namespace App\Helpers\Payment;

use App\Helpers\Number;
use App\Models\Package;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/*
 * CALL FROM THE PAYMENT PLUGINS
 * So all 'self::...' attributes need to be 'parent::...' if they are called from plugins
 */

trait PaymentTrait
{
	use PaymentUrlsTrait;
	
	/**
	 * Set the right URLs
	 *
	 * @param array $resData
	 * @return void
	 */
	protected static function setRightUrls(array $resData = []): void
	{
		$extra = $resData['extra'] ?? [];
		
		self::$uri['previousUrl'] = $extra['previousUrl'] ?? self::$uri['previousUrl'];
		self::$uri['nextUrl'] = $extra['nextUrl'] ?? self::$uri['nextUrl'];
		self::$uri['paymentCancelUrl'] = $extra['paymentCancelUrl'] ?? self::$uri['paymentCancelUrl'];
		self::$uri['paymentReturnUrl'] = $extra['paymentReturnUrl'] ?? self::$uri['paymentReturnUrl'];
	}
	
	/**
	 * Check if the payment is to: promote a listing (or) subscribe a user
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param \App\Models\Package $package
	 * @return bool
	 */
	protected static function isPayableCompatibleWithPackage(Post|User $payable, Package $package): bool
	{
		$payableType = get_class($payable); // Get the payable full name with namespace
		$isPromoting = (str_ends_with($payableType, 'Post') && $package->type == 'promotion');
		$isSubscripting = (str_ends_with($payableType, 'User') && $package->type == 'subscription');
		
		return ($isPromoting || $isSubscripting);
	}
	
	/**
	 * Check if the payment is to: promote a listing (or) subscribe a user
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $params
	 * @return bool
	 */
	protected static function isPayableCompatibleWithPackageArray(Post|User $payable, array $params): bool
	{
		$payableType = get_class($payable); // Get the payable full name with namespace
		$isPromoting = (str_ends_with($payableType, 'Post') && data_get($params, 'package.type') == 'promotion');
		$isSubscripting = (str_ends_with($payableType, 'User') && data_get($params, 'package.type') == 'subscription');
		
		return ($isPromoting || $isSubscripting);
	}
	
	/**
	 * Get the local parameters
	 * These parameters are required to save payments
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param \App\Models\Package $package
	 * @return array
	 */
	protected static function getLocalParameters(Request $request, Post|User $payable, Package $package): array
	{
		$daysLeft = self::getDaysLeftBeforePayablePaymentsExpire($payable, $package->period_start);
		$periodStart = self::periodDate($package->period_start, $daysLeft);
		$periodEnd = self::periodDate($package->period_end, $daysLeft);
		
		$params = [];
		$params['payable']['id'] = $payable->id; // payable
		$params['paymentMethod']['id'] = $request->input('payment_method_id');
		$params['cancelUrl'] = self::$uri['paymentCancelUrl'];
		$params['returnUrl'] = self::$uri['paymentReturnUrl'];
		$params['package']['id'] = $package->id; // package
		$params['package']['name'] = $package->name;
		$params['package']['description'] = $package->name;
		$params['package']['type'] = $package->type;
		$params['package']['price'] = Number::toFloat($package->price);
		$params['package']['currency_code'] = $package->currency_code;
		$params['package']['period_start'] = $periodStart->startOfDay();
		$params['package']['period_end'] = $periodEnd->endOfDay();
		
		return $params;
	}
	
	/**
	 * @param \Illuminate\Support\Carbon|string|null $value
	 * @param int $daysLeft
	 * @return \Illuminate\Support\Carbon
	 */
	public static function periodDate(Carbon|string|null $value, int $daysLeft = 0): Carbon
	{
		if (!$value instanceof Carbon) {
			$value = new Carbon($value);
		}
		
		if ($daysLeft > 0) {
			$value = $value->addDays($daysLeft);
		}
		
		return $value->startOfDay();
	}
	
	/**
	 * Get the number of days until all the payable's valid and active payments expire
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param \Illuminate\Support\Carbon|string|null $periodStart
	 * @return int
	 */
	public static function getDaysLeftBeforePayablePaymentsExpire(Post|User $payable, Carbon|string|null $periodStart): int
	{
		if (!$periodStart instanceof Carbon) {
			$periodStart = new Carbon($periodStart);
		}
		
		$daysLeft = 0;
		
		/*
		 * NOTE:
		 * Since this method is called during payment saving (specifically during storing),
		 * the payable relationship is not yet established
		 * because the current row does not exist in the database.
		 * This explains why a new query was preferred rather than using the relationship.
		 */
		$periodEndingLater = $payable->paymentEndingLater?->period_end ?? null;
		if (!empty($periodEndingLater)) {
			if (!$periodEndingLater instanceof Carbon) {
				$periodEndingLater = new Carbon($periodEndingLater);
			}
			$daysLeft = $periodStart->diffInDays($periodEndingLater);
		}
		
		return $daysLeft;
	}
}
