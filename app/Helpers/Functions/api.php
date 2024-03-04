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

use App\Helpers\Arr;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Prologue\Alerts\Facades\Alert;

/**
 * Check if the current request is from the API
 *
 * @return bool
 */
function isFromApi(): bool
{
	$isFromApi = false;
	
	if (
		request()->segment(1) == 'api'
		|| (request()->hasHeader('X-API-CALLED') && request()->header('X-API-CALLED'))
	) {
		$isFromApi = true;
	}
	
	return $isFromApi;
}

/**
 * Does the (current) request is from a Web Application?
 * Check if the current request is made from the official(s) web version(s) of the app
 *
 * Info: This function allows applying web features during API code execution
 * Note: This assumes the "X-AppType=web" header is sent from the web application
 *
 * @return bool
 */
function doesRequestIsFromWebApp(): bool
{
	return (request()->hasHeader('X-AppType') && request()->header('X-AppType') == 'web');
}

/**
 * Check if the API cURL request calls is enabled
 *
 * @return bool
 */
function isApiCurlRequestsEnabled(): bool
{
	return (strtolower(config('larapen.core.api.client')) === 'curl');
}

/**
 * Check if the API local request calls is enabled
 *
 * @return bool
 */
function isApiLocalRequestsEnabled(): bool
{
	return !isApiCurlRequestsEnabled();
}

/**
 * Make an API HTTP request
 *
 * @param string $method
 * @param string $uri
 * @param array $data
 * @param array $headers
 * @param bool $asMultipart
 * @param bool $forInternalEndpoint
 * @return array
 */
function makeApiRequest(
	string $method,
	string $uri,
	array  $data = [],
	array  $headers = [],
	bool   $asMultipart = false,
	bool   $forInternalEndpoint = true
): array
{
	try {
		/*
		 * Check if the endpoint is an external one
		 * i.e.The endpoint is a valid URL starting with 'http', except the website's URL
		 */
		$isRemoteEndpoint = (str_starts_with($uri, 'http') && !str_starts_with($uri, url('/')));
		
		if (!$isRemoteEndpoint) {
			$createMethods = ['POST', 'CREATE'];
			$updateMethods = ['PUT', 'PATCH', 'UPDATE'];
			$deleteMethods = ['DELETE'];
			$nonCacheableMethods = array_merge($createMethods, $updateMethods, $deleteMethods);
			
			// Apply persistent (required) inputs for API calls
			$defaultData = [
				'countryCode'  => config('country.code'),
				'languageCode' => config('app.locale'),
			];
			if (in_array(request()->method(), $nonCacheableMethods)) {
				$defaultData['country_code'] = (!empty($data['country_code']))
					? $data['country_code']
					: config('country.code');
				$defaultData['language_code'] = (!empty($data['language_code']))
					? $data['language_code']
					: config('app.locale');
			}
			if (in_array(request()->method(), $createMethods)) {
				$defaultData['create_from_ip'] = request()->ip();
			}
			if (in_array(request()->method(), $updateMethods)) {
				$defaultData['latest_update_ip'] = request()->ip();
			}
			$data = array_merge($defaultData, $data);
			
			// HTTP Client default headers for API calls
			$defaultHeaders = [
				'Content-Language'  => $defaultData['languageCode'] ?? null,
				'Accept'            => 'application/json',
				'X-AppType'         => 'web',
				'X-CSRF-TOKEN'      => csrf_token(),
				'X-WEB-REQUEST-URL' => request()->url(),
			];
			$appApiToken = config('larapen.core.api.token');
			if (!empty($appApiToken)) {
				$defaultHeaders['X-AppApiToken'] = $appApiToken;
			}
			if (session()->has('authToken')) {
				$defaultHeaders['Authorization'] = 'Bearer ' . session('authToken');
			}
			
			// Prevent HTTP request caching for methods that can update the database
			if (in_array(strtoupper($method), $nonCacheableMethods)) {
				$noCacheHeaders = config('larapen.core.noCacheHeaders');
				if (!empty($noCacheHeaders)) {
					foreach ($noCacheHeaders as $key => $value) {
						$defaultHeaders[$key] = $value;
					}
				}
			}
			$headers = array_merge($defaultHeaders, $headers);
		}
		
		if (isApiCurlRequestsEnabled() || $isRemoteEndpoint) {
			$array = curlHttpRequest($method, $uri, $data, $headers, $asMultipart, $forInternalEndpoint);
		} else {
			$array = laravelSubRequest($method, $uri, $data, $headers, $asMultipart, $forInternalEndpoint);
		}
	} catch (\Throwable $e) {
		$message = $e->getMessage();
		$message = !empty($message) ? $message : 'Error encountered during API request.';
		$status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
		$status = isValidHttpStatus($status) ? $status : 500;
		
		$array = [
			'success'      => false,
			'message'      => $message,
			'result'       => null,
			'isSuccessful' => false,
			'status'       => $status,
		];
	}
	
	/*
	 * Check the API auth error to log out user in the browser
	 * ---
	 * 401 Unauthorized can be used when the user login credential is wrong; or auth token passed in header is invalid.
	 * 403 Forbidden can be used when the user does not have specific permission for requested resource.
	 */
	if (data_get($array, 'status') == 401) {
		$array['message'] = logoutOnClient();
	}
	
	return $array;
}

