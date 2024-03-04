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

namespace App\Http\Controllers\Web\Admin\Panel\Library;

use Illuminate\Support\Facades\Route;

class PanelRoutes
{
	/**
	 * @param $name
	 * @param $controller
	 * @param array $options
	 */
	public static function resource($name, $controller, array $options = [])
	{
		$prefix = 'crud.' . $name . '.';
		
		// CRUD Routes
		Route::post($name . '/search', $controller . '@search')->name($prefix . 'search');
		Route::get($name . '/reorder/{lang?}', $controller . '@reorder')->name($prefix . 'reorder');
		Route::post($name . '/reorder/{lang?}', $controller . '@saveReorder')->name($prefix . 'save.reorder');
		Route::get($name . '/{id}/details', $controller . '@showDetailsRow')->name($prefix . 'showDetailsRow');
		Route::get($name . '/{id}/revisions', $controller . '@listRevisions')->name($prefix . 'listRevisions');
		Route::post($name . '/{id}/revisions/{revisionId}/restore', $controller . '@restoreRevision')->name($prefix . 'restoreRevision');
		Route::post($name . '/bulk_actions', $controller . '@bulkActions')->name($prefix . 'bulkActions');
		
		$optionsWithDefaultRouteNames = array_merge([
			'names' => [
				'index'   => $prefix . 'index',
				'create'  => $prefix . 'create',
				'store'   => $prefix . 'store',
				'edit'    => $prefix . 'edit',
				'update'  => $prefix . 'update',
				'show'    => $prefix . 'show',
				'destroy' => $prefix . 'destroy',
			],
		], $options);
		
		Route::resource($name, $controller, $optionsWithDefaultRouteNames);
	}
}
