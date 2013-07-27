=== Delete Expired Transients ===
Contributors: webaware
Plugin Name: Delete Expired Transients
Plugin URI: http://snippets.webaware.com.au/wordpress-plugins/delete-expired-transients/
Author URI: http://www.webaware.com.au/
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NJEL3SS8PBBJN
Tags: cache, clean, database, expired, transient, transients, wp_options
Requires at least: 3.2.1
Tested up to: 3.6
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

delete old, expired transients from WordPress wp_options table

== Description ==

Delete old, expired transients from the WordPress options table (`wp_options`), to prevent them from bloating your database and even slowing down your website.

Unless you are using an object cache (like memcached), WordPress stores transient records in the options table. Many transients are given an expiration time, so in theory they should disappear after some time. In practise, because old transients are only deleted when requested again after they've expired, many transients stay in the database long after they've expired. After a while, there can be thousands or even millions of expired transients needlessly taking up space in your options table.

Delete Expired Transients schedules a daily task to delete any expired transients from the options table. It performs this operation with a single SQL query, and then runs a second query to find any orphaned expiration records and deletes them too.

There are a few other plugins around that clean up expired transients. This one is written for fast performance, set-and-forget scheduled housekeeping, and maximum compatibility. It uses the PHP time to determine whether transients are expired, not the database time (which can be different). It does only one job, and it does it well with the minimum of resources.

== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

That's it! Expired transients will be deleted the next time you visit your website, and then again once every day after that.

== Frequently Asked Questions ==

= What is a "transient" anyway? =

According to [Codex](http://codex.wordpress.org/Transients_API), transients are:

> a simple and standardized way of storing cached data in the database temporarily by giving it a custom name and a timeframe after which it will expire and be deleted.

NB: by default they are stored in the database, but if you have an object cache like memcached they won't be.

= Why do they build up, and fill up my wp_options table? =

With the current way that the transients API works, expired transients are only deleted when they are accessed after their expiration date. When transients are user-specific or otherwise fairly unique, they can sit there in the database forever unless some housekeeping task is run to clean them up. WordPress doesn't currently have such a housekeeping task. That's what this plugin does.

= Will I lose any important data if I install this plugin? =

Only if you have a plugin that is really badly written. Transients can be deleted for a variety of reasons, because by definition they are considered ephemeral. They are considered safe to delete at any time because they are supposedly only ever going to contain information that can be rebuilt.

There are some notable exceptions, e.g. WooCommerce, WP e-Commerce, and some other shopping carts store cart sessions in transients; this is obviously not information that can be easily rebuilt. That data will only be deleted by this plugin if it has expired, which means it would be deleted by WordPress anyway, so it is safe to use this plugin with shopping carts.

= How do I know it's working? =

On the Tools menu in the WordPress admin, you will find a screen for deleting expired transients. It tells you how many expired transients there are in your database.

NB: after you install and activate this plugin, the first thing it does is schedule a housekeeping task to delete expired transients. This means that there won't be any transients found when you visit this page in the tools menu straight after installing the plugin, because they'll have already been deleted. You probably never need to delete expired transients manually, because they'll be automatically deleted daily.

= Do I need this if I'm running an object cache? =

No. Object caches are limited pools of data, and they already purge old data periodically so that they can fit newer data. This means that old transients will be removed from the cache automatically. It also means that new, fresh transients can be removed at any time too, which is why you should never store anything in a transient that can't be rebuilt easily -- and shopping carts that put cart sessions in transients are taking risks with your data! See this article on the WPEngine blog for more details: [A Technical Transients Treatise](http://wpengine.com/2013/02/wordpress-transient-api/).

= Can I change the schedule to run more often? =

Not yet. I'll consider adding a setting for that if it seems to be popular. I reckon daily is probably often enough even for busy websites.

== Useful SQL queries ==

Here's a few useful SQL queries to run in MySQL when you are trying to debug what's happening with transients.

`-- transients that are not autoloaded (should be all with expiration times)
select option_name, option_value
from wp_options
where option_name regexp '^(_site)?_transient_.*'
and autoload='no'
order by option_name;`

`-- transient expirations
select option_name, option_value, from_unixtime(option_value) as expiry_time
from wp_options
where option_name regexp '^(_site)?_transient_timeout_.*'
order by option_value desc;`

`-- transient expirations with paired transients (inc. orphans)
select t1.option_name, t2.option_name, t1.option_value, from_unixtime(t1.option_value) as expiry_time
from wp_options t1
left join wp_options t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
where t1.option_name regexp '^(_site)?_transient_timeout_.*'
order by t1.option_value desc;`

`-- expired transient expirations with paired transients (inc. orphans)
select t1.option_name, t2.option_name, t1.option_value, from_unixtime(t1.option_value) as expiry_time
from wp_options t1
left join wp_options t2 on t2.option_name = replace(t1.option_name, '_timeout', '')
where t1.option_name regexp '^(_site)?_transient_timeout_.*'
and t1.option_value < unix_timestamp()
order by t1.option_value desc;`


== Screenshots ==

1. Tools page for manually deleting transients

== Changelog ==

= 1.0.0 [2013-07-27] =
* initial public release
