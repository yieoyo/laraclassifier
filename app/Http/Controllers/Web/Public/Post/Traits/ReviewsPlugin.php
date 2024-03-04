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

namespace App\Http\Controllers\Web\Public\Post\Traits;

trait ReviewsPlugin
{
	private string $postHelperClass = '\extras\plugins\reviews\app\Helpers\Post';
	
	/**
	 * @param $postId
	 * @return array
	 */
	public function getReviews($postId): array
	{
		if (config('plugins.reviews.installed')) {
			if (class_exists($this->postHelperClass)) {
				return $this->postHelperClass::getReviews($postId);
			}
		}
		
		return [];
	}
}
