<?php
namespace wpo\core;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Shortcodes {
    protected $shortcodes;

    /**
     * constructor
     **/
    function __construct()
    {
        add_action( 'plugins_loaded', array( &$this, 'add_shortcodes') );

        $roles_list = WO()->get_settings('roles');

        if (!empty($roles_list)) {
            foreach ($roles_list as $key => $value) {
                add_action('wpoffice_hub_page_registration_' . $key . '_shortcode', array(&$this, 'registration_form'));
            }
        }


    }


    function get_list() {
        if( empty( $this->shortcodes ) ) {

            $roles_list = WO()->get_settings( 'roles' );

            $role_items = array();
            foreach( $roles_list as $role_key=>$role ) {
                $role_items[$role_key] = $role['title'];
            }

            $this->shortcodes = apply_filters('wpoffice_register_shortcodes', array(
                'wpoffice_hub' => array(
                    'callback' => __CLASS__ . '::hub_page'
                ),
                'wpoffice_login_form' => array(
                    'title' => __( 'Login Form', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Displays a frontend Login Form', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::login_form'
                ),
                'wpoffice_checkout' => array(
                    'title' => __( 'Checkout', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Shortcode output Checkout Form', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::checkout'
                ),
                'wpoffice_thank_you' => array(
                    'title' => __( 'Thank You', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Shortcode output Thank You in the end of Payment Process', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::thank_you'
                ),
                'wpoffice_profile_form' => array(
                    'title' => __( 'Profile Form', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Displays a frontend Profile Form', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::profile_form'
                ),
                'wpoffice_registration_form' => array(
                    'title' => __( 'Registration Form', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Displays a frontend Registration Form', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::registration_form',
                    'attributes'    => array(
                        'role' => array(
                            'label' => __( 'Role', WP_OFFICE_TEXT_DOMAIN ),
                            'tag'   => 'select',
                            'items' => $role_items
                        ),
                        'disabled_message' => array(
                            'label' => __( 'Message if registration is disabled', WP_OFFICE_TEXT_DOMAIN ),
                            'tag'   => 'input',
                            'value'  => ''
                        )
                    )
                ),
                'wpoffice_pages' => array(
                    'title' => __( 'Office Pages List', WP_OFFICE_TEXT_DOMAIN ),
                    'description' => __( 'Displays a list of Office Pages assigned to user', WP_OFFICE_TEXT_DOMAIN ),
                    'callback' => __CLASS__ . '::pages_list'
                )
            ));
        }
        return $this->shortcodes;
    }


    function get_attributes_form() {
        if( isset( $_POST['shortcode'] ) ) {
            $shortcode = $_POST['shortcode'];
            $shortcodes = $this->get_list();
            if( !isset( $shortcodes[ $shortcode ] ) ) {
                exit( json_encode( array( 'status' => false, 'response' => __('Shortcode does not exists', WP_OFFICE_TEXT_DOMAIN) ) ) );
            }

            if( !isset( $shortcodes[ $shortcode ]['title'] ) ) {
                exit( json_encode( array( 'status' => false, 'response' => __('Shortcode title is empty', WP_OFFICE_TEXT_DOMAIN) ) ) );
            }

            $content = isset( $shortcodes[ $shortcode ]['description'] ) ? '<p>' . $shortcodes[ $shortcode ]['description'] . '</p>' : '';
            if( isset( $shortcodes[ $shortcode ]['attributes'] ) && is_array( $shortcodes[ $shortcode ]['attributes'] ) ) {
                foreach( $shortcodes[ $shortcode ]['attributes'] as $key=>$attr ) {
                    if( !isset( $attr['tag'] ) ) continue;
                    $attr['name'] = $attr['id'] = $key;
                    WO()->admin_form()->add_fields( array(
                        $key => $attr
                    ) );
                }

                ob_start();
                WO()->admin_form()->display_fields( 'wpo_' . $shortcode );
                $content .= ob_get_clean();
            }
            $button = WO()->admin_form()->submit_button( __( 'Add Shortcode', WP_OFFICE_TEXT_DOMAIN ) );
            exit( json_encode( array( 'status' => true, 'title' => $shortcodes[ $shortcode ]['title'], 'response' => $content, 'button' => $button ) ) );
        }
        exit( json_encode( array( 'status' => false, 'response' => __('Wrong input data', WP_OFFICE_TEXT_DOMAIN) ) ) );
    }


    function list_popup() {
        ob_start();
        $shortcodes = $this->get_list();
        require_once WO()->plugin_dir . 'includes/admin/shortcodes_popup.php';
        $content = ob_get_clean();
        exit(json_encode(array(
            'title' => __('WP Office Shortcodes', WP_OFFICE_TEXT_DOMAIN),
            'content' => $content
        )));
    }

    /**
     *  Registrations form shortcode
     */
    static function pages_list($atts)
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $page_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page' );
        $page_ids = apply_filters('wpoffice_office_page_ids_list', $page_ids, $atts);

        $query = new \WP_Query( array(
            'post_type' => 'office_page',
            'post__in' => $page_ids
        ) );
        ob_start();
        //include_once WO()->plugin_dir . 'templates/pages-list.php';
        WO()->get_template('pages-list.php', '', array(
            'query' => $query
        ));
        $content = ob_get_contents();
        if (ob_get_length()) {
            ob_end_clean();
        }
        return $content;

    }


    /**
     *  Registrations form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    static function registration_form( $atts ) {
        $atts = shortcode_atts(
            array(
                'disabled_message' => __( 'Registration disabled', WP_OFFICE_TEXT_DOMAIN )
            ), $atts, 'wpoffice_registration_form' );

        if (is_user_logged_in()) {
            WO()->redirect(WO()->get_page_slug('hub_page'));
        }

        //deny registration for lite version
        if ( !defined( 'WP_OFFICE_PRO' ) ) {
            return __( 'Sorry, Self Registration Feature available only in Office Pro.', WP_OFFICE_TEXT_DOMAIN );
        }

        if (!empty($atts['role'])) {
            $role = $atts['role'];
            if( !WO()->role_can( $role, 'frontend_registration' ) ) {
                return $atts['disabled_message'];
            }
        } elseif (get_query_var('wpo_page_key')) {
            $role = str_replace('registration_', '', get_query_var('wpo_page_key'));
            if( !WO()->role_can( $role, 'frontend_registration' ) ) {
                WO()->redirect( get_site_url() );
            }
        } else {
            return '';
        }

        if ( !WO()->is_our_role( $role ) )
            return '';

        wp_enqueue_script( 'wpo-form-registration', WO()->plugin_url . 'assets/js/form-registration.js', array('jquery'), WP_OFFICE_VER, true );
        wp_localize_script( 'wpo-form-registration', 'wpo_form_registration', array(
            'registration_process_url' => WO()->get_ajax_route( 'wpo/core/Members', 'registration' )
        ) );

        wp_enqueue_script( 'password-strength-meter' );

            ob_start();

            WO()->get_template( 'registration-form.php', '', array(
                'role' => $role
            ) );
            //include_once WO()->plugin_dir . 'templates/registration-form.php';

            $content = ob_get_contents();
            if (ob_get_length()) {
                ob_end_clean();
            }

            return $content;
        }


    /**
     * Profile form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    static function profile_form( $atts ) {

        if ( !is_user_logged_in() )
            WO()->redirect(WO()->get_page_slug('login_page'));

        $member_data = get_userdata(get_current_user_id());

        if ( empty( $member_data ) || !WO()->is_our_role( $member_data->roles ) )
            return '';

        wp_enqueue_script( 'wpo-form-profile', WO()->plugin_url . 'assets/js/form-profile.js', array('jquery'), WP_OFFICE_VER, true );
        wp_localize_script( 'wpo-form-profile', 'wpo_form_profile', array(
            'save_profile_url' => WO()->get_ajax_route( 'wpo/core/Members', 'profile' )
        ) );

            ob_start();

            WO()->get_template( 'profile-form.php', '', array(
                'member_data' => $member_data
            ) );

            $content = ob_get_contents();

            if (ob_get_length()) {
                ob_end_clean();
            }

            return $content;
        }


    /**
     *  "Thank You" shortcode
     */
    static function thank_you( $atts, $content = null ) {
        $order = \wpo\gateways\Gateway::get_order_data();

        ob_start();
        WO()->get_template('checkout/thank_you.php', '', array(
            'order' => $order,
        ));
        $content = ob_get_clean();

        return $content;
    }


    /**
     *  Checkout shortcode
     */
    static function checkout( $atts, $content = null ) {
        $order = \wpo\gateways\Gateway::get_order_data();
        if ( empty( $order ) ) {
            WO()->wrong_page_checkout( 'checkout_without_order_id' );
        }

        $available_gateways = WO()->payment_gateways()->get_available_payment_gateways();

        if ( WO()->payment_gateways()->needs_payment( $order['total'] ) ) {
//            WC()->payment_gateways()->set_current_gateway( $available_gateways );
        } else {
            $available_gateways = array();
        }

        $billing_data = \wpo\gateways\Gateway::get_billing_data();
        $billing_data['countries'] = \wpo\gateways\Gateway::get_countries();

        ob_start();
        WO()->get_template('checkout/checkout.php', '', array(
            'order' => $order,
            'available_gateways' => $available_gateways,
            'billing_data' => $billing_data,
            'checkout_button_text' => apply_filters( 'wpoffice_checkout_button_text', __( 'Pay', WP_OFFICE_TEXT_DOMAIN ) ),
        ));
        $content = ob_get_clean();

        wp_enqueue_script( 'wpo-checkout_shortcode', WO()->plugin_url . 'assets/js/checkout_shortcode.js', array(), false, true );

        $data = array(
            'ajax_url_get_states' => WO()->get_ajax_route( 'wpo\gateways\Payment_Gateways', 'get_html_states' ),
            'ajax_url_submit' => WO()->get_ajax_route( 'wpo\gateways\Payment_Gateways', 'pay_process' ),
            'order_id' => $order['order_id'],
        );

        wp_localize_script( 'wpo-checkout_shortcode', 'data', $data );

        return $content;
    }


    /**
     *  Login form shortcode
     */
    static function login_form($atts, $content = null)
    {

        if (is_user_logged_in()) {
            WO()->redirect( WO()->get_page_slug( 'hub_page' ) );
            return '';
        }

        $message = '';
        $message_class = '';

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
        $login_url = WO()->get_page_slug( 'login_page' );
        $login_url = !empty( $login_url ) ? $login_url : wp_login_url();

        if ( isset( $_GET['key'] ) )
            $action = 'resetpass';

        // validate action so as to default to the login screen
        if ( !in_array( $action, array( 'postpass', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'login' ), true ) )
            $action = 'login';

        switch ( $action ) {
            case 'lostpassword' :
            case 'retrievepassword' : {
            $message =  __( 'Please enter your username or email address. You will receive a link to create a new password via email.', WP_OFFICE_TEXT_DOMAIN );
            $message_class = 'wpo_notice';

            if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
                $message = '';
                $user_data = array();
                $login_url = WO()->get_page_slug( 'login_page' );
                $login_url = !empty( $login_url ) ? $login_url : wp_login_url();

                if ( empty( $_POST['user_login'] ) ) {
                    $message =  __( 'Enter a username or e-mail address.', WP_OFFICE_TEXT_DOMAIN );
                    $message_class = 'wpo_error';
                } else {
                    $user_login = trim( $_POST['user_login'] );
                    if ( strpos( $user_login, '@' ) ) {
                        if ( !is_email( $user_login ) ) {
                            $message = __( 'Invalid E-mail.', WP_OFFICE_TEXT_DOMAIN );
                            $message_class = 'wpo_error';
                        } elseif ( !email_exists( $user_login ) ) {
                            $message = __( 'There is no user registered with that E-mail address.', WP_OFFICE_TEXT_DOMAIN );
                            $message_class = 'wpo_error';
                        } else {
                            $user_data = get_user_by( 'email', $user_login );
                        }
                    } else {
                        $user_data = get_user_by( 'login', $user_login );
                        if ( empty( $user_data ) ) {
                            $message = __( 'There is no user registered with that Username.', WP_OFFICE_TEXT_DOMAIN );
                            $message_class = 'wpo_error';
                        }
                    }
                }

                if( empty( $message ) ) {
                    // redefining user_login ensures we return the right case in the email
                    $user_login = $user_data->user_login;
                    $user_email = $user_data->user_email;
                    $key = $user_data->user_activation_key;

                    if ( empty( $key ) ) {
                        // Generate something random for a key...
                        $key = wp_generate_password( 20, false );
                        // Now insert the new md5 key into the db
                        global $wpdb;
                        $wpdb->update(
                            $wpdb->users,
                            array(
                                'user_activation_key' => $key
                            ),
                            array(
                                'user_login' => $user_login
                            )
                        );
                    }

                    $args = array(
                        'member_id'     => $user_data->ID,
                        'reset_password_url' => add_query_arg( array(
                            'action'    => 'rp',
                            'key'       => $key,
                            'login'     => rawurlencode( $user_login )
                        ), $login_url )
                    );

                    //send email
                    $sent = WO()->send_notification(
                        'reset_password',
                        array(
                            'doer' => $user_data->ID,
                            'member' => $user_data->ID
                        ),
                        $args
                    );

                    if ( is_wp_error( $sent ) ) {
                        $message = __('The e-mail could not be sent.', WP_OFFICE_TEXT_DOMAIN ) . "<br />\n"
                            . sprintf( __('Possible reason: %s', WP_OFFICE_TEXT_DOMAIN ), $sent->get_error_message() );
                        $message_class = 'wpo_error';
                    } else {
                        WO()->redirect( add_query_arg( array( 'checkemail' => 'confirm' ), $login_url ) );
                    }
                }
            }

            break;
        }
            case 'resetpass' :
            case 'rp' : {
            $rp_login = !empty( $_GET['login'] ) ? $_GET['login'] : '';

            $key = preg_replace( '/[^a-z0-9]/i', '', $_GET['key'] );
            if ( empty( $key ) || !is_string( $key ) ) {
                return __( 'Invalid key.', WP_OFFICE_TEXT_DOMAIN )
                . '&nbsp;<a href="' . add_query_arg( array( 'action' => 'lostpassword' ), $login_url ) . '">'
                . __( 'Get New Password', WP_OFFICE_TEXT_DOMAIN ) . '</a>';
            }

            if ( empty( $rp_login ) || !is_string( $rp_login ) ) {
                return  __( 'Invalid key.', WP_OFFICE_TEXT_DOMAIN )
                . '&nbsp;<a href="' . add_query_arg( array( 'action' => 'lostpassword' ), $login_url ) . '">'
                . __( 'Get New Password', WP_OFFICE_TEXT_DOMAIN ) . '</a>';
            }

            $user = get_user_by( 'login', $rp_login );
            if ( preg_replace( '/[^a-z0-9]/i', '', $user->user_activation_key ) != $key ) {
                return __( 'Invalid key.', WP_OFFICE_TEXT_DOMAIN )
                . '&nbsp;<a href="' . add_query_arg( array( 'action' => 'lostpassword' ), $login_url ) . '">'
                . __( 'Get New Password', WP_OFFICE_TEXT_DOMAIN ) . '</a>';
            }

            $message = __( 'Enter your new password below.', WP_OFFICE_TEXT_DOMAIN );
            $message_class = 'wpo_notice';

            if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
                if ( !empty( $_POST['user_pass'] ) ) {
                    wp_set_password( WO()->prepare_password( $_POST['user_pass'] ), $user->ID );
                    //send notification
                    WO()->send_notification(
                        'self_profile_update',
                        array(
                            'doer' => $user->ID,
                        ),
                        array(
                            'member_id' => $user->ID,
                            'object_type' => 'member',
                        )
                    );

                    WO()->redirect( add_query_arg( 'msg', 'reset', $login_url ) );
                }
            }
            break;
        }
            case 'login' :
            default : {
            if ( isset( $_GET['checkemail'] ) && 'confirm' == $_GET['checkemail'] ) {
                $message = __( 'Check your email for the confirmation link.', WP_OFFICE_TEXT_DOMAIN );
                $message_class = 'wpo_notice';
            }


            if ( isset( $_GET['msg'] ) && 'reset' == $_GET['msg'] ) {
                $message = __( 'Your password has been reset.', WP_OFFICE_TEXT_DOMAIN );
                $message_class = 'wpo_notice';
            } elseif( isset( $_GET['msg'] ) ) {
                $message = stripslashes( urldecode( $_GET['msg'] ) );
                $message_class = 'wpo_error';
            }

            break;
        }
        }

        $custom_style = WO()->get_settings( 'custom_style' );
        wp_enqueue_script( 'wpo-form-login', WO()->plugin_url . 'assets/js/form-login.js', array('jquery'), WP_OFFICE_VER, true );
        wp_localize_script( 'wpo-form-login', 'wpo_form_login', array(
            'action' => $action
        ) );

        ob_start();

        //include_once WO()->plugin_dir . 'templates/login-form.php';
        WO()->get_template('login-form.php', '', array(
            'custom_style' => $custom_style,
            'login_url' => $login_url,
            'action' => $action,
            'message' => $message,
            'message_class' => $message_class
        ));


        $content = ob_get_contents();

        if (ob_get_length()) {
            ob_end_clean();
        }

        return $content;
    }


    /**
     *  HUB page shortcode
     */
    static function hub_page($atts)
    {
        global $wp_query;

        $wpo_page_key = (!empty($wp_query->query_vars['wpo_page_key'])) ? trim($wp_query->query_vars['wpo_page_key']) : '';

        //our_hook
        do_action('wpoffice_before_load_hub_page');

        if ($wpo_page_key) {
            switch ($wpo_page_key) {
                case 'login_page':
                    do_shortcode('[wpoffice_login_form /]');
                    return '';
                    break;

                case 'profile_page':
                    do_shortcode('[wpoffice_profile_form /]');
                    return '';
                    break;

                case 'checkout':
                    do_shortcode('[wpoffice_checkout /]');
                    return '';
                case 'thank_you':
                    do_shortcode('[wpoffice_thank_you /]');
                    return '';

                default:
                    /*wpo_hook_
                        hook_name: wpoffice_hub_page_ + ['wpo_page_key'] + _shortcode
                        hook_title: Show another pages on HUB page
                        hook_description: Hook runs when open link of our pages (not HUB) and it's uses for run shortcodes of these pages.
                        hook_type: action
                        hook_in: wp-office
                        hook_location class-shortcodes.php
                        hook_param:
                        hook_since: 1.0.0
                    */
                    do_action('wpoffice_hub_page_' . $wp_query->query_vars['wpo_page_key'] . '_shortcode');
                    return '';
                    break;
            }

            //var_dump($wp_query->query_vars['wpo_page_key'] . '_shortcode');
        } else {

            //deny access for not logged in user
            if (!is_user_logged_in()) {
                WO()->redirect(WO()->get_page_slug('login_page'));
            }

            //var_dump('hub shortcode');
        }


        return '';
    }


    function get_search( $text = '' ) {
        ob_start();
        WO()->get_template( 'search.php', '', array(
            'search_value' => $text,
        ) );
        return ob_get_clean();
    }


    function get_pagination( $per_page, $total, $page, $maxPages = 5 ) {
        $pagination = new Pagination( $total, $per_page, $page );
        $pagination->setMaxPagesToShow($maxPages);
        ob_start();
        echo '<input type="hidden" name="paged" value="' . $page . '" />';
        WO()->get_template( 'pagination.php', '', array(
            'pagination' => $pagination,
        ) );
        return ob_get_clean();
    }


    function output_template( $name, $path, $args ) {
        $id = uniqid();
        wp_enqueue_script('wpo-render-shortcode');
        wp_localize_script( 'wpo-render-shortcode', 'wpo_render_shortcode', array(
            'loader'    => WO()->get_ajax_loader( 77 )
        ) );
        ob_start();
        if( empty( $name ) ) {
            echo '<div class="wpo_shortcode_block ' . ( !empty( $args['wrapper_class'] ) ? $args['wrapper_class'] : '' ) . '" id="' . $id . '">';
        }
        if( isset( $args['hidden'] ) && is_array( $args['hidden'] ) ) {
            foreach( $args['hidden'] as $field=>$value ) {
                echo '<input type="hidden" name="wpo_shortcode_attrs[' . $field . ']" value="' . $value . '"  />';
            }
        }

        if( !empty( $name ) ) {
            WO()->get_template($name, $path, isset($args['vars']) ? $args['vars'] : array());
        } else {
            ?>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('#<?php echo $id; ?>').wpoRenderShortcode({
                        ajax_url : '<?php echo $args['ajax_url']; ?>'
                    });
                });
            </script>
            <?php
        }

        return ob_get_clean();
    }

    function get_defaults( $out, $pairs, $atts, $shortcode ) {
        $shortcodes = $this->get_list();
        if( !isset( $shortcodes[ $shortcode ]['attributes'] ) ||
            !is_array( $shortcodes[ $shortcode ]['attributes'] ) ) return $out;

        foreach( $shortcodes[ $shortcode ]['attributes'] as $name=>$attr ) {
            if( !isset( $attr['value'] ) || isset( $out[ $name ] ) ) continue;

            $out[ $name ] = $attr['value'];
        }
        return $out;
    }


    /**
     * Add shortcodes
     */
    function add_shortcodes() {
        $this->get_list();

        foreach ( $this->shortcodes as $shortcode => $function ) {
            if( !isset( $function['callback'] ) ) continue;
            add_shortcode( $shortcode, $function['callback'] );

            if( !isset( $function['attributes'] ) || !is_array( $function['attributes'] ) ) continue;
            add_filter( 'shortcode_atts_' . $shortcode, array( &$this, 'get_defaults' ), 99, 4 );
        }
    }


    //end class
}
