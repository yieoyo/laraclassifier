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

namespace App\Http\Controllers\Web\Admin\Panel\Library\Traits\Panel\Buttons;

class Button
{
	public $stack;
	public $name;
	public $type = 'view';
	public $content;
	
	public function __construct($stack, $name, $type, $content)
	{
		$this->stack = $stack;
		$this->name = $name;
		$this->type = $type;
		$this->content = $content;
	}
}
