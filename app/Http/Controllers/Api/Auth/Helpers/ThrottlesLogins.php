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

namespace App\Http\Controllers\Api\Auth\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Cache\RateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

trait ThrottlesLogins
{
	/**
	 * Determine if the user has too many failed login attempts.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return bool
	 */
	protected function hasTooManyLoginAttempts(Request $request)
	{
		return $this->limiter()->tooManyAttempts(
			$this->throttleKey($request), $this->maxAttempts()
		);
	}
	
	/**
	 * Increment the login attempts for the user.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	protected function incrementLoginAttempts(Request $request)
	{
		$this->limiter()->hit(
			$this->throttleKey($request), $this->decayMinutes() * 60
		);
	}
	
	/**
	 * Redirect the user after determining they are locked out.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 *
	 * @throws \Illuminate\Validation\ValidationException
	 */
	protected function sendLockoutResponse(Request $request)
	{
		$seconds = $this->limiter()->availableIn(
			$this->throttleKey($request)
		);
		
		throw ValidationException::withMessages([
			$this->username() => [Lang::get('auth.throttle', ['seconds' => $seconds])],
		])->status(Response::HTTP_TOO_MANY_REQUESTS);
	}
	
	/**
	 * Clear the login locks for the given user credentials.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	protected function clearLoginAttempts(Request $request)
	{
		$this->limiter()->clear($this->throttleKey($request));
	}
	
	/**
	 * Fire an event when a lockout occurs.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return void
	 */
	protected function fireLockoutEvent(Request $request)
	{
		event(new Lockout($request));
	}
	
	/**
	 * Get the throttle key for the given request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	protected function throttleKey(Request $request)
	{
		return str($request->input($this->username()))->lower() . '|' . $request->ip();
	}
	
	/**
	 * Get the rate limiter instance.
	 *
	 * @return \Illuminate\Cache\RateLimiter
	 */
	protected function limiter()
	{
		return app(RateLimiter::class);
	}
	
	/**
	 * Get the maximum number of attempts to allow.
	 *
	 * @return int
	 */
	public function maxAttempts()
	{
		return property_exists($this, 'maxAttempts') ? $this->maxAttempts : 5;
	}
	
	/**
	 * Get the number of minutes to throttle for.
	 *
	 * @return int
	 */
	public function decayMinutes()
	{
		return property_exists($this, 'decayMinutes') ? $this->decayMinutes : 1;
	}
}
