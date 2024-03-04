<?php

namespace extras\plugins\paypal\app\Traits;

use App\Models\PaymentMethod;

trait InstallTrait
{
	/**
	 * @return array
	 */
	public static function getOptions(): array
	{
		$options = [];
		
		$paymentMethod = PaymentMethod::active()->where('name', 'paypal')->first();
		if (!empty($paymentMethod)) {
			$options[] = (object)[
				'name'     => mb_ucfirst(trans('admin.settings')),
				'url'      => admin_url('payment_methods/' . $paymentMethod->id . '/edit'),
				'btnClass' => 'btn-info',
			];
		}
		
		return $options;
	}
	
	/**
	 * @return bool
	 */
	public static function installed(): bool
	{
		$cacheExpiration = 86400; // Cache for 1 day (60 * 60 * 24)
		
		return cache()->remember('plugins.paypal.installed', $cacheExpiration, function () {
			$paymentMethod = PaymentMethod::active()->where('name', 'paypal')->first();
			if (empty($paymentMethod)) {
				return false;
			}
			
			return true;
		});
	}
	
	/**
	 * @return bool
	 */
	public static function install(): bool
	{
		// Remove the plugin entry
		self::uninstall();
		
		// Plugin data
		$data = [
			'id'                => 1,
			'name'              => 'paypal',
			'display_name'      => 'PayPal',
			'description'       => 'Payment with PayPal',
			'has_ccbox'         => 0,
			'is_compatible_api' => 0,
			'countries'         => null,
			'lft'               => 0,
			'rgt'               => 0,
			'depth'             => 1,
			'active'            => 1,
		];
		
		try {
			// Create plugin data
			$paymentMethod = PaymentMethod::create($data);
			if (empty($paymentMethod)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @return bool
	 */
	public static function uninstall(): bool
	{
		try {
			cache()->forget('plugins.paypal.installed');
		} catch (\Throwable $e) {
		}
		
		$paymentMethod = PaymentMethod::where('name', 'paypal')->first();
		if (!empty($paymentMethod)) {
			$deleted = $paymentMethod->delete();
			if ($deleted > 0) {
				return true;
			}
		}
		
		return false;
	}
}
