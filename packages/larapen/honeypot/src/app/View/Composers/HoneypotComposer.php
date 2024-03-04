<?php

namespace Larapen\Honeypot\app\View\Composers;

use Larapen\Honeypot\Honeypot;
use Illuminate\View\View;

class HoneypotComposer
{
	/**
	 * @throws \Exception
	 */
	public function compose(View $view): void
	{
		$setup = new Honeypot(config('honeypot'));
		
		$view->with('enabled', $setup->enabled())
			->with('nameFieldName', $setup->nameFieldName())
			->with('validFromFieldName', $setup->validFromFieldName())
			->with('encryptedValidFrom', $setup->encryptedValidFrom());
	}
}
