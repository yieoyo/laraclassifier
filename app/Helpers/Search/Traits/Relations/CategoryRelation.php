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

namespace App\Helpers\Search\Traits\Relations;

trait CategoryRelation
{
	protected function setCategoryRelation(): void
	{
		if (!(isset($this->posts) && isset($this->postsTable))) {
			abort(500, 'Fatal Error: Category relation cannot be applied.');
		}
		
		// category
		if (!config('settings.list.hide_category')) {
			$this->posts->with(['category' => fn ($query) => $query->with('parent')]);
		}
		
		if (!request()->filled('q')) {
			
			$this->posts->has('category');
			
		} else {
			
			$this->posts->join('categories as tCategory', function ($join) {
				$join->on('tCategory.id', '=', $this->postsTable . '.category_id')
					->where('tCategory.active', 1);
			});
			$this->posts->leftJoin('categories as tParentCat', function ($join) {
				$join->on('tParentCat.id', '=', 'tCategory.parent_id')
					->where('tParentCat.active', 1);
			});
			
		}
	}
}
