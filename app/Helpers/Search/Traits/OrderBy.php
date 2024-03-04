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

namespace App\Helpers\Search\Traits;

use Illuminate\Support\Facades\DB;

trait OrderBy
{
	protected function applyOrderBy(): void
	{
		if (!(isset($this->posts) && isset($this->postsTable) && isset($this->orderBy))) {
			return;
		}
		
		// Request Parameters
		// 'queryStringKey' => ['name' => 'column', 'order' => 'direction']
		$orderByParametersFields = [
			'priceAsc'  => ['name' => $this->postsTable . '.price', 'order' => 'ASC'],
			'priceDesc' => ['name' => $this->postsTable . '.price', 'order' => 'DESC'],
			'date'      => ['name' => $this->postsTable . '.created_at', 'order' => 'DESC'],
			// KeywordFilter (by 'relevance') - Only if needed
			// LocationFilter (by 'distance') - Only if needed
			// PaymentRelation (by 'premium' or 'random') - Only used by the system
		];
		$this->orderByParametersFields = array_merge($this->orderByParametersFields, $orderByParametersFields);
		
		if (config('plugins.reviews.installed')) {
			// Make possible the orderBy 'rating'
			$this->orderByParametersFields['rating'] = ['name' => 'rating_cache', 'order' => 'DESC'];
		}
		
		// Get requested order key
		$requested = data_get($this->input, 'orderBy');
		
		// If random order is requested, apply it and don't continue
		if ($requested == 'random') {
			$this->posts->orderByRaw('RAND()');
			return;
		}
		
		// Apply the 'created_at' column for orderBy
		// Check if the 'created_at' column is already apply for orderBy
		$orderByCreatedAtFound = false;
		if (is_array($this->orderBy) && count($this->orderBy) > 0) {
			$orderByCreatedAtFound = collect($this->orderBy)->contains(function ($value, $key) {
				return str_contains($value, 'created_at');
			});
		}
		if (!$orderByCreatedAtFound) {
			$this->orderBy[] = $this->postsTable . '.created_at DESC';
		}
		
		// Apply the requested order
		$requestedOrder = $this->getRequestedOrderStatement($requested);
		if (!empty($requestedOrder)) {
			if (!in_array($requestedOrder, $this->orderBy)) {
				$this->orderBy[] = $requestedOrder;
			}
		}
		
		// Set the orderBy priorities
		$this->orderBy = $this->getOrderByPriorities($requestedOrder);
		
		// Get valid columns name
		$this->orderBy = collect($this->orderBy)->map(function ($value, $key) {
			if (str_contains($value, '.')) {
				$value = DB::getTablePrefix() . $value;
			}
			
			return $value;
		})->toArray();
		
		// Set ORDER BY
		$orderBy = '';
		if (is_array($this->orderBy) && count($this->orderBy) > 0) {
			foreach ($this->orderBy as $key => $value) {
				if (trim($value) == '') {
					continue;
				}
				
				if ($orderBy == '') {
					$orderBy .= $value;
				} else {
					$orderBy .= ', ' . $value;
				}
			}
		}
		
		if (!empty($orderBy)) {
			$this->posts->orderByRaw($orderBy);
		}
	}
	
	/**
	 * Get the requested Order Statement
	 *
	 * @param $requested
	 * @return string|null
	 */
	public function getRequestedOrderStatement($requested): ?string
	{
		if (!isset($this->orderBy)) {
			return null;
		}
		
		if (!isset($this->orderByParametersFields[$requested])) {
			return null;
		}
		
		return $this->orderByParametersFields[$requested]['name'] . ' ' . $this->orderByParametersFields[$requested]['order'];
	}
	
	/**
	 * Set the orderBy priorities
	 *
	 * @param $requestedOrder
	 * @return array
	 */
	private function getOrderByPriorities($requestedOrder): array
	{
		// Default Priorities
		$orderByPriorities = [
			'lft'                     => 90,
			config('distance.rename') => 89,
			'created_at'              => 88,
		];
		
		if (config('settings.list.cities_extended_searches')) {
			if (!empty($this->city)) {
				if (request()->filled('distance')) {
					$orderByPriorities[config('distance.rename')] = 91;
				}
			}
		}
		
		$orderBy = [];
		
		if (!empty($requestedOrder)) {
			$orderBy[99] = $requestedOrder;
		}
		
		if (is_array($this->orderBy) && count($this->orderBy) > 0) {
			foreach ($this->orderBy as $key => $statement) {
				foreach ($orderByPriorities as $orderKeyword => $priority) {
					if (str_contains($statement, $orderKeyword)) {
						if (!in_array($statement, $orderBy)) {
							$orderBy[$priority] = $statement;
						}
					}
				}
				if (!in_array($statement, $orderBy)) {
					$orderBy[$key] = $statement;
				}
			}
			
			ksort($orderBy);
			$orderBy = array_reverse($orderBy, true);
		}
		
		return $orderBy;
	}
}
