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

namespace App\Http\Controllers\Web\Admin;

use App\Helpers\Arr;
use App\Http\Requests\Admin\PluginRequest;
use Prologue\Alerts\Facades\Alert;

class PluginController extends Controller
{
	private array $data = [];
	
	public function __construct()
	{
		parent::__construct();
		
		$this->data['plugins'] = [];
	}
	
	/**
	 * List all plugins
	 */
	public function index()
	{
		$plugins = [];
		try {
			// Load all the plugins' services providers
			$plugins = plugin_list();
			
			// Append the Plugin Options
			$plugins = collect($plugins)->map(function ($item, $key) {
				if (is_object($item)) {
					$item = Arr::fromObject($item);
				}
				
				// Append formatted name
				$name = $item['name'] ?? null;
				$displayName = $item['display_name'] ?? null;
				$item['formatted_name'] = $displayName . plugin_demo_info($name);
				
				if (!empty($item['item_id'])) {
					$item['activated'] = plugin_check_purchase_code($item);
				}
				
				// Append the Options
				$item['options'] = null;
				if ($item['is_compatible']) {
					$pluginClass = plugin_namespace($item['name'], ucfirst($item['name']));
					$item['options'] = method_exists($pluginClass, 'getOptions')
						? (array)call_user_func($pluginClass . '::getOptions')
						: null;
				}
				
				return Arr::toObject($item);
			})->toArray();
		} catch (\Throwable $e) {
			$message = $e->getMessage();
			if (!empty($message)) {
				Alert::error($message)->flash();
			}
		}
		
		$this->data['plugins'] = $plugins;
		$this->data['title'] = 'Plugins';
		
		return view('admin.plugins', $this->data);
	}
	
	/**
	 * Install a plugin (with purchase code)
	 *
	 * @param $name
	 * @param \App\Http\Requests\Admin\PluginRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function installWithCode($name, PluginRequest $request)
	{
		$pluginListUrl = admin_url('plugins');
		
		// Get plugin details
		$plugin = load_plugin($name);
		if (empty($plugin)) {
			return redirect()->to($pluginListUrl);
		}
		
		// Check if the plugin is compatible with the core app
		if (!$plugin->is_compatible) {
			Alert::error($plugin->compatibility_hint)->flash();
			
			return redirect()->to($pluginListUrl);
		}
		
		// Install the plugin
		$res = call_user_func($plugin->class . '::installed');
		if (!$res) {
			$res = call_user_func($plugin->class . '::install');
		}
		
		if ($res) {
			Alert::success(trans('admin.plugin_installed_successfully', ['plugin_name' => $plugin->display_name]))->flash();
		} else {
			Alert::error(trans('admin.plugin_installation_failed', ['plugin_name' => $plugin->display_name]))->flash();
		}
		
		return redirect()->to($pluginListUrl);
	}
	
	/**
	 * Install a plugin (without purchase code)
	 *
	 * @param $name
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function installWithoutCode($name)
	{
		$pluginListUrl = admin_url('plugins');
		
		// Get plugin details
		$plugin = load_plugin($name);
		if (empty($plugin)) {
			return redirect()->to($pluginListUrl);
		}
		
		// Check if the plugin is compatible with the core app
		if (!$plugin->is_compatible) {
			Alert::error($plugin->compatibility_hint)->flash();
			
			return redirect()->to($pluginListUrl);
		}
		
		// Install the plugin
		$res = call_user_func($plugin->class . '::install');
		
		if ($res) {
			Alert::success(trans('admin.plugin_installed_successfully', ['plugin_name' => $plugin->display_name]))->flash();
		} else {
			Alert::error(trans('admin.plugin_installation_failed', ['plugin_name' => $plugin->display_name]))->flash();
		}
		
		return redirect()->to($pluginListUrl);
	}
	
	/**
	 * Uninstall a plugin
	 *
	 * @param $name
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function uninstall($name)
	{
		$pluginListUrl = admin_url('plugins');
		
		// Get plugin details
		$plugin = load_plugin($name);
		if (empty($plugin)) {
			return redirect()->to($pluginListUrl);
		}
		
		// Check if the plugin is compatible with the core app
		if (!$plugin->is_compatible) {
			Alert::error($plugin->compatibility_hint)->flash();
			
			return redirect()->to($pluginListUrl);
		}
		
		// Uninstall the plugin
		$res = call_user_func($plugin->class . '::uninstall');
		
		// Result Notification
		if ($res) {
			plugin_clear_uninstall($name);
			
			Alert::success(trans('admin.plugin_uninstalled_successfully', ['plugin_name' => $plugin->display_name]))->flash();
		} else {
			Alert::error(trans('admin.plugin_uninstallation_failed', ['plugin_name' => $plugin->display_name]))->flash();
		}
		
		return redirect()->to($pluginListUrl);
	}
	
	/**
	 * Delete a plugin
	 *
	 * @param $plugin
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function delete($plugin)
	{
		$pluginListUrl = admin_url('plugins');
		
		// ...
		// Alert::success(trans('admin.plugin_removed_successfully'))->flash();
		// Alert::error(trans('admin.plugin_removal_failed', ['plugin_name' => $plugin]))->flash();
		
		return redirect()->to($pluginListUrl);
	}
}
