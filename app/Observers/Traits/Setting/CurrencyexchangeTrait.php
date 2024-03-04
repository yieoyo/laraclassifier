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

namespace App\Observers\Traits\Setting;

use App\Models\Currency;
use extras\plugins\currencyexchange\app\Helpers\CurrencyExchanger;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;

trait CurrencyexchangeTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return bool
	 */
	public function currencyexchangeUpdating($setting, $original)
	{
		// Validate the selected Currency Exchange driver's parameters
		$validateDriverParameters = $setting->value['validate_driver'] ?? false;
		if ($validateDriverParameters) {
			$this->updateCurrencyexchangeConfig($setting);
			
			try {
				$data = (new CurrencyExchanger())->getData();
				$currencyBase = data_get($data, 'base');
				$rates = data_get($data, 'rates');
				
				if (
					!is_string($currencyBase)
					|| strlen($currencyBase) != 3
					|| !is_array($rates)
					|| empty($rates)
				) {
					$message = data_get($data, 'error');
					if (isAdminPanel()) {
						Alert::error($message)->flash();
					} else {
						flash($message)->error();
					}
					
					return false;
				}
			} catch (\Throwable $e) {
				$message = $e->getMessage();
				if (isAdminPanel()) {
					Alert::error($message)->flash();
				} else {
					flash($message)->error();
				}
				
				return false;
			}
		}
		
		// If the Currency Exchange driver is changed, then clear existing rates
		if (is_array($setting->value) && array_key_exists('driver', $setting->value)) {
			if (
				is_array($original['value'])
				&& isset($original['value']['driver'])
				&& $setting->value['driver'] != $original['value']['driver']
			) {
				$driver = $setting->value['driver'];
				$defaultCurrencyBase = config('currencyexchange.drivers.' . $driver . '.currencyBase');
				$currencyBase = $setting->value[$driver . '_base'] ?? $defaultCurrencyBase;
				
				$origDriver = $original['value']['driver'];
				$origDefaultCurrencyBase = config('currencyexchange.drivers.' . $origDriver . '.currencyBase');
				$origCurrencyBase = $original['value'][$origDriver . '_base'] ?? $origDefaultCurrencyBase;
				
				if ($currencyBase != $origCurrencyBase) {
					$affected = DB::table((new Currency)->getTable())->update(['rate' => null]);
				}
			}
		}
	}
	
	/**
	 * Saved
	 *
	 * @param $setting
	 */
	public function currencyexchangeSaved($setting): void
	{
		try {
			cache()->forget('update.currencies.rates');
		} catch (\Exception $e) {
		}
	}
	
	/**
	 * @param $setting
	 */
	private function updateCurrencyexchangeConfig($setting): void
	{
		if (!isset($setting->value) || !is_array($setting->value)) {
			return;
		}
		
		// currencyexchange
		config()->set('currencyexchange.default', $setting->value['driver'] ?? null);
		
		// currencylayer
		if (config('currencyexchange.default') == 'currencylayer') {
			config()->set('currencyexchange.drivers.currencylayer.accessKey', $setting->value['currencylayer_access_key']);
			config()->set('currencyexchange.drivers.currencylayer.currencyBase', $setting->value['currencylayer_base']);
			config()->set('currencyexchange.drivers.currencylayer.pro', $setting->value['currencylayer_pro']);
		}
		
		// exchangerate_api
		if (config('currencyexchange.default') == 'exchangerate_api') {
			config()->set('currencyexchange.drivers.exchangerate_api.apiKey', $setting->value['exchangerate_api_api_key']);
			config()->set('currencyexchange.drivers.exchangerate_api.currencyBase', $setting->value['exchangerate_api_base']);
		}
		
		// exchangeratesapi_io
		if (config('currencyexchange.default') == 'exchangeratesapi_io') {
			config()->set('currencyexchange.drivers.exchangeratesapi_io.accessKey', $setting->value['exchangeratesapi_io_access_key']);
			config()->set('currencyexchange.drivers.exchangeratesapi_io.currencyBase', $setting->value['exchangeratesapi_io_base']);
			config()->set('currencyexchange.drivers.exchangeratesapi_io.pro', $setting->value['exchangeratesapi_io_pro']);
		}
		
		// openexchangerates
		if (config('currencyexchange.default') == 'openexchangerates') {
			config()->set('currencyexchange.drivers.openexchangerates.appId', $setting->value['openexchangerates_app_id']);
			config()->set('currencyexchange.drivers.openexchangerates.currencyBase', $setting->value['openexchangerates_base']);
		}
		
		// fixer_io
		if (config('currencyexchange.default') == 'fixer_io') {
			config()->set('currencyexchange.drivers.fixer_io.accessKey', $setting->value['fixer_io_access_key']);
			config()->set('currencyexchange.drivers.fixer_io.currencyBase', $setting->value['fixer_io_base']);
			config()->set('currencyexchange.drivers.fixer_io.pro', $setting->value['fixer_io_pro']);
		}
		
		// ecb
		if (config('currencyexchange.default') == 'ecb') {
			//...
		}
		
		// cbr
		if (config('currencyexchange.default') == 'cbr') {
			//...
		}
		
		// tcmb
		if (config('currencyexchange.default') == 'tcmb') {
			//...
		}
		
		// nbu
		if (config('currencyexchange.default') == 'nbu') {
			//...
		}
		
		// cnb
		if (config('currencyexchange.default') == 'cnb') {
			//...
		}
		
		// bnr
		if (config('currencyexchange.default') == 'bnr') {
			//...
		}
	}
}
