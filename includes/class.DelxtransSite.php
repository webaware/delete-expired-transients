<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* handler for stand-alone site or multisite blog not seen from network admin
*/
class DelxtransSite {

	protected $plugin;

	/**
	* hook into WordPress
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		add_action('admin_menu', array($this, 'adminMenu'));
		add_action('admin_print_styles-tools_page_delxtrans', array($this, 'adminStyles'));
	}

	/**
	* admin menu items
	*/
	public function adminMenu() {
		$title = __('Delete Transients', 'delete-expired-transients');
		add_management_page($title, $title, 'manage_options', 'delxtrans', array($this, 'toolsDelete'));
	}

	/**
	* custom CSS for page
	*/
	public function adminStyles() {
		$ver = SCRIPT_DEBUG ? time() : DELXTRANS_PLUGIN_VERSION;
		wp_enqueue_style('delxtran-admin', plugins_url('/css/admin.css', DELXTRANS_PLUGIN_FILE), false, $ver);
	}

	/**
	* process menu item call
	*/
	public function toolsDelete() {
		$blog_id = get_current_blog_id();
		$msg = '';

		// check whether user has asked for deletions
		$action = '';
		if (!empty($_POST['delxtrans-action'])) {
			check_admin_referer('delete', 'delxtrans_wpnonce');

			$action = $_POST['delxtrans-action'];
			switch ($action) {

				case 'delete-expired':
					DelxtransCleaners::clearBlogExpired($blog_id);
					$msg = __('Expired transients deleted.', 'delete-expired-transients');
					break;

				case 'delete-all':
					DelxtransCleaners::clearBlogAll($blog_id);
					$msg = __('All transients deleted.', 'delete-expired-transients');
					break;

				case 'delete-woo-sessions':
					DelxtransCleaners::clearBlogWooCommerceSessions($blog_id);
					$msg = __('All obsolete WooCommerce sessions deleted.', 'delete-expired-transients');
					break;

			}
		}

		$counts = DelxtransCleaners::countBlogTransients($blog_id);

		include DELXTRANS_PLUGIN_ROOT . 'views/admin-tools-page.php';
	}

}
