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

use App\Models\Blacklist;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\BlacklistRequest as StoreRequest;
use App\Http\Requests\Admin\BlacklistRequest as UpdateRequest;
use Prologue\Alerts\Facades\Alert;

class BlacklistController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Blacklist');
		$this->xPanel->setRoute(admin_uri('blacklists'));
		$this->xPanel->setEntityNameStrings(trans('admin.blacklist'), trans('admin.blacklists'));
		$this->xPanel->orderByDesc('id');
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		
		// Filters
		// -----------------------
		$this->xPanel->disableSearchBar();
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'entryType',
				'type'  => 'dropdown',
				'label' => mb_ucfirst(trans('admin.type')),
			],
			[
				'domain' => 'domain',
				'email'  => 'email',
				'phone'  => 'phone',
				'ip'     => 'ip',
				'word'   => 'word',
			],
			fn ($value) => $this->xPanel->addClause('where', 'type', '=', $value)
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'entry',
				'type'  => 'text',
				'label' => mb_ucfirst(trans('admin.Entry')),
			],
			false,
			fn ($value) => $this->xPanel->addClause('where', 'entry', 'LIKE', "%$value%")
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
			'name'  => 'type',
			'label' => mb_ucfirst(trans('admin.type')),
		]);
		$this->xPanel->addColumn([
			'name'  => 'entry',
			'label' => trans('admin.Entry'),
		]);
		
		// FIELDS
		$this->xPanel->addField([
			'name'  => 'type',
			'label' => mb_ucfirst(trans('admin.type')),
			'type'  => 'enum',
		]);
		$this->xPanel->addField([
			'name'       => 'entry',
			'label'      => trans('admin.Entry'),
			'type'       => 'text',
			'attributes' => [
				'placeholder' => trans('admin.Entry'),
			],
		]);
	}
	
	public function store(StoreRequest $request)
	{
		// Check admin users (Don't ban admin users)
		if ($this->isAnAdminUser()) {
			return redirect()->back();
		}
		
		return parent::storeCrud();
	}
	
	public function update(UpdateRequest $request)
	{
		// Check admin users (Don't ban admin users)
		if ($this->isAnAdminUser()) {
			return redirect()->back();
		}
		
		return parent::updateCrud();
	}
	
	/**
	 * Ban user (by email or/and phone number) (from a link)
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function banUser(): \Illuminate\Http\RedirectResponse
	{
		// Get email address
		$email = request()->query('email');
		$phone = request()->query('phone');
		
		// Get previous URL
		$previousUrl = url()->previous();
		
		// Exceptions
		if (empty($email) && empty($phone)) {
			$message = trans('admin.no_action_is_performed');
			
			if (isAdminPanel($previousUrl)) {
				Alert::info($message)->flash();
			} else {
				flash($message)->info();
			}
			
			return redirect()->back();
		}
		
		// Check admin users (Don't ban admin users)
		if ($this->isAnAdminUser($email, $phone)) {
			return redirect()->back();
		}
		
		if (!empty($email)) {
			// Check if the email address has been banned
			$bannedEmail = Blacklist::where('type', 'email')->where('entry', $email);
			if ($bannedEmail->exists()) {
				/*
				 * This for old email addresses banned...
				 * New ban actions trigger the Blacklist's Observer
				 * that deletes the user and its listings (if exist).
				 */
				
				// Delete the banned user related to the email address
				$user = User::where('email', $email)->first();
				if (!empty($user)) {
					$user->delete();
				}
				
				// Delete the banned user's listings related to the email address
				$posts = Post::where('email', $email);
				if ($posts->exists()) {
					foreach ($posts->cursor() as $post) {
						$post->delete();
					}
				}
			} else {
				// Add the email address to the blacklist
				$bannedEmail = new Blacklist();
				$bannedEmail->type = 'email';
				$bannedEmail->entry = $email;
				$bannedEmail->save();
			}
		}
		
		if (!empty($phone)) {
			// Get phone number variant
			$phoneVariant = (str_starts_with($phone, '+'))
				? ltrim($phone, '+')
				: '+' . $phone;
			
			// Check if the phone number has been banned
			$bannedPhone = Blacklist::where('type', 'phone')
				->where(function ($query) use ($phone, $phoneVariant) {
					$query->where('entry', $phone)->orWhere('entry', $phoneVariant);
				});
			if ($bannedPhone->exists()) {
				/*
				 * This for old phone numbers banned...
				 * New ban actions trigger the Blacklist's Observer
				 * that deletes the user and its listings (if exist).
				 */
				
				// Delete the banned user related to the phone number
				$user = User::where('phone', $phone)->orWhere('phone', $phoneVariant)->first();
				if (!empty($user)) {
					$user->delete();
				}
				
				// Delete the banned user's listings related to the phone number
				$posts = Post::where('phone', $phone)->orWhere('phone', $phoneVariant);
				if ($posts->exists()) {
					foreach ($posts->cursor() as $post) {
						$post->delete();
					}
				}
			} else {
				// Add the phone number to the blacklist
				$bannedPhone = new Blacklist();
				$bannedPhone->type = 'phone';
				$bannedPhone->entry = $phone;
				$bannedPhone->save();
			}
		}
		
		$message = '';
		if (!empty($email) && !empty($phone)) {
			$message = trans('admin.email_address_and_phone_number_banned_successfully', ['email' => $email, 'phone' => $phone]);
		} else {
			if (!empty($email)) {
				$message = trans('admin.email_address_banned_successfully', ['email' => $email]);
			}
			if (!empty($phone)) {
				$message = trans('admin.phone_number_banned_successfully', ['phone' => $phone]);
			}
		}
		if (!empty($message)) {
			if (isAdminPanel($previousUrl)) {
				Alert::success($message)->flash();
			} else {
				flash($message)->success();
			}
		}
		
		// Get the next URL
		$nextUrl = '/';
		if (isAdminPanel($previousUrl)) {
			$tmp = preg_split('#/[0-9]+/edit#ui', $previousUrl);
			$nextUrl = $tmp[0] ?? $previousUrl;
		}
		
		return redirect()->to($nextUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	/**
	 * Check if the current email address|phone number belongs to an admin user
	 * Prevent admin users to be banned
	 *
	 * @param $email
	 * @param $phone
	 * @return bool
	 */
	private function isAnAdminUser($email = null, $phone = null): bool
	{
		if (empty($email)) {
			if (request()->filled('type') && request()->filled('entry')) {
				if (request()->query('type') == 'email') {
					$email = request()->query('entry');
				}
			}
		}
		if (empty($phone)) {
			if (request()->filled('type') && request()->filled('entry')) {
				if (request()->query('type') == 'phone') {
					$phone = request()->query('entry');
				}
			}
		}
		
		$isAdminUser = false;
		
		// Check admin users (Don't ban admin users)
		if (!empty($email)) {
			$user = User::where('email', $email)->first();
			if (!empty($user)) {
				if ($user->can(Permission::getStaffPermissions())) {
					$isAdminUser = true;
				}
			}
		}
		if (!empty($phone)) {
			// Get phone number variant
			$phoneVariant = (str_starts_with($phone, '+'))
				? ltrim($phone, '+')
				: '+' . $phone;
			
			$user = User::where('phone', $phone)->orWhere('phone', $phoneVariant)->first();
			if (!empty($user)) {
				if ($user->can(Permission::getStaffPermissions())) {
					$isAdminUser = true;
				}
			}
		}
		
		if ($isAdminUser) {
			$msg = t('admin_users_cannot_be_banned');
			if (isAdminPanel(url()->previous())) {
				Alert::error($msg)->flash();
			} else {
				flash($msg)->error();
			}
		}
		
		return $isAdminUser;
	}
}
