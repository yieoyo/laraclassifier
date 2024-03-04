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

use App\Helpers\Files\Upload;
use App\Models\Language;

class GetSearchForm
{
	public static function passedValidation($request)
	{
		$params = [
			[
				'attribute' => 'background_image',
				'destPath'  => 'app/logo',
				'width'     => (int)config('larapen.core.picture.otherTypes.bgHeader.width', 2000),
				'height'    => (int)config('larapen.core.picture.otherTypes.bgHeader.height', 1000),
				'ratio'     => config('larapen.core.picture.otherTypes.bgHeader.ratio', '1'),
				'upsize'    => config('larapen.core.picture.otherTypes.bgHeader.upsize', '0'),
				'filename'  => 'header-',
				'quality'   => 100,
			],
		];
		
		foreach ($params as $param) {
			$file = $request->hasFile($param['attribute'])
				? $request->file($param['attribute'])
				: $request->input($param['attribute']);
			
			$request->request->set($param['attribute'], Upload::image($param['destPath'], $file, $param));
		}
		
		return $request;
	}
	
	public static function getValues($value)
	{
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 3600);
		$cacheId = 'models:languages.active';
		$languages = cache()->remember($cacheId, $cacheExpiration, function () {
			return Language::active()->get();
		});
		
		if (empty($value)) {
			
			$value['enable_extended_form_area'] = '1';
			$value['background_image'] = null;
			$value['background_image_darken'] = 0;
			
			if ($languages->count() > 0) {
				foreach ($languages as $language) {
					$value['title_' . $language->code] = t('homepage_title_text', [], 'global', $language->code);
					$value['sub_title_' . $language->code] = t('simple_fast_and_efficient', [], 'global', $language->code);
				}
			}
			
		} else {
			
			if (!isset($value['enable_extended_form_area'])) {
				$value['enable_extended_form_area'] = '1';
			}
			if (!isset($value['background_image'])) {
				$value['background_image'] = null;
			}
			if (!isset($value['background_image_darken'])) {
				$value['background_image_darken'] = 0.0;
			}
			
			if ($languages->count() > 0) {
				foreach ($languages as $language) {
					if (!isset($value['title_' . $language->code])) {
						$value['title_' . $language->code] = t('homepage_title_text', [], 'global', $language->code);
					}
					if (!isset($value['sub_title_' . $language->code])) {
						$value['sub_title_' . $language->code] = t('simple_fast_and_efficient', [], 'global', $language->code);
					}
				}
			}
			
		}
		
