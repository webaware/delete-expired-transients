<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* plugin controller class
*/
class DelxtransPlugin {

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* hook into WordPress
	*/
	protected function __construct() {
		// clean up after deactivation
		register_deactivation_hook(DELXTRANS_PLUGIN_FILE, array($this, 'deactivate'));

		// ensure we have a schedule to run the cleaner task
		add_action('init', array($this, 'initSchedule'));
		add_action(DELXTRANS_TASK_CLEAR_TRANSIENTS, array($this, 'taskSingleSite'));
		add_action(DELXTRANS_TASK_CLEAR_TRANSIENTS_MS, array($this, 'taskNetwork'));

		require DELXTRANS_PLUGIN_ROOT . 'includes/class.DelxtransCleaners.php';

		// if we're not in admin, then else nothing to do; go home.
		if (!is_admin()) {
			return;
		}

		// other actions and filters
		add_action('init', array($this, 'loadTranslations'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		if (is_network_admin()) {
			// has been network activated and is running in the network admin
			require DELXTRANS_PLUGIN_ROOT . 'includes/class.DelxtransNetwork.php';
			new DelxtransNetwork($this);
		}
		else {
			// stand-alone site or blog on multisite
			require DELXTRANS_PLUGIN_ROOT . 'includes/class.DelxtransSite.php';
			new DelxtransSite($this);
		}
	}

	/**
	* deactivate the plug-in
	*/
	public function deactivate() {
		if (is_network_admin()) {
			// remove task from all blogs on site (network)
			global $wpdb;
			$site = get_current_site();
			$sql = "select blog_id from {$wpdb->blogs} where site_id = %d";
			$blog_ids = $wpdb->get_col($wpdb->prepare($sql, $site->id));

			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				wp_clear_scheduled_hook(DELXTRANS_TASK_CLEAR_TRANSIENTS_MS);
				restore_current_blog();
			}
		}
		else {
			// remove single site task
			wp_clear_scheduled_hook(DELXTRANS_TASK_CLEAR_TRANSIENTS);
		}
	}

	/**
	* load translation strings
	*/
	public function loadTranslations() {
		load_plugin_textdomain('delete-expired-transients', false, basename(dirname(DELXTRANS_PLUGIN_FILE)) . '/languages/');
	}

	/**
	* make sure we have a schedule for clearing expired transients
	*/
	public function initSchedule() {
		if ($this->isPluginNetworkActivated()) {
			// we want to run the multisite task every hour from any site
			if (!wp_next_scheduled(DELXTRANS_TASK_CLEAR_TRANSIENTS_MS)) {
				wp_schedule_event(time() + 5, 'hourly', DELXTRANS_TASK_CLEAR_TRANSIENTS_MS);
				wp_clear_scheduled_hook(DELXTRANS_TASK_CLEAR_TRANSIENTS);
			}
		}
		else {
			// we want to run the site-specific task daily from the activated site
			if (!wp_next_scheduled(DELXTRANS_TASK_CLEAR_TRANSIENTS)) {
				wp_schedule_event(time() + 5, 'daily', DELXTRANS_TASK_CLEAR_TRANSIENTS);
				wp_clear_scheduled_hook(DELXTRANS_TASK_CLEAR_TRANSIENTS_MS);
			}
		}
	}

	/**
	* run the site-specific task to clear expired transients only for current blog
	*/
	public function taskSingleSite() {
		$blog_id = get_current_blog_id();

		DelxtransCleaners::clearBlogExpired($blog_id);
	}

	/**
	* run the network task to clear expired transients for all blogs
	*/
	public function taskNetwork() {
		// only run on current blog if task hasn't been run for more than an hour
		$last_run = get_site_option('delxtran_lastrun', array('time' => 0, 'blog_id' => 0));
		if ($last_run['time'] + HOUR_IN_SECONDS < time()) {
			global $wpdb;

			// update the last run time, to prevent another blog running this task
			$last_run['time'] = time();
			update_site_option('delxtran_lastrun', $last_run);

			// allow other plugins to override the number of blogs cleaned in one hit
			// NB: don't set too high, it could impact your multisite's performance!
			$limit = absint(apply_filters('delxtrans_ms_limit', 5));
			if ($limit < 1) {
				// restore sanity
				$limit = 1;
			}

			// get the next tranche of blogs to clean
			$site = get_current_site();
			$sql = "select blog_id from {$wpdb->blogs} where site_id = %d and blog_id > %d order by blog_id limit $limit";
			$blog_ids = $wpdb->get_col($wpdb->prepare($sql, $site->id, $last_run['blog_id']));

			if (empty($blog_ids)) {
				// no more blogs found, clean up site transients and start over
				DelxtransCleaners::clearSiteExpired($site->id);
				$blog_id = 0;
			}
			else {
				// clean each blog in tranche
				foreach ($blog_ids as $blog_id) {
					DelxtransCleaners::clearBlogExpired($blog_id);
				}
			}

			// save where we're up to, for the next run
			update_site_option('delxtran_lastrun', array('time' => time(), 'blog_id' => $blog_id));
		}
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file == DELXTRANS_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/delete-expired-transients">%s</a>', _x('Get help', 'plugin details links', 'delete-expired-transients'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/delete-expired-transients/">%s</a>', _x('Rating', 'plugin details links', 'delete-expired-transients'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/delete-expired-transients">%s</a>', _x('Translate', 'plugin details links', 'delete-expired-transients'));
			$links[] = sprintf('<a href="https://shop.webaware.com.au/donations/?donation_for=Delete+Expired+Transients">%s</a>', _x('Donate', 'plugin details links', 'delete-expired-transients'));
		}

		return $links;
	}

	/**
	* is this plugin active for network?
	* @return bool
	*/
	protected function isPluginNetworkActivated() {
		if (!is_multisite()) {
			return false;
		}

		if (!function_exists('is_plugin_active_for_network')) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network(DELXTRANS_PLUGIN_NAME);
	}

}
