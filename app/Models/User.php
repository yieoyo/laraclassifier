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
use App\Helpers\Files\Storage\StorageDisk;
use App\Models\Scopes\LocalizedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Traits\Common\HasCountryCodeColumn;
use App\Models\Traits\UserTrait;
use App\Notifications\ResetPasswordNotification;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends BaseUser
{
	use Crud, HasRoles, HasCountryCodeColumn, HasApiTokens, Notifiable, HasFactory;
	use UserTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'users';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	// protected $primaryKey = 'id';
	protected $appends = [
		'phone_intl',
		'created_at_formatted',
		'photo_url',
		'original_updated_at',
		'original_last_activity',
		'p_is_online',
		'country_flag_url',
		'remaining_posts',
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
		'language_code',
		'user_type_id',
		'gender_id',
		'name',
		'photo',
		'about',
		'auth_field',
		'email',
		'phone',
		'phone_national',
		'phone_country',
		'phone_hidden',
		'username',
		'password',
		'remember_token',
		'can_be_impersonate',
		'disable_comments',
		'create_from_ip',
		'latest_update_ip',
		'provider',
		'provider_id',
		'email_token',
		'phone_token',
		'email_verified_at',
		'phone_verified_at',
		'accept_terms',
		'accept_marketing_offers',
		'time_zone',
		'featured',
		'blocked',
		'closed',
		'last_activity',
	];
	
	/**
	 * The attributes that should be hidden for arrays
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];
	
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
		'last_login_at'     => 'datetime',
		'deleted_at'        => 'datetime',
	];
	
	/**
	 * User constructor.
	 *
	 * @param array $attributes
	 */
	public function __construct(array $attributes = [])
	{
		if (str_contains(currentRouteAction(), 'Web\Install\\') || isAdminPanel()) {
			$this->fillable[] = 'is_admin';
		}
		
		parent::__construct($attributes);
	}
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		User::observe(UserObserver::class);
		
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
	
	/**
	 * Send the password reset notification.
	 * Note: Overrides the Laravel official version
	 *
	 * @param string $token
	 * @return void
	 */
	public function sendPasswordResetNotification($token)
	{
		// Get the right auth field
		$authField = request()->filled('auth_field') ? request()->input('auth_field') : null;
		$authField = (empty($authField)) ? ($this->auth_field ?? null) : $authField;
		$authField = (empty($authField) && request()->filled('email')) ? 'email' : $authField;
		$authField = (empty($authField) && request()->filled('phone')) ? 'phone' : $authField;
		$authField = (empty($authField)) ? getAuthField() : $authField;
		
		// Send the reset password notification
		try {
			$this->notify(new ResetPasswordNotification($this, $token, $authField));
		} catch (\Throwable $e) {
			if (!isFromApi()) {
				flash($e->getMessage())->error();
			} else {
				abort(500, $e->getMessage());
			}
		}
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function posts()
	{
		return $this->hasMany(Post::class, 'user_id')->orderByDesc('created_at');
	}
	
	public function postsInCountry()
	{
		return $this->hasMany(Post::class, 'user_id')->inCountry()->orderByDesc('created_at');
	}
	
	public function gender()
	{
		return $this->belongsTo(Gender::class, 'gender_id');
	}
	
	public function receivedThreads()
	{
		return $this->hasManyThrough(
			Thread::class,
			Post::class,
			'user_id', // Foreign key on the Listing table...
			'post_id', // Foreign key on the Thread table...
			'id',      // Local key on the User table...
			'id'       // Local key on the Listing table...
		);
	}
	
	public function threads()
	{
		return $this->hasManyThrough(
			Thread::class,
			ThreadMessage::class,
			'user_id', // Foreign key on the ThreadMessage table...
			'post_id', // Foreign key on the Thread table...
			'id',      // Local key on the User table...
			'id'       // Local key on the ThreadMessage table...
		);
	}
	
	public function savedPosts()
	{
		return $this->belongsToMany(Post::class, 'saved_posts', 'user_id', 'post_id');
	}
	
	public function savedSearch()
	{
		return $this->hasMany(SavedSearch::class, 'user_id');
	}
	
	public function userType()
	{
		return $this->belongsTo(UserType::class, 'user_type_id');
	}
	
	/*
	 * The first valid payment (Covers the validity period).
	 * Its activation will be checked programmably.
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function possiblePayment()
	{
		return $this->morphOne(Payment::class, 'payable')->withoutGlobalScope(StrictActiveScope::class)->orderBy('id');
	}
	
	/*
	 * The first valid & active subscription (Covers the validity period & is active)
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function payment()
	{
		return $this->morphOne(Payment::class, 'payable')->orderBy('id');
	}
	
	/*
	 * The first valid & active subscription that is manually created
	 * NOTE: Used in the UsersPurge command in cron job
	 */
	public function subscriptionNotManuallyCreated()
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
	 * Get all the user subscriptions (payments)
	 */
	public function subscriptions()
	{
		return $this->morphMany(Payment::class, 'payable');
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
		
		return $builder;
	}
	
	public function scopeUnverified($builder)
	{
		$builder->where(function ($query) {
			$query->whereNull('email_verified_at')->orWhereNull('phone_verified_at');
		});
		
		return $builder;
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
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
	
	protected function originalUpdatedAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return $this->getRawOriginal('updated_at');
			},
		);
	}
	
	protected function lastActivity(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function originalLastActivity(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return $this->getRawOriginal('last_activity');
			},
		);
	}
	
	protected function lastLoginAt(): Attribute
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
	
	protected function photoUrl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				// Default Photo
				$defaultPhotoUrl = imgUrl(config('larapen.core.avatar.default'));
				
				// Photo from User's account
				$userPhotoUrl = null;
				if (!empty($this->photo)) {
					$disk = StorageDisk::getDisk();
					if ($disk->exists($this->photo)) {
						$userPhotoUrl = imgUrl($this->photo, 'user');
					}
				}
				
				return !empty($userPhotoUrl) ? $userPhotoUrl : $defaultPhotoUrl;
			},
		);
	}
	
	protected function email(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
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
				$value = (!empty($this->phone_national)) ? $this->phone_national : $this->phone;
				
				if ($this->phone_country == config('country.code')) {
					return phoneNational($value, $this->phone_country);
				}
				
				return phoneIntl($value, $this->phone_country);
			},
		);
	}
	
	protected function name(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => mb_ucwords($value),
		);
	}
	
	protected function photo(): Attribute
	{
		return Attribute::make(
			set: function ($value, $attributes) {
				if (!is_string($value)) {
					return $value;
				}
				
				if ($value == url('/')) {
					return null;
				}
				
				// Retrieve current value without upload a new file
				if (str_starts_with($value, config('larapen.core.picture.default'))) {
					return null;
				}
				
				if (!str_starts_with($value, 'avatars/')) {
					if (empty($attributes['id']) || empty($attributes['country_code'])) {
						return null;
					}
					$destPath = 'avatars/' . strtolower($attributes['country_code']) . '/' . $attributes['id'];
					$value = $destPath . last(explode($destPath, $value));
				}
				
				return $value;
			},
		);
	}
	
	protected function pIsOnline(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$timeAgoFromNow = Carbon::now(Date::getAppTimeZone())->subMinutes(5);
				$isOnline = (
					!empty($this->getRawOriginal('last_activity'))
					&& $this->last_activity->gt($timeAgoFromNow)
				);
				
				// Allow only logged users to get the other users status
				$guard = isFromApi() ? 'sanctum' : null;
				
				return auth($guard)->check() ? $isOnline : false;
			},
		);
	}
	
	protected function countryFlagUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->country_code, 16);
			},
		);
	}
	
	/*
	 * Remaining Posts for the User (Without to apply the current subscription)
	 * - Need to use User::with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]),
	 *   to retrieve it like this: $user->remaining_posts
	 * - The Post Remaining for the User current subscription can be got by using:
	 *   User::with('payment', fn ($q) => $q->with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()]))
	 *   and retrieve it like this: $user->payment->remaining_posts
	 */
	protected function remainingPosts(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				// If the relation is not loaded through the Eloquent 'with()' method,
				// then don't make an additional query (to prevent performance issues).
				if (!$this->relationLoaded('posts')) {
					return null;
				}
				
				$postsLimit = (int)config('settings.single.listings_limit');
				try {
					$countPosts = $this->posts->count();
				} catch (\Throwable $e) {
					$countPosts = 0;
				}
				$remainingPosts = ($postsLimit >= $countPosts) ? $postsLimit - $countPosts : 0;
				
				return (int)$remainingPosts;
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
