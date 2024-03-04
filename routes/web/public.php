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

use App\Http\Controllers\Web\Public\Account\CloseController;
use App\Http\Controllers\Web\Public\Account\EditController as AccountEditController;
use App\Http\Controllers\Web\Public\Account\MessagesController;
use App\Http\Controllers\Web\Public\Account\PostsController;
use App\Http\Controllers\Web\Public\Account\SavedSearchesController;
use App\Http\Controllers\Web\Public\Account\SubscriptionController;
use App\Http\Controllers\Web\Public\Account\TransactionsController;
use App\Http\Controllers\Web\Public\Ajax\CategoryController as AjaxCategoryController;
use App\Http\Controllers\Web\Public\Ajax\Location\AutoCompleteController;
use App\Http\Controllers\Web\Public\Ajax\Location\ModalController;
use App\Http\Controllers\Web\Public\Ajax\Location\SelectController;
use App\Http\Controllers\Web\Public\Ajax\PostController as AjaxPostController;
use App\Http\Controllers\Web\Public\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Public\Auth\LoginController;
use App\Http\Controllers\Web\Public\Auth\RegisterController;
use App\Http\Controllers\Web\Public\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Public\Auth\SocialController;
use App\Http\Controllers\Web\Public\CountriesController;
use App\Http\Controllers\Web\Public\FileController;
use App\Http\Controllers\Web\Public\HomeController;
use App\Http\Controllers\Web\Public\Locale\LocaleController;
use App\Http\Controllers\Web\Public\Page\ContactController;
use App\Http\Controllers\Web\Public\Page\PricingController;
use App\Http\Controllers\Web\Public\Page\CmsController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\CreateController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\EditController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\PaymentController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\PhotoController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\SingleStep\CreateController as SingleCreateController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\SingleStep\EditController as SingleEditController;
use App\Http\Controllers\Web\Public\Post\ShowController;
use App\Http\Controllers\Web\Public\Post\ReportController;
use App\Http\Controllers\Web\Public\Search\CategoryController;
use App\Http\Controllers\Web\Public\Search\CityController;
use App\Http\Controllers\Web\Public\Search\SearchController;
use App\Http\Controllers\Web\Public\Search\TagController;
use App\Http\Controllers\Web\Public\Search\UserController;
use App\Http\Controllers\Web\Public\SitemapController;
use App\Http\Controllers\Web\Public\SitemapsController;
use Illuminate\Support\Facades\Route;

// Select Language
Route::namespace('Locale')
	->group(function ($router) {
		Route::get('locale/{code}', [LocaleController::class, 'setLocale']);
	});

// FILES
Route::controller(FileController::class)
	->prefix('common')
	->group(function ($router) {
		Route::get('file', 'watchMediaContent');
		Route::get('js/fileinput/locales/{code}.js', 'bootstrapFileinputLocales');
		Route::get('js/intl-tel-input/countries.js', 'intlTelInputData');
		Route::get('css/style.css', 'cssStyle');
	});

if (!plugin_exists('domainmapping')) {
	// SITEMAPS (XML)
	Route::get('sitemaps.xml', [SitemapsController::class, 'getAllCountriesSitemapIndex']);
}

// Impersonate (As admin user, login as another user)
Route::middleware(['auth'])
	->group(function ($router) {
		Route::impersonate();
	});


// HOMEPAGE
if (!doesCountriesPageCanBeHomepage()) {
	Route::get('/', [HomeController::class, 'index']);
	Route::get(dynamicRoute('routes.countries'), [CountriesController::class, 'index']);
} else {
	Route::get('/', [CountriesController::class, 'index']);
}