/**
 * Make an API HTTP request internally (using Laravel sub requests)
 *
 * NOTE: By sending a sub request within the application,
 * you can simply consume your applications API without having to send separated, slower HTTP requests.
 *
 * @param string $method
 * @param string $uri
 * @param array $data
 * @param array $headers
 * @param bool $asMultipart
 * @param bool $forInternalEndpoint
 * @return array
 */
function laravelSubRequest(
	string $method,
	string $uri,
	array  $data = [],
	array  $headers = [],
	bool   $asMultipart = false,
	bool   $forInternalEndpoint = true
): array
{
	$baseUrl = '/api';
	$endpoint = $forInternalEndpoint ? ($baseUrl . $uri) : $uri;
	
	// Store the original request method, headers and input data
	config()->set('request.original.headers', request()->headers->all());
	$originalServerRequestUri = request()->server('REQUEST_URI');
	$originalMethod = request()->method();
	$originalRequest = (strtolower($originalMethod) == 'get')
		? request()->query()
		: request()->input();
	
	// Set the right request parameters for the API call
	$requestUri = $forInternalEndpoint ? $endpoint : $originalServerRequestUri;
	request()->server->set('REQUEST_URI', $requestUri);
	request()->setMethod($method);
	request()->merge($data);
	
	try {
		
		// Request segments are not available when making sub requests,
		// The 'X-API-CALLED' header is set for the function isFromApi()
		$localHeaders = ['X-API-CALLED' => true];
		$headers = array_merge($headers, $localHeaders);
		
		// Create the request to the internal API
		$cookies = [];
		$request = request()->create($endpoint, strtoupper($method), $data, $cookies, request()->file());
		
		// Apply the available headers to the request
		if (!empty($headers)) {
			foreach ($headers as $key => $value) {
				request()->headers->set($key, $value);
			}
		}
		
		// Dispatch the request instance with the router
		// NOTE: If you're consuming your own API,
		// use app()->handle() instead of \Route::dispatch()
		// $response = app()->handle($request);
		$response = Route::dispatch($request);
		
		// Fetch the response
		// dd($response->getData());
		$json = $response->getContent();
		
		// dd($json); // debug!
		$array = json_decode($json, true);
		
		// Throw an exception if the returned type is not an array
		if (!is_array($array)) {
			showApiResponseBodyTypeError($response->getData(), $baseUrl, $endpoint);
		}
		
		$array['isSuccessful'] = $response->isSuccessful();
		$array['status'] = (method_exists($response, 'status')) ? $response->status() : $response->getStatusCode();
		
	} catch (\Throwable $e) {
		$status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
		$status = isValidHttpStatus($status) ? $status : 500;
		$message = $e->getMessage();
		$message = !empty($message) ? $message : 'Error encountered during API request.';
		
		$array = [
			'success'      => false,
			'message'      => $message,
			'result'       => null,
			'isSuccessful' => false,
			'status'       => $status,
		];
	}
	
	// Restore the request method, headers & input back to the original state
	if (config('request.original.headers')) {
		request()->headers->replace(config('request.original.headers'));
	}
	request()->server->set('REQUEST_URI', $originalServerRequestUri);
	request()->setMethod($originalMethod);
	request()->replace($originalRequest);
	
	return $array;
}

/**
 * Make an API HTTP request remotely (using CURL)
 *
 * @param string $method
 * @param string $uri
 * @param array $data
 * @param array $headers
 * @param bool $asMultipart
 * @param bool $forInternalEndpoint
 * @return array
 */
