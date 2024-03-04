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

use App\Helpers\Files\Upload;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\PictureRequest as StoreRequest;
use App\Http\Requests\Admin\PictureRequest as UpdateRequest;
use App\Models\Picture;
use App\Models\Post;

class PictureController extends PanelController
{
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel('App\Models\Picture');
		$this->xPanel->with([
			'post',
			'post.country',
		]);
		$this->xPanel->setRoute(admin_uri('pictures'));
		$this->xPanel->setEntityNameStrings(trans('admin.picture'), trans('admin.pictures'));
		$this->xPanel->removeButton('create');
		if (!request()->input('order')) {
			$this->xPanel->orderByDesc('created_at');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_activation_button', 'bulkActivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deactivation_button', 'bulkDeactivationButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'bulk_deletion_button', 'bulkDeletionButton', 'end');
		$this->xPanel->addButtonFromModelFunction('line', 'edit_post', 'editPostButton', 'beginning');
		
		// Filters
		// -----------------------
		$this->xPanel->disableSearchBar();
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
				$this->xPanel->addClause('whereHas', 'post', function ($query) use ($value) {
					$query->where('country_code', '=', $value);
				});
			}
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'post_id',
				'type'  => 'text',
				'label' => trans('admin.Listing'),
			],
			false,
			function ($value) {
				if (is_numeric($value) || isHashedId($value)) {
					$value = hashId($value, true) ?? $value;
					$this->xPanel->addClause('where', 'post_id', '=', $value);
				} else {
					$this->xPanel->addClause('whereHas', 'post', function ($query) use ($value) {
						$query->where('title', 'LIKE', $value . '%');
					});
				}
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
				1 => trans('admin.Unactivated'),
				2 => trans('admin.Activated'),
			],
			function ($value) {
				if ($value == 1) {
					$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
				}
				if ($value == 2) {
					$this->xPanel->addClause('where', 'active', '=', 1);
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
			'name'          => 'filename',
			'label'         => trans('admin.Filename'),
			'type'          => 'model_function',
			'function_name' => 'getFilenameHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'post_id',
			'label'         => trans('admin.Listing'),
			'type'          => 'model_function',
			'function_name' => 'getPostTitleHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'country_code',
			'label'         => mb_ucfirst(trans('admin.country')),
			'type'          => 'model_function',
			'function_name' => 'getCountryHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Active'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
		]);
		
		// FIELDS
		$this->xPanel->addField([
			'name'  => 'post_id',
			'type'  => 'hidden',
			'value' => request()->query('post_id'),
		], 'create');
		$this->xPanel->addField([
			'name'   => 'filename',
			'label'  => trans('admin.Picture'),
			'type'   => 'image',
			'upload' => true,
			'disk'   => 'public',
		]);
		$this->xPanel->addField([
			'name'  => 'active',
			'label' => trans('admin.Active'),
			'type'  => 'checkbox_switch',
			'value' => 1,
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
		$post = null;
		
		// update
		$pictureId = request()->segment(3);
		if (!empty($pictureId) && is_numeric($pictureId)) {
			$picture = Picture::with('post')->find($pictureId);
			if (!empty($picture->post)) {
				$post = $picture->post;
			}
		}
		
		// create
		if (empty($post)) {
			$postId = request()->query('post_id');
			if (!empty($postId) && is_numeric($postId)) {
				$post = Post::find($postId);
			}
		}
		
		if (!empty($post)) {
			$attribute = 'filename';
			$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
			$file = $request->hasFile($attribute) ? $request->file($attribute) : $request->input($attribute);
			
			$request->request->set($attribute, Upload::image($destPath, $file, null, true));
			$request->request->add(['mime_type' => getUploadedFileMimeType($file)]);
		}
		
		return $request;
	}
}