		// Append files URLs
		// background_image_url
		$backgroundImage = $value['background_image'] ?? null;
		$value['background_image_url'] = !empty($backgroundImage) ? imgUrl($backgroundImage, 'bgHeader') : null;
		
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
				'name'       => 'enable_extended_form_area',
				'label'      => trans('admin.enable_extended_form_area_label'),
				'type'       => 'checkbox_switch',
				'attributes' => [
					'id'       => 'extendedForm',
					'onclick' => 'getExtendedFields(this)',
				],
				'hint'       => trans('admin.enable_extended_form_area_hint'),
			],
			[
				'name'              => 'separator_1',
				'type'              => 'custom_html',
				'value'             => trans('admin.getSearchForm_html_background'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'                => 'background_color',
				'label'               => trans('admin.Background Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#444',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'              => 'background_image',
				'label'             => trans('admin.Background Image'),
				'type'              => 'image',
				'upload'            => true,
				'disk'              => $diskName,
				'hint'              => trans('admin.getSearchForm_background_image_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'              => 'background_image_darken',
				'label'             => trans('admin.background_image_darken_label'),
				'type'              => 'range',
				'attributes'        => [
					'placeholder' => '0.5',
					'min'         => 0,
					'max'         => 1,
					'step'        => 0.05,
					'style'       => 'padding: 0;',
				],
				'default'           => 0,
				'hint'              => trans('admin.background_image_darken_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-4 extended',
				],
			],
			[
				'name'              => 'height',
				'label'             => trans('admin.Height'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => '450',
					'min'         => 45,
					'max'         => 2000,
					'step'        => 1,
				],
				'hint'              => trans('admin.Enter a value greater than 50px'),
				'wrapperAttributes' => [
					'class' => 'col-md-4 extended',
				],
			],
			[
				'name'              => 'parallax',
				'label'             => trans('admin.Enable Parallax Effect'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-4 extended',
				],
			],
			[
				'name'  => 'separator_2',
				'type'  => 'custom_html',
				'value' => trans('admin.getSearchForm_html_search_form'),
			],
			[
				'name'              => 'hide_form',
				'label'             => trans('admin.Hide the Form'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'                => 'form_border_color',
				'label'               => trans('admin.Form Border Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#333',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'form_border_width',
				'label'             => trans('admin.Form Border Width'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => '5',
					'min'         => 0,
					'max'         => 10,
					'step'        => 1,
				],
				'hint'              => trans('admin.Enter a number with unit'),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			],
			[
				'name'              => 'form_border_radius',
				'label'             => trans('admin.Form Border Radius'),
				'type'              => 'number',
				'attributes'        => [
					'placeholder' => '5',
					'min'         => 0,
					'max'         => 30,
					'step'        => 1,
				],
				'hint'              => trans('admin.Enter a number with unit'),
				'wrapperAttributes' => [
					'class' => 'col-md-3',
				],
			],
			[
				'name'                => 'form_btn_background_color',
				'label'               => trans('admin.Form Button Background Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#4682B4',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'                => 'form_btn_text_color',
				'label'               => trans('admin.Form Button Text Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#FFF',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-6',
				],
			],
			[
				'name'              => 'separator_3',
				'type'              => 'custom_html',
				'value'             => trans('admin.getSearchForm_html_titles'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'              => 'hide_titles',
				'label'             => trans('admin.Hide Titles'),
				'type'              => 'checkbox_switch',
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'              => 'separator_3_1',
				'type'              => 'custom_html',
				'value'             => trans('admin.getSearchForm_html_titles_content'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'              => 'separator_3_2',
				'type'              => 'custom_html',
				'value'             => trans('admin.dynamic_variables_stats_hint'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
		];
		
		$languages = Language::active()->get();
		if ($languages->count() > 0) {
			$titlesFields = [];
			foreach ($languages as $language) {
				$titlesFields[] = [
					'name'              => 'title_' . $language->code,
					'label'             => mb_ucfirst(trans('admin.title')) . ' (' . $language->name . ')',
					'attributes'        => [
						'placeholder' => t('homepage_title_text', [], 'global', $language->code),
					],
					'wrapperAttributes' => [
						'class' => 'col-md-6 extended',
					],
				];
				$titlesFields[] = [
					'name'              => 'sub_title_' . $language->code,
					'label'             => trans('admin.Sub Title') . ' (' . $language->name . ')',
					'attributes'        => [
						'placeholder' => t('simple_fast_and_efficient', [], 'global', $language->code),
					],
					'wrapperAttributes' => [
						'class' => 'col-md-6 extended',
					],
				];
			}
			
			$fields = array_merge($fields, $titlesFields);
		}
		
		$fields = array_merge($fields, [
			[
				'name'              => 'separator_3_3',
				'type'              => 'custom_html',
				'value'             => trans('admin.getSearchForm_html_titles_color'),
				'wrapperAttributes' => [
					'class' => 'col-md-12 extended',
				],
			],
			[
				'name'                => 'big_title_color',
				'label'               => trans('admin.Big Title Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#FFF',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-6 extended',
				],
			],
			[
				'name'                => 'sub_title_color',
				'label'               => trans('admin.Sub Title Color'),
				'type'                => 'color_picker',
				'colorpicker_options' => [
					'customClass' => 'custom-class',
				],
				'attributes'          => [
					'placeholder' => '#FFF',
				],
				'hint'                => trans('admin.Enter a RGB color code'),
				'wrapperAttributes'   => [
					'class' => 'col-md-6 extended',
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
	let extFormEl = document.querySelector("#extendedForm");
	getExtendedFields(extFormEl);
});

function getExtendedFields(extFormEl) {
	if (extFormEl.checked) {
		showEl(document.querySelectorAll(".extended"));
	} else {
		hideEl(document.querySelectorAll(".extended"));
	}
}
</script>',
			],
		]);
		
		return $fields;
	}
}
