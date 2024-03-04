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

use App\Models\Scopes\ActiveScope;
use App\Models\Traits\LanguageTrait;
use App\Observers\LanguageObserver;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

class Language extends BaseModel
{
	use Crud, HasFactory;
	use LanguageTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'languages';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'code';
	protected $keyType = 'string';
	public $incrementing = false;
	
	protected $appends = [
		'iso_locale',
		'tag',
		'primary', // Language ISO 639-1 Code
	];
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = false;
	
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
		'code',
		'locale',
		'name',
		'native',
		'flag',
		'script',
		'direction',
		'russian_pluralization',
		'date_format',
		'datetime_format',
		'active',
		'default',
		'parent_id',
		'lft',
		'rgt',
		'depth',
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
	// protected $casts = [];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected static function boot()
	{
		parent::boot();
		
		Language::observe(LanguageObserver::class);
		
		static::addGlobalScope(new ActiveScope());
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeActive($query)
	{
		return $query->where('active', 1);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function id(): Attribute
	{
		return Attribute::make(
			get: function () {
				return $this->code ?? ($this->attributes['code'] ?? null);
			},
		);
	}
	
	protected function code(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => removeLocaleCodeset($value),
			set: fn ($value) => removeLocaleCodeset($value),
		);
	}
	
	protected function locale(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$code = $this->code ?? ($this->attributes['code'] ?? null);
				
				return !empty($value) ? $value : $code;
			},
			set: function ($value) {
				$code = $this->code ?? ($this->attributes['code'] ?? null);
				
				return !empty($value) ? $value : $code;
			},
		);
	}
	
	/*
	 * Locale without codeset|encoding
	 */
	protected function isoLocale(): Attribute
	{
		return Attribute::make(
			get: function () {
				$locale = $this->locale ?? ($this->attributes['locale'] ?? null);
				
				return removeLocaleCodeset($locale);
			},
		);
	}
	
	protected function native(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$name = $this->name ?? ($this->attributes['name'] ?? null);
				
				return !empty($value) ? $value : $name;
			},
			set: function ($value) {
				$name = $this->name ?? ($this->attributes['name'] ?? null);
				
				return !empty($value) ? $value : $name;
			},
		);
	}
	
	protected function tag(): Attribute
	{
		return Attribute::make(
			get: function () {
				$code = $this->code ?? ($this->attributes['code'] ?? null);
				
				return getLangTag($code);
			},
		);
	}
	
	/*
	 * Language ISO 639-1 Code
	 */
	protected function primary(): Attribute
	{
		return Attribute::make(
			get: function () {
				$code = $this->code ?? ($this->attributes['code'] ?? null);
				
				return getPrimaryLocaleCode($code);
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
