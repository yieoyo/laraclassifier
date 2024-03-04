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

namespace App\Http\Controllers\Web\Public\Locale;

use App\Helpers\Arr;
use App\Helpers\Cookie;
use App\Http\Controllers\Web\Public\FrontController;
use App\Http\Controllers\Web\Public\Locale\Traits\TranslateUrlTrait;

class LocaleController extends FrontController
{
	use TranslateUrlTrait;
	
	/**
	 * @param string $langCode
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function setLocale(string $langCode)
	{
		// Check if the system supports the selected Language Code
		if (!isAvailableLang($langCode)) {
			$message = t('language_not_supported', ['code' => $langCode]);
			flash($message)->error();
			
			return redirect()->back()->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Check if Language Code can be saved into the session
		// and remove the Language Code session if its cannot be saved.
		$langCanBeSaved = $this->checkIfLangCanBeSavedInSession($langCode);
		
		// Save the Language Code
		$this->saveLang($langCode, $langCanBeSaved);
		
		// After the Language Operation is done, ...
		
		// If the next path (URI) is filled (using the '?from=' parameter,
		// Then, redirect to this path
		if (request()->filled('from')) {
			$path = ltrim(request()->query('from'), '/');
			if (!empty($path) && !str_contains($path, '#')) {
				$url = request()->root() . '/' . $path;
				
				return redirect()->to($url);
			}
		}
		
		// If the Country selection parameter is filled,
		// Redirect to the homepage with it (without the eventual 'from' parameter)
		// If not, redirect the user to the previous page
		if (request()->filled('country')) {
			$queryString = '';
			$queryArray = request()->except(['from']);
			if (!empty($queryArray)) {
				$queryString = '?' . Arr::query($queryArray);
			}
			
			$nextUrl = request()->root() . '/' . $queryString;
			
			if (config('settings.localization.auto_detect_language') == 'from_country') {
				$nextUrl = $this->removeCountrySelectionParameter($nextUrl);
			}
			
			return redirect()->to($nextUrl);
		} else {
			$previousUrl = url()->previous();
			if (config('settings.localization.auto_detect_language') == 'from_country') {
				$previousUrl = $this->removeCountrySelectionParameter($previousUrl);
			}
			$previousUrl = $this->translateUrl($previousUrl, $langCode);
			
			if (config('plugins.domainmapping.installed')) {
				$previousUrl = request()->root();
				
				$origParsedUrl = mb_parse_url(url()->previous());
				$parsedUrl = mb_parse_url(request()->root());
				
				if (isset($origParsedUrl['host'], $parsedUrl['host'])) {
					if ($origParsedUrl['host'] == $parsedUrl['host']) {
						$previousPath = !empty($origParsedUrl['path'])
							? $origParsedUrl['path']
							: '';
						$previousPath = ltrim($previousPath, '/');
						$previousUrl = $previousUrl . '/' . $previousPath;
						$previousUrl = $this->translateUrl($previousUrl, $langCode, request()->root());
					}
				}
			}
			
			$previousUrl = !empty($previousUrl) ? $previousUrl : '/';
			
			return redirect()->to($previousUrl);
		}
	}
	
	/**
	 * Check if Language Code can be saved into the session
	 * and remove the Language Code session if it's cannot be saved.
	 * I.e.:
	 *   - When, selected Language Code is equal to the website primary Language Code
	 *   - Or when, the 'Website Country Language' detection option is activated
	 *     and the selected Language Code is equal to the Country's Language Code
	 *
	 * @param string $langCode
	 * @return bool
	 */
	private function checkIfLangCanBeSavedInSession(string $langCode): bool
	{
		$langCanBeSaved = true;
		if ($langCode == config('appLang.code')) {
			if (config('settings.localization.auto_detect_language') == 'from_country') {
				if ($langCode == config('lang.code')) {
					$langCanBeSaved = false;
				}
			} else {
				$langCanBeSaved = false;
			}
		}
		
		return $langCanBeSaved;
	}
	
	/**
	 * @param string $langCode
	 * @param bool $langCanBeSaved
	 * @return void
	 */
	private function saveLang(string $langCode, bool $langCanBeSaved): void
	{
		if (config('larapen.core.storingUserSelectedLang') == 'cookie') {
			$this->saveLangInCookie($langCode, $langCanBeSaved);
		} else {
			$this->saveLangInSession($langCode, $langCanBeSaved);
		}
	}
	
	/**
	 * Save the Language Code in Cookie
	 *
	 * @param string $langCode
	 * @param bool $langCanBeSaved
	 * @return void
	 */
	private function saveLangInCookie(string $langCode, bool $langCanBeSaved): void
	{
		if ($langCanBeSaved) {
			Cookie::set('langCode', $langCode);
		} else {
			Cookie::forget('langCode');
		}
	}
	
	/**
	 * Save the Language Code in Session
	 *
	 * @param string $langCode
	 * @param bool $langCanBeSaved
	 * @return void
	 */
	private function saveLangInSession(string $langCode, bool $langCanBeSaved): void
	{
		if ($langCanBeSaved) {
			// Save the Language Code in Session
			session()->put('langCode', $langCode);
		} else {
			// Remove the Language Code from Session
			if (session()->has('langCode')) {
				session()->forget('langCode');
			}
		}
	}
	
	/**
	 * Remove the Country selection parameter from the URL
	 * (Helpful when the 'Website Country Language' detection option is activated)
	 * (config('settings.localization.auto_detect_language') == 'from_country')
	 *
	 * @param string|null $url
	 * @return string
	 */
	private function removeCountrySelectionParameter(string $url = null): string
	{
		$parsedUrl = mb_parse_url($url);
		
		if (isset($parsedUrl['query'])) {
			parse_str($parsedUrl['query'], $queryArray);
			if (array_key_exists('country', $queryArray)) {
				$url = preg_replace('|\?.*|ui', '', $url);
				unset($queryArray['country']);
				if (!empty($queryArray)) {
					$queryString = '?' . Arr::query($queryArray);
					$url = $url . $queryString;
				}
			}
		}
		
		return getAsString($url);
	}
}
