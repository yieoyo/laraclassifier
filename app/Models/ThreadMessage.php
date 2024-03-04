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
use App\Models\Thread\MessageTrait;
use App\Observers\ThreadMessageObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

class ThreadMessage extends BaseModel
{
	use SoftDeletes, Crud, Notifiable, MessageTrait, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'threads_messages';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	// protected $primaryKey = 'id';
	protected $appends = ['created_at_formatted', 'p_recipient'];
	
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
	 * The relationships that should be touched on save.
	 *
	 * @var array
	 */
	protected $touches = ['thread'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'thread_id',
		'user_id',
		'body',
		'filename',
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
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
		'deleted_at' => 'datetime',
	];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		ThreadMessage::observe(ThreadMessageObserver::class);
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Thread relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 *
	 * @codeCoverageIgnore
	 */
	public function thread()
	{
		return $this->belongsTo(Thread::class, 'thread_id', 'id');
	}
	
	/**
	 * User relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 *
	 * @codeCoverageIgnore
	 */
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	/**
	 * Participants relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 *
	 * @codeCoverageIgnore
	 */
	public function participants()
	{
		return $this->hasMany(ThreadParticipant::class, 'thread_id', 'thread_id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeNotDeletedByUser(Builder $query, $userId)
	{
		return $query->where(function ($q) use ($userId) {
			$q->where('deleted_by', '!=', $userId)->orWhereNull('deleted_by');
		});
	}
	
	/**
	 * Returns unread messages given the userId.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param int $userId
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeUnreadForUser(Builder $query, $userId)
	{
		return $query->has('thread')
			->where('user_id', '!=', $userId)
			->whereHas('participants', function (Builder $query) use ($userId) {
				$query->where('user_id', $userId)
					->where(function (Builder $q) {
						$q->where('last_read', '<', $this->getConnection()->raw($this->getConnection()->getTablePrefix() . $this->getTable() . '.created_at'))
							->orWhereNull('last_read');
					});
			});
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
	
	protected function createdAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($this->attributes['created_at']);
				$value->timezone(Date::getAppTimeZone());
				
				return Date::format($value, 'datetime');
			},
		);
	}
	
	protected function pRecipient(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return $this->participants()->where('user_id', '!=', $this->user_id)->first();
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
