<?php

/*
  Plugin Name: EDD Advance Reviews
  Plugin URI: www.magnigeeks.com
  Description: A light weight plugin which allows to give feedback and rating after purchase.
  Version: 1.0
  Author URI: www.magnigeeks.com
  Author: magnigeeks
 */
// No direct file access
!defined('ABSPATH') AND exit;

define('EDDAR_FILE', __FILE__);
define('EDDAR_PATH', plugin_dir_path(__FILE__));
define('EDDAR_BASE', plugin_basename(__FILE__));

require EDDAR_PATH . 'includes/function.php';

register_activation_hook( __FILE__, array( 'eddAr', 'insert_page' ) );