function curlHttpRequest(
	string $method,
	string $uri,
	array  $data = [],
	array  $headers = [],
	bool   $asMultipart = false,
	bool   $forInternalEndpoint = true
): array
{
	// Guzzle Options
	$options = ['debug' => false];
	
	$proxy = config('larapen.core.api.proxy');
	if (!empty($proxy)) {
		$options['proxy'] = $proxy;
		$options['curl'] = [
			// In some cases: https://stackoverflow.com/a/28505942
			CURLOPT_HTTPPROXYTUNNEL => true,
			CURLOPT_FOLLOWLOCATION  => true,
		];
	}
	
	$baseUrl = url('api');
	$endpoint = $forInternalEndpoint ? ($baseUrl . $uri) : $uri;
	
	try {
		
		$client = Http::withOptions($options)->withoutVerifying();
		
		/*
		// Warning: Memory issue applying the user agent
		$userAgent = request()->server('HTTP_USER_AGENT');
		if (!empty($userAgent) && is_string($userAgent)) {
			// $client->withUserAgent($userAgent);
		}
		*/
		if (!empty($headers)) {
			$client->withHeaders($headers);
		}
		if ($asMultipart) {
			if (strtolower($method) == 'put') {
				$data['_method'] = strtoupper($method);
			}
			$client->asMultipart();
			$data = multipartFormData($data);
			$method = 'post';
		}
		
		/*
		 * Make the request and wait for 60 seconds for response.
		 * If it does not receive one, wait 2000 milliseconds (2 seconds), and then try again.
		 * Keep trying up to 3 times, and finally give up and throw an exception.
		 */
		$timeout = config('larapen.core.api.timeout', 60);
		$times = config('larapen.core.api.retry.times', 3);
		$sleep = config('larapen.core.api.retry.sleep', 2000);
		$when = fn (Exception $e, PendingRequest $request) => shouldHttpRequestBeRetried($e, $request, $method);
		/*
		 * If all of the requests fail, an instance of Illuminate\Http\Client\RequestException will be thrown.
		 * If you would like to disable this behavior, you may provide a throw argument with a value of false.
		 * When disabled, the last response received by the client will be returned after all retries have been attempted
		 * More info: https://laravel.com/docs/master/http-client#retries
		 */
		$client->timeout($timeout)->retry($times, $sleep, $when, throw: false);
		
		if (strtolower($method) == 'get') {
			$response = $client->get($endpoint, $data);
		} else if (strtolower($method) == 'post') {
			$response = $client->post($endpoint, $data);
		} else if (strtolower($method) == 'put') {
			$response = $client->put($endpoint, $data);
		} else if (strtolower($method) == 'delete') {
			$response = $client->delete($endpoint, $data);
		} else {
			// Request Options (Not to be confused with the Guzzle options)
			$options = [];
			if (!empty($data)) {
				$options = ['multipart' => $data];
			}
			$response = $client->send($method, $endpoint, $options);
		}
		
		// Get the array formatted response
		// Note: Don't pass a key in argument to always expect an array
		$array = $response->json();
		
		// Throw an exception if the returned type is not an array
		if (!is_array($array)) {
			showApiResponseBodyTypeError($response->body(), $baseUrl, $endpoint);
		}
		
		$array['isSuccessful'] = $response->successful();
		$array['status'] = $response->status();
		
	} catch (\Throwable $e) {
		$status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
		$status = isValidHttpStatus($status) ? $status : 500;
		
		$array = [
			'success'      => false,
			'message'      => $e->getMessage(),
			'result'       => null,
			'isSuccessful' => false,
			'status'       => $status,
		];
	}
	
	return $array;
}

/**
 * Convert POST request to Guzzle multipart array format
 *
 * @param $inputs
 * @return array
 */
function multipartFormData($inputs): array
{
	$formData = [];
	
	$inputs = Arr::flattenPost($inputs);
	if (empty($inputs)) {
		return $formData;
	}
	
	foreach ($inputs as $key => $value) {
		if ($value instanceof UploadedFile) {
			$formData[] = [
				'name'     => $key,
				'contents' => fopen($value->getPathname(), 'r'),
				'filename' => $value->getClientOriginalName(),
			];
		} else {
			$formData[] = [
				'name'     => $key,
				'contents' => $value,
			];
		}
	}
	
	return $formData;
}

/**
 * @return string|null
 */
function getApiAuthToken(): ?string
{
	$token = null;
	
	if (request()->hasHeader('Authorization')) {
		$authorization = request()->header('Authorization');
		if (str_contains($authorization, 'Bearer')) {
			$token = str_replace('Bearer ', '', $authorization);
		}
	}
	
	return is_string($token) ? $token : null;
}

/**
 * @param $paginatedCollection
 * @return mixed
 */
function setPaginationBaseUrl($paginatedCollection)
{
	// If the request is made from the app's Web environment,
	// use the Web URL as the pagination's base URL
	if (doesRequestIsFromWebApp()) {
		if (request()->hasHeader('X-WEB-REQUEST-URL')) {
			if (method_exists($paginatedCollection, 'setPath')) {
				$paginatedCollection->setPath(request()->header('X-WEB-REQUEST-URL'));
			}
		}
	}
	
	return $paginatedCollection;
}

