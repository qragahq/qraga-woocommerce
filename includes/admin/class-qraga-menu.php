<?php

/**
 * Setup Menu Pages for Qraga
 *
 * @since     0.1.0
 */

defined('ABSPATH') || exit;

class Qraga_Menu // Renamed class
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'register_menu'));
		add_filter('plugin_action_links_qraga/qraga.php', array($this, 'add_settings_link'));
		add_filter('plugin_action_links_qraga/qraga.php', array($this, 'docs_link'));
	}

	/**
	 * Define Menu
	 *
	 * @since 0.1.0
	 */
	public function register_menu()
	{
		add_menu_page(
			__('Qraga', 'qraga'), // Updated Page Title and Text Domain
			__('Qraga', 'qraga'), // Updated Menu Title and Text Domain
			'manage_options',
			'qraga', // Updated Menu Slug
			array($this, 'display_react_admin_page'),
			'dashicons-cloud' // Icon for Qraga
		);
	}

	/**
	 * Output the root div for the React application.
	 *
	 * @since 0.1.0
	 */
	public function display_react_admin_page()
	{
		// Changed ID to match what frontend expects
		echo "<div id='qraga-admin-root'></div>"; 
	}

	/**
	 * Plugin Settings Link on plugin page
	 *
	 * @since 0.1.0
	 */
	function add_settings_link($links)
	{
		$settings = array(
			// Updated admin_url to point to 'qraga' slug and use 'qraga' text domain
			'<a href="' . admin_url('admin.php?page=qraga') . '">' . __('Settings', 'qraga') . '</a>',
		);
		return array_merge($links, $settings);
	}

	/**
	 * Plugin Documentation Link on plugin page
	 *
	 * @since 0.1.0
	 */
	function docs_link($links)
	{
		$docs = array(
			// Updated text domain
			'<a target="_blank" href="http://example.com/qraga-documentation">' . __('Documentation', 'qraga') . '</a>',
		);
		return array_merge($links, $docs);
	}
}

new Qraga_Menu(); // Updated class instantiation 