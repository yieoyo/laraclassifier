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

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Date;
use App\Helpers\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\CountryRequest as StoreRequest;
use App\Http\Requests\Admin\CountryRequest as UpdateRequest;
use App\Models\Currency;

class CountryController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Country');
		$this->xPanel->setRoute(admin_uri('countries'));
		$this->xPanel->setEntityNameStrings(trans('admin.country'), trans('admin.countries'));
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'cities', 'citiesButton', 'beginning');
		$this->xPanel->addButtonFromModelFunction('line', 'admin_divisions1', 'adminDivisions1Button', 'beginning');
		
		// Filters
		// -----------------------
		$this->xPanel->disableSearchBar();
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'name',
				'type'  => 'text',
				'label' => mb_ucfirst(trans('admin.Name')),
			],
			false,
			function ($value) {
				$countryCodePattern = '^[A-Z]{2}$';
				if (preg_match('|' . $countryCodePattern . '|', $value)) {
					$this->xPanel->addClause('where', 'code', '=', $value);
				} else {
					if (preg_match('|' . $countryCodePattern . '|i', $value)) {
						$this->xPanel->addClause('where', 'code', '=', strtoupper($value));
						$this->xPanel->addClause('orWhere', function ($query) use ($value) {
							$query->transWhere('name', 'LIKE', "%$value%");
						});
					} else {
						$this->xPanel->addClause('transWhere', 'name', 'LIKE', "%$value%");
					}
				}
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'country',
				'type'  => 'select2',
				'label' => mb_ucfirst(trans('admin.Name')) . ' (' . trans('admin.select') . ')',
			],
			getCountries(true),
			fn ($value) => $this->xPanel->addClause('where', 'code', '=', $value)
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'continent',
				'type'  => 'dropdown',
				'label' => trans('admin.Continent'),
			],
			[
				'AF' => 'Africa',
				'AN' => 'Antarctica',
				'AS' => 'Asia',
				'EU' => 'Europe',
				'NA' => 'North America',
				'OC' => 'Oceania',
				'SA' => 'South America',
			],
			fn ($value) => $this->xPanel->addClause('where', 'continent_code', '=', $value)
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'status',
				'type'  => 'dropdown',
				'label' => trans('admin.Status'),
			],
			[
				1 => trans('admin.Activated'),
				2 => trans('admin.Unactivated'),
			],
			function ($value) {
				if ($value == 1) {
					$this->xPanel->addClause('where', 'active', '=', 1);
				}
				if ($value == 2) {
					$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
				}
			}
		);
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS AND FIELDS
		|--------------------------------------------------------------------------
		*/
		// COLUMNS
		$this->xPanel->addColumn([
			'name'      => 'id',
			'label'     => '',
			'type'      => 'checkbox',
			'orderable' => false,
		]);
		$this->xPanel->addColumn([
			'name'  => 'code',
			'label' => trans('admin.Code'),
		]);
		$this->xPanel->addColumn([
			'name'          => 'name',
			'label'         => trans('admin.Name'),
			'type'          => 'model_function',
			'function_name' => 'getNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Active'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
		]);
		
		// FIELDS
		$this->xPanel->addField([
			'name'              => 'code',
			'label'             => trans('admin.Code'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Enter the country code'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		], 'create');
		$this->xPanel->addField([
			'name'              => 'name',
			'label'             => trans('admin.Name'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Enter the country name'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'capital',
			'label'             => trans('admin.Capital') . ' (' . trans('admin.Optional') . ')',
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Capital'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'continent_code',
			'label'             => trans('admin.Continent'),
			'type'              => 'select2',
			'attribute'         => 'name',
			'model'             => 'App\Models\Continent',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'tld',
			'label'             => trans('admin.TLD') . ' (' . trans('admin.Optional') . ')',
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Enter the country tld'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'phone',
			'label'             => trans('admin.Calling code'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Enter the country calling code'),
				'class'       => 'form-control m-phone',
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'currency_code',
			'label'             => trans('admin.Currency Code'),
			'type'              => 'select2_from_array',
			'options'           => $this->getCurrencyList(),
			'allows_null'       => true,
			'hint'              => trans('admin.Default country currency'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		// Check the Currency Exchange plugin data
		if (config('plugins.currencyexchange.installed')) {
			$this->xPanel->addField([
				'name'              => 'currencies',
				'label'             => trans("currencyexchange::messages.Currencies") . ' (' . trans('currencyexchange::messages.Optional') . ')',
				'type'              => 'text',
				'attributes'        => [
					'placeholder' => trans('currencyexchange::messages.eg_currencies_field'),
				],
				'hint'              => trans('currencyexchange::messages.currencies_codes_list_menu_per_country_hint', [
					'url' => admin_url('currencies'),
				]),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
		}
		$this->xPanel->addField([
			'name'   => 'background_image',
			'label'  => trans('admin.Background Image'),
			'type'   => 'image',
			'upload' => true,
			'disk'   => 'public',
			'hint'   => trans('admin.Choose a picture from your computer') . '<br>' . trans('admin.country_background_image_info'),
		]);
		$this->xPanel->addField([
			'name'              => 'languages',
			'label'             => trans('admin.country_spoken_languages_label'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.eg_languages_field'),
			],
			'hint'              => trans('admin.country_spoken_languages_hint', ['url' => admin_url('languages')]),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'time_zone',
			'label'             => t('preferred_time_zone_label'),
			'type'              => 'select2_from_array',
			'options'           => Date::getTimeZones(),
			'allows_null'       => true,
			'hint'              => t('preferred_time_zone_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		
		$dateFormatHint = (config('settings.app.php_specific_date_format')) ? 'php_date_format_hint' : 'iso_date_format_hint';
		$this->xPanel->addField([
			'name'              => 'date_format',
			'label'             => trans('admin.date_format_label'),
			'type'              => 'text',
			'hint'              => trans('admin.' . $dateFormatHint) . ' ' . trans('admin.country_date_format_hint_help'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'datetime_format',
			'label'             => trans('admin.datetime_format_label'),
			'type'              => 'text',
			'hint'              => trans('admin.' . $dateFormatHint) . ' ' . trans('admin.country_date_format_hint_help'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'  => 'admin_date_format_info',
			'type'  => 'custom_html',
			'value' => trans('admin.country_date_format_info'),
		]);
		
		$this->xPanel->addField([
			'name'              => 'admin_type', // enum
			'label'             => trans('admin.admin_division_type_label'),
			'type'              => 'select2_from_array',
			'options'           => enumCountryAdminTypes(),
			'default'           => 0,
			'hint'              => trans('admin.admin_division_type_hint', [
				'moreCities'     => t('more_cities'),
				'none'           => trans('admin.none'),
				'adminDivision1' => trans('admin.admin_division1'),
				'adminDivision2' => trans('admin.admin_division2'),
			]),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
	}
	
	public function store(StoreRequest $request)
	{
		$request = $this->uploadFile($request);
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request)
	{
		$request = $this->uploadFile($request);
		
		return parent::updateCrud($request);
	}
	
	private function uploadFile($request)
	{
		$params = [
			[
				'attribute' => 'background_image',
				'destPath'  => 'app/logo',
				'width'     => (int)config('larapen.core.picture.otherTypes.bgHeader.width', 2000),
				'height'    => (int)config('larapen.core.picture.otherTypes.bgHeader.height', 1000),
				'ratio'     => config('larapen.core.picture.otherTypes.bgHeader.ratio', '1'),
				'upsize'    => config('larapen.core.picture.otherTypes.bgHeader.upsize', '0'),
				'filename'  => 'header-',
				'quality'   => 100,
			],
		];
		
		foreach ($params as $param) {
			$file = $request->hasFile($param['attribute'])
				? $request->file($param['attribute'])
				: $request->input($param['attribute']);
			
			$request->request->set($param['attribute'], Upload::image($param['destPath'], $file, $param));
		}
		
		return $request;
	}
	
	private function getCurrencyList(): array
	{
		$currencies = Currency::query()->get();
		
		return collect($currencies)
			->mapWithKeys(function ($item) {
				return [$item->code => $item->code . ' - ' . $item->name];
			})->toArray();
	}
}
