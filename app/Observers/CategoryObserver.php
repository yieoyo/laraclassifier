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

namespace App\Observers;

use App\Helpers\Files\Storage\StorageDisk;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Post;
use App\Observers\Traits\CategoryTrait;

class CategoryObserver
{
	use CategoryTrait;
	
	/**
	 * Listen to the Entry creating event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function creating(Category $category)
	{
		// Fix required columns
		$category = $this->fixRequiredColumns($category);
		
		// Apply the nested created actions
		return $this->creatingNestedItem($category);
	}
	
	/**
	 * Listen to the Entry updating event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function updating(Category $category)
	{
		// Fix required columns
		$category = $this->fixRequiredColumns($category);
		
		// Apply the nested updating actions
		return $this->updatingNestedItem($category);
	}
	
	/**
	 * Listen to the Entry deleting event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function deleting($category)
	{
		// Apply the nested deleting actions
		$this->deletingNestedItem($category);
		
		// Storage Disk Init.
		$disk = StorageDisk::getDisk();
		
		// Delete all the Category's Custom Fields
		$catFields = CategoryField::where('category_id', $category->id)->get();
		if ($catFields->count() > 0) {
			foreach ($catFields as $catField) {
				$catField->delete();
			}
		}
		
		// Delete all the Category's Posts
		$posts = Post::where('category_id', $category->id);
		if ($posts->count() > 0) {
			foreach ($posts->cursor() as $post) {
				$post->delete();
			}
		}
		
		// Don't delete the default pictures
		$defaultPicture = 'app/default/categories/fa-folder-default.png';
		$skin = config('settings.style.skin', 'default');
		$skinPicture = 'app/default/categories/fa-folder-' . $skin . '.png';
		$skinDirectory = 'app/categories/' . $skin . '/';
		if (
			!empty($category->picture)
			&& !str_contains($category->picture, $defaultPicture)
			&& !str_contains($category->picture, $skinPicture)
			&& !str_contains($category->picture, $skinDirectory)
			&& $disk->exists($category->picture)
		) {
			$disk->delete($category->picture);
		}
		
		// Delete the category's children recursively
		$this->deleteChildrenRecursively($category);
	}
	
	/**
	 * Listen to the Entry updated event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function updated(Category $category)
	{
		// Activate|Deactivate category with its children or parent (if they exist)
		
		/*
		 * If the category is activated, check if it has a parent;
		 * If yes, active the parent also *ONLY* if it was disabled.
		 * NOTE: The *ONLY* means to prevent any infinite recursion.
		 */
		if ($category->active == 1) {
			if (!empty($category->parent_id)) {
				$parentCat = Category::find($category->parent_id);
				if ($parentCat->active != 1) {
					$parentCat->active = 1;
					$parentCat->save();
				}
			}
			
			// If the "activateChildren" field is checked,
			// then activate all the category's children.
			if (request()->has('activateChildren') && (bool)request()->input('activateChildren')) {
				$subCats = Category::childrenOf($category->id)->get();
				if ($subCats->count() > 0) {
					foreach ($subCats as $subCat) {
						if ($subCat->active != 1) {
							$subCat->active = 1;
							$subCat->save();
						}
					}
				}
			}
		} else {
			/*
			 * If the category is disabled, check if it has children;
			 * If yes, browses each child and disables it *ONLY* if it's not disabled.
			 * NOTE: The *ONLY* means to prevent any infinite recursion.
			 */
			$subCats = Category::childrenOf($category->id)->get();
			if ($subCats->count() > 0) {
				foreach ($subCats as $subCat) {
					if ($subCat->active != 0) {
						$subCat->active = 0;
						$subCat->save();
					}
				}
			}
		}
	}
	
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function saved(Category $category)
	{
		// Convert Adjacent List to Nested Set
		// $this->adjacentToNestedByItem($category);
		
		// Removing Entries from the Cache
		$this->clearCache($category);
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param Category $category
	 * @return void
	 */
	public function deleted(Category $category)
	{
		// Convert Adjacent List to Nested Set
		// $this->adjacentToNestedByItem($category);
		
		// Removing Entries from the Cache
		$this->clearCache($category);
	}
	
	/**
	 * Removing the Entity's Entries from the Cache
	 *
	 * @param $category
	 * @return void
	 */
	private function clearCache($category)
	{
		try {
			cache()->flush();
		} catch (\Exception $e) {
		}
	}
}
