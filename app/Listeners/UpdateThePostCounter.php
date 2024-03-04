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

namespace App\Listeners;

use App\Events\PostWasVisited;

class UpdateThePostCounter
{
	/**
	 * Create the event listener.
	 */
	public function __construct()
	{
		//
	}
	
	/**
	 * Handle the event.
	 *
	 * @param \App\Events\PostWasVisited $event
	 * @return bool
	 */
	public function handle(PostWasVisited $event)
	{
		$isFromApi = isFromApi();
		
		// Don't count the self-visits
		$guard = $isFromApi ? 'sanctum' : null;
		if (auth($guard)->check()) {
			if (auth($guard)->user()->id == $event->post->user_id) {
				return false;
			}
		}
		
		if ($isFromApi) {
			if (
				!request()->hasHeader('X-VISITED-BY-SAME-SESSION')
				|| request()->header('X-VISITED-BY-SAME-SESSION') != $event->post->id
			) {
				$this->updateCounter($event->post);
				
				return true;
			}
			
			return false;
		}
		
		if (
			!session()->has('postIsVisited')
			|| session('postIsVisited') != $event->post->id
		) {
			$this->updateCounter($event->post);
			session()->put('postIsVisited', $event->post->id);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param $post
	 * @return void
	 */
	private function updateCounter($post): void
	{
		try {
			// Remove|unset the 'pictures' attribute (added to limit pictures number related to a selected package)
			$attributes = $post->getAttributes();
			if (isset($attributes['pictures'])) {
				unset($attributes['pictures']);
				$post->setRawAttributes($attributes, true);
			}
			
			$post->visits = $post->visits + 1;
			$post->save();
		} catch (\Throwable $e) {
		}
	}
}
