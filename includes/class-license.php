<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Licensing class
 *
 * @version 0.0.1
 */
class Office_Licensor {

    public $api_url     = '';
    public $slug        = '';
    public $name        = '';
    public $version     = '';
    public $item_name   = '';
    public $menu_slug   = '';
    public $menu_title  = '';
    public $wp_override = false;

    public $prefix = '';
    public $hide_menu_after_activate = false;


    public $update_requested = array();
    /**
     * Class constructor.
     *
     * @uses trailingslashit()
     * @uses plugin_basename()
     * @uses wp_spaces_regexp()
     * @uses init()
     *
     * @param string  $_api_url     The URL pointing to the custom API endpoint.
     * @param string  $_plugin_file Path to the plugin file.
     * @param array   $_api_data    Optional data to send with API calls.
     */
    public function __construct( $_api_url, $_plugin_file, $_api_data = array() ) {
        $this->api_url     = trailingslashit( $_api_url );
        $this->slug        = plugin_basename( $_plugin_file );
        $this->name        = basename( $_plugin_file, '.php' );
        $this->version     = !empty( $_api_data['version'] ) ? $_api_data['version'] : '0';
        $this->item_name   = !empty( $_api_data['item_name'] ) ? $_api_data['item_name'] : '';
        $this->menu_slug   = !empty( $_api_data['menu_slug'] ) ? $_api_data['menu_slug'] : '';
        $this->menu_title  = !empty( $_api_data['menu_title'] ) ? $_api_data['menu_title'] : '';
        $this->wp_override = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;

        $spaces = wp_spaces_regexp();
        $this->prefix = preg_replace( "/$spaces/", "_", strtolower( $this->item_name ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }


    /**
     * Calls the API and, if successfull, returns the object delivered by the API.
     *
     * @uses get_bloginfo()
     * @uses wp_remote_post()
     * @uses is_wp_error()
     * @uses extend_download_url()
     *
     * @param string  $_action The requested action.
     * @param array   $data   Parameters for the API action.
     * @return false|object
     */
    private function api_request( $_action, $data ) {

        if ( $data['slug'] != $this->slug )
            return false;

        if ( trailingslashit( $this->api_url ) == trailingslashit( home_url() ) )
            return false; // Don't allow a plugin to ping itself

        $url = get_site_url( get_current_blog_id() );
        $domain  = strtolower( urlencode( rtrim( $url, '/' ) ) );

        $api_params = array(
            'action'        => $_action,
            'license'       => !empty( $data['license'] ) ? $data['license'] : '',
            'salt'          => !empty( $data['salt'] ) ? $data['salt'] : '',
            'item_name'     => urlencode( $this->item_name ),
            'blog_id'       => get_current_blog_id(),
            'site_url'      => $url,
            'domain'        => $domain,
            'slug'          => $data['slug'],
        );

        $api_url = add_query_arg( 'wc-api', 'upgrade-api', trailingslashit( $this->api_url ) );
        $request = wp_remote_post( $api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

        if ( is_wp_error( $request ) )
            return $request;

        $request = json_decode( wp_remote_retrieve_body( $request ) );

        if ( !empty( $request->package ) )
            $request->package = $this->extend_download_url( $request->package, $data );

        if ( !empty( $request->download_link ) )
            $request->download_link = $this->extend_download_url( $request->download_link, $data );

        if ( 'plugin_information' == $_action ) {
            if ( $request && isset( $request->sections ) ) {
                $request->sections = maybe_unserialize( $request->sections );
            } else {
                $request = new WP_Error( 'plugins_api_failed',
                    sprintf(
                        /* translators: %s: support forums URL */
                        __( 'An unexpected error occurred. Something may be wrong with ' . $this->api_url . ' or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
                        __( 'https://wordpress.org/support/' )
                    ),
                    wp_remote_retrieve_body( $request )
                );
            }
        }

        return $request;
    }


    private function extend_download_url( $download_url, $data ) {

        $url = get_site_url( get_current_blog_id() );
        $domain  = strtolower( urlencode( rtrim( $url, '/' ) ) );

        $api_params = array(
            'action'        => 'get_last_version',
            'license'       => !empty( $data['license'] ) ? $data['license'] : '',
            'item_name'     => urlencode( $this->item_name ),
            'blog_id'       => get_current_blog_id(),
            'site_url'      => urlencode( $url ),
            'domain'        => urlencode( $domain ),
            'slug'          => urlencode( $data['slug'] ),
        );

        $download_url = add_query_arg( $api_params, $download_url );

        return $download_url;
    }


    /**
     * Disable SSL verification in order to prevent download update failures
     *
     * @param array   $args
     * @param string  $url
     * @return array $array
     */
    public function http_request_args( $args, $url ) {
        // If it is an https request and we are performing a package download, disable ssl verification
        if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'action=package_download' ) ) {
            $args['sslverify'] = false;
        }
        return $args;
    }


    /**
     * Updates information on the "View version x.x details" popup with custom data.
     *
     * @uses api_request()
     *
     * @param mixed   $_data
     * @param string  $_action
     * @param object  $_args
     * @return object $_data
     */
    public function plugins_api_filter( $_data, $_action = '', $_args = null ) {
        //by default $data = false (from Wordpress)

        if ( $_action != 'plugin_information' )
            return $_data;

        $slug = explode( "/", $this->slug );
        $slug = $slug[0];

        if ( ! isset( $_args->slug ) || ( $_args->slug != $slug ) )
            return $_data;

        $license = get_option( $this->prefix . '_license_key' );
        $salt = get_option( $this->prefix . '_license_salt' );
        $to_send = array(
            'license'   => $license,
            'salt'      => $salt,
            'slug'      => $this->slug,
            'is_ssl'    => is_ssl(),
        );

        $cache_key = 'api_request_' . substr( md5( serialize( $this->item_name ) ), 0, 15 );

        //Get the transient where we store the api request for this plugin for 24 hours
        $api_request_transient = get_site_transient( $cache_key );

        //If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
        if ( empty( $api_request_transient ) ) {

            $api_response = $this->api_request( 'plugin_information', $to_send );
            if ( ! empty( $api_response->sections ) ) {
                $api_response->sections = (array) $api_response->sections;
            }

            //Expires in 1 day
            set_site_transient( $cache_key, $api_response, DAY_IN_SECONDS );

            $_data = $api_response;
        } else {
            $_data = $api_request_transient;
        }

        return $_data;
    }


    /**
    * Check for Updates by request to the marketplace
    * and modify the update array.
    *
    * @param array $_transient_data plugin update array build by WordPress.
    * @return stdClass modified plugin update array.
    */
    public function check_update( $_transient_data ) {
        global $pagenow;

        if ( ! is_object( $_transient_data ) )
            $_transient_data = new stdClass;

        if ( 'plugins.php' == $pagenow && is_multisite() )
            return $_transient_data;

        //if response for current product isn't empty check for override
        if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->slug ] ) && false === $this->wp_override )
            return $_transient_data;

