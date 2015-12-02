<?php

if (!defined('ABSPATH')) {
	exit;
}

class DelxtransCleaners {

	/**
	* count the transients (including orphaned expirations) for current blog
	* @param int $blog_id
	* @return int
	*/
	public static function countBlogTransients($blog_id) {
		// sanity check
		$blog_id = intval($blog_id);
		if ($blog_id <= 0) {
			return false;
		}

		global $wpdb;

		// get current PHP time, offset by a minute to avoid clashes with other tasks
		$threshold = time() - MINUTE_IN_SECONDS;

		// get table name for options on specified blog
		$table = $wpdb->get_blog_prefix($blog_id) . 'options';

		// count transient expiration records, total and expired
		$sql = "
			select count(*) as `total`, count(case when option_value < '$threshold' then 1 end) as `expired`
			from $table
			where (option_name like '\_transient\_timeout\_%' or option_name like '\_site\_transient\_timeout\_%')
		";
		$counts = $wpdb->get_row($sql);

		// count never-expire transients
		$sql = "
			select count(*)
			from $table
			where (option_name like '\_transient\_%' or option_name like '\_site\_transient\_%')
			and option_name not like '%\_timeout\_%'
			and autoload = 'yes'
		";
		$counts->never_expire = $wpdb->get_var($sql);

		return $counts;
	}

	/**
	* clear expired transients for current blog
	* @param int $blog_id
	*/
	public static function clearBlogExpired($blog_id) {
		// sanity check
		$blog_id = intval($blog_id);
		if ($blog_id <= 0) {
			return;
		}

		global $wpdb;

		// get current PHP time, offset by a minute to avoid clashes with other tasks
		$threshold = time() - MINUTE_IN_SECONDS;

		// get table name for options on specified blog
		$table = $wpdb->get_blog_prefix($blog_id) . 'options';

		// delete expired transients, using the paired timeout record to find them
		$sql = "
			delete from t1, t2
			using $table t1
			join $table t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
			where (t1.option_name like '\_transient\_timeout\_%' or t1.option_name like '\_site\_transient\_timeout\_%')
			and t1.option_value < '$threshold'
		";
		$wpdb->query($sql);

		// delete orphaned transient expirations
		// also delete NextGEN Gallery 2.x display cache timeout aliases
		$sql = "
			delete from $table
			where (
				   option_name like '\_transient\_timeout\_%'
				or option_name like '\_site\_transient\_timeout\_%'
				or option_name like 'displayed\_galleries\_%'
				or option_name like 'displayed\_gallery\_rendering\_%'
			)
			and option_value < '$threshold'
		";
		$wpdb->query($sql);
	}

	/**
	* clear all transients for blog
	* @param int $blog_id
	*/
	public static function clearBlogAll($blog_id) {
		// sanity check
		$blog_id = intval($blog_id);
		if ($blog_id <= 0) {
			return;
		}

		global $wpdb;

		// get table name for options on specified blog
		$table = $wpdb->get_blog_prefix($blog_id) . 'options';

		// delete all transients and their timeouts
		// also delete NextGEN Gallery 2.x display cache timeout aliases
		$sql = "
			delete from $table
			where option_name like '\_transient\_%'
			or    option_name like '\_site\_transient\_%'
			or    option_name like 'displayed\_galleries\_%'
			or    option_name like 'displayed\_gallery\_rendering\_%'
		";
		$wpdb->query($sql);
	}

	/**
	* count the site transients (including orphaned expirations) for current site (network)
	* @param int $site_id
	* @return int
	*/
	public static function countSiteTransients($site_id) {
		// sanity check
		$site_id = intval($site_id);
		if ($site_id <= 0) {
			return false;
		}

		global $wpdb;

		// get current PHP time, offset by a minute to avoid clashes with other tasks
		$threshold = time() - MINUTE_IN_SECONDS;

		// count transient expiration records, total and expired
		$sql = "
			select count(*) as `total`, count(case when meta_value < '$threshold' then 1 end) as `expired`
			from {$wpdb->sitemeta}
			where meta_key like '\_site\_transient\_timeout\_%'
			and site_id = $site_id
		";
		$counts = $wpdb->get_row($sql);

		// count never-expire transients
		$sql = "
			select count(*)
			from {$wpdb->sitemeta}
			where meta_key like '\_site\_transient\_%'
			and meta_key not like '%\_timeout\_%'
			and site_id = $site_id
		";
		$counts->never_expire = $wpdb->get_var($sql) - $counts->total;
		if ($counts->never_expire < 0) {
			// restore sanity!
			$counts->never_expire = 0;
		}

		return $counts;
	}

	/**
	* clear expired transients for current site (network)
	* @param int $site_id
	*/
	public static function clearSiteExpired($site_id) {
		// sanity check
		$site_id = intval($site_id);
		if ($site_id <= 0) {
			return false;
		}

		global $wpdb;

		// get current PHP time, offset by a minute to avoid clashes with other tasks
		$threshold = time() - MINUTE_IN_SECONDS;

		// delete expired transients, using the paired timeout record to find them
		$sql = "
			delete from t1, t2
			using {$wpdb->sitemeta} t1
			join {$wpdb->sitemeta} t2 on t2.site_id = t1.site_id and t2.meta_key = replace(t1.meta_key, '_timeout', '')
			where t1.meta_key like '\_site\_transient\_timeout\_%'
			and t1.meta_value < '$threshold'
			and t1.site_id = $site_id
		";
		$wpdb->query($sql);

		// delete orphaned transient expirations
		$sql = "
			delete from {$wpdb->sitemeta}
			where meta_key like '\_site\_transient\_timeout\_%'
			and meta_value < '$threshold'
			and site_id = $site_id
		";
		$wpdb->query($sql);
	}

	/**
	* clear all transients for current site (network)
	* @param int $site_id
	*/
	public static function clearSiteAll($site_id) {
		// sanity check
		$site_id = intval($site_id);
		if ($site_id <= 0) {
			return false;
		}

		global $wpdb;

		// delete all site transients and their timeouts
		$sql = "
			delete from {$wpdb->sitemeta}
			where meta_key like '\_site\_transient\_%'
			and site_id = $site_id
		";
		$wpdb->query($sql);
	}

}
