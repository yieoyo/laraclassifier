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

namespace App\Models\Traits;

use App\Helpers\UrlGen;
use App\Models\Post;
use Spatie\Feed\FeedItem;

trait PostTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getTitleHtml(): string
	{
		$out = getPostUrl($this);
		$out .= '<br>';
		$out .= '<small>';
		$out .= $this->pictures->count() . ' ' . trans('admin.pictures');
		$out .= '</small>';
		
		if (!empty($this->archived_at)) {
			$out .= '<br>';
			$out .= '<span class="badge bg-secondary">';
			$out .= trans('admin.Archived');
			$out .= '</span>';
		}
		
		return $out;
	}
	
	public function getPictureHtml(): string
	{
		// Get listing URL
		// $url = url(UrlGen::postUri($this));
		$url = dmUrl($this->country_code, UrlGen::postPath($this));
		
		// Get the first picture
		$style = ' style="width:auto; max-height:90px;"';
		$pictureUrl = $this->picture_url_small ?? config('larapen.core.picture.default');
		$out = '<img src="' . $pictureUrl . '" data-bs-toggle="tooltip" title="' . $this->title . '"' . $style . ' class="img-rounded">';
		
		// Add a link to the listing
		return '<a href="' . $url . '" target="_blank">' . $out . '</a>';
	}
	
	public function getUserNameHtml()
	{
		if (!empty($this->user)) {
			$url = admin_url('users/' . $this->user->getKey() . '/edit');
			$tooltip = ' data-bs-toggle="tooltip" title="' . $this->user->name . '"';
			
			return '<a href="' . $url . '"' . $tooltip . '>' . $this->contact_name . '</a>';
		} else {
			return $this->contact_name;
		}
	}
	
	public function getCityHtml(): string
	{
		$out = $this->getCountryHtml();
		$out .= ' - ';
		if (!empty($this->city)) {
			$out .= '<a href="' . UrlGen::city($this->city) . '" target="_blank">' . $this->city->name . '</a>';
		} else {
			$out .= $this->city_id;
		}
		
		return $out;
	}
	
	public function getReviewedHtml(): string
	{
		return ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'reviewed_at', $this->reviewed_at);
	}
	
	public function getFeaturedHtml(): string
	{
		$out = '-';
		if (config('plugins.offlinepayment.installed')) {
			$opTool = '\extras\plugins\offlinepayment\app\Helpers\OpTools';
			if (class_exists($opTool)) {
				$out = $opTool::featuredCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'featured', $this->featured);
			}
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function getFeedItems()
	{
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
		$perPage = (int)config('settings.list.items_per_page', 50);
		
		$countryCode = null;
		if (request()->filled('country') || config('plugins.domainmapping.installed')) {
			$countryCode = config('country.code');
			if (!config('plugins.domainmapping.installed')) {
				if (request()->filled('country')) {
					$countryCode = request()->query('country');
				}
			}
		}
		
		// Cache ID
		$cacheId = (!empty($countryCode)) ? $countryCode . '.' : '';
		$cacheId .= 'postModel.getFeedItems';
		
		return cache()->remember($cacheId, $cacheExpiration, function () use ($countryCode, $perPage) {
			$posts = Post::reviewed()
				->unarchived()
				->when(!empty($countryCode), fn ($query) => $query->where('country_code', $countryCode))
				->take($perPage)
				->orderByDesc('id');
			
			return $posts->get();
		});
	}
	
	public function toFeedItem(): FeedItem
	{
		$title = $this->title;
		$title .= (!empty($this->city)) ? ' - ' . $this->city->name : '';
		$title .= (!empty($this->country)) ? ', ' . $this->country->name : '';
		// $summary = str_limit(str_strip(strip_tags($this->description)), 5000);
		$summary = transformDescription($this->description);
		$link = UrlGen::postUri($this, true);
		
		return FeedItem::create()
			->id($link)
			->title($title)
			->summary($summary)
			->category($this?->category?->name ?? '')
			->updated($this->updated_at)
			->link($link)
			->authorName($this->contact_name);
	}
}
