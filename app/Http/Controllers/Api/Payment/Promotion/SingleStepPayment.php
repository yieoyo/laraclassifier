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
use App\Http\Controllers\Api\Payment\RetrievePackageFeatures;
use App\Models\Package;

trait SingleStepPayment
{
	use RetrievePackageFeatures;
	
	public array $apiMsg = [];
	public array $apiUri = [];
	public ?Package $selectedPackage = null;
	public array $payment = [];
	
	/**
	 * Set payment settings for promotion packages (Single-Step Form)
	 *
	 * @return void
	 */
	protected function setPaymentSettingsForPromotion(): void
	{
		$isNewEntry = isPostCreationRequest();
		
		// Messages
		$this->apiMsg['payable']['success'] = ($isNewEntry)
			? t('your_listing_is_created')
			: t('your_listing_is_updated');
		$this->apiMsg['checkout']['success'] = t('payment_received');
		$this->apiMsg['checkout']['cancel'] = t('payment_cancelled_text');
		$this->apiMsg['checkout']['error'] = t('payment_error_text');
		
		// Set URLs
		if ($isNewEntry) {
			$this->apiUri['previousUrl'] = url('create');
			$this->apiUri['nextUrl'] = url('create/finish');
			$this->apiUri['paymentCancelUrl'] = url('create/payment/cancel');
			$this->apiUri['paymentReturnUrl'] = url('create/payment/success');
			
			// Multi Step Form (Creation)
			$isMultiStepFormEnabled = (config('settings.single.publication_form_type') == '1');
			if ($isMultiStepFormEnabled) {
				$this->apiUri['previousUrl'] = url('posts/create/payment');
				$this->apiUri['nextUrl'] = url('posts/create/finish');
				$this->apiUri['paymentCancelUrl'] = url('posts/create/payment/cancel');
				$this->apiUri['paymentReturnUrl'] = url('posts/create/payment/success');
			}
		} else {
			$this->apiUri['previousUrl'] = url('edit/#entryId');
			$nextUrl = str_replace(
				['{slug}', '{hashableId}', '{id}'],
				['#entrySlug', '#entryId', '#entryId'],
				(config('routes.post') ?? '#entrySlug/#entryId')
			);
			$this->apiUri['nextUrl'] = url($nextUrl);
			$this->apiUri['paymentCancelUrl'] = url('edit/#entryId/payment/cancel');
			$this->apiUri['paymentReturnUrl'] = url('edit/#entryId/payment/success');
		}
		
		// Payment Helper init.
		PaymentHelper::$country = collect(config('country'));
		PaymentHelper::$lang = collect(config('lang'));
		PaymentHelper::$msg = $this->apiMsg;
		PaymentHelper::$uri = $this->apiUri;
		
		if ($isNewEntry) {
			/*
			 * Get the post's current active payment info (If exists)
			 * ---
			 * Share the Post's current payment info variables without passing a Listing in argument
			 * That is to get required variables for views (Web) or windows (Mobile)
			 */
			$this->payment = $this->getCurrentActivePaymentInfo();
		}
		
		// Selected Package (from Form)
		$this->selectedPackage = $this->getSelectedPackage();
		
		if (!isFromApi()) {
			view()->share('selectedPackage', $this->selectedPackage);
		}
	}
}
