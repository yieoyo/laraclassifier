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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create;

use App\Helpers\Files\Upload;
use App\Models\CategoryField;
use Illuminate\Http\Request;

trait SubmitTrait
{
	/**
	 * Store all input data in database
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	private function storeInputDataInDatabase(Request $request)
	{
		// Get all saved input data
		$postInput = (array)$request->session()->get('postInput');
		$picturesInput = (array)$request->session()->get('picturesInput');
		$paymentInput = (array)$request->session()->get('paymentInput');
		
		// Create the global input to send for database saving
		$inputArray = $postInput;
		if (isset($inputArray['category_id'], $inputArray['cf'])) {
			$fields = CategoryField::getFields($inputArray['category_id']);
			if ($fields->count() > 0) {
				foreach ($fields as $field) {
					if ($field->type == 'file') {
						if (isset($inputArray['cf'][$field->id]) && !empty($inputArray['cf'][$field->id])) {
							$inputArray['cf'][$field->id] = Upload::fromPath($inputArray['cf'][$field->id]);
						}
					}
				}
			}
		}
		
		$inputArray['pictures'] = [];
		if (!empty($picturesInput)) {
			foreach ($picturesInput as $key => $filePath) {
				if (!empty($filePath)) {
					$uploadedFile = Upload::fromPath($filePath);
					$inputArray['pictures'][] = $uploadedFile;
				}
			}
		}
		$inputArray = array_merge($inputArray, $paymentInput);
		
		request()->merge($inputArray);
		
		if (!empty($inputArray['pictures'])) {
			request()->files->set('pictures', $inputArray['pictures']);
		}
		
		// Call API endpoint
		$endpoint = '/posts';
		$data = makeApiRequest('post', $endpoint, request()->all(), [], true);
		
		// dd($data);
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			if (data_get($data, 'extra.previousUrl')) {
				return redirect()->to(data_get($data, 'extra.previousUrl'))->withInput($request->except('pictures'));
			} else {
				return redirect()->back()->withInput($request->except('pictures'));
			}
		}
		
		// Get the listing ID
		$postId = data_get($data, 'result.id');
		
		// Notification Message
		if (data_get($data, 'success')) {
			session()->put('message', $message);
			
			// Save the listing's ID in session
			if (!empty($postId)) {
				$request->session()->put('postId', $postId);
			}
			
			// Clear Temporary Inputs & Files
			$this->clearTemporaryInput();
		} else {
			flash($message)->error();
			
			return redirect()->back()->withInput($request->except('pictures'));
		}
		
		// Get Listing Resource
		$post = data_get($data, 'result');
		
		abort_if(empty($post), 404, t('post_not_found'));
		
		// Get the next URL
		$nextUrl = url('posts/create/finish');
		
		if (!empty($paymentInput)) {
			// Check if the payment process has been triggered
			// NOTE: Payment bypass email or phone verification
			// ===| Make|send payment (if needed) |==============
			
			$postObj = $this->retrievePayableModel($request, $postId);
			abort_if(empty($postObj), 404, t('post_not_found'));
			
			$payResult = $this->isPaymentRequested($request, $postObj);
			if (data_get($payResult, 'success')) {
				return $this->sendPayment($request, $postObj);
			}
			if (data_get($payResult, 'failure')) {
				flash(data_get($payResult, 'message'))->error();
			}
			
			// ===| If no payment is made (continue) |===========
		}
		
		if (
			data_get($data, 'extra.sendEmailVerification.emailVerificationSent')
			|| data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')
		) {
			session()->put('itemNextUrl', $nextUrl);
			
			if (data_get($data, 'extra.sendEmailVerification.emailVerificationSent')) {
				session()->put('emailVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationEmailLink($post, 'posts');
			}
			
			if (data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')) {
				session()->put('phoneVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationSmsLink($post, 'posts');
				
				// Phone Number verification
				// Get the token|code verification form page URL
				// The user is supposed to have received this token|code by SMS
				$nextUrl = url('posts/verify/phone/');
			}
		}
		
		// Mail Notification Message
		if (data_get($data, 'extra.mail.message')) {
			$mailMessage = data_get($data, 'extra.mail.message');
			if (data_get($data, 'extra.mail.success')) {
				flash($mailMessage)->success();
			} else {
				flash($mailMessage)->error();
			}
		}
		
		return redirect()->to($nextUrl);
	}
}
