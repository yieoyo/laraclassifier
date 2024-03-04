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

namespace App\Observers;

use App\Models\Blacklist;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;

class BlacklistObserver
{
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Blacklist $blacklist
	 * @return void
	 */
	public function saved(Blacklist $blacklist)
	{
		// Check if an email address has been banned
		if ($blacklist->type == 'email') {
			// Check if it is a valid email address
			if (filter_var($blacklist->entry, FILTER_VALIDATE_EMAIL)) {
				$exceptEmailDomains = [getDomain(), 'demosite.com', 'larapen.com'];
				$blacklistEmailDomain = str($blacklist->entry)->after('@');
				
				// Don't remove banned email address data for the "except" domains
				if (!in_array($blacklistEmailDomain, $exceptEmailDomains)) {
					// Delete the banned user related to the email address
					$user = User::where('email', $blacklist->entry)->first();
					$userDoesNotExist = empty($user);
					$userExistsAndIsNotAdmin = (!empty($user) && !$user->can(Permission::getStaffPermissions()));
					if ($userExistsAndIsNotAdmin) {
						$user->delete();
					}
					
					// Delete the banned user's listings related to the email address
					if ($userDoesNotExist || $userExistsAndIsNotAdmin) {
						$posts = Post::where('email', $blacklist->entry);
						if ($posts->count() > 0) {
							foreach ($posts->cursor() as $post) {
								$post->delete();
							}
						}
					}
				}
			}
		}
		
		// Check if a phone number has been banned
		if ($blacklist->type == 'phone') {
			// Delete the banned user related to the phone number
			$user = User::where('phone', $blacklist->entry)->first();
			$userDoesNotExist = empty($user);
			$userExistsAndIsNotAdmin = (!empty($user) && !$user->can(Permission::getStaffPermissions()));
			if ($userExistsAndIsNotAdmin) {
				$user->delete();
			}
			
			// Delete the banned user's listings related to the phone number
			if ($userDoesNotExist || $userExistsAndIsNotAdmin) {
				$posts = Post::where('phone', $blacklist->entry);
				if ($posts->count() > 0) {
					foreach ($posts->cursor() as $post) {
						$post->delete();
					}
				}
			}
		}
	}
}
