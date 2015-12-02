<?php
/*
Plugin Name: Delete Expired Transients
Plugin URI: http://shop.webaware.com.au/downloads/delete-expired-transients/
Description: delete old, expired transients from WordPress wp_options table
Version: 2.0.4.1
Author: WebAware
Author URI: http://webaware.com.au/
Text Domain: delete-expired-transients
Domain Path: /languages/
*/

/*
copyright (c) 2013-2015 WebAware Pty Ltd (email : support@webaware.com.au)

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


if (!defined('ABSPATH')) {
	exit;
}

define('DELXTRANS_PLUGIN_FILE', __FILE__);
define('DELXTRANS_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('DELXTRANS_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
define('DELXTRANS_PLUGIN_VERSION', '2.0.4.1');

// scheduled tasks
define('DELXTRANS_TASK_CLEAR_TRANSIENTS', 'delxtrans_cleartransients');
define('DELXTRANS_TASK_CLEAR_TRANSIENTS_MS', 'delxtrans_cleartransients_ms');

require DELXTRANS_PLUGIN_ROOT . 'includes/class.DelxtransPlugin.php';
DelxtransPlugin::getInstance();
