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

namespace App\Helpers\Response;

use App\Http\Resources\EmptyResource;
use App\Http\Resources\EmptyCollection;
use Error;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class Api
{
	/**
	 * Return generic json response with the given data.
	 *
	 * @param array $data
	 * @param int $statusCode
	 * @param array $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function json(array $data = [], int $statusCode = 200, array $headers = []): \Illuminate\Http\JsonResponse
	{
		// https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource
		
		$result = $this->parseGivenData($data, $statusCode, $headers);
		$result['headers'] = addContentTypeHeader('application/json', $result['headers']);
		
		try {
			return response()->json(
				$result['content'],
				$result['statusCode'],
				$result['headers'],
				JSON_UNESCAPED_UNICODE
			);
		} catch (\Throwable $e) {
			return $this->internalError($e->getMessage());
		}
	}
	
	/**
	 * @param array $data
	 * @param int $statusCode
	 * @param array $headers
	 * @return array
	 */
	private function parseGivenData(array $data = [], int $statusCode = 200, array $headers = []): array
	{
		$responseStructure = [
			'success' => $data['success'],
			'message' => $data['message'] ?? null,
			'result'  => $data['result'] ?? null,
		];
		
		if (isset($data['extra'])) {
			$responseStructure['extra'] = $data['extra'];
		}
		
		if (isset($data['errors'])) {
			$responseStructure['errors'] = $data['errors'];
		}
		
		if (isset($data['status'])) {
			$statusCode = $data['status'];
		}
		
		// NOTE: 'bootstrap-fileinput' need 'error' (text) element & the optional 'errorkeys' (array) element
		if (isset($data['error'])) {
			$responseStructure['error'] = $data['error'];
		}
		
		if (isset($data['exception']) && ($data['exception'] instanceof Error || $data['exception'] instanceof Exception)) {
			if (config('app.env') !== 'production') {
				$responseStructure['exception'] = [
					'message' => $data['exception']->getMessage(),
					'file'    => $data['exception']->getFile(),
					'line'    => $data['exception']->getLine(),
					'code'    => $data['exception']->getCode(),
					'trace'   => $data['exception']->getTrace(),
				];
			}
			
			if ($statusCode === 200) {
				$statusCode = 500;
			}
		}
		
		if ($data['success'] === false) {
			if (isset($data['error_code'])) {
				$responseStructure['error_code'] = $data['error_code'];
			} else {
				$responseStructure['error_code'] = 1;
			}
		}
		
		return ['content' => $responseStructure, 'statusCode' => $statusCode, 'headers' => $headers];
	}
	
	/**
	 * @param \Illuminate\Http\Resources\Json\JsonResource $resource
	 * @param string|null $message
	 * @param int $statusCode
	 * @param array $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function withResource(
		JsonResource $resource,
		?string      $message = null,
		int          $statusCode = 200,
		array        $headers = []
	): \Illuminate\Http\JsonResponse
	{
		// https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource
		
		return $this->json([
			'success' => true,
			'result'  => $resource,
			'message' => $message,
		], $statusCode, $headers);
	}
	
	/**
	 * @param \Illuminate\Http\Resources\Json\ResourceCollection $resourceCollection
	 * @param string|null $message
	 * @param int $statusCode
	 * @param array $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function withCollection(
		ResourceCollection $resourceCollection,
		?string            $message = null,
		int                $statusCode = 200,
		array              $headers = []
	): \Illuminate\Http\JsonResponse
	{
		// https://laracasts.com/discuss/channels/laravel/pagination-data-missing-from-api-resource
		
		return $this->json([
			'success' => true,
			'result'  => $resourceCollection->response()->getData(),
			'message' => $message,
		], $statusCode, $headers);
	}
	
	/**
	 * Respond with success.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function success(?string $message = ''): \Illuminate\Http\JsonResponse
	{
		return $this->json(['success' => true, 'message' => $message]);
	}
	
	/**
	 * Respond with error.
	 *
	 * @param $message
	 * @param int $statusCode
	 * @param \Exception|null $exception
	 * @param int $error_code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function error(
		$message,
		int $statusCode = 400,
		Exception $exception = null,
		int $error_code = 1
	): \Illuminate\Http\JsonResponse
	{
		return $this->json([
			'success'    => false,
			'message'    => $message ?? 'There was an internal error, Pls try again later',
			'exception'  => $exception,
			'error_code' => $error_code,
		], $statusCode);
	}
	
	/**
	 * Respond with created.
	 *
	 * @param $data
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function created($data): \Illuminate\Http\JsonResponse
	{
		return $this->json($data, Response::HTTP_CREATED);
	}
	
	/**
	 * Respond with update.
	 *
	 * @param $data
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updated($data): \Illuminate\Http\JsonResponse
	{
		return $this->json($data, 200);
	}
	
	/**
	 * Respond with no content.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function noContent(?string $message = 'No Content Found'): \Illuminate\Http\JsonResponse
	{
		return $this->json(['success' => false, 'message' => $message], 200);
	}
	
	/**
	 * Respond with no content.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function noContentResource(?string $message = 'No Content Found'): \Illuminate\Http\JsonResponse
	{
		return $this->withResource(new EmptyResource([]), $message);
	}
	
	/**
	 * Respond with no content.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function noContentCollection(?string $message = 'No Content Found'): \Illuminate\Http\JsonResponse
	{
		return $this->withCollection(new EmptyCollection([]), $message);
	}
	
	/**
	 * Respond with unauthorized.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function unauthorized(?string $message = 'Unauthorized'): \Illuminate\Http\JsonResponse
	{
		return $this->error($message, Response::HTTP_UNAUTHORIZED);
	}
	
	/**
	 * Respond with forbidden.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function forbidden(?string $message = 'Forbidden'): \Illuminate\Http\JsonResponse
	{
		return $this->error($message, Response::HTTP_FORBIDDEN);
	}
	
	/**
	 * Respond with not found.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function notFound(?string $message = 'Not Found'): \Illuminate\Http\JsonResponse
	{
		return $this->error($message, 404);
	}
	
	/**
	 * Respond with internal error.
	 *
	 * @param string|null $message
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function internalError(?string $message = 'Internal Error'): \Illuminate\Http\JsonResponse
	{
		return $this->error($message, 500);
	}
	
	/**
	 * @param \Illuminate\Validation\ValidationException $exception
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function validationErrors(ValidationException $exception): \Illuminate\Http\JsonResponse
	{
		return $this->json([
			'success' => false,
			'message' => $exception->getMessage(),
			'errors'  => $exception->errors(),
		], Response::HTTP_UNPROCESSABLE_ENTITY);
	}
}
