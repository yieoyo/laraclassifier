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

namespace App\Models\Traits;

use App\Helpers\DBTool;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait CategoryTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getNameHtml(): string
	{
		$currentUrl = preg_replace('#/(search)$#', '', url()->current());
		$url = $currentUrl . '/' . $this->id . '/edit';
		
		return '<a href="' . $url . '">' . $this->name . '</a>';
	}
	
	public function subCategoriesButton($xPanel = false): string
	{
		$out = '';
		
		$url = admin_url('categories/' . $this->id . '/subcategories');
		
		$msg = trans('admin.Subcategories of category', ['category' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		$countSubCats = $this->children->count();
		
		$out .= '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
		$out .= $countSubCats . ' ';
		$out .= ($countSubCats > 1) ? trans('admin.subcategories') : trans('admin.subcategory');
		$out .= '</a>';
		
		return $out;
	}
	
	public function customFieldsButton($xPanel = false): string
	{
		$url = admin_url('categories/' . $this->id . '/custom_fields');
		
		$msg = trans('admin.Custom Fields of category', ['category' => $this->name]);
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		$countFields = $this->fields->count();
		
		$out = '<a class="btn btn-xs btn-light" href="' . $url . '"' . $tooltip . '>';
		$out .= $countFields . ' ';
		$out .= ($countFields > 1) ? trans('admin.custom fields') : trans('admin.custom field');
		$out .= '</a>';
		
		return $out;
	}
	
	public function rebuildNestedSetNodesButton($xPanel = false): string
	{
		$url = admin_url('categories/rebuild-nested-set-nodes');
		
		$msg = trans('admin.rebuild_nested_set_nodes_info');
		$tooltip = ' data-bs-toggle="tooltip" title="' . $msg . '"';
		
		// Button
		$out = '<a class="btn btn-light shadow" href="' . $url . '"' . $tooltip . '>';
		$out .= '<i class="fas fa-code-branch"></i> ';
		$out .= trans('admin.rebuild_nested_set_nodes');
		$out .= '</a>';
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	/**
	 * Return the sluggable configuration array for this model.
	 *
	 * @return array
	 */
	public function sluggable(): array
	{
		return [
			'slug' => [
				'source' => ['slug', 'name'],
			],
		];
	}
	
	/**
	 * Get categories recursively for select box
	 *
	 * @param int|null $skippedId
	 * @param \Illuminate\Database\Eloquent\Collection|null $entries
	 * @param array $tab
	 * @param int $level
	 * @param string|null $spacerChars
	 * @return array
	 */
	public static function selectBoxTree(
		?int        $skippedId,
		?Collection $entries = null,
		array       &$tab = [],
		int         $level = 0,
		?string     $spacerChars = '-----'
	): array
	{
		if (is_null($entries)) {
			if (!empty($skippedId)) {
				$tab[0] = t('Root');
			}
			$entries = self::root()->with(['children'])->where('id', '!=', $skippedId)->orderBy('lft')->get();
			if ($entries->count() <= 0) {
				return [];
			}
		}
		
		foreach ($entries as $entry) {
			if (!empty($spacerChars)) {
				$spacer = str_repeat($spacerChars, $level) . '| ';
			} else {
				$spacer = '';
			}
			
			// Print out the item ID and the item name
			if ($skippedId != $entry->id) {
				$tab[$entry->id] = $spacer . $entry->name;
				
				// If entry has children, we have a nested data structure, so call recurse on it.
				if (isset($entry->children) && $entry->children->count() > 0) {
					self::selectBoxTree($skippedId, $entry->children, $tab, $level + 1, $spacerChars);
				}
			}
		}
		
		return $tab;
	}
	
	/**
	 * @param $catId
	 * @param array|null $parentsIds
	 * @return array|null
	 */
	public static function getParentsIds($catId, ?array &$parentsIds = []): ?array
	{
		$cat = self::query()->with('parent')->where('id', $catId)->first(['id', 'parent_id']);
		
		if (!empty($cat)) {
			$parentsIds[$cat->id] = $cat->id;
			if (!empty($cat->parent_id)) {
				if (!empty($cat->parent)) {
					return self::getParentsIds($cat->parent->id, $parentsIds);
				}
			}
		}
		
		return $parentsIds;
	}
	
	/**
	 * Count Posts by Category
	 *
	 * @param $cityId
	 * @return array
	 */
	public static function countPostsPerCategory($cityId = null): array
	{
		$whereCity = '';
		if (!empty($cityId)) {
			$whereCity = ' AND tPost.city_id = ' . $cityId;
		}
		
		$categoriesTable = (new Category())->getTable();
		$postsTable = (new Post())->getTable();
		
		$sql = 'SELECT parent.id, COUNT(*) AS total
				FROM ' . DBTool::table($categoriesTable) . ' AS node,
						' . DBTool::table($categoriesTable) . ' AS parent,
						' . DBTool::table($postsTable) . ' AS tPost
				WHERE node.lft BETWEEN parent.lft AND parent.rgt
						AND node.id = tPost.category_id
						AND tPost.country_code = :countryCode' . $whereCity . '
						AND ((tPost.email_verified_at IS NOT NULL) AND (tPost.phone_verified_at IS NOT NULL))
						AND (tPost.archived_at IS NULL)
						AND (tPost.deleted_at IS NULL)
				GROUP BY parent.id';
		$bindings = [
			'countryCode' => config('country.code'),
		];
		$cats = DB::select($sql, $bindings);
		
		return collect($cats)->keyBy('id')->toArray();
	}
}
