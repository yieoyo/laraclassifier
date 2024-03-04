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

namespace App\Http\Middleware\Install;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

trait CheckPurchaseCode
{
	/**
	 * Check Purchase Code
	 * ===================
	 * Checking your purchase code. If you do not have one, please follow this link:
	 * https://codecanyon.net/item/laraclassified-geo-classified-ads-cms/16458425
	 * to acquire a valid code.
	 *
	 * IMPORTANT: Do not change this part of the code to prevent any data losing issue.
	 *
	 * @return void
	 */
	protected function checkPurchaseCode(): void
	{
		// Send the purchase code checking
		if ($this->isPurchaseCodeVerificationRequired()) {
			$data = [];
			$endpoint = getPurchaseCodeApiEndpoint(config('settings.app.purchase_code'), config('larapen.core.itemId'));
			try {
				/*
				 * Make the request and wait for 30 seconds for response.
				 * If it does not receive one, wait 5000 milliseconds (5 seconds), and then try again.
				 * Keep trying up to 2 times, and finally give up and throw an exception.
				 */
				$response = Http::withoutVerifying()
					->timeout(30)
					->retry(2, 5000)
					->get($endpoint)
					->throw();
				$data = $response->json();
			} catch (\Throwable $e) {
				$endpoint = (str_starts_with($endpoint, 'https:'))
					? str_replace('https:', 'http:', $endpoint)
					: str_replace('http:', 'https:', $endpoint);
				
				try {
					$response = Http::withoutVerifying()
						->timeout(30)
						->retry(2, 5000)
						->get($endpoint)
						->throw();
					$data = $response->json();
				} catch (\Throwable $e) {
					$data['message'] = parseHttpRequestError($e);
				}
			}
			
			// Checking
			if (data_get($data, 'valid')) {
				File::put(storage_path('installed'), data_get($data, 'license_code'));
			} else {
				dd(data_get($data, 'message'));
			}
		}
	}
	
	// PRIVATE
	
	/**
	 * Check if the purchase code verification is required
	 * Make the purchase code verification only if 'installed' file exists
	 *
	 * @return bool
	 */
	private function isPurchaseCodeVerificationRequired(): bool
	{
		if ($this->isCurrentUriExemptFromPurchaseCodeVerification()) {
			return false;
		}
		
		$verificationIsRequired = (File::exists(storage_path('installed')) && !config('settings.error'));
		
		if ($verificationIsRequired) {
			$purchaseCode = File::get(storage_path('installed'));
			
			$verificationIsRequired = (
				empty($purchaseCode)
				|| empty(config('settings.app.purchase_code'))
				|| $purchaseCode != config('settings.app.purchase_code')
			);
		}
		
		return $verificationIsRequired;
	}
	
	/**
	 * Don't check the purchase code for these areas (install, admin, etc.)
	 *
	 * @return bool
	 */
	private function isCurrentUriExemptFromPurchaseCodeVerification(): bool
	{
		$exemptArray = ['install', admin_uri()];
		
		return in_array(request()->segment(1), $exemptArray);
	}
}