// AUTH
Route::namespace('Auth')
	->middleware(['guest', 'no.http.cache'])
	->group(function ($router) {
		// Registration Routes...
		Route::controller(RegisterController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.register'), 'showRegistrationForm');
				Route::post(dynamicRoute('routes.register'), 'register');
				Route::get('register/finish', 'finish');
			});
		
		// Authentication Routes...
		Route::controller(LoginController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.login'), 'showLoginForm');
				Route::post(dynamicRoute('routes.login'), 'login');
			});
		
		// Forgot Password Routes...
		Route::controller(ForgotPasswordController::class)
			->group(function ($router) {
				Route::get('password/reset', 'showLinkRequestForm');
				Route::post('password/email', 'sendResetLink');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				$router->pattern('token', '.*');
				Route::get('password/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('password/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('password/verify/{field}/{token?}', 'verification');
				Route::post('password/verify/{field}/{token?}', 'verification');
			});
		
		Route::controller(ResetPasswordController::class)
			->group(function ($router) {
				// Reset Password using Token
				Route::get('password/token', 'showTokenRequestForm');
				Route::post('password/token', 'sendResetToken');
				
				// Reset Password using Link (Core Routes...)
				Route::get('password/reset/{token}', 'showResetForm');
				Route::post('password/reset', 'reset');
			});
		
		// Social Authentication
		Route::controller(SocialController::class)
			->group(function ($router) {
				$router->pattern('provider', 'facebook|linkedin|twitter-oauth-2|twitter|google');
				Route::get('auth/{provider}', 'redirectToProvider');
				Route::get('auth/{provider}/callback', 'handleProviderCallback');
			});
	});

