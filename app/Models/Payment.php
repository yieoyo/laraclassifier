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
use App\Models\Scopes\LocalizedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Traits\PaymentTrait;
use App\Observers\PaymentObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

class Payment extends BaseModel
{
	use Crud, HasFactory;
	use PaymentTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'payments';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	// protected $primaryKey = 'id';
	protected $appends = [
		'interval',
		'started',
		'expired',
		'status',
		'period_start_formatted',
		'period_end_formatted',
		'canceled_at_formatted',
		'refunded_at_formatted',
		'created_at_formatted',
		'status_info',
		'starting_info',
		'expiry_info',
		'css_class_variant',
		'remaining_posts',
	];
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	// public $timestamps = false;
	
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
		'payable_id',
		'payable_type',
		'package_id',
		'payment_method_id',
		'transaction_id',
		'amount',
		'currency_code',
		'period_start',
		'period_end',
		'canceled_at',
		'refunded_at',
		'active',
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
		'period_start' => 'datetime',
		'period_end'   => 'datetime',
		'canceled_at'  => 'datetime',
		'refunded_at'  => 'datetime',
		'created_at'   => 'datetime',
		'updated_at'   => 'datetime',
	];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		Payment::observe(PaymentObserver::class);
		
		static::addGlobalScope(new ValidPeriodScope());
		static::addGlobalScope(new StrictActiveScope());
		static::addGlobalScope(new LocalizedScope());
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	/*
	 * Get the parent payable model (Post|User)
	 */
	public function payable()
	{
		return $this->morphTo('payable', 'payable_type', 'payable_id');
	}
	
	public function package()
	{
		return $this->belongsTo(Package::class, 'package_id');
	}
	
	public function paymentMethod()
	{
		return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
	}
	
	/*
	 * For subscriptions
	 * Get all the listings for the payment
	 */
	public function posts()
	{
		return $this->hasMany(Post::class, 'payment_id')->orderByDesc('id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopePromotion($builder)
	{
		return $builder->where('payable_type', Post::class);
	}
	
	public function scopeSubscription($builder)
	{
		return $builder->where('payable_type', User::class);
	}
	
	/*
	 * On hold payment(s) (The validity period will be started in the future)
	 */
	public function scopeOnHold($builder)
	{
		// Get the current Datetime
		$today = Carbon::now(Date::getAppTimeZone());
		
		return $builder->where(function ($query) use ($today) {
			$query->where('period_start', '>', $today)->where('period_end', '>', $today);
		})
			->notCanceled()
			->notRefunded();
	}
	
	/*
	 * Valid payment(s) (Covers the validity period)
	 */
	public function scopeValid($builder)
	{
		// Get the current Datetime
		$today = Carbon::now(Date::getAppTimeZone());
		
		return $builder->where(function ($query) use ($today) {
			$query->where('period_start', '<=', $today)->where('period_end', '>=', $today);
		})
			->notCanceled()
			->notRefunded();
	}
	
	/*
	 * Not valid payment(s) (Does not cover the validity period)
	 */
	public function scopeNotValid($builder)
	{
		// Get the current Datetime
		$today = Carbon::now(Date::getAppTimeZone());
		
		return $builder->where(function ($query) use ($today) {
			$query->where('period_end', '<', $today);
		})
			->orWhere(fn ($query) => $query->canceled())
			->orWhere(fn ($query) => $query->refunded());
	}
	
	/*
	 * Payment(s) manually created
	 */
	public function scopeManuallyCreated($builder)
	{
		return $builder->where(function ($query) {
			$query->where('transaction_id', 'featured');
		});
	}
	
	/*
	 * Payment(s) not manually created
	 */
	public function scopeNotManuallyCreated($builder)
	{
		return $builder->where(function ($query) {
			$query->where('transaction_id', '!=', 'featured')->orWhereNull('transaction_id');
		});
	}
	
	/*
	 * Canceled payment(s)
	 */
	public function scopeCanceled($builder)
	{
		return $builder->whereNotNull('canceled_at');
	}
	
	/*
	 * Not canceled payment(s)
	 */
	public function scopeNotCanceled($builder)
	{
		return $builder->whereNull('canceled_at');
	}
	
	/*
	 * Refunded payment(s)
	 */
	public function scopeRefunded($builder)
	{
		return $builder->whereNotNull('refunded_at');
	}
	
	/*
	 * Not refunded payment(s)
	 */
	public function scopeNotRefunded($builder)
	{
		return $builder->whereNull('refunded_at');
	}
	
	/*
	 * Active payment(s)
	 */
	public function scopeActive($builder)
	{
		return $builder->where('active', 1);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function interval(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$periodStart = $this->period_start ?? now()->startOfDay();
				$periodEnd = $this->period_end ?? now()->endOfDay();
				
				if (!$periodStart instanceof Carbon) {
					$periodStart = new Carbon($periodStart);
				}
				if (!$periodEnd instanceof Carbon) {
					$periodEnd = new Carbon($periodEnd);
				}
				
				return $periodStart->diffInDays($periodEnd);
			},
		);
	}
	
	protected function started(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$today = now();
				$periodStart = $this->period_start ?? now()->startOfDay();
				
				return $today->gt($periodStart) ? 1 : 0;
			},
		);
	}
	
	protected function expired(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$canceledAt = $this->canceled_at ?? null;
				$refundedAt = $this->refunded_at ?? null;
				if (!empty($canceledAt) || !empty($refundedAt)) {
					return 1;
				}
				
				$today = now();
				$periodEnd = $this->period_end ?? now()->endOfDay();
				
				return $today->gt($periodEnd) ? 1 : 0;
			},
		);
	}
	
	protected function status(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$today = now();
				$periodStart = $this->period_start ?? now()->startOfDay();
				$periodEnd = $this->period_end ?? now()->endOfDay();
				$canceledAt = $this->canceled_at ?? null;
				$refundedAt = $this->refunded_at ?? null;
				
				$isActive = (isset($this->active) && $this->active == 1);
				$isExpired = (isset($this->expired) && $this->expired == 1);
				
				$value = 'pending';
				if ($isActive) {
					if ($isExpired) {
						$value = 'expired';
						if (!empty($canceledAt)) {
							if ($periodEnd->gt($canceledAt)) {
								$value = 'canceled';
							}
						}
						if (!empty($refundedAt)) {
							if ($periodEnd->gt($refundedAt)) {
								$value = 'refunded';
							}
						}
					} else {
						$value = 'valid';
						if ($periodStart->gt($today)) {
							$value = 'onHold';
						}
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function periodStartFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$tz = Date::getAppTimeZone();
				
				$value = $this->period_start ?? now();
				$value->timezone($tz);
				
				return Date::format($value);
			},
		);
	}
	
	protected function periodEndFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$tz = Date::getAppTimeZone();
				
				$value = $this->period_end ?? now();
				$value->timezone($tz);
				
				return Date::format($value);
			},
		);
	}
	
	protected function canceledAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = $this->canceled_at ?? null;
				if (empty($value)) return null;
				
				$value->timezone(Date::getAppTimeZone());
				
				return Date::format($value);
			},
		);
	}
	
	protected function refundedAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = $this->refunded_at ?? null;
				if (empty($value)) return null;
				
				$value->timezone(Date::getAppTimeZone());
				
				return Date::format($value);
			},
		);
	}
	
	protected function createdAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = $this->created_at ?? now();
				$value->timezone(Date::getAppTimeZone());
				
				return Date::format($value, 'datetime');
			},
		);
	}
	
	protected function statusInfo(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return t($this->status ?? 'pending');
			},
		);
	}
	
	protected function startingInfo(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$status = $this->status ?? null;
				
				if (!isset($this->period_start_formatted) || $status == 'pending') {
					return t('starts_on_unknown_date');
				}
				
				$value = t('will_start_on', ['date' => $this->period_start_formatted]);
				if (isset($this->started) && $this->started == 1) {
					$value = t('started_on', ['date' => $this->period_start_formatted]);
				}
				
				return $value;
			},
		);
	}
	
	protected function expiryInfo(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$status = $this->status ?? null;
				
				$defaultValue = t('expires_on_unknown_date');
				
				if (!isset($this->period_end_formatted)) {
					return $defaultValue;
				}
				
				// Check status
				if ($status == 'pending') {
					return $defaultValue;
				}
				if ($status == 'onHold' || $status == 'valid') {
					$value = t('will_expire_on', ['date' => $this->period_end_formatted]);
				}
				if ($status == 'expired') {
					$value = t('expired_on', ['date' => $this->period_end_formatted]);
				}
				if ($status == 'canceled') {
					if (!isset($this->canceled_at_formatted)) {
						return $defaultValue;
					}
					$value = t('canceled_on', ['date' => $this->canceled_at_formatted]);
				}
				if ($status == 'refunded') {
					if (!isset($this->refunded_at_formatted)) {
						return $defaultValue;
					}
					$value = t('refunded_on', ['date' => $this->refunded_at_formatted]);
				}
				
				return $value;
			},
		);
	}
	
	/*
	 * Possible values: primary, secondary, success, danger,
	 * warning (warning text-dark), info (info text-dark), light (light text-dark), dark
	 */
	protected function cssClassVariant(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$status = $this->status ?? null;
				
				$value = 'light text-dark';
				if ($status == 'pending') {
					$value = 'info';
				}
				if ($status == 'onHold') {
					$value = 'primary';
				}
				if ($status == 'valid') {
					$value = 'success';
				}
				if ($status == 'expired') {
					$value = 'danger';
				}
				if ($status == 'canceled') {
					$value = 'dark';
				}
				if ($status == 'refunded') {
					$value = 'warning text-dark';
				}
				
				return $value;
			},
		);
	}
	
	/*
	 * Remaining Post Number for the Payment (Only for subscription payments).
	 * - Requires usage of Payment::with(['posts' => fn ($q) => $q->withoutGlobalScopes($postScopes)->unarchived()])
	 * - Usage: $payment->remaining_posts
	 */
	protected function remainingPosts(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$isSubscripting = (isset($this->payable_type) && str_ends_with($this->payable_type, 'User'));
				
				if (!$isSubscripting) {
					return null;
				}
				
				// If the relation is not loaded through the Eloquent 'with()' method,
				// then don't make an additional query (to prevent performance issues).
				if (!$this->relationLoaded('package')) {
					return null;
				}
				
				// If the relation is not loaded through the Eloquent 'with()' method,
				// then don't make an additional query (to prevent performance issues).
				if (!$this->relationLoaded('posts')) {
					return null;
				}
				
				$defaultPostsLimit = (int)config('settings.single.listings_limit');
				
				$postsLimit = $this->package?->listings_limit ?? null;
				$postsLimit = !empty($postsLimit) ? $postsLimit : $defaultPostsLimit;
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
