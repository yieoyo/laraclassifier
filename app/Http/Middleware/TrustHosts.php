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

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
	/**
	 * Get the host patterns that should be trusted.
	 *
	 * @return array
	 */
	public function hosts()
	{
		// Default App's Hosts
		$hosts = [
			parse_url(config('app.url'), PHP_URL_HOST),
			$this->allSubdomainsOfApplicationUrl(),
		];
		
		// Domain Mapping Plugin Hosts
		$domains = [];
		try {
			$domainModel = '\extras\plugins\domainmapping\app\Models\Domain';
			if (class_exists($domainModel)) {
				$domains = $domainModel::query()->get();
				if ($domains->count() > 0) {
					$domains = collect($domains->toArray())
						->mapWithKeys(function ($item, $key) {
							$item = str_starts_with($item['host'], 'http')
								? parse_url($item['host'], PHP_URL_HOST)
								: $item['host'];
							
							return [$key => $item];
						})
						->reject(fn ($item) => empty($item))
						->toArray();
				}
			}
		} catch (\Throwable $e) {
		}
		
		return array_merge($hosts, $domains);
	}
}