        $license = get_option( $this->prefix . '_license_key' );
        $salt = get_option( $this->prefix . '_license_salt' );

        $transient_name = md5( $this->slug . 'plugin_update_info' );
        $transient_version_info = get_site_transient( $transient_name );
        if ( empty( $transient_version_info ) ) {
            $version_info = $this->api_request( 'plugin_latest_version', array( 'license' => $license, 'slug' => $this->slug, 'current_version' => $this->version, 'salt' => $salt ) );
            $this->update_requested[ $this->slug ] = $version_info;
            set_site_transient( $transient_name, $version_info, 12 * HOUR_IN_SECONDS );
        } else {
            $version_info = $transient_version_info;
        }

        if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
            //show update version block if new version > then current
            if ( version_compare( $this->version, $version_info->new_version, '<' ) )
                $_transient_data->response[ $this->slug ] = $version_info;

            $_transient_data->last_checked           = time();
            $_transient_data->checked[ $this->slug ] = $this->version;

        }

        return $_transient_data;
    }


    function successful_activation_redirect() {
        return admin_url( 'plugins.php' );
    }


    /**
    * Activate license process
    * request to the marketplace
    *
    */
    function activate_license() {
        // listen for our activate button to be clicked
        if ( !empty( $_POST["{$this->prefix}_license_key"] ) ) {

            // run a quick security check
            if ( ! check_admin_referer( "{$this->prefix}_license_activation", md5( "{$this->prefix}_license_activation" . $this->menu_slug . get_current_user() ) ) )
                return 'Error #10010: Wrong nonce'; // get out if we didn't click the Activate button

            // retrieve the license from the database
            $license = $_POST["{$this->prefix}_license_key"];
            update_option( $this->prefix . '_license_key', $license );

            $url = get_site_url( get_current_blog_id() );
            $domain  = strtolower( urlencode( rtrim( $url, '/' ) ) );

            // data to send in our API request
            $api_params = array(
                'action'        => 'activate_license',
                'license'       => $license,
                'item_name'     => urlencode( $this->item_name ), // the name of our product in EDD
                'url'           => home_url(),
                'blog_id'       => get_current_blog_id(),
                'site_url'      => $url,
                'domain'        => $domain
            );

            $api_url     = add_query_arg( 'wc-api', 'am-software-api', trailingslashit( $this->api_url ) );

            $args = array(
                'method'        => 'POST',
                'timeout'       => 45,
                'redirection'   => 5,
                'httpversion'   => '1.0',
                'blocking'      => true,
                'sslverify'     => false,
                'headers'       => array(),
                'body'          => $api_params,
                'cookies'       => array()
            );


            //Call the custom API Without SSL checking
            $response = wp_remote_post( $api_url, $args );

            if ( is_wp_error( $response ) ) {
                //With SSL checking
                $args['sslverify'] = true;
                $response = wp_remote_post( $api_url, $args );

                if ( is_wp_error( $response ) )
                    $message = 'Error #10020: ' . $response->get_error_message();
            }

            //Can set debug mode by $_GET "activation_debug" by "true"
            if ( isset( $_GET['activation_debug'] ) && 'true' == $_GET['activation_debug'] ) {
                var_dump( $args );
                var_dump( $response );
                exit;
            }

            if ( 200 !== wp_remote_retrieve_response_code( $response ) )
                $message = 'Error #10030: Something went wrong';


            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( ! isset( $license_data->activated ) || false === $license_data->activated ) {
                $message = !empty( $license_data->error ) ? $license_data->error : 'Error #10040: Can not connect to the server!';
            }

            // If error was triggered
            if ( ! empty( $message ) )
                return $message;


            //If error not triggered update license option and redirect after activation
            update_option( $this->prefix . '_license_status', $license_data->license );
            update_option( $this->prefix . '_license_salt', $license_data->salt );

            // if $this->hide_menu_after_activate == false redirect to current page
            if ( ! $this->hide_menu_after_activate ) {
                $redirect = admin_url( 'plugins.php?page=' . $this->menu_slug );
            } else {
                $redirect = $this->successful_activation_redirect();
            }

            wp_redirect( $redirect );
            exit();
        }

        return '';
    }


    /**
     * Render HTML for License options page
     *
     * @param array $args data for render form.
     *
     * @return void
     */
    function render_license_form( $args = array() ) {
        ?>
        <div class="wrap">
            <h2><?php printf( __( '%s License Activation' ), $this->menu_title ) ?></h2>

            <?php if ( ! empty( $args['message'] ) ) { ?>
                <div class="error">
                    <p><?php echo $args['message'] ?></p>
                </div>
            <?php }

            if ( ! empty( $args['license'] ) && ! empty( $args['status'] ) && $args['status'] == 'valid' ) { ?>
                <div class="updated">
                    <p><?php _e( 'License is Active' ) ?></p>
                </div>
            <?php } ?>

            <form method="post" action="">
                <input type="hidden" name="page" value="<?php echo $this->menu_slug ?>">
                <?php wp_nonce_field( "{$this->prefix}_license_activation", md5( "{$this->prefix}_license_activation" . $this->menu_slug . get_current_user() ) ); ?>
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row" valign="top">
                                <?php _e( 'License Key' ); ?>
                            </th>
                            <td>
                                <input id="license_key" name="<?php echo $this->prefix ?>_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $args['license'] ); ?>" />
                                <label class="description" for="license_key"><?php _e( 'Enter your license key' ); ?></label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( __( 'Activate License' ) ); ?>

            </form>
        <?php
    }
    /**
     * License options page
     *
     * @return void
     */
    function license_page() {
        $license = get_option( $this->prefix . '_license_key' );
        $license = empty( $license ) ? '' : $license;

        $license = !empty( $_POST["{$this->prefix}_license_key"] ) ? $_POST["{$this->prefix}_license_key"] : $license;

        $message = $this->activate_license();

        $status  = get_option( $this->prefix . '_license_status' );

        $args = array(
            'license' => $license,
            'message' => $message,
            'status' => $status,
        );

        $this->render_license_form( $args );
    }

    /**
     * Clear license key
     *
     */
    function clear_license() {
        if ( current_user_can( 'manage_options' ) && !empty( $_REQUEST[$this->prefix . 'clearlic'] ) && wp_verify_nonce( $_REQUEST[$this->prefix . 'clearlic'], get_current_user_id() . 'clr' . $this->item_name ) ) {

            update_option( $this->prefix . '_license_key', '' );
            update_option( $this->prefix . '_license_status', '' );
            update_option( $this->prefix . '_license_salt', '' );

            header( 'Location: ' . get_admin_url() . 'plugins.php' );
            die();
        }
    }


    /**
     * Add action links to Plugin
     *
     * @param array $links plugin actions links.
     *
     * @return array
     */
    public function add_more_action_links( $links ) {

        $links['clear_license'] = '<a onclick="return confirm(\'Are you sure to clear License?\');"  href="' . get_admin_url() . 'plugins.php?' . $this->prefix . 'clearlic=' . wp_create_nonce( get_current_user_id() . 'clr' . $this->item_name ) .'" style="color:#777;">Clear License</a>';

        return $links;
    }


    /**
    * Add submenu to Plugins with license form
    *
    * @uses add_plugins_page()
    */
    public function add_license_menu() {
        add_plugins_page(
            $this->menu_title,
            $this->menu_title,
            'manage_options',
            $this->menu_slug,
            array( $this, 'license_page' )
        );
    }


    /**
    * Add submenu to Plugins with license settings
    *
    * @uses get_option()
    */
    public function _maybe_add_license_menu() {
        // There is the checking options for activating
        // for handler to show|hide activation menu after
        // complete the activation ( variable $add_menu )
        $license = get_option( $this->prefix . '_license_key' );
        $status  = get_option( $this->prefix . '_license_status' );

        $add_menu = false;
        if ( empty( $license ) || empty( $status ) || $status != 'valid' ) {
            $add_menu = true;
        } else {
            if ( ! $this->hide_menu_after_activate ) {
                $add_menu = true;
            }

            if ( current_user_can( 'manage_options' ) ) {
                //NEED plugin PATH
                add_filter( 'plugin_action_links_' . $this->slug, array( $this, 'add_more_action_links' ) );

                add_action( 'admin_init',  array( $this, 'clear_license' ) );
            }

        }

        if ( $add_menu ) {
            $this->add_license_menu();
        }
    }


    /**
     * Set up WordPress filters to hook into WP's update process.
     *
     * @uses add_action()
     * @uses add_filter()
     *
     * @return void
     */
    public function init() {
        if ( current_user_can( 'manage_options' ) )
            add_action( 'admin_menu', array( &$this, '_maybe_add_license_menu' ) );

        add_action( 'admin_init', array( $this, 'activate_license' ) );

        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 9999, 3 );
    }

    //end class
}