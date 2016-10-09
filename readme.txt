=== Delete Expired Transients ===
Contributors: webaware
Plugin Name: Delete Expired Transients
Plugin URI: https://shop.webaware.com.au/downloads/delete-expired-transients/
Author URI: https://webaware.com.au/
Donate link: https://shop.webaware.com.au/donations/?donation_for=Delete+Expired+Transients
Tags: cache, expired, transient, transients, wp_options
Requires at least: 3.7
Tested up to: 4.6.1
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

delete old, expired transients from WordPress wp_options table

== Description ==

Delete old, expired transients from the WordPress options table (`wp_options`), to prevent them from bloating your database and even slowing down your website.

Unless you are using an object cache (like memcached), WordPress stores transient records in the options table. Many transients are given an expiration time, so in theory they should disappear after some time. In practise, because old transients are only deleted when requested again after they've expired, many transients stay in the database. After a while, there can be thousands or even millions of expired transients needlessly taking up space in your options table, depending on what your plugins are doing.

Delete Expired Transients schedules a daily task to delete any expired transients from the options table. It performs this operation with a single SQL query, and then runs a second query to find any orphaned expiration records and deletes them too.

There are a few other plugins around that clean up expired transients. This one is written for fast performance, set-and-forget scheduled housekeeping, and maximum compatibility. It uses the PHP time to determine whether transients are expired, not the database time (which can be different). It does only one job, and it does it well with the minimum of resources.

Now optimised for WordPress Multisite.

= Translations =

Many thanks to the generous efforts of our translators:

* English (en_CA) -- [the English (Canadian) translation team](https://translate.wordpress.org/locale/en-ca/default/wp-plugins/delete-expired-transients)
* French (fr_FR) -- [the French translation team](https://translate.wordpress.org/locale/fr/default/wp-plugins/delete-expired-transients)
* Hungarian (hu_HU) -- [the Hungarian translation team](https://translate.wordpress.org/locale/hu/default/wp-plugins/delete-expired-transients)
* Norwegian: Bokmål (nb_NO) -- [neonnero](http://www.neonnero.com/)
* Norwegian: Nynorsk (nn_NO) -- [neonnero](http://www.neonnero.com/)
* Russian (ru_RU) -- [the Russian translation team](https://translate.wordpress.org/locale/ru/default/wp-plugins/delete-expired-transients)
* Spanish (es_ES) -- [the Spanish translation team](https://translate.wordpress.org/locale/es/default/wp-plugins/delete-expired-transients)

If you'd like to help out by translating this plugin, please [sign up for an account and dig in](https://translate.wordpress.org/projects/wp-plugins/delete-expired-transients).

== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

That's it! Expired transients will be deleted the next time you visit your website, and then again once every day after that.

== Frequently Asked Questions ==

= What is a "transient" anyway? =

According to [Codex](https://codex.wordpress.org/Transients_API), transients are:

> a simple and standardized way of storing cached data in the database temporarily by giving it a custom name and a timeframe after which it will expire and be deleted.

NB: by default they are stored in the database, but if you have an object cache like memcached they won't be.

= Do I need this plugin? =

Most websites don't need this plugin. It all depends on what plugins are installed and how they are being used. Some plugins make heavy use of transients to improve website performance, and that can lead to a build up of old transient records in the database. If your wp_options table is growing and causing problems with website performance or backups, this plugin can help you by keeping transients under control.

= Why do transients build up, and fill up my wp_options table? =

With the current way that the transients API works, expired transients are only deleted when they are accessed after their expiration date. When transients are user-specific or otherwise fairly unique, they can sit there in the database forever unless some housekeeping task is run to clean them up. WordPress doesn't currently have such a housekeeping task. That's what this plugin does.

= Will I lose any important data if I install this plugin? =

Only if you have a plugin that is really badly written. Transients can be deleted for a variety of reasons, because by definition they are considered ephemeral. They are considered safe to delete at any time because they are supposedly only ever going to contain information that can be rebuilt.

There are some notable exceptions, e.g. some shopping carts store cart sessions in transients; this is obviously not information that can be easily rebuilt. That data will only be deleted by this plugin if it has expired, which means it would be deleted by WordPress anyway, so it is safe to use this plugin with shopping carts.

= How do I know it's working? =

On the Tools menu in the WordPress admin, you will find a screen for deleting transients. It tells you how many expired transients there are in your database.

NB: after you install and activate this plugin, the first thing it does is schedule a housekeeping task to delete expired transients. This means that there may not be any transients found when you visit this page in the tools menu straight after installing the plugin, because they may have already been deleted. You probably never need to delete expired transients manually, because they'll be automatically deleted daily.

= Do I need this if I'm running an object cache? =

No. Object caches like memcached are limited pools of data, and they already purge old data periodically so that they can fit newer data. This means that old transients will be removed from the cache automatically. It also means that new, fresh transients can be removed at any time too, which is why you should never store anything in a transient that can't be rebuilt easily. See this article on the WPEngine blog for more details: [A Technical Transients Treatise](https://wpengine.com/2013/02/wordpress-transient-api/).

= Can I change the schedule to run more often? =

Not yet. I'll consider adding a setting for that if it seems to be popular. I reckon daily is often enough even for busy websites. When network activated on multisite, it runs hourly to ensure it can get around all of the sites frequently enough.

= How does the plugin handle multisite? =

If you activate it on individual sites within multisite, each site operates just the same as a stand-alone website.

If you network activate the plugin, it operates differently. You get access to a master admin screen that allows bulk deletion of transients across multiple blogs in a network. This can also help you spot problem sites, by browsing through the list of sites and seeing if any have large numbers of transients. You can find this admin page under Settings on the multisite network admin.

The scheduled task also operates differently, batching up sites to clear expired transients once every hour. The scheduled task can be initiated by activity on any blog. Only 5 blogs are cleaned on each run, so up to 120 blogs will be cleaned each day.

NB: if your website has multiple networks (e.g. if you're running [WP Multi Network](https://wordpress.org/plugins/wp-multi-network/)) then you'll need to network activate it on each network. Each activation only cleans the blogs on that network, e.g. activating on example.com will clean blog.example.com, images.example.com, shop.example.com, but not forum.example.net if that's on a separate network in the multisite.

== Contributions ==

* [Translate into your preferred language](https://translate.wordpress.org/projects/wp-plugins/delete-expired-transients)
* [Fork me on GitHub](https://github.com/webaware/delete-expired-transients)

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
2. Multisite network admin page for manually deleting transients

== Upgrade Notice ==

= 2.0.5 =

added manual deletion of obsolete sessions from WooCommerce version 2.4 and earlier

== Changelog ==

The full changelog can be found [on GitHub](https://github.com/webaware/delete-expired-transients/blob/master/changelog.md). Recent entries:

### 2.0.5, 2016-10-09

* added: Hungarian translation (thanks, [Tom Vicces](https://profiles.wordpress.org/theguitarlesson/)!)
* added: manual deletion of obsolete sessions from WooCommerce version 2.4 and earlier
