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

use App\Helpers\UrlGen;
use App\Http\Controllers\Web\Public\FrontController;
use App\Http\Requests\Front\LoginRequest;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class LoginController extends FrontController
{
	/**
	 * Where to redirect users after login / registration.
	 *
	 * @var string
	 */
	// If not logged in redirect to
	protected mixed $loginPath = 'login';
	
	// After you've logged in redirect to
	protected $redirectTo = 'account';
	
	// After you've logged out redirect to
	protected $redirectAfterLogout = '/';
	
	// The maximum number of attempts to allow
	protected $maxAttempts = 5;
	
	// The number of minutes to throttle for
	protected $decayMinutes = 15;
	
	/**
	 * LoginController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->middleware('guest')->except(['except' => 'logout']);
		
		$isFromAdminArea = str_contains(url()->previous(), '/' . admin_uri());
		$isFromLoginPage = str_contains(url()->previous(), '/' . UrlGen::loginPath());
		
		// Set default URLs
		if (!$isFromAdminArea) {
			$this->loginPath = $isFromLoginPage ? UrlGen::loginPath() : url()->previous();
			$this->redirectTo = $isFromLoginPage ? 'account' : url()->previous();
			$this->redirectAfterLogout = '/';
		} else {
			$this->loginPath = admin_uri('login');
			$this->redirectTo = admin_uri();
			$this->redirectAfterLogout = admin_uri('login');
		}
		
		// Get values from Config
		$this->maxAttempts = (int)config('settings.security.login_max_attempts', $this->maxAttempts);
		$this->decayMinutes = (int)config('settings.security.login_decay_minutes', $this->decayMinutes);
	}
	
	// -------------------------------------------------------
	// Laravel overwrites for loading LaraClassifier views
	// -------------------------------------------------------
	
	/**
	 * Show the application login form.
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function showLoginForm()
	{
		// Remembering Login
		if (auth()->viaRemember()) {
			return redirect()->intended($this->redirectTo);
		}
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('login');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
		
		return appView('auth.login.index');
	}
	
	/**
	 * @param \App\Http\Requests\Front\LoginRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function login(LoginRequest $request)
	{
		// Call API endpoint
		$endpoint = '/auth/login';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response...
		
		// Response for successful login
		if (
			data_get($data, 'isSuccessful')
			&& data_get($data, 'success')
			&& !empty(data_get($data, 'extra.authToken'))
			&& !empty(data_get($data, 'result.id'))
		) {
			auth()->loginUsingId(data_get($data, 'result.id'));
			session()->put('authToken', data_get($data, 'extra.authToken'));
			
			if (data_get($data, 'extra.isAdmin')) {
				return redirect()->to(admin_uri());
			}
			
			return redirect()->intended($this->redirectTo);
		}
		
		// Response for failed login
		// Check and retrieve the previous URL to show the login error on it.
		if (session()->has('url.intended')) {
			$this->loginPath = session('url.intended');
		}
		
		// Get the error message
		$message = data_get($data, 'message', trans('auth.failed'));
		
		return redirect()->to($this->loginPath)->withErrors(['error' => $message])->withInput();
	}
	
	/**
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function logout(Request $request)
	{
		$userId = auth()->check() ? auth()->user()->id : null;
		
		// Call API endpoint
		$endpoint = '/auth/logout/' . $userId;
		$data = makeApiRequest('get', $endpoint);
		
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		
		if (data_get($data, 'isSuccessful')) {
			// Log out the user on a web client (Browser)
			logoutOnClient($message);
		} else {
			flash($message)->error();
		}
		
		return redirect()->to(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
	}
}
