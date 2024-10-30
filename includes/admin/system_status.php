<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb, $wp_version, $wp_db_version;;

if ( isset( $_GET['wpo_custom_trigger'] ) && 'change_permalink' == $_GET['wpo_custom_trigger'] ) {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/%postname%/' );

    //update rewrite rules
    flush_rewrite_rules();
}

wp_register_style( 'wpo-system-status-style', WO()->plugin_url . 'assets/css/admin-system-status.css', array(), WP_OFFICE_VER );
wp_enqueue_style( 'wpo-system-status-style', false, array(), WP_OFFICE_VER );

//WP Memory Limit
$memory = wp_office_get_memory( WP_MEMORY_LIMIT );
$wp_memory_limit = wp_office_check_requirements( $memory >= 67108864,
        size_format( $memory ),
        sprintf( __( '%s - We recommend setting memory to at least 64 MB. See: <a href="%s" target="_blank">Increasing memory allocated to PHP</a>', WP_OFFICE_TEXT_DOMAIN )
            , size_format( $memory )
            , 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) );

// Check if phpversion function exists
if ( function_exists( 'phpversion' ) ) {
    $version = phpversion();

    $php_version = wp_office_check_requirements( version_compare( $version, '5.4', '>=' )
            , esc_html( $version )
            , sprintf( __( '%s - We recommend a minimum PHP version of 5.4.', WP_OFFICE_TEXT_DOMAIN ), esc_html( $version ) )
            );
} else {
    $php_version = wp_office_get_mark_code( false );
}

//Permalinks
$permalink_structure = get_option( 'permalink_structure' );
# checks whether the permalink is enabled or not
if ( '' != $permalink_structure ) {
    $permalinks = $permalink_structure;
} else {
    $permalinks = __( 'Default', WP_OFFICE_TEXT_DOMAIN )
            . '<a href="admin.php?page=wp-office&tab=system_status&wpo_custom_trigger=change_permalink">'
            . __( ' (Change to Post Name)', WP_OFFICE_TEXT_DOMAIN ) . '</a>' ;
}

//CURL
$temp_bool = function_exists( 'fsockopen' ) || function_exists( 'curl_init' );
$curl = wp_office_get_mark_code( $temp_bool ) . wp_office_check_requirements( $temp_bool,
        '',
        __( 'Disabled (cURL must be enabled)', WP_OFFICE_TEXT_DOMAIN ) );

//Remote POST
$response = wp_safe_remote_post('https://wordpress.org/index.php', array(
    'timeout' => 60,
));
$bool_remote_post = !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ;
$remote_post = $bool_remote_post ? wp_office_get_mark_code( $bool_remote_post )
        : wp_office_check_requirements( $bool_remote_post, '', wp_office_get_mark_code( $bool_remote_post ) );

//Remote GET
$response = wp_safe_remote_get('https://wordpress.org/index.php', array(
    'timeout' => 60,
));
$bool_remote_get = !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ;
$remote_get = $bool_remote_get ? wp_office_get_mark_code( $bool_remote_get )
        : wp_office_check_requirements( $bool_remote_get, '', wp_office_get_mark_code( $bool_remote_get ) );

//Type of browser
$browsers = array(
    'is_lynx'       => __( 'Lynx', WP_OFFICE_TEXT_DOMAIN ),
    'is_gecko'      => __( 'FireFox', WP_OFFICE_TEXT_DOMAIN ),
    'is_opera'      => __( 'Opera', WP_OFFICE_TEXT_DOMAIN ),
    'is_NS4'        => __( 'Netscape', WP_OFFICE_TEXT_DOMAIN ),
    'is_safari'     => __( 'Safari', WP_OFFICE_TEXT_DOMAIN ),
    'is_chrome'     => __( 'Google Chrome', WP_OFFICE_TEXT_DOMAIN ),
    'is_iphone'     => __( 'iPhone', WP_OFFICE_TEXT_DOMAIN ),
    'is_macIE'      => __( 'Mac Internet Explorer', WP_OFFICE_TEXT_DOMAIN ),
    'is_winIE'      => __( 'Windows Internet Explorer', WP_OFFICE_TEXT_DOMAIN ),
    'is_IE'         => __( 'Internet Explorer', WP_OFFICE_TEXT_DOMAIN ),
);
$using = wp_office_get_mark_code( false );
foreach ( $browsers as $key => $val ) {
    global $$key;
    if ( $$key ) {
        $using = sprintf(  __( 'You are using %s', WP_OFFICE_TEXT_DOMAIN ), $val );
    }
}

