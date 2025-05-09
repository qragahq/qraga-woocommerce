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
		// 1. Get your raw SVG code (e.g., from qraga_black.svg)
		// Replace the content of $raw_svg with your actual SVG code.
		// Ensure the SVG is suitable for a menu icon (ideally single color or uses currentColor for fill).
		$raw_svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?> <svg width="20" zoomAndPan="magnify" viewBox="0 0 69.691416 69.589844" height="20" preserveAspectRatio="xMidYMid" version="1.0" id="svg17" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"> <defs id="defs1"> <clipPath id="49de27c450"> <path d="m 53.707031,125.10156 h 69.749999 v 69.75 H 53.707031 Z m 0,0" clip-rule="nonzero" id="path1" /> </clipPath> </defs> <g clip-path="url(#49de27c450)" id="g2" transform="translate(-53.707031,-125.10556)"> <path fill="currentColor" d="m 88.601562,159.89844 v 23.92187 c -0.03125,0 -0.0625,0.004 -0.09766,0.004 -13.191406,0 -23.921875,-10.73438 -23.921875,-23.92578 0,-13.1875 10.730469,-23.92188 23.921875,-23.92188 13.191408,0 23.925788,10.73438 23.925788,23.92188 0,4.28906 -1.13672,8.3125 -3.11719,11.79687 l 7.89063,7.89063 c 1.32031,-1.92188 2.44531,-3.97266 3.36328,-6.14063 1.8164,-4.29297 2.73437,-8.85156 2.73437,-13.54687 0,-4.69141 -0.91797,-9.25 -2.73437,-13.54297 -1.75391,-4.14453 -4.26172,-7.86719 -7.45703,-11.0586 -3.19141,-3.19531 -6.91407,-5.70312 -11.0586,-7.45703 -4.292972,-1.8164 -8.851565,-2.73437 -13.546878,-2.73437 -4.695312,0 -9.25,0.91797 -13.542968,2.73437 -4.144532,1.75391 -7.867188,4.26172 -11.0625,7.45703 -3.191407,3.19141 -5.699219,6.91407 -7.453126,11.0586 -1.816406,4.29297 -2.738281,8.85156 -2.738281,13.54297 0,4.69531 0.921875,9.2539 2.738281,13.54687 1.753907,4.14453 4.261719,7.86719 7.453126,11.0586 3.195312,3.19531 6.917968,5.70312 11.0625,7.45703 4.292968,1.8164 8.847656,2.73437 13.542968,2.73437 3.769532,0 7.445313,-0.59375 10.96875,-1.76562 v -6.77735 l 8.546878,8.54297 h 15.37891 L 88.601562,159.89844" fill-opacity="1" fill-rule="nonzero" id="path2" /> </g> </svg>';
		$icon_base64_svg = 'data:image/svg+xml;base64,' . base64_encode($raw_svg);

		add_menu_page(
			__('Qraga', 'qraga'), 
			__('Qraga', 'qraga'), 
			'manage_options',
			'qraga', 
			array($this, 'display_react_admin_page'),
			$icon_base64_svg // Use the base64 encoded SVG
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