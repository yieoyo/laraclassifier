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

namespace App\Models\HomeSection;

class GetCategories
{
	public static function getValues($value)
	{
		if (empty($value)) {
			
			$value['max_items'] = null; // i.e. Show all root categories
			$value['cat_display_type'] = 'c_bigIcon_list';
			$value['show_icon'] = '1';
			$value['max_sub_cats'] = '3';
			
		} else {
			
			if (!isset($value['max_items'])) {
				$value['max_items'] = '12';
			}
			if (!isset($value['cat_display_type'])) {
				$value['cat_display_type'] = 'c_bigIcon_list';
			} else {
				if (in_array($value['cat_display_type'], ['c_circle_list', 'c_check_list'])) {
					$value['cat_display_type'] = 'c_bigIcon_list';
				}
			}
			if (!isset($value['show_icon'])) {
				$value['show_icon'] = '1';
			}
			if (!isset($value['max_sub_cats'])) {
				$value['max_sub_cats'] = '3';
			}
			
		}
		
		return $value;
	}
	
	public static function setValues($value, $setting)
	{
		return $value;
	}
	
	public static function getFields($diskName)
	{
		$fields = [
			[
				'name'              => 'cat_display_type',
				'label'             => trans('admin.cat_display_type_label'),
				'type'              => 'select2_from_array',
				'options'           => [
					'c_normal_list'    => trans('admin.cat_display_type_op_1'),
					'c_border_list'    => trans('admin.cat_display_type_op_2'),
					'c_bigIcon_list'   => trans('admin.cat_display_type_op_3'),
					'c_picture_list'   => trans('admin.cat_display_type_op_4'),
					'cc_normal_list'   => trans('admin.cat_display_type_op_5'),
					'cc_normal_list_s' => trans('admin.cat_display_type_op_6'),
				],
				'allows_null'       => false,
				'attributes'        => [
					'id'       => 'catDisplayType',
					'onchange' => 'getCatDisplayTypeFields(this)',
				],
				'hint'              => trans('admin.cat_display_type_hint', [
					'type_1' => trans('admin.cat_display_type_op_5'),
					'type_2' => trans('admin.cat_display_type_op_6'),
				]),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'max_items',
				'label'             => trans('admin.max_categories_label'),
				'type'              => 'number',
				'hint'              => trans('admin.max_categories_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 normal-type',
				],
			],
			[
				'name'  => 'separator_clear_1',
				'type'  => 'custom_html',
				'value' => '<div style="clear: both;"></div>',
			],
			[
				'name'              => 'max_sub_cats',
				'label'             => trans('admin.Max subcategories displayed by default'),
				'type'              => 'number',
				'hint'              => trans('admin.max_sub_cats_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6 nested-type',
				],
			],
			[
				'name'              => 'cache_expiration',
				'label'             => trans('admin.Cache Expiration Time for this section'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => '0',
				],
				'hint'              => trans('admin.home_cache_expiration_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'  => 'separator_last',
				'type'  => 'custom_html',
				'value' => '<hr>',
			],
			[
				'name'  => 'hide_on_mobile',
				'label' => trans('admin.hide_on_mobile_label'),
				'type'  => 'checkbox_switch',
				'hint'  => trans('admin.hide_on_mobile_hint'),
			],
			[
				'name'  => 'active',
				'label' => trans('admin.Active'),
				'type'  => 'checkbox_switch',
			],
			
			[
				'name'  => 'javascript',
				'type'  => 'custom_html',
				'value' => '<script>
docReady(function() {
	let catDisplayTypeEl = document.querySelector("#catDisplayType");
	getCatDisplayTypeFields(catDisplayTypeEl);
});

function getCatDisplayTypeFields(catDisplayTypeEl) {
	let catDisplayTypeElValue = catDisplayTypeEl.value;
	
	hideEl(document.querySelectorAll(".normal-type, .nested-type"));
	
	if (
		catDisplayTypeElValue === "c_normal_list"
		|| catDisplayTypeElValue === "c_border_list"
		|| catDisplayTypeElValue === "c_bigIcon_list"
		|| catDisplayTypeElValue === "c_picture_list"
	) {
		showEl(document.querySelectorAll(".normal-type"));
	}
	if (catDisplayTypeElValue === "cc_normal_list" || catDisplayTypeElValue === "cc_normal_list_s") {
		showEl(document.querySelectorAll(".nested-type"));
	}
}
</script>',
			],
		];
		
		return $fields;
	}
}
