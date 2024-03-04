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

namespace App\Models\Post;

trait ReviewsPlugin
{
	private string $postHelperClass = '\extras\plugins\reviews\app\Helpers\Post';
	
	/*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
	public function recalculateRating(): void
	{
		if (config('plugins.reviews.installed')) {
			if (class_exists($this->postHelperClass)) {
				$this->postHelperClass::recalculateRating($this);
			}
		}
	}
	
	public function userRating()
	{
		if (config('plugins.reviews.installed')) {
			if (class_exists($this->postHelperClass)) {
				return $this->postHelperClass::getUserRating($this);
			}
		}
		
		return null;
	}
	
	public function countUserRatings()
	{
		if (config('plugins.reviews.installed')) {
			if (class_exists($this->postHelperClass)) {
				return $this->postHelperClass::getCountUserRatings($this);
			}
		}
		
		return null;
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function reviews()
	{
		if (config('plugins.reviews.installed')) {
			if (class_exists($this->postHelperClass)) {
				return $this->postHelperClass::reviews($this);
			}
		}
		
		return $this;
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
}
