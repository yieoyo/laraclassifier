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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps;

use App\Helpers\Files\TmpUpload;
use App\Helpers\Referrer;
use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Payment\HasPaymentTrigger;
use App\Http\Controllers\Api\Payment\Promotion\SingleStepPayment;
use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Web\Public\Auth\Traits\VerificationTrait;
use App\Http\Controllers\Web\Public\Payment\HasPaymentRedirection;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create\ClearTmpInputTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create\SubmitTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits\PricingPageUrlTrait;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Requests\Front\PhotoRequest;
use App\Http\Requests\Front\PostRequest;
use App\Models\CategoryField;
use App\Models\Post;
use App\Models\Scopes\VerifiedScope;
use App\Http\Controllers\Web\Public\FrontController;
use App\Models\Scopes\ReviewedScope;
use App\Observers\Traits\PictureTrait;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class CreateController extends FrontController
{
	use VerificationTrait;
	use HasPaymentReferrers;
	use WizardTrait;
	use PricingPageUrlTrait;
	use PictureTrait, ClearTmpInputTrait;
	use SubmitTrait;
	use HasPaymentTrigger, SingleStepPayment, HasPaymentRedirection;
	
	protected string $baseUrl = '/posts/create';
	protected string $cfTmpUploadDir = 'temporary';
	protected string $tmpUploadDir = 'temporary';
	
	/**
	 * CreateController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Check if guests can post Listings
		if (config('settings.single.guest_can_submit_listings') != '1') {
			$this->middleware('auth');
		}
		
		$this->middleware(function ($request, $next) {
			$this->commonQueries();
			
			return $next($request);
		});
		
		$this->baseUrl = url($this->baseUrl);
	}
	
	/**
	 * @return void
	 */
	public function commonQueries(): void
	{
		$this->getPaymentReferrersData();
		$this->setPaymentSettingsForPromotion();
		
		if (config('settings.single.show_listing_type')) {
			$postTypes = Referrer::getPostTypes($this->cacheExpiration);
			view()->share('postTypes', $postTypes);
		}
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('create');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
	}
	
	/**
	 * Checking for the current step
	 *
	 * @param Request $request
	 * @return int
	 */
	public function step(Request $request): int
	{
		if ($request->query('error') == 'paymentCancelled') {
			if ($request->session()->has('postId')) {
				$request->session()->forget('postId');
			}
		}
		
		$postId = $request->session()->get('postId');
		
		$step = 0;
		
		$data = $request->session()->get('postInput');
		if (isset($data) || !empty($postId)) {
			$step = 1;
		} else {
			return $step;
		}
		
		$data = $request->session()->get('picturesInput');
		if (isset($data) || !empty($postId)) {
			$step = 2;
		} else {
			return $step;
		}
		
		$data = $request->session()->get('paymentInput');
		if (isset($data) || !empty($postId)) {
			$step = 3;
		} else {
			return $step;
		}
		
		return $step;
	}
	
	/**
	 * Post's Step
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getPostStep(Request $request)
	{
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Check if the form type is 'Single-Step Form' and make redirection to it (permanently).
		$isSingleStepFormEnabled = (config('settings.single.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			$url = url('create');
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Create an unique temporary ID
		if (!$request->session()->has('cfUid')) {
			$request->session()->put('cfUid', 'cf-' . uniqueCode(9));
		}
		
		$this->shareWizardMenu($request);
		
		$postInput = $request->session()->get('postInput');
		
		return appView('post.createOrEdit.multiSteps.create', compact('postInput'));
	}
	
	/**
	 * Post's Step (POST)
	 *
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postPostStep(PostRequest $request)
	{
		$postInput = $request->all();
		
		// Use unique ID to store post's pictures
		if ($request->session()->has('cfUid')) {
			$this->cfTmpUploadDir = $this->cfTmpUploadDir . '/' . $request->session()->get('cfUid');
		}
		
		// Save uploaded files
		// Get Category's Fields details
		$fields = CategoryField::getFields($request->input('category_id'));
		if ($fields->count() > 0) {
			foreach ($fields as $field) {
				if ($field->type == 'file') {
					if ($request->hasFile('cf.' . $field->id)) {
						// Get the file
						$file = $request->file('cf.' . $field->id);
						
						// Check if the file is valid
						if (!$file->isValid()) {
							continue;
						}
						
						$postInput['cf'][$field->id] = TmpUpload::file($this->cfTmpUploadDir, $file);
					}
				}
			}
		}
		
		$request->session()->put('postInput', $postInput);
		
		// Get the next URL
		$nextUrl = url('posts/create/photos');
		$nextUrl = qsUrl($nextUrl, request()->only(['package']), null, false);
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * Pictures' Step
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getPicturesStep(Request $request)
	{
		if ($this->step($request) < 1) {
			$backUrl = url($this->baseUrl);
			$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
			
			return redirect()->to($backUrl);
		}
		
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$this->shareWizardMenu($request);
		
		// Create an unique temporary ID
		if (!$request->session()->has('uid')) {
			$request->session()->put('uid', uniqueCode(9));
		}
		
		$picturesInput = $request->session()->get('picturesInput');
		
		// Get next step URL
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			$nextUrl = url('posts/create/payment');
			$nextStepLabel = t('Next');
		} else {
			$nextUrl = url('posts/create/finish');
			$nextStepLabel = t('submit');
		}
		$nextUrl = qsUrl($nextUrl, request()->only(['package']), null, false);
		
		view()->share('nextStepUrl', $nextUrl);
		view()->share('nextStepLabel', $nextStepLabel);
		
		return appView('post.createOrEdit.multiSteps.photos.create', compact('picturesInput'));
	}
	
	/**
	 * Pictures' Step (POST)
	 *
	 * @param \App\Http\Requests\Front\PhotoRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function postPicturesStep(PhotoRequest $request)
	{
		if (!$request->ajax()) {
			if ($this->step($request) < 1) {
				$backUrl = url($this->baseUrl);
				$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
				
				return redirect()->to($backUrl);
			}
		}
		
		$savedPicturesInput = (array)$request->session()->get('picturesInput');
		
		// Get default/global pictures limit
		$defaultPicturesLimit = (int)config('settings.single.pictures_limit', 5);
		
		// Get the picture number limit
		$countExistingPictures = count($savedPicturesInput);
		$picturesLimit = $defaultPicturesLimit - $countExistingPictures;
		
		// Use unique ID to store post's pictures
		if ($request->session()->has('uid')) {
			$this->tmpUploadDir = $this->tmpUploadDir . '/' . $request->session()->get('uid');
		}
		
		$picturesInput = [];
		
		// Save uploaded files
		$files = $request->file('pictures');
		if (is_array($files) && count($files) > 0) {
			foreach ($files as $key => $file) {
				if (empty($file)) {
					continue;
				}
				
				$picturesInput[] = TmpUpload::image($this->tmpUploadDir, $file);
				
				// Check the picture number limit
				if ($key >= ($picturesLimit - 1)) {
					break;
				}
			}
			
			$newPicturesInput = array_merge($savedPicturesInput, $picturesInput);
			
			$request->session()->put('picturesInput', $newPicturesInput);
		}
		
		// AJAX response
		$data = [];
		$data['initialPreview'] = [];
		$data['initialPreviewConfig'] = [];
		if ($request->ajax()) {
			if (is_array($picturesInput) && count($picturesInput) > 0) {
				foreach ($picturesInput as $key => $filePath) {
					if (empty($filePath)) {
						continue;
					}
					
					// Get Deletion Url
					$initialPreviewConfigUrl = url('posts/create/photos/' . $key . '/delete');
					
					$pictureSize = (isset($this->disk) && $this->disk->exists($filePath))
						? (int)$this->disk->size($filePath)
						: 0;
					
					// Build Bootstrap-FileInput plugin's parameters
					$data['initialPreview'][] = imgUrl($filePath, 'medium');
					$data['initialPreviewConfig'][] = [
						'caption' => basename($filePath),
						'size'    => $pictureSize,
						'url'     => $initialPreviewConfigUrl,
						'key'     => $key,
						'extra'   => ['id' => $key],
					];
				}
			}
			
			return response()->json($data);
		}
		
		// Response
		// Get the next URL & button label
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			if (is_array($picturesInput) && count($picturesInput) > 0) {
				flash(t('The pictures have been updated'))->success();
			}
			
			$nextUrl = url('posts/create/payment');
			$nextUrl = qsUrl($nextUrl, request()->only(['package']), null, false);
			
			return redirect()->to($nextUrl);
		} else {
			$request->session()->flash('message', t('your_listing_is_created'));
			
			return $this->storeInputDataInDatabase($request);
		}
	}
	
	/**
	 * Payment's Step
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getPaymentStep(Request $request)
	{
		if ($this->step($request) < 2) {
			if (config('settings.single.picture_mandatory')) {
				$backUrl = url($this->baseUrl . '/photos');
				$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
				
				return redirect()->to($backUrl);
			}
		}
		
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$this->shareWizardMenu($request);
		
		$payment = $request->session()->get('paymentInput');
		
		return appView('post.createOrEdit.multiSteps.packages.create', compact('payment'));
	}
	
	/**
	 * Payment's Step (POST)
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postPaymentStep(PackageRequest $request)
	{
		if ($this->step($request) < 2) {
			if (config('settings.single.picture_mandatory')) {
				$backUrl = url($this->baseUrl . '/photos');
				$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
				
				return redirect()->to($backUrl);
			}
		}
		
		$request->session()->put('paymentInput', $request->validated());
		
		return $this->storeInputDataInDatabase($request);
	}
	
	/**
	 * End of the steps (Confirmation)
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function finish(Request $request)
	{
		if (!session()->has('message')) {
			return redirect()->to('/');
		}
		
		// Clear the step wizard
		if (session()->has('postId')) {
			// Get the Post
			$post = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('id', session('postId'))
				->first();
			
			abort_if(empty($post), 404, t('post_not_found'));
			
			session()->forget('postId');
		}
		
		// Redirect to the Post,
		// - If User is logged
		// - Or if Email and Phone verification option is not activated
		$doesVerificationIsDisabled = (config('settings.mail.email_verification') != 1 && config('settings.sms.phone_verification') != 1);
		if (auth()->check() || $doesVerificationIsDisabled) {
			if (!empty($post)) {
				flash(session('message'))->success();
				
				return redirect()->to(UrlGen::postUri($post));
			}
		}
		
		// Meta Tags
		MetaTag::set('title', session('message'));
		MetaTag::set('description', session('message'));
		
		return appView('post.createOrEdit.multiSteps.finish');
	}
	
	/**
	 * Remove a picture
	 *
	 * @param $pictureId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function removePicture($pictureId, Request $request)
	{
		$picturesInput = $request->session()->get('picturesInput');
		
		$message = t('The picture cannot be deleted');
		$result = ['status' => 0, 'message' => $message];
		
		if (isset($picturesInput[$pictureId])) {
			$res = true;
			try {
				$this->removePictureWithItsThumbs($picturesInput[$pictureId]);
			} catch (\Throwable $e) {
				$res = false;
			}
			
			if ($res) {
				unset($picturesInput[$pictureId]);
				
				if (!empty($picturesInput)) {
					$request->session()->put('picturesInput', $picturesInput);
				} else {
					$request->session()->forget('picturesInput');
				}
				
				$message = t('The picture has been deleted');
				
				if (request()->ajax()) {
					$result['status'] = 1;
					$result['message'] = $message;
					
					return response()->json($result);
				} else {
					flash($message)->success();
					
					return redirect()->back();
				}
			}
		}
		
		if (request()->ajax()) {
			return response()->json($result);
		} else {
			flash($message)->error();
			
			return redirect()->back();
		}
	}
	
	/**
	 * Reorder pictures
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function reorderPictures(Request $request)
	{
		$params = $request->input('params');
		
		$result = ['status' => 0];
		
		if (isset($params['stack']) && count($params['stack']) > 0) {
			// Use unique ID to store post's pictures
			if ($request->session()->has('uid')) {
				$this->tmpUploadDir = $this->tmpUploadDir . '/' . $request->session()->get('uid');
			}
			
			$newPicturesInput = [];
			$statusOk = false;
			foreach ($params['stack'] as $position => $item) {
				if (array_key_exists('caption', $item) && !empty($item['caption'])) {
					$newPicturesInput[] = $this->tmpUploadDir . '/' . $item['caption'];
					$statusOk = true;
				}
			}
			if ($statusOk) {
				$request->session()->put('picturesInput', $newPicturesInput);
				$result['status'] = 1;
				$result['message'] = t('Your picture has been reorder successfully');
			}
		}
		
		return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
	}
}
