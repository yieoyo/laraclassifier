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

trait MetaTagTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getPageHtml()
	{
		$entries = self::getDefaultPages();
		
		// Get Page Name
		$out = $this->page;
		if (isset($entries[$this->page])) {
			$url = admin_url('meta_tags/' . $this->id . '/edit');
			$out = '<a href="' . $url . '">' . $entries[$this->page] . '</a>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function getDefaultPages(): array
	{
		return [
			'home'           => 'Homepage',
			'search'         => 'Search (Default)',
			'searchCategory' => 'Search (Category)',
			'searchLocation' => 'Search (Location)',
			'searchProfile'  => 'Search (Profile)',
			'searchTag'      => 'Search (Tag)',
			'listingDetails' => 'Listing Details',
			'register'       => 'Register',
			'login'          => 'Login',
			'create'         => 'Listings Creation',
			'countries'      => 'Countries',
			'contact'        => 'Contact',
			'sitemap'        => 'Sitemap',
			'password'       => 'Password',
			'pricing'        => 'Pricing',
			'staticPage'     => 'Page (Static)',
		];
	}
}
