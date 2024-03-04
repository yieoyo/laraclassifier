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

namespace App\Http\Requests\Admin;

class PluginRequest extends Request
{
	protected bool $isValidPurchaseCode = false;
	protected ?string $invalidPurchaseCodeMessage = null;
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = [];
		
		$name = $this->segment(3);
		$plugin = load_plugin($name);
		if (empty($plugin)) {
			return $rules;
		}
		
		if ($this->has('purchase_code')) {
			$purchaseCodeData = plugin_purchase_code_data($plugin, $this->input('purchase_code'));
			$this->isValidPurchaseCode = (
				is_bool(data_get($purchaseCodeData, 'valid'))
				&& data_get($purchaseCodeData, 'valid')
			);
			$defaultMessage = 'Impossible to retrieve error message.';
			$this->invalidPurchaseCodeMessage = data_get($purchaseCodeData, 'message', $defaultMessage);
			
			if (!$this->isValidPurchaseCode) {
				$rules['purchase_code_valid'] = 'required'; // With customized message bellow
			}
		}
		
		return $rules;
	}
	
	/**
	 * Get custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages()
	{
		$messages = [];
		
		$name = $this->segment(3);
		$plugin = load_plugin($name);
		if (empty($plugin)) {
			return $messages;
		}
		
		if ($this->has('purchase_code')) {
			if (!$this->isValidPurchaseCode) {
				$apiMsg = ' ERROR: <strong>' . $this->invalidPurchaseCodeMessage . '</strong>';
				$msg = trans('admin.plugin_invalid_code', ['plugin_name' => $plugin->display_name]);
				$messages = ['purchase_code_valid.required' => $msg . $apiMsg];
			}
		}
		
		return $messages;
	}
}
