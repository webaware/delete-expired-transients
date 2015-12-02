<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* handler for multisite network admin
*/
class DelxtransNetwork {

	protected $plugin;
	protected $pageURL;

	/**
	* hook into WordPress
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		add_action('load-settings_page_delxtrans', array($this, 'processActions'));
		add_action('network_admin_menu', array($this, 'adminMenu'));
		add_action('admin_enqueue_scripts', array($this, 'adminStyles'));
	}

	/**
	* admin menu items
	*/
	public function adminMenu() {
		$title = __('Delete Transients', 'delete-expired-transients');
		add_submenu_page('settings.php', $title, $title, 'manage_network_options', 'delxtrans', array($this, 'networkDelete'));

		$this->pageURL = admin_url('network/settings.php?page=delxtrans');
	}

	/**
	* custom CSS for page
	*/
	public function adminStyles() {
		$screen = get_current_screen();

		if (!empty($screen->id) && $screen->id == 'settings_page_delxtrans-network') {
			wp_enqueue_style('delxtran-admin', plugins_url('/css/admin.css', DELXTRANS_PLUGIN_FILE), false, DELXTRANS_PLUGIN_VERSION);
		}
	}

	/**
	* process menu item call
	*/
	public function networkDelete() {
		// extend the list table class for displaying our blog list
		if (!class_exists('WP_List_Table')) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		require DELXTRANS_PLUGIN_ROOT . 'includes/class.DelxtransSiteListTable.php';
		$sitelistTable = new DelxtransSiteListTable($this->plugin, $this->pageURL);

		// get site transient counts
		$site = get_current_site();
		$site_counts = DelxtransCleaners::countSiteTransients($site->id);

		$messages = array(
			'expired'			=> __('Expired transients deleted.', 'delete-expired-transients'),
			'deleteall'			=> __('All transients deleted.', 'delete-expired-transients'),
			'site-expired'		=> __('Expired site transients deleted.', 'delete-expired-transients'),
			'site-deleteall'	=> __('All site transients deleted.', 'delete-expired-transients'),
		);

		$message = false;
		if (isset($_GET['message'])) {
			if (isset($messages[$_GET['message']])) {
				$message = $messages[$_GET['message']];
			}

			// ensure that message is stripped from navigation links
			$_SERVER['REQUEST_URI'] = remove_query_arg('message', $_SERVER['REQUEST_URI']);
		}

		// show the page
		include DELXTRANS_PLUGIN_ROOT . 'views/admin-network-page.php';
	}

	/**
	* process delete actions
	*/
	public function processActions() {
		// check whether user has asked for deletions
		$action = empty($_REQUEST['action']) ? '' : $_REQUEST['action'];
		if ($action === '-1' && !empty($_REQUEST['action2'])) {
			$action = $_REQUEST['action2'];
		}

		if (!empty($action)) {
			$sendback = remove_query_arg(array('action', 'site_id', 'blog_id', 'blog_ids', 'message', 'delxtrans_nonce'), wp_get_referer());

			$blog_ids = false;
			if (!empty($_REQUEST['blog_id'])) {
				$blog_ids = array(intval($_REQUEST['blog_id']));
			}
			else if (!empty($_REQUEST['blog_ids'])) {
				$blog_ids = $_REQUEST['blog_ids'];
				if (!is_array($blog_ids)) {
					$blog_ids = (array) $blog_ids;
				}
				$blog_ids = array_map('intval', $blog_ids);
			}

			if (!empty($blog_ids)) {
				check_admin_referer('blog-delete', 'delxtrans_nonce');

				switch ($action) {

					case 'expired':
						foreach ($blog_ids as $blog_id) {
							DelxtransCleaners::clearBlogExpired($blog_id);
						}
						wp_redirect(add_query_arg('message', $action, $sendback));
						exit;
						break;

					case 'deleteall':
						foreach ($blog_ids as $blog_id) {
							DelxtransCleaners::clearBlogAll($blog_id);
						}
						wp_redirect(add_query_arg('message', $action, $sendback));
						exit;
						break;

				}
			}

			$site_id = empty($_REQUEST['site_id']) ? 0 : intval($_REQUEST['site_id']);
			if (!empty($site_id)) {
				check_admin_referer('site-delete', 'delxtrans_nonce');

				switch ($action) {

					case 'site-expired':
						DelxtransCleaners::clearSiteExpired($site_id);
						wp_redirect(add_query_arg('message', $action, $sendback));
						exit;
						break;

					case 'site-deleteall':
						DelxtransCleaners::clearSiteAll($site_id);
						wp_redirect(add_query_arg('message', $action, $sendback));
						exit;
						break;

				}
			}
		}
	}

	/**
	* delete expired transients on listed blogs
	*/
	protected function clearExpiredTransients($ids) {
		foreach ($ids as $blog_id) {
			if ($blog_id > 0) {
				DelxtransCleaners::clearBlogExpired($blog_id);
			}
		}
	}

	/**
	* delete all transients on listed blogs
	*/
	protected function clearAllTransients($ids) {
		foreach ($ids as $blog_id) {
			if ($blog_id > 0) {
				DelxtransCleaners::clearBlogAll($blog_id);
			}
		}
	}

}
