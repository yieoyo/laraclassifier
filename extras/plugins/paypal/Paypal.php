<?php

namespace extras\plugins\paypal;

use App\Helpers\Number;
use App\Models\Post;
use App\Models\User;
use extras\plugins\paypal\app\Traits\InstallTrait;
use Illuminate\Http\Request;
use App\Helpers\Payment;
use App\Models\Package;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalHttp\HttpException;

class Paypal extends Payment
{
	use InstallTrait;
	
	/**
	 * Send Payment
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $resData
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public static function sendPayment(Request $request, Post|User $payable, array $resData = [])
	{
		// Set the right URLs
		parent::setRightUrls($resData);
		
		// Get the Package
		$package = Package::find($request->input('package_id'));
		
		// Don't make a payment if 'price' = 0 or null
		if (empty($package) || $package->price <= 0) {
			return redirect()->to(parent::$uri['previousUrl'] . '?error=package')->withInput();
		}
		
		// Don't make payment if selected Package is not compatible with payable (Post|User)
		if (!parent::isPayableCompatibleWithPackage($payable, $package)) {
			return redirect()->to(parent::$uri['previousUrl'] . '?error=packageType')->withInput();
		}
		
		// Get the amount
		$amount = Number::toFloat($package->price);
		
		$referenceId = md5($payable->id . $package->id . $package->type . uniqid('', true));
		
		// API Parameters
		$providerParams = [
			'intent'              => 'CAPTURE',
			'purchase_units'      => [
				[
					'reference_id' => $referenceId, // Unique value
					'description'  => str($package->name)->limit(122)->toString(), // Maximum length: 127.
					'amount'       => [
						'value'         => $amount,
						'currency_code' => $package->currency_code,
					],
				],
			],
			'application_context' => [
				'cancel_url' => parent::$uri['paymentCancelUrl'],
				'return_url' => parent::$uri['paymentReturnUrl'],
				'brand_name' => config('app.name'),
			],
		];
		
		// Local Parameters
		$localParams = parent::getLocalParameters($request, $payable, $package);
		
		// Try to make the Payment
		try {
			// Creating an environment
			$clientId = config('payment.paypal.clientId');
			$clientSecret = config('payment.paypal.clientSecret');
			
			if (config('payment.paypal.mode') == 'sandbox') {
				$environment = new SandboxEnvironment($clientId, $clientSecret);
			} else {
				$environment = new ProductionEnvironment($clientId, $clientSecret);
			}
			$client = new PayPalHttpClient($environment);
			
			// Creating an Order
			$request = new OrdersCreateRequest();
			$request->prefer('return=representation');
			$request->body = $providerParams;
			
			// Make the payment
			// Call API with your client and get a response for your call
			$response = $client->execute($request);
			
			// Payment by Credit Card when Card info is provided from the form.
			if (
				isset($response->statusCode)
				&& isset($response->result->status)
				&& $response->statusCode == 201
				&& $response->result->status == 'CREATED'
			) {
				
				// Save the Transaction ID at the Provider
				if (isset($response->result->id)) {
					$localParams['transaction_id'] = $response->result->id;
				}
				
				if (isset($response->result->links)) {
					$link = null;
					for ($i = 0; $i < count($response->result->links); ++$i) {
						$link = $response->result->links[$i];
						if ($link->rel == 'approve') {
							break;
						}
					}
					
					$isValidPaymentUrl = (
						!empty($link->href)
						&& is_string($link->href)
						&& str_starts_with(strtolower($link->href), 'http')
					);
					if ($isValidPaymentUrl) {
						// Save local parameters into session
						session()->put('params', $localParams);
						session()->save(); // If redirection to an external URL will be done using PHP header() function
						
						// Make the payment
						// Redirect the user to the PayPal payment page
						redirectUrl($link->href);
					}
				}
				
				$errorMessage = trans('paypal::messages.payment_page_url_not_found');
				
				// Apply actions when Payment failed
				return parent::paymentFailureActions($payable, $errorMessage);
				
			} else {
				
				// Apply actions when Payment failed
				return parent::paymentFailureActions($payable, 'Error during PayPal order creation.');
				
			}
		} catch (HttpException|\Throwable $e) {
			
			// Apply actions when API failed
			return parent::paymentApiErrorActions($payable, $e);
			
		}
	}
	
	/**
	 * NOTE: Not managed by a route.
	 * Check the method: \App\Http\Controllers\Api\Payment\MakePayment::paymentConfirmation()
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public static function paymentConfirmation(Post|User $payable, array $params)
	{
		// Replace patterns in URLs
		parent::$uri = parent::replacePatternsInUrls($payable, parent::$uri);
		
		// Get Charge ID
		$approvedOrderId = $params['transaction_id'] ?? null;
		
		// Try to make the Payment
		try {
			// Creating an environment
			$clientId = config('payment.paypal.clientId');
			$clientSecret = config('payment.paypal.clientSecret');
			
			if (config('payment.paypal.mode') == 'sandbox') {
				$environment = new SandboxEnvironment($clientId, $clientSecret);
			} else {
				$environment = new ProductionEnvironment($clientId, $clientSecret);
			}
			$client = new PayPalHttpClient($environment);
			
			// Capturing an Order
			// Before capture, Order should be approved by the buyer using the approval URL returned to the creation order response.
			$request = new OrdersCaptureRequest($approvedOrderId);
			$request->prefer('return=representation');
			
			// Make the payment
			// Call API with your client and get a response for your call
			$response = $client->execute($request);
			
			// Check the Payment
			if (
				isset($response->statusCode)
				&& isset($response->result->status)
				&& $response->statusCode == 201
				&& $response->result->status == 'COMPLETED'
			) {
				
				// Save the Transaction ID at the Provider
				if (isset($response->result->id)) {
					$params['transaction_id'] = $response->result->id;
				}
				
				// Apply actions after successful Payment
				return parent::paymentConfirmationActions($payable, $params);
				
			} else {
				
				// Apply actions when Payment failed
				return parent::paymentFailureActions($payable);
				
			}
		} catch (\Throwable $e) {
			
			// Apply actions when API failed
			return parent::paymentApiErrorActions($payable, $e);
			
		}
	}
}
