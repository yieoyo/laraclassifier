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

namespace App\Http\Controllers\Web\Public\Search\Traits;

use App\Helpers\UrlGen;
use App\Http\Controllers\Web\Public\Post\Traits\CatBreadcrumbTrait;
use Illuminate\Support\Arr;

trait TitleTrait
{
	use CatBreadcrumbTrait;
	
	/**
	 * Get Search HTML Title
	 *
	 * @param array|null $preSearch
	 * @param array|null $sidebar
	 * @return string
	 */
	public function getHtmlTitle(?array $preSearch = [], ?array $sidebar = []): string
	{
		// Title
		$htmlTitle = '';
		
		// Init.
		$htmlTitle .= '<a href="' . UrlGen::searchWithoutQuery() . '" class="current">';
		$htmlTitle .= '<span>' . t('All listings') . '</span>';
		$htmlTitle .= '</a>';
		
		// Location
		$searchUrl = UrlGen::search([], ['l', 'r', 'location', 'distance']);
		
		if (request()->filled('r') && !request()->filled('l')) {
			// Administrative Division
			if (!empty(data_get($preSearch, 'admin'))) {
				$htmlTitle .= ' ' . t('in') . ' ';
				$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
				$htmlTitle .= data_get($preSearch, 'admin.name');
				$htmlTitle .= '</a>';
			}
		} else {
			// City
			if (!empty(data_get($preSearch, 'city'))) {
				if (config('settings.list.cities_extended_searches')) {
					$distance = (data_get($preSearch, 'distance.current') == 1) ? 0 : data_get($preSearch, 'distance.current') ?? 0;
					$htmlTitle .= ' ' . t('within') . ' ';
					$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
					$htmlTitle .= t('x_distance_around_city', [
						'distance' => $distance,
						'unit'     => getDistanceUnit(config('country.code')),
						'city'     => data_get($preSearch, 'city.name'),
					]);
				} else {
					$htmlTitle .= ' ' . t('in') . ' ';
					$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
					$htmlTitle .= data_get($preSearch, 'city.name');
				}
				$htmlTitle .= '</a>';
			}
		}
		
		// Category
		if (!empty(data_get($preSearch, 'cat'))) {
			// Get the parent of parent category URL
			$exceptArr = ['c', 'sc', 'cf', 'minPrice', 'maxPrice'];
			$searchUrl = UrlGen::getCatParentUrl(data_get($preSearch, 'cat.parent.parent') ?? null, data_get($preSearch, 'city') ?? null, $exceptArr);
			
			if (!empty(data_get($preSearch, 'cat.parent'))) {
				$htmlTitle .= ' ' . t('in') . ' ';
				$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
				$htmlTitle .= data_get($preSearch, 'cat.parent.name');
				$htmlTitle .= '</a>';
				
				// Get the parent category URL
				$exceptArr = ['sc', 'cf', 'minPrice', 'maxPrice'];
				$searchUrl = UrlGen::getCatParentUrl(data_get($preSearch, 'cat.parent'), data_get($preSearch, 'city'), $exceptArr);
			}
			
			$htmlTitle .= ' ' . t('in') . ' ';
			$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
			$htmlTitle .= data_get($preSearch, 'cat.name');
			$htmlTitle .= '</a>';
		}
		
		// Tag
		if (!empty($this->tag)) {
			$htmlTitle .= ' ' . t('for') . ' ';
			$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . UrlGen::searchWithoutQuery() . '">';
			$htmlTitle .= $this->tag;
			$htmlTitle .= '</a>';
		}
		
		// Date
		$postedDate = request()->filled('postedDate') ? request()->query('postedDate') : null;
		$postedDateLabel = data_get($sidebar, 'periodList.' . $postedDate);
		if (!empty($postedDateLabel)) {
			$exceptArr = ['postedDate'];
			$searchUrl = UrlGen::search([], $exceptArr);
			
			$htmlTitle .= t('last');
			$htmlTitle .= '<a rel="nofollow" class="jobs-s-tag" href="' . $searchUrl . '">';
			$htmlTitle .= $postedDateLabel;
			$htmlTitle .= '</a>';
		}
		
		view()->share('htmlTitle', $htmlTitle);
		
		return $htmlTitle;
	}
	
	/**
	 * Get Breadcrumbs Tabs
	 *
	 * @param array|null $preSearch
	 * @return array
	 */
	public function getBreadcrumb(?array $preSearch = []): array
	{
		$bcTab = [];
		
		// City
		if (!empty(data_get($preSearch, 'city'))) {
			$distance = (data_get($preSearch, 'distance.current') == 1) ? 0 : data_get($preSearch, 'distance.current') ?? 0;
			$title = t('in_x_distance_around_city', [
				'distance' => $distance,
				'unit'     => getDistanceUnit(config('country.code')),
				'city'     => data_get($preSearch, 'city.name'),
			]);
			
			$bcTab[] = collect([
				'name'     => (!empty(data_get($preSearch, 'city')) ? t('All listings') . ' ' . $title : data_get($preSearch, 'city.name')),
				'url'      => UrlGen::city(data_get($preSearch, 'city')),
				'position' => (!empty(data_get($preSearch, 'cat')) ? 5 : 3),
				'location' => true,
			]);
		}
		
		// Admin
		if (!empty(data_get($preSearch, 'admin'))) {
			$queryArr = [
				'country' => config('country.icode'),
				'r'       => data_get($preSearch, 'admin.name'),
			];
			$exceptArr = ['l', 'location', 'distance'];
			$searchUrl = UrlGen::search($queryArr, $exceptArr);
			
			$title = data_get($preSearch, 'admin.name');
			
			$bcTab[] = collect([
				'name'     => (!empty(data_get($preSearch, 'cat')) ? t('All listings') . ' ' . $title : data_get($preSearch, 'admin.name')),
				'url'      => $searchUrl,
				'position' => (!empty(data_get($preSearch, 'cat')) ? 5 : 3),
				'location' => true,
			]);
		}
		
		// Category
		$catBreadcrumb = $this->getCatBreadcrumb(data_get($preSearch, 'cat'), 3);
		$bcTab = array_merge($bcTab, $catBreadcrumb);
		
		// Sort by Position
		$bcTab = array_values(Arr::sort($bcTab, function ($value) {
			return $value->get('position');
		}));
		
		view()->share('bcTab', $bcTab);
		
		return $bcTab;
	}
}