Route::namespace('Auth')
	->group(function ($router) {
		// Email Address or Phone Number verification
		Route::controller(RegisterController::class)
			->group(function ($router) {
				$router->pattern('field', 'email|phone');
				Route::get('users/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('users/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('users/verify/{field}/{token?}', 'verification');
				Route::post('users/verify/{field}/{token?}', 'verification');
			});
		
		// User Logout
		Route::get(dynamicRoute('routes.logout'), [LoginController::class, 'logout']);
	});


// POSTS
Route::namespace('Post')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		$hidPrefix = config('larapen.core.hashableIdPrefix');
		if (is_string($hidPrefix) && !empty($hidPrefix)) {
			$router->pattern('hashableId', '([0-9]+)?(' . $hidPrefix . '[a-z0-9A-Z]{11})?');
		} else {
			$router->pattern('hashableId', '([0-9]+)?([a-z0-9A-Z]{11})?');
		}
		
		// $router->pattern('slug', '.*');
		$bannedSlugs = regexSimilarRoutesPrefixes();
		if (!empty($bannedSlugs)) {
			/*
			 * NOTE:
			 * '^(?!companies|users)$' : Don't match 'companies' or 'users'
			 * '^(?=.*)$'              : Match any character
			 * '^((?!\/).)*$'          : Match any character, but don't match string with '/'
			 */
			$router->pattern('slug', '^(?!' . implode('|', $bannedSlugs) . ')(?=.*)((?!\/).)*$');
		} else {
			$router->pattern('slug', '^(?=.*)((?!\/).)*$');
		}
		
		// SingleStep Listing creation
		Route::namespace('CreateOrEdit\SingleStep')
			->controller(SingleCreateController::class)
			->group(function ($router) {
				Route::get('create', 'getForm');
				Route::post('create', 'postForm');
				Route::get('create/finish', 'finish');
				
				// Payment Gateway Success & Cancel
				Route::get('create/payment/success', 'paymentConfirmation');
				Route::get('create/payment/cancel', 'paymentCancel');
				Route::post('create/payment/success', 'paymentConfirmation');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				Route::get('posts/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('posts/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('posts/verify/{field}/{token?}', 'verification');
				Route::post('posts/verify/{field}/{token?}', 'verification');
			});
		
		// MultiSteps Listing creation
		Route::namespace('CreateOrEdit\MultiSteps')
			->controller(CreateController::class)
			->group(function ($router) {
				Route::get('posts/create', 'getPostStep');
				Route::post('posts/create', 'postPostStep');
				Route::get('posts/create/photos', 'getPicturesStep');
				Route::post('posts/create/photos', 'postPicturesStep');
				Route::post('posts/create/photos/{photoId}/delete', 'removePicture');
				Route::post('posts/create/photos/reorder', 'reorderPictures');
				Route::get('posts/create/payment', 'getPaymentStep');
				Route::post('posts/create/payment', 'postPaymentStep');
				Route::post('posts/create/finish', 'finish');
				Route::get('posts/create/finish', 'finish');
				
				// Payment Gateway Success & Cancel
				Route::get('posts/create/payment/success', 'paymentConfirmation');
				Route::post('posts/create/payment/success', 'paymentConfirmation');
				Route::get('posts/create/payment/cancel', 'paymentCancel');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				Route::get('posts/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('posts/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('posts/verify/{field}/{token?}', 'verification');
				Route::post('posts/verify/{field}/{token?}', 'verification');
			});
		
		Route::middleware(['auth'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// SingleStep Listing edition
				Route::namespace('CreateOrEdit\SingleStep')
					->controller(SingleEditController::class)
					->group(function ($router) {
						Route::get('edit/{id}', 'getForm');
						Route::put('edit/{id}', 'postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('edit/{id}/payment/success', 'paymentConfirmation');
						Route::get('edit/{id}/payment/cancel', 'paymentCancel');
						Route::post('edit/{id}/payment/success', 'paymentConfirmation');
					});
				
				// MultiSteps Listing Edition
				Route::namespace('CreateOrEdit\MultiSteps')
					->group(function ($router) {
						Route::controller(EditController::class)
							->group(function ($router) {
								Route::get('posts/{id}/edit', 'getForm');
								Route::put('posts/{id}/edit', 'postForm');
							});
						Route::controller(PhotoController::class)
							->group(function ($router) {
								Route::get('posts/{id}/photos', 'getForm');
								Route::post('posts/{id}/photos', 'postForm');
								Route::post('posts/{id}/photos/{photoId}/delete', 'delete');
								Route::post('posts/{id}/photos/reorder', 'reorder');
							});
						Route::controller(PaymentController::class)
							->group(function ($router) {
								Route::get('posts/{id}/payment', 'getForm');
								Route::post('posts/{id}/payment', 'postForm');
								
								// Payment Gateway Success & Cancel
								Route::get('posts/{id}/payment/success', 'paymentConfirmation');
								Route::post('posts/{id}/payment/success', 'paymentConfirmation');
								Route::get('posts/{id}/payment/cancel', 'paymentCancel');
							});
					});
			});
		
		// Post's Details
		Route::get(dynamicRoute('routes.post'), [ShowController::class, 'index']);
		
		// Send report abuse
		Route::controller(ReportController::class)
			->group(function ($router) {
				Route::get('posts/{hashableId}/report', 'showReportForm');
				Route::post('posts/{hashableId}/report', 'sendReport');
			});
	});


// ACCOUNT
Route::namespace('Account')
	->prefix('account')
	->group(function ($router) {
		// Messenger
		// Contact Post's Author
		Route::post('messages/posts/{id}', [MessagesController::class, 'store']);
		
		Route::middleware(['auth', 'banned.user', 'no.http.cache'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// Users
				Route::controller(AccountEditController::class)
					->group(function ($router) {
						Route::get('/', 'index');
						Route::middleware(['impersonate.protect'])
							->group(function ($router) {
								Route::put('/', 'updateDetails');
								Route::put('settings', 'updateDetails');
								Route::put('photo', 'updatePhoto');
								Route::put('photo/delete', 'deletePhoto');
							});
					});
				Route::controller(CloseController::class)
					->group(function ($router) {
						Route::get('close', 'index');
						Route::middleware(['impersonate.protect'])
							->group(function ($router) {
								Route::post('close', 'submit');
							});
					});
				
				// Subscription
				Route::controller(SubscriptionController::class)
					->group(function ($router) {
						Route::get('subscription', 'getForm');
						Route::post('subscription', 'postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('{id}/payment/success', 'paymentConfirmation');
						Route::post('{id}/payment/success', 'paymentConfirmation');
						Route::get('{id}/payment/cancel', 'paymentCancel');
					});
				
				// Posts
				Route::controller(PostsController::class)
					->prefix('posts')
					->group(function ($router) {
						$router->pattern('pagePath', '(list|archived|pending-approval|favourite)+');
						Route::get('{pagePath}', 'getPage');
						Route::get('{pagePath}/{id}/delete', 'destroy');
						Route::post('{pagePath}/delete', 'destroy');
						
						Route::get('list/{id}/offline', 'index');
						Route::get('archived/{id}/repost', 'archivedPosts');
					});
				
				// Saved Searches
				Route::controller(SavedSearchesController::class)
					->prefix('saved-searches')
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::get('/', 'index');
						Route::get('{id}', 'show');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Messenger
				Route::controller(MessagesController::class)
					->prefix('messages')
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::post('check-new', 'checkNew');
						Route::get('/', 'index');
						Route::post('/', 'store');
						Route::get('{id}', 'show');
						Route::put('{id}', 'update');
						Route::get('{id}/actions', 'actions');
						Route::post('actions', 'actions');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Transactions
				Route::namespace('Transactions')
					->prefix('transactions')
					->group(function ($router) {
						Route::get('promotion', [TransactionsController::class, 'index']);
						Route::get('subscription', [TransactionsController::class, 'index']);
					});
			});
	});


// AJAX
Route::namespace('Ajax')
	->prefix('ajax')
	->group(function ($router) {
		Route::namespace('Location')
			->group(function ($router) {
				$router->pattern('countryCode', getCountryCodeRoutePattern());
				Route::post('countries/{countryCode}/cities/autocomplete', [AutoCompleteController::class, 'index']);
				Route::controller(SelectController::class)
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::get('countries/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::get('countries/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::get('countries/{countryCode}/cities/{id}', 'getSelectedCity');
					});
				Route::controller(ModalController::class)
					->group(function ($router) {
						Route::post('locations/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::post('locations/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::post('locations/{countryCode}/cities', 'getCities');
					});
			});
		Route::controller(AjaxCategoryController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				Route::post('categories/select', 'getCategoriesHtml');
				Route::post('categories/{id}/fields', 'getCustomFieldsHtml');
			});
		Route::controller(AjaxPostController::class)
			->group(function ($router) {
				Route::post('save/post', 'savePost');
				Route::post('save/search', 'saveSearch');
				Route::post('post/phone', 'getPhone');
			});
	});


// FEEDS
Route::feeds();


if (!plugin_exists('domainmapping')) {
	// SITEMAPS (XML)
	Route::controller(SitemapsController::class)
		->group(function ($router) {
			$router->pattern('countryCode', getCountryCodeRoutePattern());
			Route::get('{countryCode}/sitemaps.xml', 'getSitemapIndexByCountry');
			Route::get('{countryCode}/sitemaps/pages.xml', 'getPagesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/categories.xml', 'getCategoriesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/cities.xml', 'getCitiesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/posts.xml', 'getListingsSitemapByCountry');
		});
}


// PAGES
Route::namespace('Page')
	->group(function ($router) {
		Route::get(dynamicRoute('routes.pricing'), [PricingController::class, 'index']);
		Route::get(dynamicRoute('routes.pageBySlug'), [CmsController::class, 'index']);
		Route::controller(ContactController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.contact'), 'getForm');
				Route::post(dynamicRoute('routes.contact'), 'postForm');
			});
	});

// SITEMAP (HTML)
Route::get(dynamicRoute('routes.sitemap'), [SitemapController::class, 'index']);

// SEARCH
Route::namespace('Search')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		$router->pattern('username', '[a-zA-Z0-9]+');
		Route::get(dynamicRoute('routes.search'), [SearchController::class, 'index']);
		Route::get(dynamicRoute('routes.searchPostsByUserId'), [UserController::class, 'index']);
		Route::get(dynamicRoute('routes.searchPostsByUsername'), [UserController::class, 'profile']);
		Route::get(dynamicRoute('routes.searchPostsByTag'), [TagController::class, 'index']);
		Route::get(dynamicRoute('routes.searchPostsByCity'), [CityController::class, 'index']);
		Route::get(dynamicRoute('routes.searchPostsBySubCat'), [CategoryController::class, 'index']);
		Route::get(dynamicRoute('routes.searchPostsByCat'), [CategoryController::class, 'index']);
	});
