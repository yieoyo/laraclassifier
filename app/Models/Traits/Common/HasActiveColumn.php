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

namespace App\Models\Traits\Common;

trait HasActiveColumn
{
    public function getActiveHtml(): ?string
    {
        if (!isset($this->active)) return null;
        
        return ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'active', $this->active);
    }
}
