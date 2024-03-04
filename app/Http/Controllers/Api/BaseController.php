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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Base\SettingsTrait;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Public\Traits\CommonTrait;
use App\Http\Controllers\Web\Public\Traits\EnvFileTrait;
use Illuminate\Database\Eloquent\Builder;

class BaseController extends Controller
{
	use CommonTrait, SettingsTrait, EnvFileTrait;
	
	public ?string $locale = null;
	public ?string $countryCode = null;
	
	public $messages = [];
	public $errors = [];
	
	public int $cacheExpiration = 3600; // In minutes (e.g. 60 * 60 for 1h)
	public int $perPage = 10;
	
	/**
	 * BaseController constructor.
	 */
	public function __construct()
	{
		// CommonTrait: Set the storage disk
		$this->setStorageDisk();
		
		// SettingsTrait
		$this->applyFrontSettings();
		
		// CommonTrait: Check & Change the App Key (If needed)
		$this->checkAndGenerateAppKey();
		
		// CommonTrait: Load the Plugins
		$this->loadPlugins();
		
		// EnvFileTrait: Check & Update the /.env file
		$this->checkDotEnvEntries();
		
		// Max items per page
		$perPageMax = 300;
		if (config('settings.single.city_selection') == 'select') {
			if (str_contains(currentRouteAction(), 'SubAdmin2Controller')) {
				$perPageMax = 5000;
			}
		}
		
		// Items per page
		$perPage = config('settings.list.items_per_page');
		$perPage = (is_numeric($perPage) && $perPage > 1 && $perPage <= 100) ? $perPage : $this->perPage;
		$perPage = (request()->filled('perPage')) ? request()->integer('perPage') : $perPage;
		$this->perPage = (is_numeric($perPage) && $perPage > 1 && $perPage <= $perPageMax) ? $perPage : $this->perPage;
	}
	
	/**
	 * Apply Sorting
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param array|null $fillable
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function applySorting(Builder $builder, ?array $fillable = []): Builder
	{
		if (empty($fillable) || !is_array($fillable)) {
			$fillable = $builder->getModel()->getFillable();
		}
		$primaryKey = $builder->getModel()->getKeyName();
		$fillable[] = $primaryKey;
		
		$columnWithOrder = request()->query('sort');
		if (is_array($columnWithOrder)) {
			foreach ($columnWithOrder as $colWithOrder) {
				if (is_string($colWithOrder)) {
					$builder = $this->addOrderBy($builder, $fillable, $colWithOrder, $primaryKey);
				}
			}
		} else {
			if (is_string($columnWithOrder)) {
				$builder = $this->addOrderBy($builder, $fillable, $columnWithOrder, $primaryKey);
			}
		}
		
		return $builder;
	}
	
	/**
	 * Add an orderBy statement
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder
	 * @param array $fillable
	 * @param string $columnWithOrder
	 * @param string|null $primaryKey
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function addOrderBy(Builder $builder, array $fillable, string $columnWithOrder, ?string $primaryKey = null): Builder
	{
		$column = ltrim($columnWithOrder, '-');
		if (in_array($column, $fillable)) {
			if (str_starts_with($columnWithOrder, '-')) {
				$builder->orderBy($column);
			} else {
				$builder->orderByDesc($column);
			}
		} else {
			if (!empty($primaryKey)) {
				$builder->orderByDesc($primaryKey);
			}
		}
		
		return $builder;
	}
	
	/**
	 * Cache control
	 *
	 * @return void
	 */
	protected function updateCachingParameters(): void
	{
		$cacheDriver = config('cache.default');
		$cacheExpiration = $this->cacheExpiration;
		
		$noCache = (request()->filled('noCache') && request()->integer('noCache') == 1);
		if ($noCache) {
			config()->set('cache.default', 'array');
			$this->cacheExpiration = -1;
		}
		
		config()->set('cache.tmp.driver', $cacheDriver);
		config()->set('cache.tmp.expiration', $cacheExpiration);
	}
	
	/**
	 * Reset caching parameters
	 *
	 * @return void
	 */
	protected function resetCachingParameters(): void
	{
		config()->set('cache.default', config('cache.tmp.driver', 'file'));
		$this->cacheExpiration = (int)config('cache.tmp.expiration', 3600);
	}
}
