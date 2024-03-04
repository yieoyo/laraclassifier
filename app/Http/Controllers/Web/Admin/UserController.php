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
use App\Http\Controllers\Web\Public\Auth\Traits\VerificationTrait;
use App\Http\Requests\Admin\Request;
use App\Http\Requests\Admin\UserRequest as StoreRequest;
use App\Http\Requests\Admin\UserRequest as UpdateRequest;
use App\Models\Gender;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Web\Admin\Panel\PanelController;

class UserController extends PanelController
{
	use VerificationTrait;
	
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\User');
		$this->xPanel->with([
			'permissions',
			'roles',
			'country:code,name',
			// IMPORTANT:
			// Payable can have multiple on-hold, pending, expired, canceled or refunded payments
			// Payable can have only one valid payment, so we have to add that as filter for the Eager Loading.
			// This allows displaying the right payment status (So only when payment is valid).
			'payment' => fn ($query) => $query->valid(),
			'payment.package:id,type,name,short_name',
		]);
		
		// If the logged admin user has permissions to manage users and has not 'super-admin' role,
		// don't allow him to manage 'super-admin' role's users.
		if (!auth()->user()->can(Permission::getSuperAdminPermissions())) {
			// Get 'super-admin' role's users IDs
			$usersIds = [];
			try {
				$users = User::query()
					->withoutGlobalScopes([VerifiedScope::class])
					->role('super-admin')
					->get(['id', 'created_at']);
				if ($users->count() > 0) {
					$usersIds = $users->keyBy('id')->keys()->toArray();
				}
			} catch (\Throwable $e) {
			}
			
			// Exclude 'super-admin' role's users from list
			if (!empty($usersIds)) {
				$this->xPanel->addClause('whereNotIn', 'id', $usersIds);
			}
		}
		
