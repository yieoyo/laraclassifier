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

namespace App\Models;

use App\Helpers\Date;
use App\Helpers\Number;
use App\Helpers\RemoveFromString;
use App\Models\Post\ReviewsPlugin;
use App\Models\Post\SimilarByCategory;
use App\Models\Post\SimilarByLocation;
use App\Models\Scopes\LocalizedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\Traits\Common\HasCountryCodeColumn;
use App\Models\Traits\PostTrait;
use App\Observers\PostObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Spatie\Feed\Feedable;

class Post extends BaseModel implements Feedable
{
	use Crud, HasCountryCodeColumn, Notifiable, HasFactory;
	use PostTrait;
	use SimilarByCategory, SimilarByLocation;
	use ReviewsPlugin;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'posts';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	protected $appends = [
		'reference',
		'slug',
		'url',
		'phone_intl',
		'created_at_formatted',
		'user_photo_url',
		'country_flag_url',
		'count_pictures',
		'picture', /* Main Picture */
		'picture_url',
		'picture_url_small',
		'picture_url_medium',
		'picture_url_big',
		'price_label',
		'price_formatted',
		'visits_formatted',
		'distance_info',
	];
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = true;
	
	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array
	 */
	protected $guarded = ['id'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'country_code',
		'user_id',
		'payment_id',
		'category_id',
		'post_type_id',
		'title',
		'description',
		'tags',
		'price',
		'currency_code',
		'negotiable',
		'contact_name',
		'auth_field',
		'email',
		'phone',
		'phone_national',
		'phone_country',
		'phone_hidden',
		'address',
		'city_id',
		'lat',
		'lon',
		'create_from_ip',
		'latest_update_ip',
		'visits',
		'tmp_token',
		'email_token',
		'phone_token',
		'email_verified_at',
		'phone_verified_at',
		'accept_terms',
		'accept_marketing_offers',
		'is_permanent',
		'reviewed_at',
		'featured',
		'archived_at',
		'archived_manually_at',
		'deletion_mail_sent_at',
		'fb_profile',
		'partner',
		'created_at',
		'updated_at',
	];
	
