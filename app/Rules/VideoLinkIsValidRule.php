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

namespace App\Rules;

use App\Helpers\VideoEmbedding;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VideoLinkIsValidRule implements ValidationRule
{
	public ?string $attrLabel = '';
	
	public function __construct($attrLabel = '')
	{
		$this->attrLabel = $attrLabel;
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail($this->message());
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = is_stringable($value) ? $value : '';
		$value = trim($value);
		
		// Get the video standard link
		try {
			$value = VideoEmbedding::getVideoUrl($value);
		} catch (\Throwable $e) {
			abort(500, $e->getMessage());
		}
		
		return !empty($value);
	}
	
	/**
	 * Get the validation error message.
	 *
	 * @return string
	 */
	private function message(): string
	{
		if (!empty($this->attrLabel)) {
			return trans('validation.video_link_is_valid_rule', ['attribute' => mb_strtolower($this->attrLabel)]);
		} else {
			if (!empty($this->attr) && !empty(trans('validation.attributes.' . $this->attr))) {
				return trans('validation.video_link_is_valid_rule', ['attribute' => trans('validation.attributes.' . $this->attr)]);
			} else {
				return trans('validation.video_link_is_valid_rule');
			}
		}
	}
}
