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

namespace App\Http\Controllers\Web\Public\Account;

use App\Helpers\Cookie;

class CloseController extends AccountBaseController
{
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		return appView('account.close');
	}
	
	/**
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function submit()
	{
		if (request()->input('close_account_confirmation') == 1) {
			// Call API endpoint
			$endpoint = '/users/' . auth()->user()->id;
			$data = makeApiRequest('delete', $endpoint, request()->all());
			
			// Parsing the API response
			$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
			
			// HTTP Error Found
			if (!data_get($data, 'isSuccessful')) {
				flash($message)->error();
				
				return redirect()->back()->withInput();
			}
			
			// Notification Message
			if (data_get($data, 'success')) {
				// Log out the user if he is still logged on a web device
				if (auth()->check()) {
					// The logout() method is no longer available (in auth()->logout()) once the user is deleted
					request()->session()->flush();
					request()->session()->regenerate();
				}
				
				// Remove all user's stored cookies (from his browser)
				Cookie::forgetAll();
				
				flash($message)->success();
			} else {
				flash($message)->error();
			}
		}
		
		return redirect()->to('/');
	}
}
