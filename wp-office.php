<?php
/*
Plugin Name: WP-Office Starter
Plugin URI: https://OfficeWP.com
Description:  Private Client Portals
Author: OfficeWP.com
Version: 1.1.4
Author URI: https://OfficeWP.com
*/

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



//current plugin version
define( 'WP_OFFICE_VER', '1.1.4' );

// The text domain for strings localization
define( 'WP_OFFICE_TEXT_DOMAIN', 'wp-office' );

define( 'WP_OFFICE_FILE', __FILE__ );





require_once 'includes/class-functions.php';
require_once 'includes/class-init.php';