		$this->xPanel->setRoute(admin_uri('users'));
		$this->xPanel->setEntityNameStrings(trans('admin.user'), trans('admin.users'));
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('created_at');
		}
		$this->xPanel->enableDetailsRow();
		$this->xPanel->allowAccess(['details_row']);
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'impersonate', 'impersonateButton', 'beginning');
		$this->xPanel->removeButton('delete');
		$this->xPanel->addButtonFromModelFunction('line', 'delete', 'deleteButton', 'end');
		
		// Filters
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
				'name'  => 'name',
				'type'  => 'text',
				'label' => trans('admin.Name'),
			],
			false,
			function ($value) {
				$this->xPanel->addClause('where', function ($query) use ($value) {
					$query->where('name', 'LIKE', "%$value%");
				});
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'username',
				'type'  => 'text',
				'label' => t('Username'),
			],
			false,
			function ($value) {
				$this->xPanel->addClause('where', function ($query) use ($value) {
					$query->where('username', 'LIKE', "%$value%");
				});
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'country',
				'type'  => 'select2',
				'label' => mb_ucfirst(trans('admin.country')),
			],
			getCountries(),
			function ($value) {
				$this->xPanel->addClause('where', 'country_code', '=', $value);
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'has_subscription',
				'type'  => 'dropdown',
				'label' => trans('admin.has_subscription'),
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
					$this->xPanel->addClause('whereHas', 'payment', function ($query) {
						$query->where(function ($query) {
							$query->valid()->orWhere(fn ($query) => $query->onHold());
						})->columnIsEmpty('active');
					});
				}
				if ($value == 'onHold') {
					$this->xPanel->addClause('whereHas', 'payment', function ($query) {
						$query->onHold()->active();
					});
				}
				if ($value == 'valid') {
					$this->xPanel->addClause('whereHas', 'payment', function ($query) {
						$query->valid()->active();
					});
				}
				if ($value == 'expired') {
					$this->xPanel->addClause('whereHas', 'payment', function ($query) {
						$query->where(function ($query) {
							$query->notValid()->where(fn ($query) => $query->columnIsEmpty('active'));
						})->orWhere(fn ($query) => $query->notValid());
					});
				}
				if ($value == 'canceled') {
					$this->xPanel->addClause('whereHas', 'payment', fn ($query) => $query->canceled());
				}
				if ($value == 'refunded') {
					$this->xPanel->addClause('whereHas', 'payment', fn ($query) => $query->refunded());
				}
			}
		);
		// -----------------------
		if (config('plugins.offlinepayment.installed')) {
			$this->xPanel->addFilter(
				[
					'name'  => 'has_valid_subscription',
					'type'  => 'dropdown',
					'label' => trans('admin.has_valid_subscription'),
				],
				[
					'real' => trans('admin.with_real_payment'),
					'fake' => trans('admin.with_fake_payment'),
				],
				function ($value) {
					if ($value == 'real') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->valid()->active()->notManuallyCreated();
						});
					}
					if ($value == 'fake') {
						$this->xPanel->addClause('whereHas', 'payment', function ($query) {
							$query->valid()->active()->manuallyCreated();
						});
					}
				}
			);
		}
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'status',
				'type'  => 'dropdown',
				'label' => trans('admin.Status'),
			],
			[
				1 => trans('admin.Unactivated'),
				2 => trans('admin.Activated'),
			],
			function ($value) {
				if ($value == 1) {
					$this->xPanel->addClause('where', fn ($query) => $query->unverified());
				}
				if ($value == 2) {
					$this->xPanel->addClause('where', fn ($query) => $query->verified());
				}
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'type',
				'type'  => 'dropdown',
				'label' => trans('admin.permissions_roles'),
			],
			[
				1 => trans('admin.Has Admins Permissions'),
				2 => trans('admin.Has Super-Admins Permissions'),
				3 => trans('admin.Has Super-Admins Role'),
			],
			function ($value) {
				if ($value == 1) {
					$this->xPanel->addClause('permission', Permission::getStaffPermissions());
				}
				if ($value == 2) {
					$this->xPanel->addClause('permission', Permission::getSuperAdminPermissions());
				}
				if ($value == 3) {
					$this->xPanel->addClause('role', Role::getSuperAdminRole());
				}
			}
		);
		
		$isPhoneVerificationEnabled = (config('settings.sms.phone_verification') == 1);
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS AND FIELDS
		|--------------------------------------------------------------------------
		*/
		if (request()->segment(2) == 'account') {
			return;
		}
		
		// COLUMNS
		$this->xPanel->addColumn([
			'name'      => 'id',
			'label'     => '',
			'type'      => 'checkbox',
			'orderable' => false,
		]);
		$this->xPanel->addColumn([
			'name'  => 'created_at',
			'label' => trans('admin.Date'),
			'type'  => 'datetime',
		]);
		$this->xPanel->addColumn([
			'name'          => 'name',
			'label'         => trans('admin.Name'),
			'type'          => 'model_function',
			'function_name' => 'getNameHtml',
		]);
		$emailParams = [
			'name'  => 'email',
			'label' => trans('admin.Email'),
		];
		if ($isPhoneVerificationEnabled) {
			$emailParams['type'] = 'model_function';
			$emailParams['function_name'] = 'getEmailHtml';
		}
		$this->xPanel->addColumn($emailParams);
		if ($isPhoneVerificationEnabled) {
			$this->xPanel->addColumn([
				'name'          => 'phone',
				'label'         => mb_ucfirst(t('phone')),
				'type'          => 'model_function',
				'function_name' => 'getPhoneHtml',
			]);
		}
		$this->xPanel->addColumn([
			'label'         => mb_ucfirst(trans('admin.country')),
			'name'          => 'country_code',
			'type'          => 'model_function',
			'function_name' => 'getCountryHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'email_verified_at',
			'label'         => trans('admin.Verified Email'),
			'type'          => 'model_function',
			'function_name' => 'getVerifiedEmailHtml',
		]);
		if ($isPhoneVerificationEnabled) {
			$this->xPanel->addColumn([
				'name'          => 'phone_verified_at',
				'label'         => trans('admin.Verified Phone'),
				'type'          => 'model_function',
				'function_name' => 'getVerifiedPhoneHtml',
			]);
		}
		
		$entity = $this->xPanel->getModel()->find(request()->segment(3));
		
		// FIELDS
		$this->xPanel->addField([
			'label'             => trans('admin.Gender'),
			'name'              => 'gender_id',
			'type'              => 'select2_from_array',
			'options'           => $this->gender(),
			'allows_null'       => false,
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
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
			'name'              => 'email',
			'label'             => trans('admin.Email'),
			'type'              => 'email',
			'attributes'        => [
				'placeholder' => trans('admin.Email'),
			],
			'prefix'            => '<i class="far fa-envelope"></i>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'username',
			'label'             => t('Username'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => t('Username'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$phoneCountry = (!empty($entity) && isset($entity->phone_country)) ? strtolower($entity->phone_country) : 'us';
		$this->xPanel->addField([
			'name'              => 'phone',
			'label'             => trans('admin.Phone'),
			'type'              => 'intl_tel_input',
			'phone_country'     => $phoneCountry,
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'phone_hidden',
			'label'             => trans('admin.Phone hidden'),
			'type'              => 'checkbox_switch',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'password',
			'label'             => trans('admin.Password'),
			'type'              => 'password',
			'attributes'        => [
				'placeholder'  => trans('admin.Password'),
				'autocomplete' => 'new-password',
			],
			'prefix'            => '<i class="fas fa-lock"></i>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		], 'create');
		$this->xPanel->addField([
			'label'             => mb_ucfirst(trans('admin.country')),
			'name'              => 'country_code',
			'model'             => 'App\Models\Country',
			'entity'            => 'country',
			'attribute'         => 'name',
			'type'              => 'select2',
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
			'hint'              => t('preferred_time_zone_info_lite'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'auth_field',
			'label'             => t('auth_field_label'),
			'type'              => 'select2_from_array',
			'options'           => getAuthFields(),
			'allows_null'       => true,
			'default'           => getAuthField($entity),
			'hint'              => t('auth_field_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'  => 'sep_creation_01',
			'type'  => 'custom_html',
			'value' => '<div style="clear: both;"></div>',
		]);
		$this->xPanel->addField([
			'name'              => 'email_verified_at',
			'label'             => trans('admin.Verified Email'),
			'type'              => 'checkbox_switch',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'phone_verified_at',
			'label'             => trans('admin.Verified Phone'),
			'type'              => 'checkbox_switch',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'blocked',
			'label'             => trans('admin.Blocked'),
			'type'              => 'checkbox_switch',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		if (!empty($entity)) {
			$this->xPanel->addField([
				'name'  => 'empty_line_before_ip',
				'type'  => 'custom_html',
				'value' => '<div style="clear: both;"></div>',
			], 'update');
			
			$emptyIp = 'N/A';
			
			$label = '<span class="fw-bold">' . trans('admin.create_from_ip') . ':</span>';
			if (!empty($entity->create_from_ip)) {
				$ipUrl = config('larapen.core.ipLinkBase') . $entity->create_from_ip;
				$ipLink = '<a href="' . $ipUrl . '" target="_blank">' . $entity->create_from_ip . '</a>';
			} else {
				$ipLink = $emptyIp;
			}
			$this->xPanel->addField([
				'name'              => 'create_from_ip',
				'type'              => 'custom_html',
				'value'             => '<h5>' . $label . ' ' . $ipLink . '</h5>',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			], 'update');
			
			$label = '<span class="fw-bold">' . trans('admin.latest_update_ip') . ':</span>';
			if (!empty($entity->latest_update_ip)) {
				$ipUrl = config('larapen.core.ipLinkBase') . $entity->latest_update_ip;
				$ipLink = '<a href="' . $ipUrl . '" target="_blank">' . $entity->latest_update_ip . '</a>';
			} else {
				$ipLink = $emptyIp;
			}
			$this->xPanel->addField([
				'name'              => 'latest_update_ip',
				'type'              => 'custom_html',
				'value'             => '<h5>' . $label . ' ' . $ipLink . '</h5>',
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			], 'update');
			
			$this->xPanel->addField([
				'name'  => 'empty_line_after_ip',
				'type'  => 'custom_html',
				'value' => '<div style="clear: both;"></div>',
			], 'update');
			
			if (!empty($entity->email) || !empty($entity->phone)) {
				$btnUrl = admin_url('blacklists/add') . '?';
				$btnQs = (!empty($entity->email)) ? 'email=' . $entity->email : '';
				$btnQs = (!empty($btnQs)) ? $btnQs . '&' : $btnQs;
				$btnQs = (!empty($entity->phone)) ? $btnQs . 'phone=' . $entity->phone : $btnQs;
				$btnUrl = $btnUrl . $btnQs;
				
				$btnText = trans('admin.ban_the_user');
				$btnHint = $btnText;
				if (!empty($entity->email) && !empty($entity->phone)) {
					$btnHint = trans('admin.ban_the_user_email_and_phone', ['email' => $entity->email, 'phone' => $entity->phone]);
				} else {
					if (!empty($entity->email)) {
						$btnHint = trans('admin.ban_the_user_email', ['email' => $entity->email]);
					}
					if (!empty($entity->phone)) {
						$btnHint = trans('admin.ban_the_user_phone', ['phone' => $entity->phone]);
					}
				}
				$tooltip = ' data-bs-toggle="tooltip" title="' . $btnHint . '"';
				
				$btnLink = '<a href="' . $btnUrl . '" class="btn btn-danger confirm-simple-action"' . $tooltip . '>' . $btnText . '</a>';
				$this->xPanel->addField([
					'name'              => 'ban_button',
					'type'              => 'custom_html',
					'value'             => $btnLink,
					'wrapperAttributes' => [
						'style' => 'text-align:center;',
					],
				], 'update');
			}
		}
		// Only 'super-admin' can assign 'roles' or 'permissions' to users
		// Also logged admin user cannot manage his own 'role' or 'permissions'
		if (
			auth()->user()->can(Permission::getSuperAdminPermissions())
			&& auth()->user()->id != request()->segment(3)
		) {
			$this->xPanel->addField([
				'name'  => 'separator',
				'type'  => 'custom_html',
				'value' => '<hr>',
			]);
			$this->xPanel->addField([
				// two interconnected entities
				'label'             => trans('admin.user_role_permission'),
				'field_unique_name' => 'user_role_permission',
				'type'              => 'checklist_dependency',
				'name'              => 'roles_and_permissions', // the methods that defines the relationship in your Model
				'subfields'         => [
					'primary'   => [
						'label'            => trans('admin.roles'),
						'name'             => 'roles', // the method that defines the relationship in your Model
						'entity'           => 'roles', // the method that defines the relationship in your Model
						'entity_secondary' => 'permissions', // the method that defines the relationship in your Model
						'attribute'        => 'name', // foreign key attribute that is shown to user
						'model'            => config('permission.models.role'), // foreign key model
						'pivot'            => true, // on create&update, do you need to add/delete pivot table entries?]
						'number_columns'   => 3, //can be 1,2,3,4,6
					],
					'secondary' => [
						'label'          => mb_ucfirst(trans('admin.permission_singular')),
						'name'           => 'permissions', // the method that defines the relationship in your Model
						'entity'         => 'permissions', // the method that defines the relationship in your Model
						'entity_primary' => 'roles', // the method that defines the relationship in your Model
						'attribute'      => 'name', // foreign key attribute that is shown to user
						'model'          => config('permission.models.permission'), // foreign key model
						'pivot'          => true, // on create&update, do you need to add/delete pivot table entries?]
						'number_columns' => 3, //can be 1,2,3,4,6
					],
				],
			]);
		}
	}
	
	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function account()
	{
		$authUser = auth()->check() ? auth()->user() : null;
		
		// FIELDS
		$this->xPanel->addField([
			'label'             => trans('admin.Gender'),
			'name'              => 'gender_id',
			'type'              => 'select2_from_array',
			'options'           => $this->gender(),
			'allows_null'       => false,
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
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
			'name'              => 'auth_field',
			'label'             => t('auth_field_label'),
			'type'              => 'select2_from_array',
			'options'           => getAuthFields(),
			'allows_null'       => true,
			'default'           => getAuthField($authUser),
			'hint'              => t('auth_field_hint'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'username',
			'label'             => t('Username'),
			'type'              => 'text',
			'attributes'        => [
				'placeholder' => t('Username'),
			],
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'email',
			'label'             => trans('admin.Email'),
			'type'              => 'email',
			'attributes'        => [
				'placeholder' => trans('admin.Email'),
			],
			'prefix'            => '<i class="far fa-envelope"></i>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'password',
			'label'             => trans('admin.Password'),
			'type'              => 'password',
			'attributes'        => [
				'placeholder'  => trans('admin.Password'),
				'autocomplete' => 'new-password',
			],
			'prefix'            => '<i class="fas fa-lock"></i>',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$phoneCountry = (!empty($authUser) && isset($authUser->phone_country)) ? strtolower($authUser->phone_country) : 'us';
		$this->xPanel->addField([
			'name'              => 'phone',
			'label'             => trans('admin.Phone'),
			'type'              => 'intl_tel_input',
			'phone_country'     => $phoneCountry,
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'name'              => 'phone_hidden',
			'label'             => trans('admin.Phone hidden'),
			'type'              => 'checkbox_switch',
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		$this->xPanel->addField([
			'label'             => mb_ucfirst(trans('admin.country')),
			'name'              => 'country_code',
			'model'             => 'App\Models\Country',
			'entity'            => 'country',
			'attribute'         => 'name',
			'type'              => 'select2',
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
			'hint'              => t('admin_preferred_time_zone_info_lite'),
			'wrapperAttributes' => [
				'class' => 'col-md-6',
			],
		]);
		
		// Get logged user
		if (!empty($authUser) && isset($authUser->id)) {
			return $this->edit($authUser->id);
		} else {
			abort(Response::HTTP_FORBIDDEN, 'Not allowed.');
		}
	}
	
	public function store(StoreRequest $request)
	{
		$request = $this->handleInput($request);
		
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request)
	{
		$request = $this->handleInput($request);
		
		// Is the admin user's own account?
		// If from self account form?
		$isTheAdminOwnAccount = (auth()->user()->id == request()->segment(3));
		$isFromSelfAccountForm = str_contains(url()->previous(), admin_uri('account'));
		
		// Prevent user's role removal
		if ($isTheAdminOwnAccount || $isFromSelfAccountForm) {
			$this->xPanel->disableSyncPivot();
		}
		
		return parent::updateCrud($request);
	}
	
	// PRIVATE METHODS
	
	/**
	 * @return array
	 */
	private function gender(): array
	{
		$entries = Gender::query()->get();
		
		return collect($entries)->mapWithKeys(function ($item) {
			return [$item['id'] => $item['name']];
		})->toArray();
	}
	
	/**
	 * Handle Input values
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return \App\Http\Requests\Admin\Request
	 */
	private function handleInput(Request $request)
	{
		// Handle password input fields
		// Remove 'password_confirmation' field & Encrypt password if specified
		$request->request->remove('password_confirmation');
		if (request()->filled('password')) {
			$request->request->set('password', Hash::make(request()->input('password')));
		} else {
			$request->request->remove('password');
		}
		
		// Is an admin user?
		if ($this->isAdminUser($request)) {
			$request->request->set('is_admin', 1);
		} else {
			$request->request->set('is_admin', 0);
		}
		
		return $request;
	}
	
	/**
	 * Check if the set permissions are corresponding to the Staff permissions
	 *
	 * @param \App\Http\Requests\Admin\Request $request
	 * @return bool
	 */
	private function isAdminUser(Request $request): bool
	{
		$isAdmin = false;
		if (request()->filled('roles')) {
			$rolesIds = request()->input('roles');
			foreach ($rolesIds as $rolesId) {
				$role = Role::find($rolesId);
				if (!empty($role)) {
					$permissions = $role->permissions;
					if ($permissions->count() > 0) {
						foreach ($permissions as $permission) {
							if (in_array($permission->name, Permission::getStaffPermissions())) {
								$isAdmin = true;
							}
						}
					}
				}
			}
		}
		
		if (request()->filled('permissions')) {
			$permissionIds = request()->input('permissions');
			foreach ($permissionIds as $permissionId) {
				$permission = Permission::find($permissionId);
				if (in_array($permission->name, Permission::getStaffPermissions())) {
					$isAdmin = true;
				}
			}
		}
		
		return $isAdmin;
	}
}
