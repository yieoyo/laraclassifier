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

namespace App\Observers\Traits\Setting;

use App\Models\Post;
use Prologue\Alerts\Facades\Alert;

trait SingleTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return false|void
	 */
	public function singleUpdating($setting, $original)
	{
		// title length
		$passwordMinLength = $setting->value['title_min_length'] ?? 2;
		$passwordMaxLength = $setting->value['title_max_length'] ?? 150;
		if ($passwordMinLength > $passwordMaxLength) {
			$message = trans('admin.min_max_error_message', ['attribute' => trans('admin.title_length')]);
			
			if (isAdminPanel()) {
				Alert::error($message)->flash();
			} else {
				flash($message)->error();
			}
			
			return false;
		}
		
		// description length
		$passwordMinLength = $setting->value['description_min_length'] ?? 5;
		$passwordMaxLength = $setting->value['description_max_length'] ?? 6000;
		if ($passwordMinLength > $passwordMaxLength) {
			$message = trans('admin.min_max_error_message', ['attribute' => trans('admin.description_length')]);
			
			if (isAdminPanel()) {
				Alert::error($message)->flash();
			} else {
				flash($message)->error();
			}
			
			return false;
		}
		
		// tags length
		$passwordMinLength = $setting->value['tags_min_length'] ?? 2;
		$passwordMaxLength = $setting->value['tags_max_length'] ?? 30;
		if ($passwordMinLength > $passwordMaxLength) {
			$message = trans('admin.min_max_error_message', ['attribute' => trans('admin.tags_length')]);
			
			if (isAdminPanel()) {
				Alert::error($message)->flash();
			} else {
				flash($message)->error();
			}
			
			return false;
		}
		
		$this->autoReviewedExistingPostsIfApprobationIsEnabled($setting);
	}
	
	/**
	 * Auto approve all the existing listings,
	 * If the Posts Approbation feature is enabled
	 *
	 * @param $setting
	 * @return void
	 */
	private function autoReviewedExistingPostsIfApprobationIsEnabled($setting): void
	{
		// Enable Posts Approbation by User Admin (Post Review)
		if (array_key_exists('listings_review_activation', $setting->value)) {
			// If Listing Approbation is enabled,
			// then set the reviewed field to "true" for all the existing Posts
			if ((int)$setting->value['listings_review_activation'] == 1) {
				Post::whereNull('reviewed_at')->update(['reviewed_at' => now()]);
			}
		}
	}
}
