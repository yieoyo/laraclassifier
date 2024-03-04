<?php

namespace Larapen\Honeypot\app\Http\Middleware;

use Larapen\Honeypot\SpamProtection;
use Closure;
use Illuminate\Http\Request;

class ProtectAgainstSpam
{
	public function handle(Request $request, Closure $next)
	{
		// Apply only for POST requests
		if (!$request->isMethod('POST')) {
			return $next($request);
		}
		
		// Check Spam
		try {
			$spamProtection = new SpamProtection();
			$spamProtection->check($request->all());
			
			return $next($request);
		} catch (\Throwable $e) {
			$this->detectResponsibleUser();
			
			$respondToSpamWith = config('honeypot.respond_to_spam_with');
			
			if ($respondToSpamWith == 'http_error_500') {
				// HTTP Error 500
				config()->set('app.debug', false);
				$status = 500;
				$message = 'Whoops something went wrong on our end.';
				
				abort($status, $message);
			} else {
				// Blank Page
				return response('');
			}
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