//Active plugins
$active_plugins = (array)get_option( 'active_plugins', array() );
$count_active_plugins = count( $active_plugins );

if ( is_multisite() ) {
    $network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
    $active_plugins = array_merge( $active_plugins, $network_activated_plugins );
}

$plugins = array();
foreach ( $active_plugins as $plugin ) {

    $plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false );
    $dirname        = dirname( $plugin );

    if ( $plugin_data['AuthorURI'] ) {
        if ( $plugin_data['Author'] ) {
            $plugin_data['Author'] =  __( 'by', WP_OFFICE_TEXT_DOMAIN ) . ' <a href="' . $plugin_data['AuthorURI']
                    . '" title="' . esc_attr__( 'Visit author homepage' ) . '" target="_blank">' . $plugin_data['Author'] . '</a> - ';
        } else {
            $plugin_data['Author'] = '';
        }
    }


    if ( ! empty( $plugin_data['Name'] ) ) {
        if ( false !== get_option( 'whtlwpo_settings' ) &&  0 === strpos( $plugin_data['Name'],  WO()->plugin['old_title'] ) ) {
            $hide_name = str_replace( WO()->plugin['old_title'], WO()->plugin['title'], $plugin_data['Name'] );
            $plugins[ $hide_name ] = array(
                'title' => $hide_name,
                'value' => __( 'version', WP_OFFICE_TEXT_DOMAIN ) . ' ' . $plugin_data['Version'],
            );
        } else {
            $plugin_title = $plugin_name = esc_html( $plugin_data['Name'] );

            if ( !empty( $plugin_data['PluginURI'] ) ) {
                $plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . esc_attr__( 'Visit plugin homepage' , WP_OFFICE_TEXT_DOMAIN ) . '" target="_blank">' . $plugin_name . '</a>';
            }
            $plugins[ $plugin_title ] = array(
                'title' => $plugin_name,
                'value' => $plugin_data['Author'] . ' ' . __( 'version', WP_OFFICE_TEXT_DOMAIN ) . ' ' . $plugin_data['Version'],
            );
        }

    }
}

//Active theme
include_once( ABSPATH . 'wp-admin/includes/theme-install.php' );

$active_theme         = wp_get_theme();
$theme_version        = $update_theme_version = $active_theme->Version;
$api                  = themes_api( 'theme_information', array( 'slug' => get_template(), 'fields' => array( 'sections' => false, 'tags' => false ) ) );

// Check .org
if ( $api && ! is_wp_error( $api ) ) {
    $update_theme_version = $api->version;
}

$text_theme_version = wp_office_check_requirements( version_compare( $theme_version, $update_theme_version, '>=' )
        , esc_html( $theme_version )
        , sprintf( __( '%s - %s is available.', WP_OFFICE_TEXT_DOMAIN ), esc_html( $theme_version ), esc_html( $update_theme_version ) )
        );
//child theme
if( is_child_theme() ) {
    $parent_theme = wp_get_theme( $active_theme->Template );
}

//PHP Extension
$bool_mbstring = extension_loaded( 'mbstring' );
$mbstring = $bool_mbstring ? wp_office_get_mark_code( $bool_mbstring )
        : wp_office_check_requirements( $bool_mbstring, '', wp_office_get_mark_code( $bool_mbstring ) );

$bool_zlib = extension_loaded( 'mbstring' );
$zlib = $bool_zlib ? wp_office_get_mark_code( $bool_zlib )
        : wp_office_check_requirements( $bool_zlib, '', wp_office_get_mark_code( $bool_zlib ) );


