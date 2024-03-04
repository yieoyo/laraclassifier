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

use App\Models\Post;
use App\Models\User;

trait PaymentUrlsTrait
{
	/**
	 * Replace patterns in URLs
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $uri
	 * @return array
	 */
	protected static function replacePatternsInUrls(Post|User $payable, array $uri = []): array
	{
		// Check if this method is called from a payment plugin
		$className = static::class;
		$isCalledInsidePlugin = (
			str_contains($className, 'extras\plugins')
			|| str_contains($className, 'App\Helpers\Payment')
		);
		
		// Get URLs
		$previousUrl = $uri['previousUrl']; // Payment form page
		$nextUrl = $uri['nextUrl'];         // Destination page | Page to show after payment
		$paymentCancelUrl = $uri['paymentCancelUrl'] ?? '';
		$paymentReturnUrl = $uri['paymentReturnUrl'] ?? '';
		
		// Replace Patterns
		if (!empty($payable->id)) {
			$previousUrl = str_replace('#entryId', $payable->id, $previousUrl);
			$nextUrl = str_replace('#entryId', $payable->id, $nextUrl);
			$paymentCancelUrl = str_replace('#entryId', $payable->id, $paymentCancelUrl);
			$paymentReturnUrl = str_replace('#entryId', $payable->id, $paymentReturnUrl);
		}
		if (!empty($payable->tmp_token)) {
			$previousUrl = str_replace('#entryToken', $payable->tmp_token, $previousUrl);
			$nextUrl = str_replace('#entryToken', $payable->tmp_token, $nextUrl);
			$paymentCancelUrl = str_replace('#entryToken', $payable->tmp_token, $paymentCancelUrl);
			$paymentReturnUrl = str_replace('#entryToken', $payable->tmp_token, $paymentReturnUrl);
		}
		if (!empty($payable->slug)) {
			$nextUrl = str_replace('#entrySlug', $payable->slug, $nextUrl);
		}
		
		// Set full URL
		$previousUrl = !str_starts_with($previousUrl, 'http') ? url($previousUrl) : $previousUrl;
		$nextUrl = !str_starts_with($nextUrl, 'http') ? url($nextUrl) : $nextUrl;
		$paymentCancelUrl = !empty($paymentCancelUrl)
			? (!str_starts_with($paymentCancelUrl, 'http') ? url($paymentCancelUrl) : $paymentCancelUrl)
			: null;
		$paymentReturnUrl = !empty($paymentReturnUrl)
			? (!str_starts_with($paymentReturnUrl, 'http') ? url($paymentReturnUrl) : $paymentReturnUrl)
			: null;
		
		// Save URLs
		$uri['previousUrl'] = $previousUrl;
		$uri['nextUrl'] = $nextUrl;
		if ($isCalledInsidePlugin) {
			if (array_key_exists('paymentCancelUrl', $uri)) {
				unset($uri['paymentCancelUrl']);
			}
			if (array_key_exists('paymentReturnUrl', $uri)) {
				unset($uri['paymentReturnUrl']);
			}
		} else {
			$uri['paymentCancelUrl'] = $paymentCancelUrl;
			$uri['paymentReturnUrl'] = $paymentReturnUrl;
		}
		
		return $uri;
	}
}
