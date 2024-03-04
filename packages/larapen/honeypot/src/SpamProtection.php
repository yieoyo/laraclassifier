<?php

namespace Larapen\Honeypot;

use Illuminate\Support\Collection;

class SpamProtection
{
	/**
	 * @throws \Exception
	 */
	public function check(Collection|array $requestFields): void
	{
		if (!config('honeypot.enabled')) {
			return;
		}
		
		$requestFields = Collection::wrap($requestFields);
		
		$nameFieldName = config('honeypot.name_field_name');
		$validFromFieldName = config('honeypot.valid_from_field_name');
		
		if (config('honeypot.randomize_name_field_name')) {
			$nameFieldName = $this->getRandomizedNameFieldName($nameFieldName, $requestFields);
		}
		
		if (!$this->shouldCheckHoneypot($requestFields, $nameFieldName, $validFromFieldName)) {
			return;
		}
		
		if (!$requestFields->has($nameFieldName)) {
			throw new \Exception();
		}
		
		$honeypotValue = $requestFields->get($nameFieldName);
		
		if (!empty($honeypotValue)) {
			throw new \Exception();
		}
		
		if (!config('honeypot.valid_from_timestamp')) {
			return;
		}
		
		$validFrom = $requestFields->get($validFromFieldName);
		
		if (!$validFrom) {
			throw new \Exception();
		}
		
		try {
			$time = new EncryptedTime($validFrom);
		} catch (\Throwable) {
			$time = null;
		}
		
		if (!$time || $time->isFuture()) {
			throw new \Exception();
		}
	}
	
	protected function getRandomizedNameFieldName(string $nameFieldName, Collection $requestFields): ?string
	{
		return $requestFields
			->filter(fn ($value, $key) => str_starts_with($key, $nameFieldName))
			->keys()
			->first();
	}
	
	private function shouldCheckHoneypot(Collection $requestFields, ?string $nameFieldName, ?string $validFromFieldName): bool
	{
		if (config('honeypot.honeypot_fields_required_for_all_forms') === true) {
			return true;
		}
		
		return ($requestFields->has($nameFieldName) || $requestFields->has($validFromFieldName));
	}
}
