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

namespace App\Http\Controllers\Api\Category;

use App\Models\CategoryField;
use Illuminate\Http\Request;

trait FieldTrait
{
	/**
	 * List category's fields
	 *
	 * @bodyParam language_code string The code of the user's spoken language. Example: en
	 * @bodyParam post_id int required The unique ID of the post. Example: 1
	 *
	 * Note:
	 * - Called when showing Post's creation or edit forms
	 * - POST method is used instead of GET due to big JSON data sending (errors & old)
	 *
	 * @param $categoryId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCustomFields($categoryId, Request $request): \Illuminate\Http\JsonResponse
	{
		$languageCode = config('app.locale');
		$postId = $request->input('post_id');
		
		// Custom Fields vars
		$errors = $request->input('errors');
		$errors = convertUTF8HtmlToAnsi($errors); // Convert UTF-8 HTML to ANSI
		$errors = stripslashes($errors);
		$errors = collect(json_decode($errors, true));
		// ...
		$oldInput = $request->input('oldInput');
		$oldInput = convertUTF8HtmlToAnsi($oldInput); // Convert UTF-8 HTML to ANSI
		$oldInput = stripslashes($oldInput);
		$oldInput = json_decode($oldInput, true);
		
		// Get the Category's Custom Fields buffer
		$fields = CategoryField::getFields($categoryId, $postId, $languageCode);
		
		$success = ($errors->count() <= 0);
		
		// Get Result's Data
		$data = [
			'success' => $success,
			'result'  => $fields->toArray(),
			'extra'   => [
				'errors'   => $errors->toArray(),
				'oldInput' => $oldInput,
			],
		];
		
		return apiResponse()->json($data);
	}
}
