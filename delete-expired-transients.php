<?php
/*
Plugin Name: Delete Expired Transients
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/delete-expired-transients/
Description: delete old, expired transients from WordPress wp_options table
Version: 1.0.0
Author: WebAware
Author URI: http://www.webaware.com.au/
Text Domain: delxtrans
*/

/*
copyright (c) 2013 WebAware Pty Ltd (email : rmckay@webaware.com.au)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('DELXTRANS_PLUGIN_ROOT')) {
	define('DELXTRANS_PLUGIN_ROOT', dirname(__FILE__) . '/');
	define('DELXTRANS_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));

	// scheduled tasks
	define('DELXTRANS_TASK_CLEAR_TRANSIENTS', 'delxtrans_cleartransients');
}

class DeleteExpiredTransients {

	/**
	* hook into WordPress
	*/
	public static function run() {
		// clean up after deactivation
		register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate'));

		// actions and filters
		add_action('init', array(__CLASS__, 'init'));
		add_action('admin_menu', array(__CLASS__, 'adminMenu'));
		add_filter('plugin_row_meta', array(__CLASS__, 'addPluginDetailsLinks'), 10, 2);
		add_action(DELXTRANS_TASK_CLEAR_TRANSIENTS, array(__CLASS__, 'clearExpiredTransients'));
	}

	/**
	* deactivate the plug-in
	*/
	public static function deactivate() {
		// remove scheduled tasks
		wp_clear_scheduled_hook(DELXTRANS_TASK_CLEAR_TRANSIENTS);
	}

	/**
	* initialise the plug-in
	*/
	public static function init() {
		// make sure we have a schedule for clearing expired transients
		if (!wp_next_scheduled(DELXTRANS_TASK_CLEAR_TRANSIENTS)) {
			wp_schedule_event(time(), 'daily', DELXTRANS_TASK_CLEAR_TRANSIENTS);
		}

		// load gettext domain
		load_plugin_textdomain('delxtrans', false, DELXTRANS_PLUGIN_ROOT . 'languages');
	}

	/**
	* admin menu items
	*/
	public static function adminMenu() {
		$title = __('Delete Expired Transients', 'delxtrans');
		add_management_page($title, $title, 'manage_options', 'delxtrans', array(__CLASS__, 'toolsDelete'));
	}

	/**
	* action hook for adding plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file == DELXTRANS_PLUGIN_NAME) {
			$links[] = '<a href="http://wordpress.org/support/plugin/delete-expired-transients">' . __('Get help', 'delxtrans') . '</a>';
			$links[] = '<a href="http://wordpress.org/plugins/delete-expired-transients/">' . __('Rating', 'delxtrans') . '</a>';
			$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=NJEL3SS8PBBJN">' . __('Donate', 'delxtrans') . '</a>';
		}

		return $links;
	}

	/**
	* process Tools menu item call
	*/
	public static function toolsDelete() {
		// check whether user has asked for deletions
		$action = '';
		if (!empty($_POST['delxtrans-action'])) {
			$action = $_POST['delxtrans-action'];
			switch ($action) {
				case 'delete-expired':
					self::clearExpiredTransients();
					break;
				case 'delete-all':
					self::clearAllTransients();
					break;
			}
		}

		$expiredCount = self::countExpiredTransients();

		include DELXTRANS_PLUGIN_ROOT . 'views/admin-tools-page.php';
	}

	/**
	* count the expired transients (including orphaned expirations)
	* @return int
	*/
	public static function countExpiredTransients() {
		global $wpdb;

		// get current PHP time, offset by a minute to prevent clashes with other tasks
		$threshold = time() - 60;

		$sql = "
			select count(*)
			from {$wpdb->options}
			where option_name regexp '^(_site)?_transient_timeout_.*'
			and option_value < '$threshold';
		";

		return $wpdb->get_var($sql);
	}

	/**
	* clear expired transients -- called on a schedule by wp-cron
	*/
	public static function clearExpiredTransients() {
		global $wpdb;

		// get current PHP time, offset by a minute to prevent clashes with other tasks
		$threshold = time() - 60;

		// delete expired transients, using the paired timeout record to find them
		$sql = "
			delete from t1, t2
			using {$wpdb->options} t1 join {$wpdb->options} t2
			on t2.option_name = replace(t1.option_name, '_timeout', '')
			where t1.option_name regexp '^(_site)?_transient_timeout_.*'
			and t1.option_value < '$threshold';
		";
		$wpdb->query($sql);

		// delete orphaned transient expirations
		$sql = "
			delete from {$wpdb->options}
			where option_name regexp '^(_site)?_transient_timeout_.*'
			and option_value < '$threshold';
		";
		$wpdb->query($sql);
	}

	/**
	* clear all transients
	*/
	public static function clearAllTransients() {
		global $wpdb;

		// delete all transients
		$sql = "
			delete from {$wpdb->options}
			where option_name regexp '^(_site)?_transient_.*'
		";
		$wpdb->query($sql);
	}

}

DeleteExpiredTransients::run();