/**
 * Log out the user on a web client (Browser)
 *
 * @param string|null $message
 * @return string|null
 */
function logoutOnClient(?string $message = null): ?string
{
	// Get the current Country
	if (session()->has('countryCode')) {
		$countryCode = session('countryCode');
	}
	if (session()->has('allowMeFromReferrer')) {
		$allowMeFromReferrer = session('allowMeFromReferrer');
	}
	if (session()->has('browserLangCode')) {
		$browserLangCode = session('browserLangCode');
	}
	
	// Remove all session vars
	auth()->logout();
	request()->session()->flush();
	request()->session()->regenerate();
	
	// Retrieve the current Country
	if (!empty($countryCode)) {
		session()->put('countryCode', $countryCode);
	}
	if (!empty($allowMeFromReferrer)) {
		session()->put('allowMeFromReferrer', $allowMeFromReferrer);
	}
	if (!empty($browserLangCode)) {
		session()->put('browserLangCode', $browserLangCode);
	}
	
	// Unintentional disconnection
	if (empty($message)) {
		$message = t('unintentional_logout');
		if (isAdminPanel()) {
			Alert::error($message)->flash();
		} else {
			flash($message)->error();
		}
		
		return $message;
	}
	
	// Intentional disconnection
	if (isAdminPanel()) {
		Alert::success($message)->flash();
	} else {
		flash($message)->success();
	}
	
	return $message;
}

/**
 * @return bool
 */
function isPostCreationRequest(): bool
{
	if (isFromApi()) {
		$isPostCreationRequest = (str_contains(currentRouteAction(), 'Api\PostController@store'));
	} else {
		$isMultiStepFormEnabled = (config('settings.single.publication_form_type') == '1');
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		
		$isNewEntryUri = (
			($isMultiStepFormEnabled && request()->segment(2) == 'create')
			|| ($isSingleStepFormEnabled && request()->segment(1) == 'create')
		);
		
		$isPostCreationRequest = (
			$isNewEntryUri
			|| str_contains(currentRouteAction(), 'Post\CreateOrEdit\MultiSteps\CreateController')
			|| str_contains(currentRouteAction(), 'Post\CreateOrEdit\SingleStep\CreateController')
		);
	}
	
	return $isPostCreationRequest;
}

function showApiResponseBodyTypeError($body, string $baseUrl, string $endpoint): void
{
	$canBeRelatedToCaptchaProtection = (
		!empty($body)
		&& is_string($body)
		&& (str_contains($body, '<html') && str_contains($body, '</html>'))
		&& (str_contains($body, '<script') && str_contains($body, '</script>'))
	);
	if ($canBeRelatedToCaptchaProtection) {
		$message = getApiSecurityBasedError($baseUrl, $endpoint);
		abort(403, $message);
	}
	
	$message = getApiResponseTypeError($endpoint, $body);
	config()->set('app.debug', false);
	abort(500, $message);
}

/**
 * @param string $baseUrl
 * @param string $endpoint
 * @return string
 */
function getApiSecurityBasedError(string $baseUrl, string $endpoint): string
{
	$message = 'The server has blocked the request to the API <code>' . $endpoint . '</code> endpoint.';
	$message .= '<br><br>';
	$message .= 'Please set a configuration that authorizes all requests that have ';
	$message .= '<code>' . str($baseUrl)->finish('/') . '</code> as URL base. ';
	$message .= '<br>';
	$message .= 'Or if a proxy is needed to communicate with your server you have to set it with its port separated by <code>:</code> in the <code>/.env</code> file with the variable <code>APP_API_PROXY</code> like this:';
	$message .= '<ul>';
	$message .= '<li><code>APP_API_PROXY=proxy-host:proxy-port</code></li>';
	$message .= '<li>or <code>APP_API_PROXY=proxy-ip-address:proxy-port</code></li>';
	$message .= '<li>or <code>APP_API_PROXY=http://username:password@proxy-ip-address:proxy-port</code></li>';
	$message .= '</ul>';
	
	return $message;
}

/**
 * @param string $endpoint
 * @param $body
 * @return string
 */
function getApiResponseTypeError(string $endpoint, $body): string
{
	$strippedBody = null;
	if (!empty($body) && is_string($body)) {
		$strippedBody = strip_tags($body);
	}
	
	$message = 'The API response for "' . $endpoint . '" request failed.';
	if (!empty($strippedBody) && mb_strlen($strippedBody) <= 1000) {
		$message .= '<br><br><h5>Response:</h5>' . $strippedBody;
	}
	
	return $message;
}
