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

use App\Models\Package;
use App\Models\Post;
use App\Models\User;

trait RetrievePackageFeatures
{
	/**
	 * Check & Get the selected Package
	 *
	 * @return \App\Models\Package|null
	 */
	public function getSelectedPackage(): ?Package
	{
		$package = null;
		
		$isNewEntry = isPostCreationRequest();
		
		// Make this available only on Listing Creation pages
		if ($isNewEntry) {
			$packageId = requestPackageId();
			if (!empty($packageId)) {
				$package = getPackageById($packageId);
			}
		}
		
		return $package;
	}
	
	/**
	 * Get the payable's current active payment & its method/gateway's info
	 * Get the selected package's info; detectable, when it's not passed as argument
	 * Get the pictures limit, related to the auth user subscription's package,
	 *     the selected package or to payable's promotion payment's package
	 *
	 * Todo: Find a more appropriate name for this function.
	 *
	 * @param \App\Models\Post|\App\Models\User|array|null $payable
	 * @param \App\Models\Package|array|null $package
	 * @return array
	 */
	public function getCurrentActivePaymentInfo(Post|User|array|null $payable = null, Package|array|null $package = null): array
	{
		// Get the payable full name with namespace
		$payableClass = is_object($payable) ? get_class($payable) : '';
		$packageType = null;
		$packageType = str_ends_with($payableClass, 'Post') ? 'promotion' : $packageType;
		$packageType = str_ends_with($payableClass, 'User') ? 'subscription' : $packageType;
		$packageType = !empty($packageType) ? $packageType : getRequestPackageType();
		
		$data = [];
		$data['picturesLimit'] = config('settings.single.pictures_limit', 0);
		
		if ($packageType == 'promotion') {
			$data = $this->getAuthUserPossiblePaymentInfo($data);
		}
		$data = $this->getSelectedPackageInfo($package, $data);
		$data = $this->getPossiblePaymentInfo($payable, $data);
		
		$data['picturesLimit'] = $data['package']['pictures_limit']
			?? $data['payment']['package']['pictures_limit']
			?? $data['picturesLimit'];
		
		$picturesLimit = data_get($data, 'picturesLimit');
		config()->set('settings.single.pictures_limit', $picturesLimit);
		
		if (!isFromApi()) {
			view()->share('packageType', $packageType);
			view()->share('package', data_get($data, 'package', []));
			view()->share('payment', data_get($data, 'payment', []));
			view()->share('upcomingPayment', data_get($data, 'upcomingPayment', []));
			view()->share('picturesLimit', $picturesLimit);
		}
		
		return $data;
	}
	
	// PRIVATE
	
	/**
	 * Get the post's user's possible subscription info
	 * Note: All we need here is the subscription's features (like: the pictures limit)
	 *
	 * @param array $data
	 * @return array
	 */
	private function getAuthUserPossiblePaymentInfo(array $data = []): array
	{
		$picturesLimit = config('settings.single.pictures_limit', 0);
		
		// Get packages features
		$guard = isFromApi() ? 'sanctum' : null;
		$authUser = auth($guard)->user();
		if (!empty($authUser)) {
			$picturesLimit = getUserSubscriptionFeatures($authUser, 'picturesLimit') ?? $picturesLimit;
		}
		
		$data['picturesLimit'] = $picturesLimit;
		
		return $data;
	}
	
	/**
	 * Get the payable's possible payment info
	 *
	 * @param \App\Models\Post|\App\Models\User|array|null $payable
	 * @param array $data
	 * @return array
	 */
	private function getPossiblePaymentInfo(Post|User|array|null $payable = null, array $data = []): array
	{
		$isValidPayable = (
			!empty($payable)
			&& ($payable instanceof Post || $payable instanceof User || is_array($payable))
		);
		
		if (!$isValidPayable) {
			return $data;
		}
		
		$possiblePayment = data_get($payable, 'possiblePayment');
		if (!empty($possiblePayment)) {
			// Get the current payment data
			$data['payment']['expiry_info'] = data_get($possiblePayment, 'expiry_info');
			$data['payment']['active'] = data_get($possiblePayment, 'active');
			$data['payment']['paymentMethod']['id'] = data_get($possiblePayment, 'payment_method_id');
			
			// Get the current payment's package data
			if (data_get($payable, 'featured') == 1) {
				if (!empty(data_get($possiblePayment, 'package'))) {
					$data['payment']['package']['id'] = data_get($possiblePayment, 'package.id');
					$data['payment']['package']['type'] = data_get($possiblePayment, 'package.type');
					$data['payment']['package']['price'] = data_get($possiblePayment, 'package.price');
					$data['payment']['package']['currency_code'] = data_get($possiblePayment, 'package.currency_code');
					
					// Set the Package's picture number limit
					$paymentPackagePrice = data_get($possiblePayment, 'package.price');
					$paymentPackagePicturesLimit = data_get($possiblePayment, 'package.pictures_limit');
					$isPictureNumberLimitFilled = (
						$paymentPackagePrice > 0
						&& !empty($paymentPackagePicturesLimit)
						&& $paymentPackagePicturesLimit > 0
					);
					if ($isPictureNumberLimitFilled) {
						$data['payment']['package']['pictures_limit'] = $paymentPackagePicturesLimit;
					}
				}
			}
			
			// Get the upcoming payment's period start date
			$ppPeriodEnd = data_get($possiblePayment, 'period_end_formatted');
			$paymentEndingLater = data_get($payable, 'paymentEndingLater');
			$periodStart = data_get($paymentEndingLater, 'period_end_formatted', $ppPeriodEnd);
			$data['upcomingPayment']['period_start_formatted'] = $periodStart;
		}
		
		return $data;
	}
	
	/**
	 * Get the selected package info
	 *
	 * @param \App\Models\Package|array|null $package
	 * @param array $data
	 * @return array
	 */
	private function getSelectedPackageInfo(Package|array|null $package = null, array $data = []): array
	{
		// Check if a package object or ID is filled and retrieve its info
		$packageId = requestPackageId();
		if (!empty($packageId) && empty($package)) {
			$package = getPackageById($packageId);
		}
		
		// Get the Package's pictures' number limit (from the selected package)
		if (!empty($package) && $package instanceof Package) {
			$data['package']['id'] = data_get($package, 'id');
			$data['package']['type'] = data_get($package, 'type');
			$data['package']['price'] = data_get($package, 'price');
			$data['package']['currency_code'] = data_get($package, 'currency_code');
			
			$packagePrice = data_get($package, 'price');
			$packagePicturesLimit = data_get($package, 'pictures_limit');
			$isPictureNumberLimitFilled = ($packagePrice > 0 && !empty($packagePicturesLimit) && $packagePicturesLimit > 0);
			if ($isPictureNumberLimitFilled) {
				$data['package']['pictures_limit'] = $packagePicturesLimit;
			}
		}
		
		return $data;
	}
}
