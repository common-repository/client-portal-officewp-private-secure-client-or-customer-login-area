<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class lswpoaccess extends \ABCWYZ_License {

        /**
         * @var null
         */
        protected static $_instance = null;

        public static function instance( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain = '', $extra = '' ) {
                if ( is_null( self::$_instance ) ) {
                        self::$_instance = new self( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain, $extra );
                }

                return self::$_instance;
        }

        public function __construct( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain, $extra ) {


                parent::__construct( $file, $software_title, $software_version, $plugin_or_theme, $api_url, $text_domain, $extra );


                do_action( 'lget_activation_init_'  . $this->data_prefix );

        }



        public function more_action_links( $links ) {

                $links['clear_license'] = '<a onclick="return confirm(\'Are you sure to clear License?\');"  href="' . get_admin_url() . 'plugins.php?clact=' . wp_create_nonce( get_current_user_id() . WO()->plugin['title'] ) .'" style="color:#777;">Clear License</a>';

                return $links;
        }




        function clear_license() {
                if ( current_user_can( 'manage_options' ) && !empty( $_REQUEST['clact'] ) && wp_verify_nonce( $_REQUEST['clact'], get_current_user_id() . WO()->plugin['title'] ) ) {

                        update_option( $this->ame_data_key, array(
                                $this->ame_api_key          => '',
                                $this->ame_activation_email => '',
                        ) );
                        update_option( $this->ame_activated_key, '' );

                        header( 'Location: ' . get_admin_url() . 'plugins.php' );
                        die();
                }
        }

        /**
         * Register submenu specific to this product.
         */
        public function register_menu() {
                if ( 'Activated' != get_option( $this->ame_activated_key ) ) {
                        add_menu_page( WO()->plugin['title'], WO()->plugin['title'], 'manage_options', 'wp-office-act', array( $this, 'config_page'), WO()->plugin['icon_url'], '2,000000000006' );
                } else {

                        if ( current_user_can( 'manage_options' ) ) {
                                //NEED plugin PATH
                                add_filter( 'plugin_action_links_wp-office/wp-office.php', array( $this, 'more_action_links' ) );

                                add_action( 'admin_init',  array( $this, 'clear_license' ) );
                        }

                }
        }

        // Draw option page
        public function config_page() {
                ?>

                <style type="text/css">

                        /*#wpo_activation_block {*/
                        /*border: 1px solid #8ba8d3;*/
                        /*border-radius: 25px;*/
                        /*margin: 50px auto;*/
                        /*padding: 20px;*/
                        /*width: 400px;*/
                        /*background-color: #d1e4ff;*/
                        /*}*/

                        #wpo_activation_block {
                                background: linear-gradient(45deg, rgba( 255, 255, 255, 0.2 ), rgba( 255, 255, 255, 0.4 ) );
                                /*border: 2px solid rgba( 209, 228, 255, 0.8 );*/
                                /*border: 2px solid rgba( 255, 255, 255, 0.5 );*/
                                border: 2px solid rgba(221,235,255, 0.5 );
                                padding: 15px 20px 0px 20px;
                                box-shadow: 0 0 4px rgba(0,0,0,0.3);
                                border-radius:0px;
                                width: 350px;
                                margin: 50px auto;
                                background-color: #d1e4ff;
                        }

                        .error {
                                display: none;
                        }

                        .updated {
                                display: none;
                        }

                        #wpo_activation_block .settings-error {
                                display: block;
                                background-color: rgba(255, 255, 255, 0.3);
                                margin-top: 20px;
                        }

                        #wpo_activation_block .settings-error strong {
                                font-weight: normal;
                        }

                        #wpo_activation_block span.dashicons-yes,
                        #wpo_activation_block span.dashicons-no {
                                display: none;
                        }

                        #wpo_activation_block .submit {
                                text-align: center;
                        }

                        #wpo_activation_block h2 {
                                display: none;
                        }

                        #wpo_activation_block h2.lget_atcivation_title {
                                display: block;
                                text-align: center;
                                font-size: 20px;
                                margin-bottom: 10px;
                                font-weight: normal;
                        }

                        #wpo_activation_block .form-table th {
                                font-weight: normal;
                                font-size: 16px;
                                display: none;
                        }

                        #wpo_activation_block .form-table input {
                                width: 100%;
                        }


                </style>

                <?php
                remove_all_actions( 'sanitize_option_' . $this->ame_data_key );

                if ( !empty( $_POST['option_page'] ) ) {

                        $api_key = !empty( $_POST[$_POST['option_page']][$this->ame_api_key] ) ? $_POST[$_POST['option_page']][$this->ame_api_key] : '';
                        $activation_email = !empty( $_POST[$_POST['option_page']][$this->ame_activation_email] ) ? $_POST[$_POST['option_page']][$this->ame_activation_email] : '';

                        $input[$this->ame_api_key] = $api_key;
                        $input[$this->ame_activation_email] = $activation_email;

                        if ( empty( $this->ame_options ) ) {
                                $this->ame_options = array(
                                        $this->ame_api_key          => $api_key,
                                        $this->ame_activation_email => $activation_email,
                                );
                        }

                        $this->validate_options( $input );

                        $errors = get_settings_errors();

                        if ( !empty( $errors[0]['setting'] ) && 'activate_text' == $errors[0]['setting'] ) {
                                settings_errors();

                                $global_options = array(
                                        $this->ame_api_key          => $api_key,
                                        $this->ame_activation_email => $activation_email,
                                );

                                update_option( $this->ame_data_key, $global_options );

                                $this->redirect( admin_url( 'admin.php?page=wp-office' ) );
                                exit;

                        } else {
                                settings_errors();
                        }
                }

                global $wp_settings_fields;


                if ( !empty( $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key]['status'] ) ) {
                        unset( $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key]['status'] );
                }

                if ( !empty( $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key][$this->ame_api_key]['title'] ) ) {
                        $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key][$this->ame_api_key]['title'] = '';
                }

                if ( !empty( $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key][$this->ame_activation_email]['title'] ) ) {
                        $wp_settings_fields[$this->ame_activation_tab_key][$this->ame_api_key][$this->ame_activation_email]['title'] = '';
                }

                ?>

                <div class='wrap'>
                        <div id="wpo_activation_block">

                                <?php do_action( 'lget_activation_logo_' . $this->data_prefix ) ?>

                                <h2 class="lget_atcivation_title">License Activation</h2>
                                <form action='' method='post'>
                                        <div class="main">
                                                <?php

                                                settings_fields( $this->ame_data_key );
                                                do_settings_sections( $this->ame_activation_tab_key );
                                                submit_button( __( 'Activate License', $this->text_domain ) );

                                                ?>
                                        </div>
                                </form>
                        </div>
                </div>
                <?php
        }


        // Returns API Key text field
        public function wc_am_api_key_field() {
                echo "<input id='api_key' placeholder='License Code' name='" . $this->ame_data_key . "[" . $this->ame_api_key . "]' size='25' type='text' value='" . $this->ame_options[ $this->ame_api_key ] . "' />";
        }

        // Returns API email text field
        public function wc_am_api_email_field() {
                echo "<input id='activation_email' placeholder='License Email' name='" . $this->ame_data_key . "[" . $this->ame_activation_email . "]' size='25' type='text' value='" . $this->ame_options[ $this->ame_activation_email ] . "' />";
        }


        /**
         * Displays an inactive notice when the software is inactive.
         */
        public function inactive_notice() { ?>
                <?php if ( ! current_user_can( 'manage_options' ) ) {
                        return;
                } ?>
                <div id="message" class="error">
                        <p><?php printf( __( 'The <strong>%s</strong> License Code has not been activated, so plugin features are unavailable! %sClick here%s to register <strong>%s</strong>.', $this->text_domain ), esc_attr( $this->software_title ), '<a href="' . esc_url( admin_url( 'admin.php?page=wp-office-act' ) ) . '">', '</a>', esc_attr( $this->software_title ) ); ?></p>
                </div>
                <?php
        }

        public function redirect( $url ) {
                if ( headers_sent() || empty( $url ) ) {
                        //for blank redirects
                        if ( '' == $url ) {
                                $url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
                        }

                        $funtext="echo \"<script data-cfasync='false' type='text/javascript'>window.location = '" . $url . "'</script>\";";
                        register_shutdown_function(create_function('',$funtext));

                        if ( 1 < ob_get_level() ) {
                                while ( ob_get_level() > 1 ) {
                                        ob_end_clean();
                                }
                        }

                        ?>
                        <script data-cfasync='false' type="text/javascript">
                                window.location = '<?php echo $url; ?>';
                        </script>
                        <?php
                        exit;
                } else {
                        wp_redirect( $url );
                }
                exit;
        }


        public function deactivate( $args ) {
                return true;
        }

        public function uninstall() {
                return true;
        }


}
