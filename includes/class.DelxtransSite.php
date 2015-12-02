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
	}

	/**
	* admin menu items
	*/
	public function adminMenu() {
		$title = __('Delete Transients', 'delete-expired-transients');
		add_management_page($title, $title, 'manage_options', 'delxtrans', array($this, 'toolsDelete'));
	}

	/**
	* process menu item call
	*/
	public function toolsDelete() {
		$blog_id = get_current_blog_id();

		// check whether user has asked for deletions
		$action = '';
		if (!empty($_POST['delxtrans-action'])) {
			check_admin_referer('delete', 'delxtrans_wpnonce');

			$action = $_POST['delxtrans-action'];
			switch ($action) {
				case 'delete-expired':
					DelxtransCleaners::clearBlogExpired($blog_id);
					break;
				case 'delete-all':
					DelxtransCleaners::clearBlogAll($blog_id);
					break;
			}
		}

		$counts = DelxtransCleaners::countBlogTransients($blog_id);

		include DELXTRANS_PLUGIN_ROOT . 'views/admin-tools-page.php';
	}

}
