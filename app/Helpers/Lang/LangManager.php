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

namespace App\Helpers\Lang;

use App\Helpers\Lang\Traits\LangFilesTrait;
use App\Helpers\Lang\Traits\LangLinesTrait;

class LangManager
{
	use LangFilesTrait, LangLinesTrait;
	
	/**
	 * The path to the language files.
	 *
	 * @var string
	 */
	protected string $path;
	
	/**
	 * The master language code
	 *
	 * @var string
	 */
	protected string $masterLangCode = 'en';
	
	/**
	 * Included languages files
	 *
	 * @var array
	 */
	protected array $includedLanguagesFiles = [
		'en', // English
		'fr', // French - Français
		'es', // Spanish - Español
		'ar', // Arabic - ‫العربية
		'pt', // Portuguese - Português
		'de', // German - Deutsch
		'it', // Italian - Italiano
		'tr', // Turkish - Türkçe
		'ru', // Russian - Русский
		'hi', // Hindi - हिन्दी
		'bn', // Bengali - বাংলা
		'zh', // Simplified Chinese - 简体中文
		'ja', // Japanese - 日本語
		'th', // Thai - ไทย
		'ro', // Romanian - Română
		'ka', // Georgian - ქართული
		'he', // Hebrew - עִברִית
	];
	
	/**
	 * LangManager constructor.
	 */
	public function __construct()
	{
		$this->path = base_path('lang/');
	}
	
	/**
	 * Get all codes of the included languages
	 *
	 * @return array
	 */
	public function getIncludedLanguages(): array
	{
		return $this->includedLanguagesFiles;
	}
	
	/**
	 * Get all the codes of included and existing languages
	 *
	 * @return array
	 */
	public function getTranslatedLanguages(): array
	{
		$languages = [];
		
		if (!empty($this->includedLanguagesFiles)) {
			foreach($this->includedLanguagesFiles as $code) {
				$path = $this->path . $code;
				if (file_exists($path) && is_dir($path)) {
					$languages[] = $code;
				}
			}
		}
		
		return $languages;
	}
}