	/**
	 * The attributes that should be hidden for arrays
	 *
	 * @var array
	 */
	// protected $hidden = [];
	
	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'email_verified_at' => 'datetime',
		'phone_verified_at' => 'datetime',
		'created_at'        => 'datetime',
		'updated_at'        => 'datetime',
		'deleted_at'        => 'datetime',
		'reviewed_at'       => 'datetime',
		'archived_at'       => 'datetime',
	];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		Post::observe(PostObserver::class);
		
		static::addGlobalScope(new VerifiedScope());
		static::addGlobalScope(new ReviewedScope());
		static::addGlobalScope(new LocalizedScope());
	}
	
	public function routeNotificationForMail()
	{
		return $this->email;
	}
	
	public function routeNotificationForVonage()
	{
		$phone = phoneE164($this->phone, $this->phone_country);
		
		return setPhoneSign($phone, 'vonage');
	}
	
	public function routeNotificationForTwilio()
	{
		$phone = phoneE164($this->phone, $this->phone_country);
		
		return setPhoneSign($phone, 'twilio');
	}
	
	/*
	|--------------------------------------------------------------------------
	| QUERIES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function postType()
	{
		return $this->belongsTo(PostType::class, 'post_type_id');
	}
	
	public function category()
	{
		return $this->belongsTo(Category::class, 'category_id');
	}
	
	public function city()
	{
		return $this->belongsTo(City::class, 'city_id');
	}
	
	public function currency()
	{
		return $this->belongsTo(Currency::class, 'currency_code', 'code');
	}
	
	/*
	 * The first valid payment (Covers the validity period).
	 * Its activation needs to be checked programmably (if needed).
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function possiblePayment()
	{
		return $this->morphOne(Payment::class, 'payable')->withoutGlobalScope(StrictActiveScope::class)->orderBy('id');
	}
	
	/*
	 * The first valid & active payment (Covers the validity period & is active)
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function payment()
	{
		return $this->morphOne(Payment::class, 'payable')->orderBy('id');
	}
	
	/*
	 * The first valid & active payment that is manually created
	 * NOTE: Used in the ListingsPurge command in cron job
	 */
	public function paymentNotManuallyCreated()
	{
		return $this->morphOne(Payment::class, 'payable')->notManuallyCreated()->orderBy('id');
	}
	
	/*
	 * The ending later valid (or on hold) active payment (Covers the validity period & is active)
	 * This is useful to calculate the starting period to allow payable to have multiple valid & active payments
	 */
	public function paymentEndingLater()
	{
		return $this->morphOne(Payment::class, 'payable')
			->withoutGlobalScope(ValidPeriodScope::class)
			->where(function ($q) {
				$q->where(fn ($q) => $q->valid())->orWhere(fn ($q) => $q->onHold());
			})
			->orderByDesc('period_end');
	}
	
	/*
	 * Get all the listing payments
	 */
	public function payments()
	{
		return $this->morphMany(Payment::class, 'payable');
	}
	
	public function pictures()
	{
		return $this->hasMany(Picture::class, 'post_id')->orderBy('position')->orderByDesc('id');
	}
	
	public function savedByLoggedUser()
	{
		$guard = isFromApi() ? 'sanctum' : null;
		$userId = auth($guard)->user()?->getAuthIdentifier() ?? '-1';
		
		return $this->hasMany(SavedPost::class, 'post_id')->where('user_id', $userId);
	}
	
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	public function postValues()
	{
		return $this->hasMany(PostValue::class, 'post_id');
	}
	
	public function subscription()
	{
		return $this->belongsTo(Payment::class, 'payment_id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeVerified($builder)
	{
		$builder->where(function ($query) {
			$query->whereNotNull('email_verified_at')->whereNotNull('phone_verified_at');
		});
		
		if (config('settings.single.listings_review_activation')) {
			$builder->whereNotNull('reviewed_at');
		}
		
		return $builder;
	}
	
	public function scopeUnverified($builder)
	{
		$builder->where(function ($query) {
			$query->whereNull('email_verified_at')->orWhereNull('phone_verified_at');
		});
		
		if (config('settings.single.listings_review_activation')) {
			$builder->orWhereNull('reviewed_at');
		}
		
		return $builder;
	}
	
	public function scopeArchived($builder)
	{
		return $builder->whereNotNull('archived_at');
	}
	
	public function scopeUnarchived($builder)
	{
		return $builder->whereNull('archived_at');
	}
	
	public function scopeReviewed($builder)
	{
		if (config('settings.single.listings_review_activation')) {
			return $builder->whereNotNull('reviewed_at');
		} else {
			return $builder;
		}
	}
	
	public function scopeUnreviewed($builder)
	{
		if (config('settings.single.listings_review_activation')) {
			return $builder->whereNull('reviewed_at');
		} else {
			return $builder;
		}
	}
	
	public function scopeWithCountryFix($builder)
	{
		// Check the Domain Mapping Plugin
		if (config('plugins.domainmapping.installed')) {
			return $builder->where('country_code', config('country.code'));
		} else {
			return $builder;
		}
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function reference(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = $this->id ?? null;
				if (empty($value)) {
					return $value;
				}
				
				return hashId($value, false, false);
			},
		);
	}
	
	protected function visitsFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$number = (int)($this->visits ?? 0);
				$shortNumber = Number::short($number);
				
				$value = $shortNumber;
				$value .= ' ';
				$value .= trans_choice('global.count_views', getPlural($number), [], config('app.locale'));
				
				return $value;
			},
		);
	}
	
	protected function createdAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function updatedAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function deletedAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (empty($value)) {
					return null;
				}
				
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function createdAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$createdAt = $this->attributes['created_at'] ?? null;
				if (empty($createdAt)) {
					return null;
				}
				
				$value = new Carbon($createdAt);
				$value->timezone(Date::getAppTimeZone());
				
				return Date::formatFormNow($value);
			},
		);
	}
	
	protected function deletionMailSentAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (empty($value)) {
					return null;
				}
				
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function userPhotoUrl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				// Default Photo
				$defaultPhotoUrl = imgUrl(config('larapen.core.avatar.default'));
				
				// If the relation is not loaded through the Eloquent 'with()' method,
				// then don't make an additional query. So the default value will be returned.
				if (!$this->relationLoaded('user')) {
					return $defaultPhotoUrl;
				}
				
				$photoUrl = $this->user?->photo_url ?? null;
				
				return !empty($photoUrl) ? $photoUrl : $defaultPhotoUrl;
			},
		);
	}
	
	protected function email(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('user')) {
					return $value;
				}
				
				if (isAdminPanel()) {
					if (
						isDemoDomain()
						&& request()->segment(2) != 'password'
					) {
						if (auth()->check()) {
							if (auth()->user()->getAuthIdentifier() != 1) {
								if (isset($this->phone_token)) {
									if ($this->phone_token == 'demoFaker') {
										return $value;
									}
								}
								$value = email_prefix_mask($value);
							}
						}
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function phoneCountry(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$countryCode = $this->country_code ?? config('country.code');
				
				return !empty($value) ? $value : $countryCode;
			},
		);
	}
	
	protected function phone(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return phoneE164($value, $this->phone_country);
			},
		);
	}
	
	protected function phoneNational(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = !empty($value) ? $value : $this->phone;
				
				return phoneNational($value, $this->phone_country);
			},
		);
	}
	
	protected function phoneIntl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = !empty($this->phone_national)
					? $this->phone_national
					: $this->phone;
				
				if ($this->phone_country == config('country.code')) {
					return phoneNational($value, $this->phone_country);
				}
				
				return phoneIntl($value, $this->phone_country);
			},
		);
	}
	
	protected function title(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = mb_ucfirst($value);
				$cleanedValue = RemoveFromString::contactInfo($value, false, true);
				
				if (!$this->relationLoaded('user')) {
					return $cleanedValue;
				}
				
				if (!isAdminPanel()) {
					if (!empty($this->user)) {
						if (!$this->user->hasAllPermissions(Permission::getStaffPermissions())) {
							$value = $cleanedValue;
						}
					} else {
						$value = $cleanedValue;
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function slug(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = (is_null($value) && isset($this->title)) ? $this->title : $value;
				
				$value = strip_non_utf($value);
				$value = slugify($value);
				
				// To prevent 404 error when the slug starts by a banned slug/prefix,
				// Add a tilde (~) as prefix to it.
				$bannedSlugs = regexSimilarRoutesPrefixes();
				foreach ($bannedSlugs as $bannedSlug) {
					if (str_starts_with($value, $bannedSlug)) {
						$value = '~' . $value;
						break;
					}
				}
				
				return $value;
			},
		);
	}
	
	/*
	 * For API calls, to allow listing sharing
	 */
	protected function url(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->id) && isset($this->title)) {
					$path = str_replace(
						['{slug}', '{hashableId}', '{id}'],
						[$this->slug, hashId($this->id), $this->id],
						config('routes.post')
					);
				} else {
					$path = '#';
				}
				
				if (config('plugins.domainmapping.installed')) {
					$url = dmUrl($this->country_code, $path);
				} else {
					$url = url($path);
				}
				
				return $url;
			},
		);
	}
	
	protected function contactName(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => mb_ucwords($value),
		);
	}
	
	protected function description(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isAdminPanel()) {
					return $value;
				}
				
				$cleanedValue = RemoveFromString::contactInfo($value, false, true);
				
				if (!$this->relationLoaded('user')) {
					$value = $cleanedValue;
				} else {
					if (!empty($this->user)) {
						if (!$this->user->hasAllPermissions(Permission::getStaffPermissions())) {
							$value = $cleanedValue;
						}
					} else {
						$value = $cleanedValue;
					}
				}
				
				$apiValue = (doesRequestIsFromWebApp()) ? transformDescription($value) : str_strip(strip_tags($value));
				
				return isFromApi() ? $apiValue : $value;
			},
		);
	}
	
	protected function tags(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => tagCleaner($value, true),
			set: function ($value) {
				if (is_array($value)) {
					$value = implode(',', $value);
				}
				
				return (!empty($value)) ? mb_strtolower($value) : $value;
			},
		);
	}
	
	protected function countryFlagUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->country_code);
			},
		);
	}
	
	protected function countPictures(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('pictures')) {
					return 0;
				}
				
				try {
					return $this->pictures->count();
				} catch (\Throwable $e) {
					return 0;
				}
			},
		);
	}
	
	protected function picture(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('pictures')) {
					return $this->getDefaultImg();
				}
				
				try {
					return $this->pictures->get(0)->filename;
				} catch (\Throwable $e) {
					return $this->getDefaultImg();
				}
			},
		);
	}
	
	protected function pictureUrl(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getMainPictureUrl(),
		);
	}
	
	protected function pictureUrlSmall(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getMainPictureUrl('small'),
		);
	}
	
	protected function pictureUrlMedium(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getMainPictureUrl('medium'),
		);
	}
	
	protected function pictureUrlBig(): Attribute
	{
		return Attribute::make(
			get: fn () => $this->getMainPictureUrl('big'),
		);
	}
	
	protected function priceLabel(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$defaultLabel = t('price') . ':';
				
				if (!$this->relationLoaded('category')) {
					return $defaultLabel;
				}
				
				$categoryType = $this->category?->type ?? null;
				
				$isJob = (in_array($categoryType, ['job-offer', 'job-search']));
				$isRent = ($categoryType == 'rent');
				$isNotSalable = ($categoryType == 'not-salable');
				
				$result = match (true) {
					$isJob => t('Salary') . ':',
					$isRent => t('Rent') . ':',
					$isNotSalable => null,
					default => $defaultLabel,
				};
				
				return (string)$result;
			},
		);
	}
	
	protected function priceFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$defaultValue = t('Contact us');
				
				if (config('settings.list.hide_category')) {
					return $this->priceFormattedWithoutCategory($defaultValue);
				}
				
				// Relation with Category
				if (!$this->relationLoaded('category')) {
					return $this->priceFormattedWithoutCategory($defaultValue);
				}
				
				return $this->priceFormattedWithCategory($defaultValue);
			},
		);
	}
	
	protected function negotiable(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('category')) {
					return 0;
				}
				
				$categoryType = $this->category?->type ?? null;
				$isNotSalable = ($categoryType == 'not-salable');
				
				return $isNotSalable ? 0 : $value;
			},
		);
	}
	
	protected function distanceInfo(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('city')) {
					return null;
				}
				
				if (!isset($this->distance)) {
					return null;
				}
				
				if (!is_numeric($this->distance)) {
					return null;
				}
				
				return round($this->distance, 2) . getDistanceUnit();
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	private function getMainPictureUrl(?string $size = null): ?string
	{
		if (!$this->relationLoaded('pictures')) {
			return $this->getDefaultImgUrl();
		}
		
		try {
			$size = !empty($size) ? '_' . $size : '';
			$pictureUrl = $this->pictures->get(0)->{'filename_url' . $size};
			
			return is_string($pictureUrl) ? $pictureUrl : null;
		} catch (\Throwable $e) {
			return $this->getDefaultImgUrl();
		}
	}
	
	private function getDefaultImg(): ?string
	{
		$defaultImg = config('larapen.core.picture.default');
		
		return (is_string($defaultImg)) ? $defaultImg : null;
	}
	
	private function getDefaultImgUrl(): string
	{
		return imgUrl($this->getDefaultImg());
	}
	
	private function priceFormattedWithCategory(?string $defaultValue): string
	{
		$categoryType = $this->category?->type ?? null;
		$price = $this->price ?? null;
		
		$isNotSalable = ($categoryType == 'not-salable');
		$isNotFree = (is_numeric($price) && $price > 0);
		$isFree = (is_numeric($price) && $price == 0);
		
		// Relation with Currency
		$currency = [];
		if ($this->relationLoaded('currency')) {
			if (!empty($this->currency)) {
				$currency = $this->currency->toArray();
			}
		}
		
		$result = match (true) {
			$isNotSalable => null,
			default => match (true) {
				$isNotFree => Number::money($price, $currency),
				$isFree => t('free_as_price'),
				default => $defaultValue,
			},
		};
		
		return (string)$result;
	}
	
	private function priceFormattedWithoutCategory(?string $defaultValue): string
	{
		$price = $this->price ?? null;
		
		$isNotSalable = false; // @todo: Save this information in the 'posts' table
		$isNotFree = (is_numeric($price) && $price > 0);
		$isFree = (is_numeric($price) && $price == 0);
		
		// Relation with Currency
		$currency = [];
		if ($this->relationLoaded('currency')) {
			if (!empty($this->currency)) {
				$currency = $this->currency->toArray();
			}
		}
		
		$result = match (true) {
			$isNotSalable => null,
			default => match (true) {
				$isNotFree => Number::money($price, $currency),
				$isFree => t('free_as_price'),
				default => $defaultValue,
			},
		};
		
		return (string)$result;
	}
}
