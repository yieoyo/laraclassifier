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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models;

/*
|--------------------------------------------------------------------------
| Methods for working with translatable models.
|--------------------------------------------------------------------------
*/
trait HasTranslatableFields
{
	/**
	 * Get the attributes that were casted in the model.
	 * Used for translations because Spatie/Laravel-Translatable
	 * overwrites the getCasts() method.
	 *
	 * @return self
	 */
	public function getCastedAttributes()
	{
		return parent::getCasts();
	}
	
	/**
	 * Check if a model is translatable.
	 * All translation adaptors must have the translationEnabledForModel() method.
	 *
	 * @return bool
	 */
	public function translationEnabled()
	{
		if (method_exists($this, 'translationEnabledForModel')) {
			return $this->translationEnabledForModel();
		}
		
		return false;
	}
}
