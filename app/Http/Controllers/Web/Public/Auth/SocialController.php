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
use App\Http\Controllers\Api\Auth\Social\SaveProviderData;
use App\Http\Controllers\Web\Public\FrontController;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends FrontController
{
	use SaveProviderData;
	
	// If not logged in redirect to
	protected mixed $loginPath = 'login';
	
	// After you've logged in redirect to
	protected string $redirectTo = 'account';
	
	// Supported Providers (URI => Service Key)
	private array $network = [
		'facebook'        => 'facebook',
		'linkedin'        => 'linkedin-openid',
		'twitter-oauth-2' => 'twitter-oauth-2',
		'twitter'         => 'twitter',
		'google'          => 'google',
	];
	private array $networkChecker;
	
	private string $serviceNotFound = 'The social network "%s" is not available.';
	private string $serviceNotEnabled = 'The social network "%s" is not enabled.';
	private string $serviceError = "Unknown error. The service does not work.";
	
	/**
	 * SocialController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Set default URLs
		$isFromLoginPage = str_contains(url()->previous(), '/' . UrlGen::loginPath());
		$this->loginPath = $isFromLoginPage ? UrlGen::loginPath() : url()->previous();
		$this->redirectTo = $isFromLoginPage ? 'account' : url()->previous();
		
		// Providers Checker
		$this->networkChecker = [
			'facebook'        => (
				config('settings.social_auth.facebook_client_id')
				&& config('settings.social_auth.facebook_client_secret')
			),
			'linkedin'        => (
				config('settings.social_auth.linkedin_client_id')
				&& config('settings.social_auth.linkedin_client_secret')
			),
			'twitter-oauth-2' => (
				config('settings.social_auth.twitter_oauth_2_client_id')
				&& config('settings.social_auth.twitter_oauth_2_client_secret')
			),
			'twitter'         => (
				config('settings.social_auth.twitter_client_id')
				&& config('settings.social_auth.twitter_client_secret')
			),
			'google'          => (
				config('settings.social_auth.google_client_id')
				&& config('settings.social_auth.google_client_secret')
			),
		];
	}
	
	/**
	 * Redirect the user to the Provider authentication page.
	 *
	 * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function redirectToProvider(): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\RedirectResponse
	{
		// Get the Provider and verify that if it's supported
		$provider = request()->segment(2);
		$serviceKey = $this->network[$provider] ?? null;
		
		if (empty($serviceKey)) {
			$message = sprintf($this->serviceNotFound, $provider);
			abort(404, $message);
		}
		
		// Check if the Provider is enabled
		$providerIsEnabled = (array_key_exists($provider, $this->networkChecker) && $this->networkChecker[$provider]);
		if (!$providerIsEnabled) {
			$message = sprintf($this->serviceNotEnabled, $provider);
			flash($message)->error();
			
			return redirect()->to(UrlGen::loginPath(), 301);
		}
		
		// If previous page is not the Login page...
		if (!str_contains(url()->previous(), UrlGen::loginPath())) {
			// Save the previous URL to retrieve it after success or failed login.
			session()->put('url.intended', url()->previous());
		}
		
		// Redirect to the Provider's website
		try {
			
			return Socialite::driver($serviceKey)->redirect();
			
		} catch (\Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->serviceError;
			}
			flash($message)->error();
			
			return redirect()->to($this->loginPath);
		}
	}
	
	/**
	 * Obtain the user information from the Provider.
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function handleProviderCallback(): \Illuminate\Http\RedirectResponse
	{
		// Get the Provider and verify that if it's supported
		$provider = request()->segment(2);
		$serviceKey = $this->network[$provider] ?? null;
		
		if (empty($serviceKey)) {
			$message = sprintf($this->serviceNotFound, $provider);
			abort(404, $message);
		}
		
		// Check and retrieve the previous URL to show the login error on it.
		if (session()->has('url.intended')) {
			$this->loginPath = session('url.intended');
		}
		
		// API CALL - GET USER FROM PROVIDER
		try {
			$providerData = Socialite::driver($serviceKey)->user();
			
			// Data aren't found
			if (!$providerData) {
				$message = t('unknown_error_please_try_again');
				flash($message)->error();
				
				return redirect()->to($this->loginPath);
			}
			
			// Email isn't found
			if (!filter_var($providerData->getEmail(), FILTER_VALIDATE_EMAIL)) {
				$message = t('email_not_found_at_provider', ['provider' => str($provider)->headline()]);
				flash($message)->error();
				
				return redirect()->to($this->loginPath);
			}
		} catch (\Throwable $e) {
			$message = $e->getMessage();
			if (empty($message)) {
				$message = $this->serviceError;
			}
			flash($message)->error();
			
			return redirect()->to($this->loginPath);
		}
		
		// DEBUG
		// dd($providerData);
		
		// SAVE USER
		$data = $this->saveUser($provider, $providerData)->getData(true);
		
		$userIsSaved = data_get($data, 'success');
		if ($userIsSaved) {
			// Response for successful login
			$userIsLoggedIn = (!empty(data_get($data, 'extra.authToken')) && !empty(data_get($data, 'result.id')));
			
			if ($userIsLoggedIn) {
				auth()->loginUsingId(data_get($data, 'result.id'));
				session()->put('authToken', data_get($data, 'extra.authToken'));
				
				return redirect()->intended($this->redirectTo);
			}
		}
		
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : 'Unknown Error.';
		flash($message)->error();
		
		return redirect()->to($this->loginPath);
	}
}
