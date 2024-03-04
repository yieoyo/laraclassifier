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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;

class UsernameIsAllowedRule implements ValidationRule
{
	/**
	 * The router instance used to check the username against application routes.
	 *
	 * @var \Illuminate\Routing\Router
	 */
	private Router $router;
	
	/**
	 * The filesystem class used to retrieve public files and directories.
	 *
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	private Filesystem $files;
	
	/**
	 * Create a new allowed username validator instance.
	 */
	public function __construct()
	{
		$this->router = app(Router::class);
		$this->files = app(Filesystem::class);
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail(trans('validation.username_is_allowed_rule'));
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
		$value = is_stringable($value) ? $value : '';
		$value = trim(strtolower($value));
		
		if ($this->isReservedUsername($value)) {
			return false;
		}
		
		if ($this->matchesRoute($value)) {
			return false;
		}
		
		if ($this->matchesPublicFileOrDirectory($value)) {
			return false;
		}
		
		return true;
	}
	
	/* PRIVATES */
	
	/**
	 * Determine whether the given username is in the reserved usernames list.
	 *
	 * @param string|null $value
	 * @return bool
	 */
	private function isReservedUsername(?string $value): bool
	{
		if (is_null($value)) {
			return false;
		}
		
		$reservedUsernames = getReservedUsernameRefList();
		$reservedUsernames = array_map('trim', $reservedUsernames);
		
		return in_array($value, $reservedUsernames);
	}
	
	/**
	 * Determine whether the given username matches an application route.
	 *
	 * @param string|null $value
	 * @return bool
	 */
	private function matchesRoute(?string $value): bool
	{
		foreach ($this->router->getRoutes() as $route) {
			if (strtolower($route->uri) === $value) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Determine whether the given username matches a public file or directory.
	 *
	 * @param string|null $value
	 * @return bool
	 */
	private function matchesPublicFileOrDirectory(?string $value): bool
	{
		foreach ($this->files->glob(public_path() . DIRECTORY_SEPARATOR . '*') as $path) {
			if (strtolower(basename($path)) === $value) {
				return true;
			}
		}
		
		return false;
	}
}
