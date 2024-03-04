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

namespace App\Helpers;

class Referrer
{
	/**
	 * @param int $cacheExpiration
	 * @return array
	 */
	public static function getPostTypes(int $cacheExpiration): array
	{
		// Get postTypes - Call API endpoint
		$cacheId = 'api.postTypes.all.' . config('app.locale');
		$postTypes = cache()->remember($cacheId, $cacheExpiration, function () {
			$endpoint = '/postTypes';
			$queryParams = ['sort' => '-lft'];
			$queryParams = array_merge(request()->all(), $queryParams);
			$data = makeApiRequest('get', $endpoint, $queryParams);
			
			$apiMessage = self::handleHttpError($data);
			$apiResult = data_get($data, 'result');
			
			return data_get($apiResult, 'data');
		});
		
		return is_array($postTypes) ? $postTypes : [];
	}
	
	// PRIVATE
	
	/*
	 * Handle HTTP error for GET requests
	 */
	private static function handleHttpError(?array $data = [])
	{
		// Parsing the API response
		$message = !empty(data_get($data, 'message')) ? data_get($data, 'message') : null;
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			$message = !empty($message) ? $message : 'Unknown Error.';
			$errorCode = (int)data_get($data, 'status');
			$errorCode = (strlen($errorCode) == 3) ? $errorCode : 400;
			
			abort($errorCode, $message);
		}
		
		return $message;
	}
}
