<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* manage the site list for network admin page
*/
class DelxtransSiteListTable extends WP_List_Table {

	protected $plugin;
	protected $pageURL;

	/**
	* configure the list table
	*/
	public function __construct($plugin, $pageURL) {
		$this->plugin = $plugin;
		$this->pageURL = $pageURL;

		// Set defaults
		parent::__construct( array(
			'singular'	=> _x('Blog', 'a single site in network', 'delete-expired-transients'),
			'plural'	=> _x('Blogs', 'sites in network', 'delete-expired-transients'),
			'ajax'		=> false,
		));
	}

	/**
	* set the columns in the list
	* @return array
	*/
	public function get_columns() {
		return array(
			'cb'			=> '<input type="checkbox" />',
			'blog_id'		=> __('Blog ID', 'delete-expired-transients'),
			'blogname'		=> __('Blog Name', 'delete-expired-transients'),
			'expired'		=> __('Expired transients', 'delete-expired-transients'),
			'total'			=> __('Total transients', 'delete-expired-transients'),
		);
	}

	/**
	* set the sortable columns
	* @return array
	*/
	public function get_sortable_columns() {
		return array(
			'blog_id'		=> array('blog_id', true),
			'blogname'		=> array('blogname', true),
		);
	}

	/**
	* add some bulk actions
	* @return array
	*/
	function get_bulk_actions() {
		return array(
			'expired'	=> __('Delete expired', 'delete-expired-transients'),
			'deleteall'	=> __('Delete all', 'delete-expired-transients'),
		);
	}

	/**
	* prepare the list data
	*/
	public function prepare_items() {

		// Define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		// Build the array
		$this->_column_headers = array($columns, $hidden, $sortable);

		// Figure out the current page and how many items there are
		$blogs = $this->getBlogs();
		$current_page = $this->get_pagenum();
		$total_items = count($blogs);
		$per_page = 10;
        $blogs = array_slice($blogs, (($current_page - 1) * $per_page), $per_page);

		// fetch data for current page
		$this->items = array();
		foreach ($blogs as $blog) {
			$counts = DelxtransCleaners::countBlogTransients($blog['blog_id']);

			$blog['expired']	= $counts->expired;
			$blog['total']		= $counts->total + $counts->never_expire;

			$this->items[] = $blog;
		}

		// Register pagination options
		$this->set_pagination_args( array(
			'total_items'		=> $total_items,
			'per_page'			=> $per_page,
			'total_pages'		=> ceil($total_items / $per_page),
		));
	}

	/**
	* show column data using default function
	* @param array $item
	* @param string $column_name
	* @return string
	*/
	public function column_default($item, $column_name) {
		switch($column_name) {

			case 'expired':
			case 'total':
				return number_format_i18n($item[$column_name]);

			default:
				return esc_html($item[$column_name]);

		}
	}

	/**
	* show checkbox column
	* @param array $item
	* @return string
	*/
	public function column_cb($item) {
		return sprintf('<input type="checkbox" name="blog_ids[]" id="cb_blog_id_%1$d" value="%1$d" />', $item['blog_id']);
	}

	/**
	* show blogname column with row actions
	* @param array $item
	* @return string
	*/
	public function column_blogname($item) {
		$action_url = add_query_arg('blog_id', $item['blog_id'], $this->pageURL);

		$actions = array(
			'expired'	=> sprintf('<a href="%s">%s</a>',
								wp_nonce_url(add_query_arg('action', 'expired', $action_url), 'blog-delete', 'delxtrans_nonce', false),
								__('Delete expired', 'delete-expired-transients')),

			'delete'	=> sprintf('<a href="%s">%s</a>',
								wp_nonce_url(add_query_arg('action', 'deleteall', $action_url), 'blog-delete', 'delxtrans_nonce', false),
								__('Delete all', 'delete-expired-transients')),
		);

		return sprintf('%s %s', esc_html($item['blogname']), $this->row_actions($actions));
	}

	/**
	* get list of blogs on this site (network)
	* @return array
	*/
	protected function getBlogs() {
		global $wpdb;

		// get list of IDs for blogs on current site (network)
		// defaults to sorted by blog_id ascending
		$site = get_current_site();
		$sql = "select blog_id from {$wpdb->blogs} where site_id = %d order by blog_id";
		$ids = $wpdb->get_col($wpdb->prepare($sql, $site->id));

		$orderby = empty($_REQUEST['orderby']) ? 'blog_id' : $_REQUEST['orderby'];
		$order   = empty($_REQUEST['order']) ? 'asc' : strtolower($_REQUEST['order']);

		if ($orderby == 'blog_id' && $order == 'desc') {
			// sort by blog_id descending
			$ids = array_reverse($ids);
		}

		// collect the blognames from each blog, building the base array for blogs
		$blogs = array();
		foreach ($ids as $blog_id) {
			$blogs[] = array(
				'blog_id'		=> $blog_id,
				'blogname'		=> get_blog_option($blog_id, 'blogname'),
			);
		}

		if ($orderby == 'blogname') {
			if ($order == 'desc') {
				// sort by blogname descending
				usort($blogs, array(__CLASS__, 'orderBlognameDesc'));
			}
			else {
				// sort by blogname ascending
				usort($blogs, array(__CLASS__, 'orderBlognameAsc'));
			}
		}

		return $blogs;
	}

	/**
	* comparison for sorting blog list by name, ascending
	* @param array $blog1
	* @param array $blog2
	* @return int
	*/
	public static function orderBlognameAsc($blog1, $blog2) {
		return strcasecmp($blog1['blogname'], $blog2['blogname']);
	}

	/**
	* comparison for sorting blog list by name, descending
	* @param array $blog1
	* @param array $blog2
	* @return int
	*/
	public static function orderBlognameDesc($blog1, $blog2) {
		return strcasecmp($blog2['blogname'], $blog1['blogname']);
	}

}
