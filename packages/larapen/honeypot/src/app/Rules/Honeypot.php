<?php

namespace Larapen\Honeypot\app\Rules;

use Larapen\Honeypot\SpamProtection;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Honeypot implements ValidationRule
{
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if ($this->passes($attribute, $value)) {
			return;
		}
		
		$respondToSpamWith = config('honeypot.respond_to_spam_with');
		
		if ($respondToSpamWith == 'http_error_500') {
			// HTTP Error 500
			config()->set('app.debug', false);
			$status = 500;
			$message = 'Whoops something went wrong on our end.';
			
			abort($status, $message);
		} else {
			// Blank Page
			echo '';
			exit();
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		// Apply only for POST requests
		if (!request()->isMethod('POST')) {
			return true;
		}
		
		// Check Spam
		try {
			$spamProtection = new SpamProtection();
			$spamProtection->check(request()->all());
			
			return true;
		} catch (\Throwable) {
			$this->detectResponsibleUser();
			
			return false;
		}
	}
	
	/**
	 * Detect the user responsible for using the bot
	 *
	 * @return void
	 */
	private function detectResponsibleUser(): void
	{
		$guard = isFromApi() ? 'sanctum' : null;
		$authUser = auth($guard)->user();
		
		if (!empty($authUser)) {
			logger('Honeypot detected a bot for user ID: ' . $authUser->getAuthIdentifier());
		}
	}
}
