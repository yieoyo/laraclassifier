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
use App\Models\Country;
use App\Models\SubAdmin1;
use App\Models\SubAdmin2;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Models\City;
use App\Models\Scopes\ActiveScope;
use App\Http\Requests\Admin\CityRequest as StoreRequest;
use App\Http\Requests\Admin\CityRequest as UpdateRequest;

class CityController extends PanelController
{
	public $parentEntity = null;
	public $countryCode = null;
	public $admin1Code = null;
	public $admin2Code = null;
	
	public function setup()
	{
		// Parents Entities
		$parentEntities = ['countries', 'admins1', 'admins2'];
		
		// Get the parent Entity slug
		$this->parentEntity = request()->segment(2);
		if (!in_array($this->parentEntity, $parentEntities)) {
			abort(404);
		}
		
		// Country => City
		if ($this->parentEntity == 'countries') {
			// Get the Country Code
			$this->countryCode = request()->segment(3);
			
			// Get the Country's name
			$country = Country::findOrFail($this->countryCode);
		}
		
		// Admin1 => City
		if ($this->parentEntity == 'admins1') {
			// Get the Admin1 Codes
			$this->admin1Code = request()->segment(3);
			
			// Get the Admin1's name
			$admin1 = SubAdmin1::findOrFail($this->admin1Code);
			
			// Get the Country Code
			$this->countryCode = $admin1->country_code;
			
			// Get the Country's name
			$country = Country::findOrFail($this->countryCode);
		}
		
		// Admin2 => City
		if ($this->parentEntity == 'admins2') {
			// Get the Admin2 Codes
			$this->admin2Code = request()->segment(3);
			
			// Get the Admin2's name
			$admin2 = SubAdmin2::findOrFail($this->admin2Code);
			
			// Get the Admin1 Codes
			$this->admin1Code = $admin2->subadmin1_code;
			
			// Get the Admin1's name
			$admin1 = SubAdmin1::findOrFail($this->admin1Code);
			
			// Get the Country Code
			$this->countryCode = $admin1->country_code;
			
			// Get the Country's name
			$country = Country::findOrFail($this->countryCode);
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\City');
		$this->xPanel->with(['country', 'subAdmin1', 'subAdmin2']);
		$this->xPanel->enableParentEntity();
		$this->xPanel->allowAccess(['parent']);
		
		// Country => City
		if ($this->parentEntity == 'countries') {
			$this->xPanel->setRoute(admin_uri('countries/' . $this->countryCode . '/cities'));
			$this->xPanel->setEntityNameStrings(
				trans('admin.city') . ' &rarr; ' . '<strong>' . $country->name . '</strong>',
				trans('admin.cities') . ' &rarr; ' . '<strong>' . $country->name . '</strong>'
			);
			$this->xPanel->setParentKeyField('country_code');
			$this->xPanel->addClause('where', 'country_code', '=', $this->countryCode);
			$this->xPanel->setParentRoute(admin_uri('countries'));
			$this->xPanel->setParentEntityNameStrings(trans('admin.country'), trans('admin.countries'));
		}
		
		// Admin1 => City
		if ($this->parentEntity == 'admins1') {
			$this->xPanel->setRoute(admin_uri('admins1/' . $this->admin1Code . '/cities'));
			$this->xPanel->setEntityNameStrings(
				trans('admin.city') . ' &rarr; ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>',
				trans('admin.cities') . ' &rarr; ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>'
			);
			$this->xPanel->setParentKeyField('subadmin1_code');
			$this->xPanel->addClause('where', 'subadmin1_code', '=', $this->admin1Code);
			$this->xPanel->setParentRoute(admin_uri('countries/' . $this->countryCode . '/admins1'));
			$this->xPanel->setParentEntityNameStrings(
				trans('admin.admin division 1') . ' &rarr; ' . '<strong>' . $country->name . '</strong>',
				trans('admin.admin divisions 1') . ' &rarr; ' . '<strong>' . $country->name . '</strong>'
			);
		}
		
		// Admin2 => City
		if ($this->parentEntity == 'admins2') {
			$this->xPanel->setRoute(admin_uri('admins2/' . $this->admin2Code . '/cities'));
			$this->xPanel->setEntityNameStrings(
				trans('admin.city') . ' &rarr; ' . '<strong>' . $admin2->name . '</strong>' . ', ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>',
				trans('admin.cities') . ' &rarr; ' . ' <strong>' . $admin2->name . '</strong>' . ', ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>'
			);
			$this->xPanel->setParentKeyField('subadmin2_code');
			$this->xPanel->addClause('where', 'subadmin2_code', '=', $this->admin2Code);
			$this->xPanel->setParentRoute(admin_uri('admins1/' . $this->admin1Code . '/admins2'));
			$this->xPanel->setParentEntityNameStrings(
				trans('admin.admin division 2') . ' &rarr; ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>',
				trans('admin.admin divisions 2') . ' &rarr; ' . '<strong>' . $admin1->name . '</strong>' . ', ' . '<strong>' . $country->name . '</strong>'
			);
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		
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
			'name'  => 'country_code',
			'label' => trans('admin.Country Code'),
		]);
		$this->xPanel->addColumn([
			'name'  => 'name',
			'label' => trans('admin.Name'),
		]);
		$this->xPanel->addColumn([
			'name'          => 'subadmin1_code',
			'label'         => trans('admin.Admin1 Code'),
			'type'          => 'model_function',
			'function_name' => 'getAdmin1Html',
		]);
		$this->xPanel->addColumn([
			'name'          => 'subadmin2_code',
			'label'         => trans('admin.Admin2 Code'),
			'type'          => 'model_function',
			'function_name' => 'getAdmin2Html',
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Active'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
		]);
		
		// FIELDS
		$this->xPanel->addField([
			'name'    => 'id',
			'type'    => 'hidden',
			'default' => $this->autoIncrementId(),
		], 'create');
		
		// Country => City
		if (!empty($this->countryCode)) {
			$this->xPanel->addField([
				'name'  => 'country_code',
				'type'  => 'hidden',
				'value' => $this->countryCode,
			], 'create');
		} else {
			if (!empty($this->admin1Code)) {
				$this->xPanel->addField([
					'name'  => 'country_code',
					'type'  => 'hidden',
					'value' => $this->countryCode,
				], 'create');
			} else {
				if (!empty($this->admin2Code)) {
					$this->xPanel->addField([
						'name'  => 'country_code',
						'type'  => 'hidden',
						'value' => $this->countryCode,
					], 'create');
				} else {
					$this->xPanel->addField([
						'name'       => 'country_code',
						'label'      => trans('admin.Country Code'),
						'type'       => 'select2',
						'attribute'  => 'name',
						'model'      => 'App\Models\Country',
						'attributes' => [
							'placeholder' => trans('admin.Enter the country code'),
						],
					]);
				}
			}
		}
		
		// Admin1 => City
		if (!empty($this->admin1Code)) {
			$this->xPanel->addField([
				'name'  => 'subadmin1_code',
				'type'  => 'hidden',
				'value' => $this->admin1Code,
			], 'create');
		} else {
			if (!empty($this->admin2Code)) {
				$this->xPanel->addField([
					'name'  => 'subadmin1_code',
					'type'  => 'hidden',
					'value' => $this->admin1Code,
				], 'create');
			} else {
				$this->xPanel->addField([
					'name'        => 'subadmin1_code',
					'label'       => trans('admin.Admin1 Code'),
					'type'        => 'select2_from_array',
					'options'     => $this->subAdmin1s(),
					'allows_null' => true,
				]);
			}
		}
		
		// Admin2 => City
		if (!empty($this->admin2Code)) {
			$this->xPanel->addField([
				'name'  => 'subadmin2_code',
				'type'  => 'hidden',
				'value' => $this->admin2Code,
			], 'create');
		} else {
			if (!empty($this->admin1Code)) {
				$this->xPanel->addField([
					'name'        => 'subadmin2_code',
					'label'       => trans('admin.Admin2 Code'),
					'type'        => 'select2_from_array',
					'options'     => $this->subAdmin2s(),
					'allows_null' => true,
				]);
			}
		}
		
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
			'name'              => 'latitude',
			'label'             => trans('admin.Latitude'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Latitude'),
			],
			'hint'              => trans('admin.In decimal degrees'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'longitude',
			'label'             => trans('admin.Longitude'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Longitude'),
			],
			'hint'              => trans('admin.In decimal degrees'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'population',
			'label'             => trans('admin.Population'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Population'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'time_zone',
			'label'             => trans('admin.time_zone_label'),
			'type'              => 'select2_from_array',
			'options'           => Date::getTimeZones(),
			'allows_null'       => true,
			'hint'              => trans('admin.time_zone_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'  => 'active',
			'label' => trans('admin.Active'),
			'type'  => 'checkbox_switch',
		]);
	}
	
	public function store(StoreRequest $request)
	{
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request)
	{
		return parent::updateCrud();
	}
	
	/**
	 * Increment new cities IDs
	 * NOTE: Obsolete if the ID column is auto-incremented on the MySQL side
	 *
	 * @return int
	 */
	public function autoIncrementId(): int
	{
		// Note: 10793747 is the higher ID found in Geonames cities database
		// To guard against any MySQL error we will increment new IDs from 14999999
		$startId = 14999999;
		
		// Count all non-Geonames entries
		$lastAddedEntry = City::query()
			->withoutGlobalScope(ActiveScope::class)
			->where('id', '>=', $startId)
			->orderByDesc('id')
			->first();
		$lastAddedId = (!empty($lastAddedEntry)) ? (int)$lastAddedEntry->id : $startId;
		$lastAddedId = ($lastAddedId >= $startId) ? $lastAddedId : $startId;
		
		// Set new ID
		return $lastAddedId + 1;
	}
	
	private function subAdmin1s(): array
	{
		// Get the Administrative Divisions
		$admins = SubAdmin1::query()->inCountry( $this->countryCode)->get();
		
		$tab = [];
		if ($admins->count() > 0) {
			foreach ($admins as $admin) {
				$tab[$admin->code] = $admin->name . ' (' . $admin->code . ')';
			}
		}
		
		return $tab;
	}
	
	private function subAdmin2s(): array
	{
		// Get the Admin1 Code
		if (empty($this->admin1Code)) {
			$city = $this->xPanel->model->find(request()->segment(5));
			if (!empty($city)) {
				$this->admin1Code = $city->subadmin1_code;
			}
		}
		
		// Get the Administrative Divisions
		$admins = SubAdmin2::query()
			->inCountry($this->countryCode)
			->where('subadmin1_code', $this->admin1Code)
			->get();
		
		$tab = [];
		if ($admins->count() > 0) {
			foreach ($admins as $admin) {
				$tab[$admin->code] = $admin->name . ' (' . $admin->code . ')';
			}
		}
		
		return $tab;
	}
}
