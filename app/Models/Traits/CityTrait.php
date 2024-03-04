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

namespace App\Models\Traits;

trait CityTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getAdmin2Html()
	{
		return (!empty($this->subAdmin2))
			? $this->subAdmin2->name
			: ($this->subadmin2_code ?? null);
	}
	
	public function getAdmin1Html()
	{
		return (!empty($this->subAdmin1))
			? $this->subAdmin1->name
			: ($this->subadmin1_code ?? null);
	}
	
	// ===| OTHER METHODS |===
}
