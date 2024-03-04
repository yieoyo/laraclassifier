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

namespace App\Http\Controllers\Web\Public\Auth;

use App\Http\Controllers\Web\Public\Auth\Helpers\RedirectsUsers;
use App\Http\Controllers\Web\Public\FrontController;
use App\Http\Requests\Front\ResetPasswordRequest;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ResetPasswordController extends FrontController
{
	use RedirectsUsers;
	
	/**
	 * Where to redirect users after resetting their password.
	 *
	 * @var string
	 */
	protected $redirectTo = '/account';
	
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware('guest');
	}
	
	// -------------------------------------------------------
	// Laravel overwrites for loading LaraClassifier views
	// -------------------------------------------------------
	
	/**
	 * Display the password reset view for the given token.
	 *
	 * If no token is present, display the link request form.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param $token
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showResetForm(Request $request, $token = null)
	{
		// Meta Tags
		MetaTag::set('title', t('reset_password'));
		MetaTag::set('description', t('reset_your_password'));
		
		return appView('auth.passwords.reset')->with([
			'token' => $token,
			'email' => $request->input('email'),
			'phone' => $request->input('phone'),
		]);
	}
	
	/**
	 * URL: Token Form
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showTokenRequestForm()
	{
		return appView('token');
	}
	
	/**
	 * Reset the given user's password.
	 *
	 * @param \App\Http\Requests\Front\ResetPasswordRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function reset(ResetPasswordRequest $request)
	{
		// If the password was successfully reset,
		// we will redirect the user back to the application's home authenticated view.
		// If there is an error, we can redirect them back to where they came from with their error message.
		
		// Call API endpoint
		$endpoint = '/auth/password/reset';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		if (data_get($data, 'isSuccessful') && data_get($data, 'success')) {
			if (
				!empty(data_get($data, 'extra.authToken'))
				&& !empty(data_get($data, 'result.id'))
			) {
				auth()->loginUsingId(data_get($data, 'result.id'));
				session()->put('authToken', data_get($data, 'extra.authToken'));
			}
			
			flash($message)->success();
			
			return redirect()->to($this->redirectPath())->with('status', $message);
		}
		
		return redirect()->back()
			->withInput($request->only('email'))
			->withErrors(['email' => $message]);
	}
}
