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

use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\PackageRequest as StoreRequest;
use App\Http\Requests\Admin\PackageRequest as UpdateRequest;
use App\Models\Package;

class PackageController extends PanelController
{
	protected bool $isPromoPackage = false;
	protected bool $isSubsPackage = false;
	
	public function setup()
	{
		$type = request()->segment(3);
		$this->isPromoPackage = ($type == 'promotion');
		$this->isSubsPackage = ($type == 'subscription');
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Package');
		$this->xPanel->addClause('where', fn ($query) => $query->$type());
		$this->xPanel->setRoute(admin_uri('packages/' . $type));
		if ($this->isPromoPackage) {
			$this->xPanel->setEntityNameStrings(trans('admin.promotion_package'), trans('admin.promotion_packages'));
		}
		if ($this->isSubsPackage) {
			$this->xPanel->setEntityNameStrings(trans('admin.subscription_package'), trans('admin.subscription_packages'));
		}
		$this->xPanel->enableReorder('name', 1);
		$this->xPanel->allowAccess(['reorder']);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		
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
				$this->xPanel->addClause('where', function ($query) use ($value) {
					$query->where('name', 'LIKE', "%$value%")
						->orWhere('short_name', 'LIKE', "%$value%");
				});
			}
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
			'name'          => 'name',
			'label'         => trans('admin.Name'),
			'type'          => 'model_function',
			'function_name' => 'getNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'  => 'price',
			'label' => trans('admin.Price'),
		]);
		$this->xPanel->addColumn([
			'name'  => 'currency_code',
			'label' => trans('admin.Currency'),
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Active'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
			'on_display'    => 'checkbox',
		]);
		
		// FIELDS
		$this->xPanel->addField([
			'name'    => 'type',
			'type'    => 'hidden',
			'default' => $type,
		]);
		$this->xPanel->addField([
			'name'              => 'name',
			'label'             => trans('admin.Name'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Name'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'short_name',
			'label'             => trans('admin.short_name_label'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.short_name_label'),
			],
			'hint'              => trans('admin.short_name_hint_detailed'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		if ($this->isPromoPackage) {
			$this->xPanel->addField([
				'name'              => 'ribbon',
				'label'             => trans('admin.Ribbon'),
				'type'              => 'enum',
				'hint'              => trans('admin.Show listings with ribbon when viewing listings in search results list'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
			$this->xPanel->addField([
				'name'              => 'has_badge',
				'label'             => trans('admin.Show listings with a badge'),
				'type'              => 'checkbox_switch',
				'hint'              => '<br><br>',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
		}
		$this->xPanel->addField([
			'name'              => 'price',
			'label'             => trans('admin.Price'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => trans('admin.Price'),
			],
			'hint'              => trans('admin.package_price_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'label'             => trans('admin.Currency'),
			'name'              => 'currency_code',
			'model'             => 'App\Models\Currency',
			'entity'            => 'currency',
			'attribute'         => 'code',
			'type'              => 'select2',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		if ($this->isPromoPackage) {
			$this->xPanel->addField([
				'name'              => 'promotion_time',
				'label'             => trans('admin.promotion_time'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => trans('admin.promotion_time_in_days'),
					'min'         => 0,
					'step'        => 1,
				],
				'hint'              => trans('admin.promotion_time_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
		}
		if ($this->isSubsPackage) {
			$this->xPanel->addField([
				'name'              => 'interval',
				'label'             => trans('admin.interval_label'),
				'type'              => 'select2_from_array',
				'options'           => $this->getIntervalOptions(),
				'allows_null'       => true,
				'default'           => 'month',
				'hint'              => trans('admin.interval_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
			$this->xPanel->addField([
				'name'              => 'listings_limit',
				'label'             => trans('admin.subs_listings_limit_label'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => trans('admin.subs_listings_limit_label'),
					'min'         => 0,
					'step'        => 1,
				],
				'default'           => config('settings.single.listings_limit', 5),
				'hint'              => trans('admin.subs_listings_limit_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			]);
		}
		$this->xPanel->addField([
			'name'              => 'pictures_limit',
			'label'             => trans('admin.pictures_limit_label'),
			'type'              => 'number',
			'attributes'        => [
				'placeholder' => trans('admin.pictures_limit_label'),
				'min'         => 0,
				'step'        => 1,
			],
			'default'           => config('settings.single.pictures_limit', 5),
			'hint'              => ($this->isSubsPackage)
				? trans('admin.subs_pictures_limit_hint')
				: trans('admin.package_pictures_limit_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'expiration_time',
			'label'             => trans('admin.expiration_time_label'),
			'type'              => 'number',
			'attributes'        => [
				'placeholder' => trans('admin.expiration_time_in_days'),
				'min'         => 0,
				'step'        => 1,
			],
			'default'           => config('settings.cron.activated_listings_expiration', 30),
			'hint'              => trans('admin.expiration_time_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		if ($this->isPromoPackage) {
			$this->xPanel->addField([
				'name'  => 'separator_1',
				'type'  => 'custom_html',
				'value' => '<div style="clear: both;"></div>',
			]);
			$this->xPanel->addField([
				'name'              => 'facebook_ads_duration',
				'label'             => trans('admin.facebook_ads_duration'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'              => trans('admin.external_sponsored_listings_hint', ['provider' => 'Facebook']),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			]);
			$this->xPanel->addField([
				'name'              => 'google_ads_duration',
				'label'             => trans('admin.google_ads_duration'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'              => trans('admin.external_sponsored_listings_hint', ['provider' => 'Google']),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			]);
			$this->xPanel->addField([
				'name'              => 'twitter_ads_duration',
				'label'             => trans('admin.twitter_ads_duration'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'              => trans('admin.external_sponsored_listings_hint', ['provider' => 'Twitter']),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			]);
			$this->xPanel->addField([
				'name'              => 'linkedin_ads_duration',
				'label'             => trans('admin.linkedin_ads_duration'),
				'type'              => 'number',
				'attributes'        => [
					'min'  => 0,
					'step' => 1,
				],
				'hint'              => trans('admin.external_sponsored_listings_hint', ['provider' => 'LinkedIn']),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			]);
		}
		$this->xPanel->addField([
			'name'  => 'separator_2',
			'type'  => 'custom_html',
			'value' => '<div style="clear: both;"></div>',
		]);
		$this->xPanel->addField([
			'name'       => 'description',
			'label'      => trans('admin.Description'),
			'type'       => 'textarea',
			'attributes' => [
				'placeholder' => trans('admin.Description'),
				'rows'        => 6,
			],
			'hint'       => trans('admin.package_description_hint'),
		]);
		$this->xPanel->addField([
			'name'              => 'lft',
			'label'             => trans('admin.Position'),
			'type'              => 'number',
			'attributes'        => [
				'min'  => 0,
				'step' => 1,
			],
			'hint'              => trans('admin.Quick Reorder') . ': '
				. trans('admin.Enter a position number') . ' '
				. trans('admin.position_number_note'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'  => 'separator_3',
			'type'  => 'custom_html',
			'value' => '<div style="clear: both;"></div>',
		]);
		$this->xPanel->addField([
			'name'              => 'recommended',
			'label'             => trans('admin.recommended'),
			'type'              => 'checkbox_switch',
			'hint'              => trans('admin.recommended_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'active',
			'label'             => trans('admin.Active'),
			'type'              => 'checkbox_switch',
			'default'           => '1',
			'hint'              => '<br><br>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		], 'create');
		$this->xPanel->addField([
			'name'              => 'active',
			'label'             => trans('admin.Active'),
			'type'              => 'checkbox_switch',
			'hint'              => '<br><br>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		], 'update');
	}
	
	public function store(StoreRequest $request)
	{
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request)
	{
		return parent::updateCrud();
	}
	
	private function getIntervalOptions(): array
	{
		return Package::getEnumValuesAsAssocArray('interval');
	}
}
