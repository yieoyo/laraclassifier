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

namespace App\Http\Controllers\Web\Admin\Panel\Traits;

trait ShowDetailsRow
{
	/**
	 * Used with AJAX in the list view (datatables) to show extra information about that row that didn't fit in the table.
	 * It defaults to showing all connected translations and their CRUD buttons.
	 *
	 * It's enabled by:
	 * - setting the $crud['details_row'] variable to true;
	 * - adding the details route for the entity; ex: Route::get('page/{id}/details', 'PageCrudController@showDetailsRow');
	 *
	 * @param $id
	 * @param null $childId
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showDetailsRow($id, $childId = null)
	{
		$this->xPanel->hasAccessOrFail('list');
		
		if (!empty($childId)) {
			$id = $childId;
		}
		
		// Get the info for that entry
		$this->data['xPanel'] = $this->xPanel;
		$this->data['entry'] = $this->xPanel->model->find($id);
		
		$view = 'admin.panel.details_row.' . $this->xPanel->getModel()->getTable();
		
		if (view()->exists($view)) {
			return view($view, $this->data);
		}
		
		return view('admin.panel.details_row', $this->data);
	}
}
