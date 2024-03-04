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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\SpatieTranslatable;

use Cviebrock\EloquentSluggable\SluggableScopeHelpers as OriginalSluggableScopeHelpers;
use Illuminate\Database\Eloquent\Builder;

trait SluggableScopeHelpers
{
	use OriginalSluggableScopeHelpers;
	
	/**
	 * Query scope for finding a model by its primary slug.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $scope
	 * @param string                                $slug
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeWhereSlug(Builder $scope, string $slug): Builder
	{
		return $scope->where($this->getSlugKeyName().'->'.$this->getLocale(), $slug);
	}
	
	/**
	 * Find a model by its primary slug.
	 *
	 * @param string $slug
	 * @param array  $columns
	 *
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
	 */
	public static function findBySlug(string $slug, array $columns = ['*'])
	{
		return static::whereSlug($slug)->first($columns);
	}
	
	/**
	 * Find a model by its primary slug or throw an exception.
	 *
	 * @param string $slug
	 * @param array  $columns
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 *
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
	 */
	public static function findBySlugOrFail(string $slug, array $columns = ['*'])
	{
		return static::whereSlug($slug)->firstOrFail($columns);
	}
}
