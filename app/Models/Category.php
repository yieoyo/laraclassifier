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

use App\Helpers\Files\Storage\StorageDisk;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\CategoryTrait;
use App\Observers\CategoryObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentSluggable\SluggableScopeHelpers;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\SpatieTranslatable\HasTranslations;

class Category extends BaseModel
{
	use Crud, HasFactory, Sluggable, SluggableScopeHelpers, HasTranslations;
	use CategoryTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'categories';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	// protected $primaryKey = 'id';
	protected $appends = ['picture_url'];
	
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
		'parent_id',
		'name',
		'slug',
		'description',
		'hide_description',
		'picture',
		'icon_class',
		'seo_title',
		'seo_description',
		'seo_keywords',
		'lft',
		'rgt',
		'depth',
		'type',
		'is_for_permanent',
		'active',
	];
	public $translatable = ['name', 'description', 'seo_title', 'seo_description', 'seo_keywords'];
	
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
		
		Category::observe(CategoryObserver::class);
		
		static::addGlobalScope(new ActiveScope());
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function posts()
	{
		if (isAdminPanel()) {
			return $this->hasMany(Post::class, 'category_id');
		} else {
			return $this->hasMany(Post::class, 'category_id')
				->where('country_code', config('country.code'));
		}
	}
	
	public function children()
	{
		return $this->hasMany(Category::class, 'parent_id')
			->with('children')
			->orderBy('lft');
	}
	
	public function childrenClosure()
	{
		return $this->hasMany(Category::class, 'parent_id')
			->orderBy('lft');
	}
	
	public function parent()
	{
		return $this->belongsTo(Category::class, 'parent_id')
			->with('parent');
	}
	
	public function parentClosure()
	{
		return $this->belongsTo(Category::class, 'parent_id');
	}
	
	public function fields()
	{
		return $this->belongsToMany(Field::class, 'category_field', 'category_id', 'field_id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	// root()
	public function scopeRoot($builder)
	{
		return $builder->columnIsEmpty('parent_id');
	}
	
	// childrenOf()
	public function scopeChildrenOf($builder, $parentId)
	{
		return $builder->where('parent_id', $parentId);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function name(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['name']) && !isJson($this->attributes['name'])) {
					return $this->attributes['name'];
				}
				
				return $value;
			},
		);
	}
	
	protected function iconClass(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$defaultIconClass = 'fas fa-folder';
				
				if (empty($value)) {
					return $defaultIconClass;
				}
				
				// This part will be removed at: 2022-10-14
				$filePath = public_path('assets/plugins/bootstrap-iconpicker/js/iconset/iconset-fontawesome5-all.js');
				$buffer = file_get_contents($filePath);
				
				$ifVersion = '5.15.4';
				$ifVersion = str_replace('.', '\.', $ifVersion);
				
				$matches = [];
				preg_match('#version:[^\']+\'' . $ifVersion . '\',[^i]+icons:[^\[]*\[([^]]+)]#s', $buffer, $matches);
				$iClasses = $matches[1] ?? '';
				$iClasses = str_replace("'", '', $iClasses);
				$iClasses = preg_replace('#[\n\t]*#', '', $iClasses);
				
				$iClassesArray = array_map('trim', explode(',', $iClasses));
				
				if (!empty($iClassesArray)) {
					if (!in_array($value, $iClassesArray)) {
						return $defaultIconClass;
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function description(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['description']) && !isJson($this->attributes['description'])) {
					return $this->attributes['description'];
				}
				
				return $value;
			},
		);
	}
	
	protected function type(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (empty($value)) {
					if (
						isset($this->parent)
						&& $this->parent->type
						&& !empty($this->parent->type)
					) {
						$value = $this->parent->type;
					}
					if (empty($value)) {
						$value = 'classified';
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function picture(): Attribute
	{
		return Attribute::make(
			get: fn ($value, $attributes) => $this->getPicture($value, $attributes),
		);
	}
	
	protected function pictureUrl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return imgUrl($this->picture, 'cat');
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	private function getPicture($value, $attributes)
	{
		// OLD PATH
		$oldValue = $this->getPictureFromOriginPath($value);
		if (!empty($oldValue)) {
			return $oldValue;
		}
		
		// NEW PATH
		if (empty($value)) {
			if (isset($attributes['picture'])) {
				$value = $attributes['picture'];
			}
		}
		
		$disk = StorageDisk::getDisk();
		
		$defaultIcon = 'app/default/categories/fa-folder-default.png';
		$skin = getFrontSkin(request()->input('skin'));
		$defaultSkinnedIcon = 'app/default/categories/fa-folder-' . $skin . '.png';
		
		// File path is empty
		if (empty($value)) {
			if ($disk->exists($defaultSkinnedIcon)) {
				return $defaultSkinnedIcon;
			}
			
			return $defaultIcon;
		}
		
		// File not found
		if (!$disk->exists($value)) {
			if ($disk->exists($defaultSkinnedIcon)) {
				return $defaultSkinnedIcon;
			}
			
			return $defaultIcon;
		}
		
		// If the Category contains a skinnable icon,
		// Change it by the selected skin icon.
		if (str_contains($value, 'app/categories/') && !str_contains($value, '/custom/')) {
			$pattern = '/app\/categories\/[^\/]+\//iu';
			$replacement = 'app/categories/' . $skin . '/';
			$value = preg_replace($pattern, $replacement, $value);
		}
		
		// (Optional)
		// If the Category contains a skinnable default icon,
		// Change it by the selected skin default icon.
		if (str_contains($value, 'app/default/categories/fa-folder-')) {
			$pattern = '/app\/default\/categories\/fa-folder-[^\.]+\./iu';
			$replacement = 'app/default/categories/fa-folder-' . $skin . '.';
			$value = preg_replace($pattern, $replacement, $value);
		}
		
		if (!$disk->exists($value)) {
			if ($disk->exists($defaultSkinnedIcon)) {
				return $defaultSkinnedIcon;
			}
			
			return $defaultIcon;
		}
		
		return $value;
	}
	
	/**
	 * Category icons pictures from original version
	 * Only the file name is set in Category 'picture' field
	 * Example: fa-car.png
	 *
	 * @param $value
	 * @return string|null
	 */
	private function getPictureFromOriginPath($value): ?string
	{
		// Fix path
		$skin = config('settings.style.skin', 'default');
		$value = 'app/categories/' . $skin . '/' . $value;
		
		$disk = StorageDisk::getDisk();
		if (!$disk->exists($value)) {
			return null;
		}
		
		return $value;
	}
}
