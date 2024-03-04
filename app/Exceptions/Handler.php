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

namespace App\Exceptions;

use App\Exceptions\Traits\JsonRenderTrait;
use App\Exceptions\Traits\NotificationTrait;
use App\Exceptions\Traits\PluginTrait;
use App\Helpers\Cookie;
use App\Helpers\DBTool;
use App\Helpers\UrlGen;
use Illuminate\Contracts\Container\Container;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Prologue\Alerts\Facades\Alert;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
	use JsonRenderTrait, PluginTrait, NotificationTrait;
	
	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		//
	];
	
	/**
	 * A list of the inputs that are never flashed for validation exceptions.
	 *
	 * @var array
	 */
	protected $dontFlash = [
		'current_password',
		'password',
		'password_confirmation',
	];
	
	/**
	 * Illuminate request class.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;
	
	/**
	 * Handler constructor.
	 *
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		parent::__construct($container);
		
		$this->app = app();
		
		// Fix the 'files' & 'filesystem' binging.
		$this->app->register(\Illuminate\Filesystem\FilesystemServiceProvider::class);
		
		// Create a config var for current language
		$this->getLanguage();
	}
	
	/**
	 * Register the exception handling callbacks for the application.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->reportable(fn (Throwable $e) => $this->sendNotification($e));
	}
	
	/**
	 * Report or log an exception.
	 *
	 * @param \Throwable $e
	 * @return void
	 * @throws \Throwable
	 */
	public function report(Throwable $e)
	{
		// Prevent error 500 from PDO Exception
		if (appInstallFilesExist()) {
			// Maximum execution time exceeded
			if ($this->isMaximumExecutionTimeException($e)) {
				die($this->getMaximumExecutionTimeMessage($e));
			}
			
			// Memory is full
			// Called only when reporting some Laravel error traces
			if ($this->isFullMemoryException($e)) {
				die($this->getFullMemoryMessage($e));
			}
			
			if ($this->isPDOException($e)) {
				// Too many connections
				if ($this->isTooManyConnectionsException($e)) {
					die($this->getTooManyConnectionsMessage($e));
				}
				
				if (($res = $this->testDatabaseConnection()) !== true) {
					die($res);
				}
			}
		} else {
			// Clear PDO error log during installation
			if ($this->isPDOException($e)) {
				$this->clearLog();
			}
		}
		
		parent::report($e);
	}
	
	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param $request
	 * @param \Throwable $e
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
	 * @throws \Throwable
	 */
	public function render($request, Throwable $e)
	{
		// Restore the request headers back to the original state
		// saved before API call (using sub request option)
		if (config('request.original.headers')) {
			request()->headers->replace(config('request.original.headers'));
		}
		
		// Show API or AJAX requests exceptions
		if (
			isFromApi()
			|| str_starts_with($request->path(), 'api/')
			|| $request->ajax()
			|| $request->expectsJson()
		) {
			return $this->jsonRender($e);
		}
		
		// Maximum execution time exceeded
		if ($this->isMaximumExecutionTimeException($e)) {
			die($this->getMaximumExecutionTimeMessage($e));
		}
		
		// Memory is full
		// Called only when reporting some Laravel error traces
		if ($this->isFullMemoryException($e)) {
			die($this->getFullMemoryMessage($e));
		}
		
		// Show HTTP exceptions
		if ($this->isHttpException($e)) {
			// Check if the app is installed when page is not found (or when 404 page is called),
			// to prevent any DB error when the app is not installed yet
			if (method_exists($e, 'getStatusCode')) {
				if ($e->getStatusCode() == 404) {
					if (!appIsInstalled() && $request->input('exception') != '404') {
						return redirect()->to(getRawBaseUrl() . '/install?exception=404');
					}
				}
			}
			
			if ($e instanceof PostTooLargeException) {
				$message = 'Maximum data (including files to upload) size to post and memory usage are limited on the server.';
				$message = 'Payload Too Large. ' . $message;
				$backLink = ' <a href="' . url()->previous() . '">' . t('Back') . '</a>';
				$message = $message . $backLink;
				
				abort(500, $message);
			}
			
			// Original Code
			return parent::render($request, $e);
		}
		
		/*
		 * Temporary fix when forms (after failed validation) are not redirect to back with explicit error messages per field
		 * Issue found on type of server: Apache/2.4.52 (Win64) OpenSSL/1.1.1m PHP/8.1.2
		 */
		if ($e instanceof ValidationException) {
			if (method_exists($e, 'errors')) {
				return back()->withErrors($e->errors())->withInput();
			}
		}
		
		// Show caching exception (APC or Redis)
		if (preg_match('#apc_#ui', $e->getMessage()) || preg_match('#/predis/#i', $e->getFile())) {
			$message = $e->getMessage() . "\n";
			if (preg_match('#apc_#ui', $e->getMessage())) {
				$message .= 'This looks like that the <a href="https://www.php.net/manual/en/book.apcu.php" target="_blank">APC extension</a> ';
				$message .= 'is not installed (or not properly installed) for PHP.' . "\n";
			}
			$message .= 'Make sure you have properly installed the components related to the selected cache driver on your server.' . "\n";
			$message .= 'To get your website up and running again you have to change the cache driver in the /.env file ';
			$message .= 'with the "file" or "array" driver (example: CACHE_DRIVER=file).' . "\n";
			
			$data = ['exception' => $e, 'message' => $message];
			
			return response()->view('errors.custom', $data, 500);
		}
		
		// Show DB exceptions
		if ($e instanceof \PDOException) {
			// Check if the app installation files exist,
			// to prevent any DB error (from the Admin Panel) when the app is not installed yet.
			if (!appInstallFilesExist() && $request->input('exception') != 'PDO') {
				$msg = $e->getMessage();
				if (!empty($msg)) {
					dd($msg);
				}
				
				$this->clearLog();
				
				return redirect()->to(getRawBaseUrl() . '/install?exception=PDO');
			}
			
			/*
			 * DB Connection Error:
			 * http://dev.mysql.com/doc/refman/5.7/en/error-messages-server.html
			 */
			$dbErrorCodes = ['mysql' => ['1042', '1044', '1045', '1046', '1049'], 'standardized' => ['08S01', '42000', '28000', '3D000', '42000', '42S22'],];
			$tableErrorCodes = ['mysql' => ['1051', '1109', '1146'], 'standardized' => ['42S02'],];
			
			// Database errors
			if (in_array($e->getCode(), $dbErrorCodes['mysql']) || in_array($e->getCode(), $dbErrorCodes['standardized'])) {
				return response()->view('errors.custom', ['exception' => $e], 500);
			}
			
			// Tables and fields errors
			if (in_array($e->getCode(), $tableErrorCodes['mysql']) || in_array($e->getCode(), $tableErrorCodes['standardized'])) {
				$message = 'Some tables of the database are absent.' . "\n";
				$message .= $e->getMessage() . "\n";
				$message .= '1/ Remove all tables from the database (if existing)' . "\n";
				$message .= '2/ Delete the <code>/.env</code> file (required before re-installation)' . "\n";
				$message .= '3/ and reload this page -or- go to install URL: <a href="' . url('install') . '">' . url('install') . '</a>.' . "\n";
				$message .= 'BE CAREFUL: If your site is already in production, you will lose all your data in both cases.' . "\n";
				
				$data = ['exception' => $e, 'message' => $message];
				
				return response()->view('errors.custom', $data, 500);
			}
		}
		
		// Show Token exceptions
		if ($e instanceof TokenMismatchException) {
			$message = t('Your session has expired');
			if (isAdminPanel()) {
				Alert::error($message)->flash();
			} else {
				flash($message)->error();
			}
			$previousUrl = url()->previous();
			if (!str_contains($previousUrl, 'CsrfToken')) {
				$queryString = (parse_url($previousUrl, PHP_URL_QUERY) ? '&' : '?') . 'error=CsrfToken';
				$previousUrl = $previousUrl . $queryString;
			}
			
			return redirect()->to($previousUrl)->withInput();
		}
		
		// Show MethodNotAllowed HTTP exceptions
		if ($e instanceof MethodNotAllowedHttpException) {
			$message = "Whoops! Seems you use a bad request method. Please try again.";
			$backLink = ' <a href="' . url()->previous() . '">' . t('Back') . '</a>';
			$message = $message . $backLink;
			abort(500, $message);
		}
		
		// Try to fix the cookies issue related the Laravel security release:
		// https://laravel.com/docs/5.6/upgrade#upgrade-5.6.30
		if (
			str_contains($e->getMessage(), 'unserialize()')
			&& request()->query('exception') != 'unserialize'
		) {
			// Unset cookies
			Cookie::forgetAll();
			
			// Customize and Redirect to the previous URL
			$previousUrl = url()->previous();
			$queryString = (parse_url($previousUrl, PHP_URL_QUERY) ? '&' : '?') . 'exception=unserialize';
			$previousUrl = $previousUrl . $queryString;
			
			redirectUrl($previousUrl, 301, config('larapen.core.noCacheHeaders'));
		}
		
		// Customize the HTTP 500 error page
		$filePath = $e->getFile();
		if (!empty($filePath)) {
			$message = $e->getMessage();
			
			// Check if there is no plugin class loading issue (inside composer class loader)
			$isPluginClassLoadingIssue = (
				str_contains($filePath, '/vendor/composer/ClassLoader.php')
				&& str_contains($message, '/extras/plugins/')
			);
			if ($isPluginClassLoadingIssue) {
				$message = $this->tryToFixPluginDirName($message);
				$data = ['exception' => $e, 'message' => $message];
				
				return response()->view('errors.custom', $data, 500);
			}
			
			// Check if there are no problems in a plugin code
			$isIssueInPluginCodeFound = (
				str_contains($filePath, '/extras/plugins/')
				&& str_contains($message, 'extras\plugins\\')
				&& str_contains($message, 'must be compatible')
			);
			if ($isIssueInPluginCodeFound) {
				$message = $this->tryToArchivePlugin($message);
				$data = ['exception' => $e, 'message' => $message];
				
				return response()->view('errors.custom', $data, 500);
			}
			
			// Show custom error 500 page,
			// when the error is not from the '/vendor/' folder
			if (!str_contains($filePath, '/vendor/')) {
				return response()->view('errors.500', ['exception' => $e], 500);
			}
		}
		
		// Original Code
		return parent::render($request, $e);
	}
	
	/**
	 * Convert an authentication exception into an unauthenticated response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Illuminate\Auth\AuthenticationException $exception
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	protected function unauthenticated($request, AuthenticationException $exception)
	{
		if (
			isFromApi()
			|| str_starts_with($request->path(), 'api/')
			|| $request->expectsJson()
		) {
			$message = $exception->getMessage();
			if (empty($message)) {
				$message = 'Unauthenticated.';
			}
			
			return apiResponse()->unauthorized($message);
		}
		
		return redirect()->guest(UrlGen::loginPath());
	}
	
	/**
	 * Convert a validation exception into a JSON response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Illuminate\Validation\ValidationException $exception
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function invalidJson($request, ValidationException $exception): \Illuminate\Http\JsonResponse
	{
		return response()->json($exception->errors(), $exception->status);
	}
	
	// PRIVATE METHODS
	
	/**
	 * Is a PDO Exception
	 *
	 * @param \Throwable $e
	 * @return bool
	 */
	private function isPDOException(Throwable $e): bool
	{
		if (
			($e instanceof \PDOException)
			|| $e->getCode() == 1045
			|| str_contains($e->getMessage(), 'SQLSTATE')
			|| str_contains($e->getFile(), 'Database/Connectors/Connector.php')
		) {
			return true;
		}
		
		return false;
	}
	
	private function isMaximumExecutionTimeException(Throwable $e): bool
	{
		return (
			str_contains($e->getMessage(), 'Maximum execution time')
			&& str_contains($e->getMessage(), 'exceeded')
		);
	}
	
	private function isFullMemoryException(Throwable $e): bool
	{
		return (
			str_contains($e->getMessage(), 'Allowed memory size of')
			&& str_contains($e->getMessage(), 'tried to allocate')
		);
	}
	
	private function isTooManyConnectionsException(Throwable $e): bool
	{
		return (
			str_contains($e->getMessage(), 'max_user_connections')
			&& str_contains($e->getMessage(), 'active connections')
		);
	}
	
	/**
	 * Test Database Connection
	 *
	 * @return bool
	 */
	private function testDatabaseConnection(): bool
	{
		$pdo = DBTool::getPDOConnexion();
		
		if ($pdo instanceof \PDO) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Create a config var for current language
	 */
	private function getLanguage(): void
	{
		// Get the language only the app is already installed
		// to prevent HTTP 500 error through DB connexion during the installation process.
		if (appInstallFilesExist()) {
			$this->app['config']->set('lang.code', config('app.locale'));
		}
	}
	
	/**
	 * Clear Laravel Log files
	 */
	private function clearLog(): void
	{
		$mask = storage_path('logs') . DIRECTORY_SEPARATOR . '*.log';
		$logFiles = glob($mask);
		if (is_array($logFiles) && !empty($logFiles)) {
			foreach ($logFiles as $filename) {
				@unlink($filename);
			}
		}
	}
	
	private function getMaximumExecutionTimeMessage(Throwable $e): string
	{
		// Maximum execution time exceeded
		$message = $e->getMessage() . '. <br>';
		$message .= 'The server\'s maximum execution time must be increased so that it can support the execution time of the request.';
		$message .= '<br><br>';
		$message .= 'For quick fix to complete the execution of the current request, you can refresh this page as many times until this error disappears.
		If the error persists you must be increase your server\'s "max_execution_time" and "max_input_time" directives.';
		
		return '<pre>' . $message . '</pre>';
	}
	
	private function getFullMemoryMessage(Throwable $e): string
	{
		// die($e->getMessage());
		// Memory is full
		$message = $e->getMessage() . '. <br>';
		$message .= 'The server\'s memory must be increased so that it can support the load of the requested resource.';
		
		return '<pre>' . $message . '</pre>';
	}
	
	private function getTooManyConnectionsMessage(Throwable $e): string
	{
		// Too many connections
		$message = 'We are currently receiving a large number of connections. ';
		$message .= 'Please try again later. We apologize for the inconvenience.';
		
		return '<pre>' . $message . '</pre>';
	}
}