$all_settings = array(
    'WordPress Environment' => array(
        'title'     => __( 'WordPress Environment', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
            'Home URL'  => array(
                'title' => __( 'Home URL', WP_OFFICE_TEXT_DOMAIN ) . WO()->get_help_tip( __( 'The URL of your site\'s homepage.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => esc_attr( get_option( 'home' ) ),
            ),
            'Site URL'  => array(
                'title' => __( 'Site URL', WP_OFFICE_TEXT_DOMAIN ) . WO()->get_help_tip( __( 'The root URL of your site.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => esc_attr( get_option( 'siteurl' ) ),
            ),
            'WP Office Version'  => array(
                'title' => sprintf( __( '%s Version', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) . WO()->get_help_tip( sprintf( __( 'The version of %s installed on your site.', WP_OFFICE_TEXT_DOMAIN ), WO()->plugin['title'] ) ),
                'value' => esc_html( WP_OFFICE_VER ),
            ),
            'WP Version' => array(
                'title' => __('WP Version', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The version of WordPress installed on your site.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $wp_version,
//                'value' => get_bloginfo( 'version', 'display' ),
            ),
            'WP Multisite' => array(
                'title' => __('WP Multisite', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Whether or not you have WordPress Multisite enabled.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => wp_office_get_mark_code( is_multisite() ),
            ),
            'WP Memory Limit' => array(
                'title' => __('WP Memory Limit', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The maximum amount of memory (RAM) that your site can use at one time.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $wp_memory_limit,
            ),
            'WP Debug Mode' => array(
                'title' => __('WP Debug Mode', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Displays whether or not WordPress is in Debug Mode.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => wp_office_get_mark_code( defined('WP_DEBUG') && WP_DEBUG ),
            ),
            'Language' => array(
                'title' => __('Language', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The current language used by WordPress. Default = English', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => get_locale(),
            ),
        ),
    ),

    'Server Environment' => array(
        'title'     => __( 'Server Environment', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
            'Server Info'  => array(
                'title' => __( 'Server Info', WP_OFFICE_TEXT_DOMAIN ) . WO()->get_help_tip( __( 'Information about the web server that is currently hosting your site.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => esc_html( $_SERVER['SERVER_SOFTWARE'] ),
            ),
            'PHP Version' => array(
                'title' => __('PHP Version', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The version of PHP installed on your hosting server.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $php_version,
            ),
            'MySQL Version' => array(
                'title' => __('MySQL Version', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The version of MySQL installed on your hosting server.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $wpdb->db_version(),
            ),
            'Max Upload Size' => array(
                'title' => __('Max Upload Size', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The largest filesize that can be uploaded to your WordPress installation.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => size_format( wp_max_upload_size() ),
            ),
            'fsockopen/cURL' => array(
                'title' => __('fsockopen/cURL', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Plugin can use cURL to communicate with remote servers (for example: payment gateways or some remote services) other plugins may also use it when communicating with remote services.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $curl,
            ),
            'Remote Post' => array(
                'title' => __('Remote Post', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Plugin can use Remote Post to communicate with remote servers (for example: payment gateways or some remote services) other plugins may also use it when communicating with remote services.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $remote_post,
            ),
            'Remote Get' => array(
                'title' => __('Remote Get', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Plugin can use Remote Get to communicate with remote servers (for example: payment gateways or some remote services) other plugins may also use it when communicating with remote services.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $remote_get,
            ),
        ),
    ),

    'Settings' => array(
        'title'     => __( 'Settings', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
            'Force SSL' => array(
                'title' => __('Force SSL', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Does your site force a SSL Certificate for transactions?', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => wp_office_get_mark_code( is_ssl() ),
            ),
            'Permalinks' => array(
                'title' => __('Permalinks', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Permalinks are the permanent URLs to your posts.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $permalinks,
            ),
            'Browser' => array(
                'title' => __('Browser', WP_OFFICE_TEXT_DOMAIN),
                'value' => $using,
                'hide'  => true,
            ),
        ),
    ),

    'Database' => array(
        'title'     => __( 'Database', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
            'WP Database Version' => array(
                'title' => __('WP Database Version', WP_OFFICE_TEXT_DOMAIN),
                'value' => $wp_db_version,
            ),
        ),
    ),

    'WPO Pages' => array(
        'title'     => __( 'WPO Pages', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
        ),
    ),

    'Theme' => array(
        'title'     => __( 'Theme', WP_OFFICE_TEXT_DOMAIN ),
        'settings'  => array(
            'Name' => array(
                'title' => __('Name', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The name of the current active theme.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $active_theme->Name,
            ),
            'Version' => array(
                'title' => __('Version', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The installed version of the current active theme.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $text_theme_version,
            ),
            'Author URL' => array(
                'title' => __('Author URL', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The theme developers URL.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => $active_theme->{'Author URI'},
            ),
            'Child Theme'  => array(
                'title' => __('Child Theme', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Displays whether or not the current theme is a child theme.', WP_OFFICE_TEXT_DOMAIN ) ),
                'value' => wp_office_get_mark_code( isset( $parent_theme ) ),
            ),
        ),
    ),

    sprintf( 'Active Plugins (%s)', $count_active_plugins ) => array(
        'title'     => sprintf( __( 'Active Plugins (%s)', WP_OFFICE_TEXT_DOMAIN ), $count_active_plugins ),
        'settings'  => $plugins,
    ),

);

if( isset( $parent_theme ) ) {
    $all_settings['Theme']['settings']['Parent Theme Name'] = array(
        'title' => __('Parent Theme Name', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The name of the parent theme.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => $parent_theme->Name,
    );
    $all_settings['Theme']['settings']['Parent Theme Version'] = array(
        'title' => __('Parent Theme Version', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The installed version of the parent theme.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => $parent_theme->Version,
    );
    $all_settings['Theme']['settings']['Parent Theme Author URL'] = array(
        'title' => __('Parent Theme Author URL', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The parent theme developers URL.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => $parent_theme->{'Author URI'},
    );
}

if ( function_exists( 'ini_get' ) ) {

    $all_settings['Server Environment']['settings']['PHP Post Max Size'] = array(
        'title' => __('PHP Post Max Size', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The largest filesize that can be contained in one post.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => size_format( wp_office_get_memory( ini_get('post_max_size') ) ),
    );

    $all_settings['Server Environment']['settings']['PHP Time Limit'] = array(
        'title' => __('PHP Time Limit', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => ini_get('max_execution_time'),
    );

    $all_settings['Server Environment']['settings']['PHP Max Input Vars'] = array(
        'title' => __('PHP Max Input Vars', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'The maximum number of variables your server can use for a single function to avoid overloads.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => ini_get('max_input_vars'),
    );

    $all_settings['Server Environment']['settings']['PHP Mbstring Extension'] = array(
        'title' => __('PHP Mbstring Extension', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'Mbstring Extension is designed to handle Unicode.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => $mbstring,
    );

    $all_settings['Server Environment']['settings']['PHP Zlib Extension'] = array(
        'title' => __('PHP Zlib Extension', WP_OFFICE_TEXT_DOMAIN) . WO()->get_help_tip( __( 'This module enables you to transparently read and write gzip (.gz) compressed files.', WP_OFFICE_TEXT_DOMAIN ) ),
        'value' => $zlib,
    );

}

echo '<div class="wpo_admin_content" id="wpo_admin_system_status">';

foreach ( $all_settings as $key => $block) {
    echo '<table class="wpo_system_status_table" cellspacing="0">';
        echo '<thead><tr><th colspan="2" class="wpo_main_title" data-title="' . $key . '">'
                . $block['title'] . '</th></tr>'
                . '<tr><th class="wpo_title_hr" colspan="2">'
                . WO()->hr( '0 0 0 0' ) . '</th></tr></thead>';
        echo '<tbody>';
        foreach ( $block['settings'] as $title => $data ) {
            echo '<tr' . ( !empty( $data['hide'] ) ? ' style="display: none"' : '' ) . '>';
                echo '<td data-title="' . $title . '">' . $data['title'] . '</td>';
                echo '<td>' . $data['value'] . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
    echo '</table>';
}

echo '</div>';

/**
 * Get mark code as - OR V
 *
 * @param boolean $check
 * @return string
 */
function wp_office_get_mark_code( $check ) {
    return ( $check ) ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-minus"></span>';
}


/**
 * Get memory with numeric format
 *
 * @access public
 * @param string $size
 * @return int|string
 */
function wp_office_get_memory( $size ) {
    $l       = substr( $size, -1 );
    $ret     = substr( $size, 0, -1 );
    switch( strtoupper( $l ) ) {
        case 'P':
            $ret *= 1024;
        case 'T':
            $ret *= 1024;
        case 'G':
            $ret *= 1024;
        case 'M':
            $ret *= 1024;
        case 'K':
            $ret *= 1024;
    }

    return $ret;
}


/**
 * Check setting requirements
 *
 * @param boolean $true well | ill
 * @param string $well Message for well value
 * @param string $ill Message for bad value
 * @return string
 */
function wp_office_check_requirements( $true, $well = '', $ill = '' ) {
    $return = '<span class="wpo_ill">' . __( "Some Error", WP_OFFICE_TEXT_DOMAIN ) . '</span>';

    if ( $true ) {
        $return = '<span class="wpo_well">' . $well . '</span>';
    } else {
        $return = '<span class="wpo_ill">' . $ill . '</span>';
    }

    return $return;
}