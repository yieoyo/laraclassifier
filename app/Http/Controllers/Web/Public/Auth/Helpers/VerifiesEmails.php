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

namespace App\Http\Controllers\Web\Public\Auth\Helpers;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;

trait VerifiesEmails
{
	use RedirectsUsers;
	
	/**
	 * Show the email verification notice.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
	 */
	public function show(Request $request)
	{
		return $request->user()->hasVerifiedEmail()
			? redirect($this->redirectPath())
			: view('auth.verify');
	}
	
	/**
	 * Mark the authenticated user's email address as verified.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function verify(Request $request)
	{
		if ($request->route('id') != $request->user()->getKey()) {
			throw new AuthorizationException;
		}
		
		if ($request->user()->hasVerifiedEmail()) {
			return redirect($this->redirectPath());
		}
		
		if ($request->user()->markEmailAsVerified()) {
			event(new Verified($request->user()));
		}
		
		return redirect($this->redirectPath())->with('verified', true);
	}
	
	/**
	 * Resend the email verification notification.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function resend(Request $request)
	{
		if ($request->user()->hasVerifiedEmail()) {
			return redirect($this->redirectPath());
		}
		
		$request->user()->sendEmailVerificationNotification();
		
		return back()->with('resent', true);
	}
}
