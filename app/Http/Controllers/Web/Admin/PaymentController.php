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

use App\Models\Package;
use App\Models\PaymentMethod;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\Request as StoreRequest;
use App\Http\Requests\Admin\Request as UpdateRequest;
use App\Models\Post;
use App\Models\User;

class PaymentController extends PanelController
{
	protected ?string $type = '';
	protected bool $isPromoPackage = false;
	protected bool $isSubsPackage = false;
	
	public function setup()
	{
		$this->type = request()->segment(3);
		$this->isPromoPackage = ($this->type == 'promotion');
		$this->isSubsPackage = ($this->type == 'subscription');
		
		$withRelation = [
			'payable',
			'payable.country',
			'package',
			'paymentMethod',
		];
		if ($this->isPromoPackage) {
			$withRelation = array_merge($withRelation, ['payable.user']);
		}
		
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Payment');
		$this->xPanel->addClause('where', fn ($query) => $query->{$this->type}());
		$this->xPanel->with($withRelation);
		$this->xPanel->setRoute(admin_uri('payments/' . $this->type));
		$this->xPanel->setEntityNameStrings(trans('admin.payment'), trans('admin.payments'));
		$this->xPanel->denyAccess(['create', 'update', 'delete']);
		$this->xPanel->removeAllButtons(); // Remove also: 'create' & 'reorder' buttons
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('created_at');
		}
		
		// Filters
		// -----------------------
		$this->xPanel->disableSearchBar();
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'id',
				'type'  => 'text',
				'label' => 'ID',
			],
			false,
			function ($value) {
				$this->xPanel->addClause('where', 'id', '=', $value);
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'from_to',
				'type'  => 'date_range',
				'label' => trans('admin.Date range'),
			],
			false,
			function ($value) {
				$dates = json_decode($value);
				$this->xPanel->addClause('where', 'created_at', '>=', $dates->from);
				$this->xPanel->addClause('where', 'created_at', '<=', $dates->to);
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'        => 'country',
				'type'        => 'select2',
				'label'       => mb_ucfirst(trans('admin.country')),
				'placeholder' => trans('admin.select'),
			],
			getCountries(),
			function ($value) {
				$payableClass = ($this->isPromoPackage) ? Post::class : User::class;
				
				$this->xPanel->addClause('whereHasMorph', 'payable', $payableClass, function ($query) use ($value) {
					$query->where('country_code', '=', $value);
				});
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'payable_id',
				'type'  => 'text',
				'label' => $this->isPromoPackage ? trans('admin.Listing') : mb_ucfirst(trans('admin.user')),
			],
			false,
			function ($value) {
				if (is_numeric($value) || isHashedId($value)) {
					$value = hashId($value, true) ?? $value;
					$this->xPanel->addClause('where', 'payable_id', '=', $value);
				} else {
					$payableClass = ($this->isPromoPackage) ? Post::class : User::class;
					$column = ($this->isPromoPackage) ? 'title' : 'name';
					
					$this->xPanel->addClause('whereHasMorph', 'payable', $payableClass, function ($query) use ($column, $value) {
						$query->where($column, 'like', $value . '%');
					});
				}
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'package',
				'type'  => 'dropdown',
				'label' => trans('admin.Package'),
			],
			$this->getPackages(),
			function ($value) {
				$this->xPanel->addClause('where', 'package_id', '=', $value);
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'payment_method',
				'type'  => 'dropdown',
				'label' => trans('admin.Payment Method'),
			],
			$this->getPaymentMethods(),
			function ($value) {
				$this->xPanel->addClause('where', 'payment_method_id', '=', $value);
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
				'pending' => t('pending'),
				'onHold'  => t('onHold'),
				'valid'   => t('valid'),
				'expired' => t('expired'),
				// 'canceled' => t('canceled'),
				// 'refunded' => t('refunded'),
			],
			function ($value) {
				if ($value == 'pending') {
					$this->xPanel->addClause('where', function ($query) {
						$query->where(function ($query) {
							$query->valid()->orWhere(fn ($query) => $query->onHold());
						})->columnIsEmpty('active');
					});
				}
				if ($value == 'onHold') {
					$this->xPanel->addClause('where', function ($query) {
						$query->onHold()->active();
					});
				}
				if ($value == 'valid') {
					$this->xPanel->addClause('where', function ($query) {
						$query->valid()->active();
					});
				}
				if ($value == 'expired') {
					$this->xPanel->addClause('where', function ($query) {
						$query->where(function ($query) {
							$query->notValid()->where(fn ($query) => $query->columnIsEmpty('active'));
						})->orWhere(fn ($query) => $query->notValid());
					});
				}
				if ($value == 'canceled') {
					$this->xPanel->addClause('where', fn ($query) => $query->canceled());
				}
				if ($value == 'refunded') {
					$this->xPanel->addClause('where', fn ($query) => $query->refunded());
				}
			}
		);
		// -----------------------
		if (config('plugins.offlinepayment.installed')) {
			$this->xPanel->addFilter(
				[
					'name'  => 'payment_type',
					'type'  => 'dropdown',
					'label' => trans('admin.type'),
				],
				[
					'real' => trans('admin.real_payment'),
					'fake' => trans('admin.fake_payment'),
				],
				function ($value) {
					if ($value == 'real') {
						$this->xPanel->addClause('where', fn ($query) => $query->notManuallyCreated());
					}
					if ($value == 'fake') {
						$this->xPanel->addClause('where', fn ($query) => $query->manuallyCreated());
					}
				}
			);
		}
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS AND FIELDS
		|--------------------------------------------------------------------------
		*/
		// COLUMNS
		$this->xPanel->addColumn([
			'name'  => 'id',
			'label' => "ID",
		]);
		$this->xPanel->addColumn([
			'name'  => 'created_at',
			'label' => trans('admin.Date'),
		]);
		$this->xPanel->addColumn([
			'name'          => 'payable_id',
			'label'         => $this->isPromoPackage ? trans('admin.Listing') : mb_ucfirst(trans('admin.user')),
			'type'          => 'model_function',
			'function_name' => 'getPayableTitleHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'package_id',
			'label'         => trans('admin.Package'),
			'type'          => 'model_function',
			'function_name' => 'getPackageNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'payment_method_id',
			'label'         => trans('admin.Payment Method'),
			'type'          => 'model_function',
			'function_name' => 'getPaymentMethodNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'amount',
			'label'         => trans('admin.amount'),
			'type'          => 'model_function',
			'function_name' => 'getAmountHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Approved'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
		]);
		
		// FIELDS
	}
	
	public function store(StoreRequest $request)
	{
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request)
	{
		return parent::updateCrud();
	}
	
	public function getPackages(): array
	{
		$entries = Package::query()
			->{$this->type}()
			->where('price', '>', 0)
			->orderBy('currency_code')
			->orderBy('lft')
			->get();
		
		$array = [];
		if ($entries->count() > 0) {
			foreach ($entries as $entry) {
				$array[$entry->id] = $entry->name . ' (' . $entry->price . ' ' . $entry->currency_code . ')';
			}
		}
		
		return $array;
	}
	
	public function getPaymentMethods(): array
	{
		$entries = PaymentMethod::query()->orderBy('lft')->get();
		
		$array = [];
		if ($entries->count() > 0) {
			foreach ($entries as $entry) {
				$array[$entry->id] = $entry->display_name;
			}
		}
		
		return $array;
	}
}
