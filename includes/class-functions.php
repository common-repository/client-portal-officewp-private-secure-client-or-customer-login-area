<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'WPOffice_Functions' ) ) {

    /**
     * functions WPOffice_Functions Class
     *
     * @class WPOffice_Functions
     * @version    1.0.0
     */
    class WPOffice_Functions {

        var $cache;
        protected $content_types = array( 'own', 'personal_assigned', 'children_own', 'children_assigned' );
        protected $cap_types = array( 'own', 'assigned', 'on' );


        /**
         * Inserts a new key/value after the key in the array.
         *
         * @param $needle array Key to insert the element after
         * @param $haystack array to insert the element into
         * @param $new_key string The key to insert
         * @param $new_value string The value to insert
         * @return array The new array if the $needle key exists, otherwise an unmodified $haystack
         */
        public function array_insert_after( $needle, $haystack, $new_key, $new_value ) {

            if ( array_key_exists( $needle, $haystack ) ) {

                $new_array = array();

                foreach ( $haystack as $key => $value ) {

                    $new_array[ $key ] = $value;

                    if ( $key === $needle ) {
                        $new_array[ $new_key ] = $new_value;
                    }
                }

                return $new_array;
            }

            return $haystack;
        }


        /**
         * Get all currency with all info
         *
         * @return array
         */
        function get_currencies() {
            return get_option( 'wpo_currencies', array() );
        }


        /**
         * Get currency symbol
         *
         * @param string $currency_id
         * @return string
         */
        function get_currency( $currency_id_or_abc = '' ) {
            return '$';
            $currencies = $this->get_currencies();
            if ( isset( $currencies[ $currency_id ] ) && isset( $currencies[ $currency_id ]['symbol'] ) ){
                $currency = $currencies[ $currency_id ]['symbol'];
            } elseif ( $first = array_shift ( $currencies ) && isset ( $first['symbol'] ) ) {
                $currency = $first['symbol'];
            } else {
                $currency = 'N\A';
            }
            return $currency;
        }


        /**
         * Prepare nice format of number
         *
         * @param boolean $currency
         * @param float $number
         * @param int $decimals
         * @param string $dec_point
         * @param string $thousands_sep
         * @return string
         */
        function number_format( $number = 0, $currency = false, $decimals = null, $dec_point = null, $thousands_sep = null ) {
            $decimals = !is_null( $decimals ) ? $decimals : 2;
            $dec_point = !is_null( $dec_point ) ? $dec_point : '.';
            $thousands_sep = !is_null( $thousands_sep ) ? $thousands_sep : ',';

            $minus = 0 > $number ? '- ' : '';
            $return = number_format( (float)abs($number), $decimals, $dec_point, $thousands_sep );

            if ( $currency ) {
                $return = $this->get_currency() . $return;
            }

            return $minus . $return;
        }


        /**
         * Function for decode string for valid AJAX to array
         *
         * @param string $string
         * @return array
         */
        function valid_ajax_decode( $string ) {

            $decoded_array = json_decode( base64_decode( $string ), true );

            return $decoded_array;
        }


        /**
         * Function for encode array for valid sending with AJAX data
         *
         * @param array $array
         * @return string
         */
        function valid_ajax_encode( $array ) {

            $encoded_string = base64_encode( json_encode( $array ) );

            return $encoded_string;
        }

        /**
         * Reset our rewrite rules
         *
         * @return void;
         */
        function reset_rewrite_rules() {

            update_option( 'wpo_flush_rewrite_rules', 1 );

        }


        function get_post_types() {
            $office_pages_slug = WO()->get_page_slug( 'office_page', array(), false, false );

            $office_page_capability_type = 'office_page';
            $office_page_capabilities = array(
                'edit_post'             => 'edit_' . $office_page_capability_type,
                'read_post'             => 'read_' . $office_page_capability_type,
                'delete_post'           => 'delete_' . $office_page_capability_type,
                'edit_posts'            => 'edit_' . $office_page_capability_type . 's',
                'edit_others_posts'     => 'edit_others_' . $office_page_capability_type . 's',
                'publish_posts'         => 'publish_' . $office_page_capability_type . 's',
                'read_private_posts'    => 'read_private_' . $office_page_capability_type . 's',
                'create_posts'          => 'edit_' . $office_page_capability_type . 's',
            );

            $office_hub_capability_type = 'office_hub';
            $office_hub_capabilities = array(
                'edit_post'             => 'edit_' . $office_hub_capability_type,
                'read_post'             => 'read_' . $office_hub_capability_type,
                'delete_post'           => 'delete_' . $office_hub_capability_type,
                'edit_posts'            => 'edit_' . $office_hub_capability_type . 's',
                'edit_others_posts'     => 'edit_others_' . $office_hub_capability_type . 's',
                'publish_posts'         => 'publish_' . $office_hub_capability_type . 's',
                'read_private_posts'    => 'read_private_' . $office_hub_capability_type . 's',
                'create_posts'          => 'edit_' . $office_hub_capability_type . 's',
            );

            $our_post_types = array(
                'office_page' => array(
                    'labels'                => array(
                        'name'                  => __( 'Office Pages', WP_OFFICE_TEXT_DOMAIN ),
                        'singular_name'         => __( 'Office Page', WP_OFFICE_TEXT_DOMAIN ),
                        'edit_item'             => sprintf( __('Edit %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office Page', WP_OFFICE_TEXT_DOMAIN ) ),
                        'add_new_item'          => sprintf( __('Add %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office Page', WP_OFFICE_TEXT_DOMAIN ) ),
                        'view_item'             => sprintf( __('View %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office Page', WP_OFFICE_TEXT_DOMAIN ) ),
                        'search_items'          => sprintf( __('Search %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office Page', WP_OFFICE_TEXT_DOMAIN ) ),
                        'not_found'             => __('Nothing found', WP_OFFICE_TEXT_DOMAIN ),
                        'not_found_in_archive'  => __('Nothing found in Archive', WP_OFFICE_TEXT_DOMAIN ),
                        'parent_item_colon'     => ''
                    ),
                    'public'                => true,
                    'publicly_queryable'    => true,
                    'show_ui'               => true,
                    'query_var'             => true,
                    'show_in_menu'          => true,
                    'show_in_admin_bar'     => false,
                    'capability_type'       => $office_page_capability_type,
                    'capabilities'          => $office_page_capabilities,
                    'map_meta_cap'          => true,
                    'hierarchical'          => true,
                    'exclude_from_search'   => true,
                    'menu_position'         => 145,
                    'supports'              => array('title', 'editor', 'thumbnail', 'meta', 'revisions'),
                    'rewrite'               => array( 'slug' => $office_pages_slug, 'with_front' => false, 'pages' => false, ),

                ),

                'office_hub' => array(
                    'labels'                => array(
                        'name'                  => __( 'Office HUBs', WP_OFFICE_TEXT_DOMAIN ),
                        'singular_name'         => __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ),
                        'add_new_item'          => sprintf( __('Add New %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'edit_item'             => sprintf( __('Edit %s Item', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'view_item'             => sprintf( __('View %s Item', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'new_item'              => sprintf( __('New %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'insert_into_item'      => sprintf( __('Insert into %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'uploaded_to_this_item' => sprintf( __('Uploaded to this %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'search_items'          => sprintf( __('Search %s', WP_OFFICE_TEXT_DOMAIN ), __( 'Office HUB', WP_OFFICE_TEXT_DOMAIN ) ),
                        'not_found'             => __('Nothing found', WP_OFFICE_TEXT_DOMAIN ),
                        'not_found_in_archive'  => __('Nothing found in Archive', WP_OFFICE_TEXT_DOMAIN ),
                        'parent_item_colon'     => ''
                    ),
                    'public'                => true,
                    'publicly_queryable'    => true,
                    'show_ui'               => true,
                    'query_var'             => true,
                    'show_in_menu'          => true,
                    'show_in_admin_bar'     => false,
                    'capability_type'       => $office_hub_capability_type,
                    'capabilities'          => $office_hub_capabilities,
                    'map_meta_cap'          => true,
                    'hierarchical'          => true,
                    'exclude_from_search'   => true,
                    'menu_position'         => 145,
                    'supports'              => array('title', 'editor', 'thumbnail', 'meta', 'revisions'),
                    'rewrite'               => array( '', 'with_front' => false, 'pages' => false, ),

                ),

            );

            //our_hook
            return apply_filters( 'wpoffice_custom_post_types', $our_post_types );
        }


        /**
         * Echo HelpTip block
         *
         * @param string $help_text Help HTML
         * @return string
         */
        function render_helptip( $help_text, $echo = true ) {
            if ( $echo ) { ?>
                <span class="wpo_helptip" data-wpo_tooltip="<?php echo esc_attr( $help_text ) ?>"></span>
            <?php } else {
                return '<span class="wpo_helptip" data-wpo_tooltip="' . esc_attr( $help_text ) . '"></span>';
            }
            return '';
        }

        /**
         * Generate button switcher
         *
         * @param $name
         * @param $values
         */
        function render_switch_button( $attributes, $values ) { ?>
            <div class="wpo_switch_button">
                <?php
                $attr_string = '';
                foreach( $attributes as $attr_key=>$attr_value ) {
                    $attr_string .= "$attr_key=\"$attr_value\" ";
                }
                $name = isset( $attributes['name'] ) ? $attributes['name'] : '';
                foreach ( $values as $value ) { ?>
                    <div class="wpo_switch_button_value">
                        <input type="radio" <?php echo $attr_string; ?> value="<?php echo $value['value'] ?>" id="<?php echo $name . '_' . $value['value'] ?>" <?php checked( ( isset( $value['checked'] ) && $value['checked'] ) ) ?> />
                        <label for="<?php echo $name . '_' . $value['value'] ?>"><?php echo $value['title'] ?></label>
                    </div>
                <?php } ?>
            </div>
        <?php }


        function get_endpoints() {
            if( !isset( $this->cache['endpoints'] ) ) {
                $endpoints = array(
                    'office_page' => array(
                        'title' => __('Office Page', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'office-page',
                        'no_page' => true
                    ),
                    'login_page' => array(
                        'title' => __('Login', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'login',
                        'content' => '[wpoffice_login_form /]'
                    ),
                    'logout_page' => array(
                        'title' => __('Logout', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'logout',
                        'no_page' => true
                    ),
                    'profile_page' => array(
                        'title' => __('Profile', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'profile',
                        'content' => '[wpoffice_profile_form /]'
                    ),
                    'checkout' => array(
                        'title' => __('Checkout', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'checkout',
                        'content' => '[wpoffice_checkout /]',
                    ),
                    'thank_you' => array(
                        'title' => __('Thank You Page', WP_OFFICE_TEXT_DOMAIN),
                        'slug' => 'thank_you',
                        'content' => '[wpoffice_thank_you /]',
                    ),
                );

                $roles_list = $this->get_settings('roles');

                if (!empty($roles_list)) {
                    foreach ($roles_list as $key => $value) {
                        $endpoints['registration_' . $key] = array(
                            'title' => sprintf(__('%s Registration', WP_OFFICE_TEXT_DOMAIN), $value['title']),
                            'slug' => 'registration-' . $key,
                            'content' => '[wpoffice_registration_form role="' . $key . '" /]'
                        );
                    }
                }

                //our_hook
                $this->cache['endpoints'] = apply_filters('wpoffice_link_endpoints', $endpoints);
            }

            return $this->cache['endpoints'];
        }


        function multiString( $text = '' ) {
            if( is_string( $text ) && !empty( $text ) ) {
                $text = str_replace( array("\r\n", "\n\r", "\r", "\n"), '<br />', $text );
                return $text;
            } else {
                return '';
            }
        }

        function linkifyText( $text = '' ) {
            if( is_string( $text ) && !empty( $text ) ) {
                $text = preg_replace('/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ims', '<a href="$1" target="_blank">$1</a>', $text );
                $text = preg_replace('/(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/ims', '<a href="mailto:$1" target="_blank">$1</a>', $text );
                return $text;
            } else {
                return '';
            }
        }



        function prepare_text_view( $text, $escape = true, $multi_string = true, $linkify = true ) {
            if( $escape ) {
                $text = esc_html( $text );
            }
            if( $multi_string ) {
                $text = $this->multiString( $text );
            }
            if( $linkify ) {
                $text = $this->linkifyText( $text );
            }
            return $text;
        }


        /**
         * decode password special chars
         *
         * @param $pass
         * @return string
         */
        function prepare_password( $pass ) {
            return html_entity_decode( esc_attr( trim( $pass ) ) );
        }


        function is_wp_login() {

            if ( !isset( WO()->wpo_flags['is_wp_login'] ) ) {

                // The blog's URL
                $blog_url = trailingslashit( get_bloginfo( 'url' ) );
                $blog_url = str_replace( 'https://', '', $blog_url );
                $blog_url = str_replace( 'http://', '', $blog_url );

                // The Current URL
                $current_url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                $request_url = str_replace( $blog_url, '', $current_url );
                $request_url = str_replace( 'index.php/', '', $request_url );

                $url_parts = explode( '?', $request_url, 2 );
                $base = $url_parts[0];

                // Remove trailing slash
                $base = rtrim( $base, "/" );
                $exp = explode( '/', $base, 2 );
                $super_base = end( $exp );


                if ( $super_base == 'wp-login.php' ) {
                    WO()->wpo_flags['is_wp_login'] = true;
                } else {
                    WO()->wpo_flags['is_wp_login'] = false;
                }

            }

            return WO()->wpo_flags['is_wp_login'];
        }

        // Function to Copy folders and files
        function rcopy($src, $dst) {
            if ( !is_dir( $src ) ) {
                die("$src must be a directory");
            }

            $handle = opendir( $src );

            while( false !== ( $entry = readdir( $handle ) ) ) {
                if( $entry == '.' || $entry == '..' ) continue;
                if( is_dir( $src . '/' . $entry ) ) {
                    if( !is_dir( $dst . '/' . $entry ) ) {
                        mkdir( $dst . '/' . $entry );
                    }
                    self::rcopy( $src . '/' . $entry, $dst . '/' . $entry );
                } else {
                    copy( $src . '/' . $entry, $dst . '/' . $entry );
                }
            }
            closedir( $handle );

            self::rrmdir($src);
        }

        public static function rrmdir( $dirPath ) {
            if ( !is_dir( $dirPath ) ) {
                die("$dirPath must be a directory");
            }
            $dir = rtrim( $dirPath, '/' ) . '/';
            if ( is_dir( $dir ) ) {
                $objects = scandir( $dir );
                foreach ( $objects as $object ) {
                    if ( $object != '.' && $object != '..' ) {
                        if ( is_dir( $dir . DIRECTORY_SEPARATOR . $object ) ) {
                            self::rrmdir( $dir . '/' . $object );
                        } else {
                            unlink( $dir . DIRECTORY_SEPARATOR . $object );
                        }
                    }
                }
                rmdir( $dir );
            }
        }


        /**
         * Function for echo Animated number in circle
         *
         * @param int $size round Size
         * @param int $current_value current Value
         * @param int $max_value Max Value
         * @param int $duration Animation Duration
         * @param bool|false $circle_width  Size of the loading line
         */
        function numeric_circle_spinner( $size, $current_value, $max_value, $duration = 2, $circle_width = false ) {
            $unique = uniqid();
            if( 0 == $max_value ) {
                $max_value = 1;
            } ?>
            <style type="text/css">
                .radial-progress<?php echo $unique ?> {
                    width:  <?php echo $size ?>px;
                    height: <?php echo $size ?>px;
                    background: #d6dadc;
                    border-radius: 50%;
                    margin: 0 auto;
                }

                .radial-progress<?php echo $unique ?> .inset {
                    width:       <?php echo !$circle_width ? $size*0.8 : $size - $circle_width*2 ?>px;
                    height:      <?php echo !$circle_width ? $size*0.8 : $size - $circle_width*2 ?>px;
                    position:    absolute;
                    margin-left: <?php echo !$circle_width ? $size*0.2/2 : $circle_width ?>px;
                    margin-top:  <?php echo !$circle_width ? $size*0.2/2 : $circle_width ?>px;
                    background-color: #fff;
                    border-radius: 50%;
                }

                .radial-progress<?php echo $unique ?> .mask,
                .radial-progress<?php echo $unique ?> .fill {
                    width:    <?php echo $size ?>px;
                    height:   <?php echo $size ?>px;
                    position: absolute;
                    border-radius: 50%;
                    -webkit-backface-visibility: hidden;
                    transition: -webkit-transform 1s;
                    transition: -ms-transform 1s;
                    transition: transform 1s;
                }

                .radial-progress<?php echo $unique ?> .mask {
                    clip: rect(0px, <?php echo $size ?>px, <?php echo $size ?>px, <?php echo $size/2 ?>px);
                }

                .radial-progress<?php echo $unique ?> .fill {
                    clip: rect(0px, <?php echo $size/2 ?>px, <?php echo $size ?>px, 0px);
                    background-color: #16a6b6;
                }

                .radial-progress<?php echo $unique ?> .mask.full,
                .radial-progress<?php echo $unique ?> .fill {
                    -webkit-transform: rotate( <?php echo $current_value/$max_value * 180 ?>deg );
                    -ms-transform: rotate( <?php echo $current_value/$max_value * 180 ?>deg );
                    transform: rotate( <?php echo $current_value/$max_value * 180 ?>deg );

                    animation: rotation<?php echo $unique ?> <?php echo $duration ?>s linear;
                }
                .radial-progress<?php echo $unique ?> .fill.fix {
                    -webkit-transform: rotate( <?php echo $current_value/$max_value * 360 ?>deg );
                    -ms-transform: rotate( <?php echo $current_value/$max_value * 360 ?>deg );
                    transform: rotate( <?php echo $current_value/$max_value * 360 ?>deg );

                    animation: rotation2<?php echo $unique ?> <?php echo $duration ?>s linear;
                }

                .radial-progress<?php echo $unique ?> .value {
                    float:left;
                    width:100%;
                    text-align: center;
                    height: 100%;
                    line-height: <?php echo !$circle_width ? $size*0.8 - 2 : $size - $circle_width*2 - 2 ?>px;
                    font-size: <?php echo !$circle_width ? $size/5 : ( $size - $circle_width*2 ) / 5 ?>px;
                    font-weight: lighter;
                }


                @keyframes rotation<?php echo $unique ?> {
                    0% {
                        transform: rotate(0deg);
                    }
                    100% {
                        transform: rotate(<?php echo $current_value/$max_value * 180 ?>deg);
                    }
                }
                @keyframes rotation2<?php echo $unique ?> {
                    0% {
                        transform: rotate(0deg);
                    }
                    100% {
                        transform: rotate(<?php echo $current_value/$max_value * 360 ?>deg);
                    }
                }
            </style>

            <script type="text/javascript">
                jQuery( document ).ready( function(){
                    jQuery('.radial-progress<?php echo $unique ?> .value').each( function () {
                        var $this = jQuery(this);
                        jQuery({ Counter: 0 }).animate({
                            Counter: $this.data('number')
                        }, {
                            duration: <?php echo $duration*1000 ?>,
                            easing: 'swing',
                            step: function () {
                                $this.text( Math.ceil( this.Counter ) );
                            }
                        });
                    });
                });
            </script>

            <div class="wpo_radial_progress radial-progress<?php echo $unique ?>">
                <div class="circle">
                    <div class="mask full">
                        <div class="fill"></div>
                    </div>
                    <div class="mask half">
                        <div class="fill"></div>
                        <div class="fill fix"></div>
                    </div>
                </div>
                <div class="inset">
                    <div class="value" data-number="<?php echo $current_value ?>"></div>
                </div>
            </div>

            <?php
        }


        /**
         * Get HUB default and set/create if not exist
         *
         *
         * @return int Default HUB ID
         */
        function get_hub_default() {

            //get default HUB page
            $default = WO()->get_settings( 'default_hub' );
            if ( ! $default ) {

                $hubs = get_posts( array( 'post_type' => 'office_hub', 'numberposts' => '1', 'post_status' => 'publish' ) );
                if ( !empty( $hubs[0]->ID ) ) {
                    $default = $hubs[0]->ID ;
                } else {
                    $post_author_id = '';

                    $current_user = wp_get_current_user();
                    if ( $current_user ) {
                        $post_author_id = $current_user->ID;
                    }

                    //Construct args for the new page
                    $args = array(
                        'post_title'     => 'First HUB',
                        'post_status'    => 'publish',
                        'post_author'    => $post_author_id,
                        'post_content'   => 'Welcome to your HUB! You can use this page to view your assigned pages, upload a file, send us a message, and more!
<h2><span style="text-decoration: underline;">Assigned Pages</span></h2>
[wpoffice_pages /]
<h2><span style="text-decoration: underline;">File Sharing</span></h2>
[wpoffice_files_list access_type="all" sort_by="title" sort="desc" categories="0" with_subcategories="yes" show_sort="yes" show_category="yes" show_thumbnail="yes" show_search="yes" show_bulk_actions="yes" per_page="20" /]
<h2><span style="text-decoration: underline;">Private Messages</span></h2>
[wpoffice_message_box /]',
                        'post_type'      => 'office_hub',
                        'ping_status'    => 'closed',
                        'comment_status' => 'closed'
                    );

                    $default = wp_insert_post( $args );
                }

                WO()->set_settings( 'default_hub', $default );
            }

            return $default;
        }


        /**
         * Get Title for our pages
         *
         * @param string $page_key
         *
         * @return string Title or empty
         */
        function get_page_titles( $page_key ) {
            $endpoints = $this->get_endpoints();
            $our_page_titles = array(
                'hub_page'          => __( 'HUB', WP_OFFICE_TEXT_DOMAIN )
            );
            foreach( $endpoints as $key=>$val ) {
                $our_page_titles[ $key ] = $val['title'];
            }

            /*wpo_hook_
                hook_name: wpoffice_page_titles
                hook_title: Get Our Page Titles
                hook_description: Hook runs when we show title of our pages.
                hook_type: filter
                hook_in: wp-office
                hook_location class-functions.php
                hook_param: array $our_page_titles
                hook_since: 1.0.0
            */
            $our_page_titles = apply_filters( 'wpoffice_page_titles', $our_page_titles );


            if ( !empty( $our_page_titles[$page_key] ) ) {
                return $our_page_titles[$page_key];
            }

            return '';
        }


        function replace_placeholders( $content, $args = array(), $place = '' ) {
            $content = stripslashes( $content );

            $member = false;
            $member_id = false;
            if ( isset( $args['member_id'] ) && 0 < $args['member_id'] ) {
                $member_id = $args['member_id'];
                $member = get_userdata( $member_id );
            }

            $current_user = '';
            if( is_user_logged_in() ) {
                $current_user = wp_get_current_user();
            }

            $contact_info = WO()->get_settings( 'contact_info' );

            $post_id = false;
            $post = false;
            if ( isset( $args['post_id'] ) && 0 < $args['post_id'] ) {
                $post_id = $args['post_id'];
                $post = get_post( $post_id, ARRAY_A );
            }

            $placeholders_data = array (
                '{wpo_site_title}'                => get_option( 'blogname' ),
                '{wpo_site_url}'                  => site_url(),

                '{wpo_current_user_id}'           => is_user_logged_in() ? $current_user->get( 'ID' ) : '',
                '{wpo_current_user_name}'         => is_user_logged_in() ? $current_user->get( 'display_name' ) : '',
                '{wpo_current_user_email}'        => is_user_logged_in() ? $current_user->get( 'user_email' ) : '',
                '{wpo_current_user_login}'        => is_user_logged_in() ? $current_user->get( 'user_login' ) : '',
                '{wpo_current_user_registered}'   => is_user_logged_in() ? $current_user->get( 'user_registered' ) : '',
                '{wpo_current_user_first_name}'   => is_user_logged_in() ? get_user_meta( $current_user->get( 'ID' ), 'first_name', true ) : '',
                '{wpo_current_user_last_name}'    => is_user_logged_in() ? get_user_meta( $current_user->get( 'ID' ), 'last_name', true ) : '',

                '{wpo_member_id}'                 => $member !== false ? $member->get( 'ID' ) : '',
                '{wpo_member_name}'               => $member !== false ? $member->get( 'display_name' ) : '',
                '{wpo_member_email}'              => $member !== false ? $member->get( 'user_email' ) : '',
                '{wpo_member_login}'              => $member !== false ? $member->get( 'user_login' ) : '',
                '{wpo_member_registered}'         => $member !== false ? $member->get( 'user_registered' ) : '',
                '{wpo_member_first_name}'         => $member !== false ? get_user_meta( $member_id, 'first_name', true ) : '',
                '{wpo_member_last_name}'          => $member !== false ? get_user_meta( $member_id, 'last_name', true ) : '',
                '{wpo_member_password}'           => !empty( $args['member_password'] ) ? $args['member_password'] : '',

                '{wpo_login_url}'                 => '' != WO()->get_page_slug( 'login_page' ) ? WO()->get_page_slug( 'login_page' ) : wp_login_url(),
                '{wpo_logout_url}'                => '' != WO()->get_page_slug( 'logout_page' ) ? WO()->get_page_slug( 'logout_page' ) : wp_logout_url(),

                '{wpo_contact_name}'             => ( isset( $contact_info['name'] ) ) ? $contact_info['name'] : '',
                '{wpo_contact_mailing_address}'  => ( isset( $contact_info['mailing_address'] ) ) ? $contact_info['mailing_address'] : '',
                '{wpo_contact_website}'          => ( isset( $contact_info['website'] ) ) ? $contact_info['website'] : '',
                '{wpo_contact_email}'            => ( isset( $contact_info['email'] ) ) ? $contact_info['email'] : '',
                '{wpo_contact_phone}'            => ( isset( $contact_info['phone'] ) ) ? $contact_info['phone'] : '',

                '{wpo_reset_password_url}'        => !empty( $args['reset_password_url'] ) ? $args['reset_password_url'] : '',
            );

            if ( isset( $post['post_type'] ) && $post['post_type'] == 'office_page' ) {
                $placeholders_data['{wpo_office_page_title}'] = $post['post_title'];

                $author = get_userdata( $post['post_author'] );
                $placeholders_data['{wpo_office_page_author}'] = $author !== false ? $author->get( 'display_name' ) : '';

                $category_name = '';
                $category_id = get_post_meta( $post_id, 'category_id', true );
                if ( !empty( $category_id ) ) {
                    $category = WO()->get_object( $category_id );
                    $category_name = $category['title'];
                }
                $placeholders_data['{wpo_office_page_category}'] = $category_name;
                $placeholders_data['{wpo_office_page_url}'] = get_permalink( $post_id );
            }

            $placeholders_data = apply_filters( "wpoffice_replace_placeholders", $placeholders_data, $args, $place );

            foreach ( $placeholders_data as $key => $value ) {
                if  ( is_string( $value ) ) {
                    $placeholders_data[$key] = str_replace(array('{', '}'), array('&#123;', '&#125;'), $value);
                }
            }

            $content = apply_filters( "wpoffice_replace_placeholders_content", $content, $args, $place );
            $content = str_replace( array_keys( $placeholders_data ), array_values( $placeholders_data ), $content );
            return $content;
        }


        //todo
        function get_members_can_view_object( $object_type, $object_id ) {
            if ( has_filter( 'wpoffice_members_can_view_object_' . $object_type ) ) {
                return apply_filters( 'wpoffice_members_can_view_object_' . $object_type, array(), $object_id );
            } else {
                global $wpdb;

                $member_ids = array();
                if ( 'office_page' == $object_type ) {
                    /*
                     * Block for getting member own pages
                     * 1) Get page's author
                     * 2) Page assigned to member page->member
                     * 3) Page assigned to member page->profile->member
                     * 4) Page assigned to member page->page category->profile->member
                     *
                     * 5) Can add filter to feature page->profile->member circle->member
                     *                              page->page category->profile->member circle->member
                     *
                     */


                    //2) Members can see all office pages
                    $roles = $this->roles_can( 'view_office_page', 'on' );
                    if ( !empty( $roles ) ) {
                        $member_ids = array_merge( $member_ids, get_users( array(
                            'role__in' => $roles,
                            'fields' =>'ids',
                        ) ) );
                    }

                    //1) Member is page's author
                    $roles = $this->roles_can( 'view_office_page', 'own' );
                    $manage_roles = $roles;
                    if ( !empty( $roles ) ) {
                        $post = get_post( $object_id );
                        if ( !empty( $post ) ) {
                            $member_ids = array_merge( $member_ids, get_users( array(
                                'role__in' => $roles,
                                'include' => $post->post_author,
                                'fields' =>'ids',
                            ) ) );
                        }
                    }

                    $roles = $this->roles_can( 'view_office_page', 'assigned' );
                    $manage_roles = array_merge( $manage_roles, $roles );
                    if ( !empty( $roles ) ) {
                        $users = WO()->get_assign_data_by_object( 'office_page', $object_id, 'member' );

                        $office_page_profiles = WO()->get_assign_data_by_object( 'office_page', $object_id, 'profile' );
                        $office_page_category = get_post_meta( $object_id, 'category_id', true );
                        if ( !empty( $office_page_category ) ) {
                            $office_page_profiles = array_merge( $office_page_profiles, WO()->get_assign_data_by_object( 'office_page_category', $office_page_category, 'profile' ) );
                        }

                        $users = array_merge( $users, WO()->get_assign_data_by_assign( 'user', 'profile', $office_page_profiles ) );

                        $circles = WO()->get_assign_data_by_assign( 'circle', 'profile', $office_page_profiles );

                        $users = array_merge( $users, WO()->get_assign_data_by_assign( 'user', 'circle', $circles ) );

                        if ( !empty( $users ) ) {
                            $member_ids = array_merge( $member_ids, get_users( array(
                                'role__in' => $roles,
                                'include' => $users,
                                'fields' =>'ids',
                            ) ) );
                        }
                    }

                    $manage_roles = $this->roles_can( 'view_office_page', 'assigned' );
                    $roles = $this->roles_can( 'view_office_page', 'assigned' );


                    if ( WO()->member_can( $user_id, $capability ) == 'assigned' ) {
                        //2) Page assigned to member page->member
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                            in_array( 'personal_assigned', $ownership ) ) {
                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                WO()->get_assign_data_by_assign( 'office_page', 'member', $user_id );

                            //get member's profiles
                            $member_profiles = WO()->get_assign_data_by_object('user', $user_id, 'profile');
                            //5) Can add filter to feature page->profile->member circle->member
                            //                             page->page category->profile->member circle->member
                            $member_profiles = apply_filters('wpoffice_member_assigned_profiles', $member_profiles, $user_id);

                            //get files from profiles
                            if ( !empty( $member_profiles ) ) {
                                //3) Page assigned to member page->profile->member
                                $result = WO()->get_assign_data_by_assign('office_page', 'profile', $member_profiles);
                                if (!empty($result)) {
                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] = array_merge(
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                        $result
                                    );
                                }

                                //get categories in profile
                                $cat_ids = WO()->get_assign_data_by_assign('office_page_category', 'profile', $member_profiles);
                                //select only page categories
                                if( !empty( $cat_ids ) ) {
                                    $categories = $wpdb->get_col(
                                        "SELECT DISTINCT o.id
                                            FROM {$wpdb->prefix}wpo_objects o
                                            WHERE o.type = 'office_page_category' AND
                                                  o.id IN('" . implode("','", $cat_ids) . "')"
                                    );
                                }

                                //4) Page assigned to member page->page category->profile->member
                                if ( !empty( $categories ) ) {
                                    $result = $wpdb->get_col(
                                        "SELECT DISTINCT p.ID
                                            FROM {$wpdb->posts} p
                                            LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'category_id' )
                                            WHERE pm.meta_value IN('" . implode("','", $categories) . "')"
                                    );
                                    if (!empty($result)) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                            array_merge(
                                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                                $result
                                            );
                                    }
                                }
                            }
                        }
                    }

                    /*
                     * Block for getting pages, which assigned to child members assigned to current member
                     * 1) Member is page's author member->member
                     * 2) Page assigned to member page->member
                     * 3) Page assigned to member page->profile->member
                     * 4) Page assigned to member page->page category->profile->member
                     *
                     * 5) Can add filter to feature page->profile->member circle->member
                     *                              page->page category->profile->member circle->member
                     *
                     */
                    //get child roles for current member
                    $child_roles_list = array();
                    if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) ||
                        empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                        count( array_intersect( array( 'children_own', 'children_assigned' ), $ownership ) ) ) {

                        $child_roles_list = $this->get_role_all_child( $this->get_office_role( $user_id ) );
                    }

                    $managing_members_array = array();
                    //current member can view assigned member's own files
                    if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) &&
                        in_array( 'children_own', $ownership ) ) {
                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array();
                        foreach( $child_roles_list as $role ) {
                            if( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                continue;

                            $managing_members = $managing_members_array[ $role ] =
                                $this->get_available_members_by_role( $role, $user_id );

                            if ( !empty( $managing_members ) ) {
                                //1) Member is page's author
                                $result = $wpdb->get_col(
                                    "SELECT p.ID
                                    FROM {$wpdb->posts} p
                                    WHERE p.post_type = 'office_page' AND
                                          p.post_status IN('publish','trash','draft') AND
                                          p.post_author IN('" . implode( "','", $managing_members ) . "')"
                                );
                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array_merge(
                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'],
                                    $result
                                );
                            }
                        }
                    }

                    //current member can view assigned member's assigned pages
                    if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                        in_array( 'children_assigned', $ownership ) ) {
                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array();
                        foreach ( $child_roles_list as $role ) {
                            if ( WO()->member_can_manage( $user_id, $capability, $role ) != 'assigned' )
                                continue;

                            if( isset( $managing_members_array[ $role ] ) ) {
                                $managing_members = $managing_members_array[ $role ];
                            } else {
                                $managing_members = $this->get_available_members_by_role( $role, $user_id );
                            }

                            if ( !empty( $managing_members ) ) {
                                //2) Page assigned to member page->member
                                $result = WO()->get_assign_data_by_assign( 'office_page', 'member', $managing_members );
                                if (!empty($result)) {
                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                        $result
                                    );
                                }

                                //get member's profiles
                                $member_profiles = WO()->get_assign_data_by_object( 'user', $managing_members, 'profile' );
                                //5) Can add filter to feature page->profile->member circle->member
                                //                             page->page category->profile->member circle->member
                                $member_profiles = apply_filters( 'wpoffice_member_assigned_profiles', $member_profiles, $managing_members );

                                //get files from profiles
                                if ( !empty( $member_profiles ) ) {
                                    //3) Page assigned to member page->profile->member
                                    $result = WO()->get_assign_data_by_assign( 'office_page', 'profile', $member_profiles );
                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type]['children_assigned'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type]['children_assigned'],
                                            $result
                                        );
                                    }

                                    //get categories in profile
                                    $categories = WO()->get_assign_data_by_assign( 'office_page_category', 'profile', $member_profiles );
                                    //select only file categories
                                    $categories = $wpdb->get_col(
                                        "SELECT DISTINCT o.id
                                        FROM {$wpdb->prefix}wpo_objects o
                                        WHERE o.type = 'office_page_category' AND
                                              o.id IN('" . implode("','", $categories) . "')"
                                    );

                                    //4) Page assigned to member page->page category->profile->member
                                    if ( !empty( $categories ) ) {
                                        $result = $wpdb->get_col(
                                            "SELECT DISTINCT p.ID
                                            FROM {$wpdb->posts} p
                                            LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'category_id' )
                                            WHERE p.post_type = 'office_page' AND
                                                  pm.meta_value IN('" . implode("','", $categories) . "')"
                                        );
                                        if ( !empty( $result ) ) {
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                                $result
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return $member_ids;
            }
        }


        function get_members_assigned_to_object( $object_type, $object_id ) {

            if ( has_filter( 'wpoffice_members_assigned_to_object_' . $object_type ) ) {
                return apply_filters( 'wpoffice_members_assigned_to_object_' . $object_type, array(), $object_id );
            } elseif ( 'member' == $object_type ) {
                $members = WO()->get_assign_data_by_assign( 'user', 'member', $object_id );
                $members = apply_filters( 'wpoffice_members_assigned_to_member_by_circle', $members, $object_id );
            } else {
                //get direct assigned users
                $assigned_members = WO()->get_assign_data_by_object( $object_type, $object_id, 'member' );

                //get users of profiles
                $profiles_members = $this->get_members_of_profiles_by_object( $object_type, $object_id );

                $members = array_merge( $assigned_members, $profiles_members );
            }

            return array_unique( $members );
        }


        /**
         * Get members assigned to profiles by object
         *
         * @param string $object_type
         * @param int $object_id
         * @return array ids of members
         */
        private function get_members_of_profiles_by_object( $object_type, $object_id ) {
            //get direct profiles of object
            $assigned_profiles = WO()->get_assign_data_by_object( $object_type, $object_id, 'profile' );

            /*wpo_hook_
                hook_name: wpoffice_profiles_assigned_to_object
                hook_title: Filter assigned profiles to object
                hook_description: Hook runs when you need assigned members.
                hook_type: filter
                hook_in: wp-office
                hook_location class-function.php
                hook_param: $assigned_profiles array of direct profiles
                hook_since: 1.0.0
            */
            $assigned_profiles = apply_filters( 'wpoffice_profiles_assigned_to_' . $object_type, $assigned_profiles, $object_id );

            //get users of profiles
            $profiles_members = WO()->get_assign_data_by_assign( 'user', 'profile', $assigned_profiles );

            /*wpo_hook_
                hook_name: wpoffice_members_of_profiles_by_object
                hook_title: Filter members of profiles by object
                hook_description: Hook runs before return members of profiles by object.
                hook_type: filter
                hook_in: wp-office
                hook_location class-function.php
                hook_param:  $profiles_members array of members assigned to profiles, $assigned_profiles array of profiles
                hook_since: 1.0.0
            */
            return apply_filters( 'wpoffice_members_of_profiles_by_object', $profiles_members, $assigned_profiles );

        }


        function get_rule_recipients() {
            $rule_actions = $this->get_rule_actions();
            $rule_recipients = array();

            $roles = WO()->get_settings( 'roles' );
            $members_caps = array();
            foreach( $roles as $role_key=>$role_val ) {
                $members_caps = array_merge( $members_caps, array( "create_{$role_key}", "update_{$role_key}_profile", "approve_{$role_key}" ) );
            }

            foreach ( $rule_actions as $key=>$title ) {
                if ( in_array( $key, array( 'self_registration', 'self_profile_update', 'reset_password' ) ) ) {
                    $rule_recipients[$key] = array(
                        'doer'  => __( 'Doer', WP_OFFICE_TEXT_DOMAIN ),
                        'all_selected_roles' => __( 'All Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
                        'assigned_selected_roles' => __( 'Assigned Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
//                        'caps_view_selected_roles' => __( 'Caps View Selected Roles Users', WP_OFFICE_TEXT_DOMAIN )
                    );
                } elseif ( in_array( $key, array( 'update_office_page' ) ) ) {
                    $rule_recipients[$key] = array(
                        'doer'                      => __( 'Doer', WP_OFFICE_TEXT_DOMAIN ),
                        'object_author'             => __( 'Office Page Author', WP_OFFICE_TEXT_DOMAIN ),
                        'all_selected_roles'        => __( 'All Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
                        'assigned_selected_roles'   => __( 'Assigned Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
//                        'caps_view_selected_roles'  => __( 'Caps View Selected Roles Users', WP_OFFICE_TEXT_DOMAIN )
                    );
                } elseif ( in_array( $key, $members_caps ) ) {
                    $rule_recipients[$key] = array(
                        'doer'                      => __( 'Doer', WP_OFFICE_TEXT_DOMAIN ),
                        'member'                    => __( 'Member', WP_OFFICE_TEXT_DOMAIN ),
                        'all_selected_roles'        => __( 'All Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
                        'assigned_selected_roles'   => __( 'Assigned Selected Roles Users', WP_OFFICE_TEXT_DOMAIN ),
//                        'caps_view_selected_roles'  => __( 'Caps View Selected Roles Users', WP_OFFICE_TEXT_DOMAIN )
                    );
                } else {
                    $rule_recipients[$key] = apply_filters( 'wpoffice_rule_recipients_' . $key, array() );
                }
            }

            return $rule_recipients;
        }

        


        /**
         * Get Email Notifications
         * Sending rules actions
         *
         * @return array
         */
        function get_rule_actions() {
            $rule_actions = array(
                'self_registration' => __( 'Registration', WP_OFFICE_TEXT_DOMAIN ),
                'self_profile_update' => __( 'Update Member Profile', WP_OFFICE_TEXT_DOMAIN ),
            );

            $roles = WO()->get_settings( 'roles' );
            foreach( $roles as $role_key=>$role_val ) {
                $rule_actions["create_{$role_key}"] = sprintf( __( 'Create %s', WP_OFFICE_TEXT_DOMAIN ), $role_val['title'] );
                $rule_actions["update_{$role_key}_profile"] = sprintf( __( 'Update %s', WP_OFFICE_TEXT_DOMAIN ), $role_val['title'] );
                $rule_actions["approve_{$role_key}"] = sprintf( __( 'Approve %s', WP_OFFICE_TEXT_DOMAIN ), $role_val['title'] );
            }

            $rule_actions = array_merge( $rule_actions, array(
                'reset_password' => __( 'Reset Password', WP_OFFICE_TEXT_DOMAIN ),
                'update_office_page' => __( 'Update Office Page', WP_OFFICE_TEXT_DOMAIN )
            ) );

            return apply_filters( 'wpoffice_email_notifications_sending_rule_actions', $rule_actions );
        }


        /**
         * Get emails array for notifications
         *
         * @param $action
         * @param $user_ids
         * @param $rule
         * @param $args
         * @return array
         */
        function get_notification_emails_list( $action, $user_ids, $rule, $args ) {

            $to_array = array();

            if ( $rule['recipient'] == 'doer' ) {

                $doer_id = !empty( $user_ids['doer'] ) ? $user_ids['doer'] : get_current_user_id();
                $user = get_userdata( $doer_id );
                if ( !empty( $user ) ) {
                    $to_array[] = $user->user_email;
                }

            } elseif ( $rule['recipient'] == 'member' ) {

                $member_id = !empty( $user_ids['member'] ) ? $user_ids['member'] : '';
                $user = get_userdata( $member_id );
                if ( !empty( $user ) ) {
                    $to_array[] = $user->user_email;
                }

            } elseif ( $rule['recipient'] == 'object_author' ) {

                $object_author_id = !empty( $user_ids['object_author'] ) ? $user_ids['object_author'] : '';
                $user = get_userdata( $object_author_id );
                if ( !empty( $user ) ) {
                    $to_array[] = $user->user_email;
                }

            } elseif ( $rule['recipient'] == 'all_selected_roles' ) {
                foreach ( $rule['recipient_select'] as $data=>$values ) {
                    $decoded_data = WO()->valid_ajax_decode( $data );
                    if ( $decoded_data['key'] == 'plugin_roles' ) {
                        $emails = get_users( array(
                            'role__in' => $values,
                            'fields' => array( 'user_email' ),
                        ) );
                        if ( !empty( $emails ) ) {
                            foreach ( $emails as $email ) {
                                $to_array[] = $email->user_email;
                            }
                        }
                    }
                }
            } elseif ( $rule['recipient'] == 'assigned_selected_roles' ) {
                if ( has_filter( 'wpoffice_send_notification_' . $action . '_assigned_selected_roles' ) ) {
                    $to_array = apply_filters( 'wpoffice_send_notification_' . $action . '_assigned_selected_roles', $to_array, $rule, $args );
                } else {
                    foreach ( $rule['recipient_select'] as $data=>$values ) {
                        $decoded_data = WO()->valid_ajax_decode( $data );
                        if ( $decoded_data['key'] == 'plugin_roles' ) {
                            $user_ids = WO()->get_members_assigned_to_object( $args['object_type'], $args['object_id'] );
                            if ( count( $user_ids ) ) {
                                $emails = get_users( array(
                                    'role__in'  => $values,
                                    'include'   => $user_ids,
                                    'fields'    => array( 'user_email' ),
                                ) );

                                if ( !empty( $emails ) ) {
                                    foreach ( $emails as $email ) {
                                        $to_array[] = $email->user_email;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $to_array = apply_filters( 'wpoffice_send_notification_' . $action, $to_array, $rule, $args );
            }

            return $to_array;
        }


        /**
         * Send notification with template
         *
         * @param string $action
         * @param array $user_ids doer|member|object_author ID
         * @param array $args
         * @param array $attachments
         * @return bool
         */
        function send_notification( $action, $user_ids = array(), $args = array(), $attachments = array() ) {
            global $wpdb;

            $doer_id = !empty( $user_ids['doer'] ) ? $user_ids['doer'] : get_current_user_id();

            $wpdb->query("SET SESSION SQL_BIG_SELECTS=1");
            $notifications = $wpdb->get_results( $wpdb->prepare(
                "SELECT o.*,
                    om.meta_value AS content,
                    om4.meta_value AS doer,
                    om5.meta_value AS recipient,
                    om6.meta_value AS doer_select,
                    om7.meta_value AS recipient_select
                FROM {$wpdb->prefix}wpo_objects o
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id AND om.meta_key='body'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om2 ON om2.meta_value = o.id AND om2.meta_key='notification_id'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om3 ON om3.object_id = om2.object_id AND om3.meta_key='action'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om4 ON om4.object_id = om2.object_id AND om4.meta_key='doer'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om5 ON om5.object_id = om2.object_id AND om5.meta_key='recipient'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om6 ON om6.object_id = om2.object_id AND om6.meta_key='doer_select'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om7 ON om7.object_id = om2.object_id AND om7.meta_key='recipient_select'
                LEFT JOIN {$wpdb->prefix}wpo_objectmeta om8 ON om8.object_id = o.id AND om8.meta_key='active'
                WHERE o.type = 'email_notification' AND
                      om8.meta_value = 'yes' AND
                      om3.meta_value = %s",
                $action
            ), ARRAY_A );

            if ( empty( $notifications ) ) {
                return false;
            }

            $items = array();
            foreach ( $notifications as $k=>$notification ) {
                $items[$notification['id']]['subject'] = $notification['title'];
                $items[$notification['id']]['content'] = $notification['content'];
                $items[$notification['id']]['rules'][] = array(
                    'doer' => $notification['doer'],
                    'recipient' => $notification['recipient'],
                    'doer_select' => maybe_unserialize( $notification['doer_select'] ),
                    'recipient_select' => maybe_unserialize( $notification['recipient_select'] )
                );
            }
            $notifications = array_values( $items );

            $active_notifications = array();
            foreach ( $notifications as $notification ) {
                foreach ( $notification['rules'] as $rule ) {

                    $is_active = false;
                    if ( $rule['doer'] == 'all' ) {
                        $is_active = true;
                    } else {
                        foreach ( $rule['doer_select'] as $data=>$values ) {
                            $decoded_data = WO()->valid_ajax_decode( $data );
                            if ( $decoded_data['key'] == 'plugin_roles' ) {
                                foreach ( $values as $role ) {
                                    if ( user_can( $doer_id, $role ) ) {
                                        $is_active = true;
                                    }
                                }
                            }
                        }
                    }

                    if ( $is_active ) {
                        $to_array = WO()->get_notification_emails_list( $action, $user_ids, $rule, $args );

                        $active_notifications[] = array(
                            'to'        => array_unique( $to_array ),
                            'subject'   => $notification['subject'],
                            'content'   => $notification['content'],
                        );
                    }
                }
            }

            foreach ( $active_notifications as $notification ) {
                $subject = str_replace( "_", '-', $this->replace_placeholders( $notification['subject'], $args ) );
                $message = $this->replace_placeholders( $notification['content'], $args );
                $headers = array('Content-Type: text/html; charset=UTF-8');
                foreach( $notification['to'] as $to ) {
                    if ( is_email( $to ) ) {
                        return wp_mail( $to, $subject, $message, $headers, $attachments );
                    }
                }
            }

            return false;
        }


        /**
         * Getting content ids for current member
         *
         * @param int $user_id User ID
         * @param string $object_type Object Type
         * @param string $cap_type Capability
         * @param string|array $ownership
         * @return array
         */
        function get_access_content_ids( $user_id, $object_type = 'all', $cap_type = 'view', $ownership = 'all' ) {
            global $wpdb;

            //fix for user assign popup getting data
            $object_type = ( $object_type == 'user' ) ? 'member' : $object_type;

            $capability = $cap_type . '_' . $object_type;
            $ownership = ( 'all' == $ownership ) ? $this->content_types : ( is_array( $ownership ) ? $ownership : array( $ownership ) );
            $types = $this->content_types;

            if ( 'all' == $object_type ) {
                $objects = array(
                    'member',
                    'office_page',
                    'office_page_category',
                    'office_hub',
                    'profile',
                );

                //our hook
                $objects = apply_filters( 'wpoffice_access_content_objects', $objects );

                foreach ( $objects as $object ) {
                    foreach ( $ownership as $owner ) {
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object ][ $cap_type ][ $owner ] ) ) {
                            $this->cache['content_ids'][ $user_id ][ $object ][ $cap_type ][ $owner ] =
                                array_unique( $this->get_access_content_ids( $user_id, $object, $cap_type, $owner ) );
                        }
                    }
                }
                $ids = array();
                foreach ( $objects as $object ) {
                    $ids[ $object ] = array();
                    foreach ( $ownership as $owner ) {
                        $ids[ $object ] = array_merge(
                            $ids[ $object ],
                            $this->cache['content_ids'][ $user_id ][ $object ][ $cap_type ][ $owner ]
                        );
                    }
                    $ids[ $object ] = array_unique( $ids[ $object ] );
                }
                return $ids;
            } else {

                $core_object_type = false;
                if ( has_filter( 'wpoffice_access_content_' . $object_type . '_ids' ) ) {
                    return apply_filters( 'wpoffice_access_content_' . $object_type . '_ids', array(), $user_id, $cap_type, $ownership );
                } elseif ( 'office_page' == $object_type ) {
                    $core_object_type = true;

                    if ( !WO()->member_can( $user_id, $capability ) ) {
                        foreach( $types as $type ) {
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ][$type] = array();
                        }
                    } elseif ( WO()->member_can( $user_id, $capability ) == 'on' ) {
                        if( empty( $this->cache['content_ids']['all'][$object_type][ $cap_type ] ) ) {
                            $this->cache['content_ids']['all'][$object_type][ $cap_type ] =
                                $wpdb->get_col(
                                    "SELECT p.ID
                                    FROM {$wpdb->posts} p
                                    WHERE p.post_type = 'office_page' AND
                                          p.post_status IN('publish','trash','draft')"
                                );
                        }
                        return $this->cache['content_ids']['all'][$object_type][ $cap_type ];
                    } else {

                        /*
                         * Block for getting member own pages
                         * 1) Member is page's author
                         * 2) Page assigned to member page->member
                         * 3) Page assigned to member page->profile->member
                         * 4) Page assigned to member page->page category->profile->member
                         *
                         * 5) Can add filter to feature page->profile->member circle->member
                         *                              page->page category->profile->member circle->member
                         *
                         */

                        //1) Member is page's author
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] ) &&
                            in_array( 'own', $ownership ) ) {

                            $result = $wpdb->get_col($wpdb->prepare(
                                "SELECT p.ID
                            FROM {$wpdb->posts} p
                            WHERE p.post_type = 'office_page' AND
                                  p.post_status IN('publish','trash','draft') AND
                                  p.post_author = %d",
                                $user_id
                            ));

                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['own'] = $result;
                        }

                        if ( WO()->member_can( $user_id, $capability ) == 'assigned' ) {
                            //2) Page assigned to member page->member
                            if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                                in_array( 'personal_assigned', $ownership ) ) {
                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                    WO()->get_assign_data_by_assign( 'office_page', 'member', $user_id );

                                //get member's profiles
                                $member_profiles = WO()->get_assign_data_by_object('user', $user_id, 'profile');
                                //5) Can add filter to feature page->profile->member circle->member
                                //                             page->page category->profile->member circle->member
                                $member_profiles = apply_filters('wpoffice_member_assigned_profiles', $member_profiles, $user_id);

                                //get files from profiles
                                if ( !empty( $member_profiles ) ) {
                                    //3) Page assigned to member page->profile->member
                                    $result = WO()->get_assign_data_by_assign('office_page', 'profile', $member_profiles);
                                    if (!empty($result)) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                            $result
                                        );
                                    }

                                    //get categories in profile
                                    $cat_ids = WO()->get_assign_data_by_assign('office_page_category', 'profile', $member_profiles);
                                    //select only page categories
                                    if( !empty( $cat_ids ) ) {
                                        $categories = $wpdb->get_col(
                                            "SELECT DISTINCT o.id
                                            FROM {$wpdb->prefix}wpo_objects o
                                            WHERE o.type = 'office_page_category' AND
                                                  o.id IN('" . implode("','", $cat_ids) . "')"
                                        );
                                    }

                                    //4) Page assigned to member page->page category->profile->member
                                    if ( !empty( $categories ) ) {
                                        $result = $wpdb->get_col(
                                            "SELECT DISTINCT p.ID
                                            FROM {$wpdb->posts} p
                                            LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'category_id' )
                                            WHERE pm.meta_value IN('" . implode("','", $categories) . "')"
                                        );
                                        if (!empty($result)) {
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                                array_merge(
                                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                                    $result
                                                );
                                        }
                                    }
                                }
                            }
                        }

                        /*
                         * Block for getting pages, which assigned to child members assigned to current member
                         * 1) Member is page's author member->member
                         * 2) Page assigned to member page->member
                         * 3) Page assigned to member page->profile->member
                         * 4) Page assigned to member page->page category->profile->member
                         *
                         * 5) Can add filter to feature page->profile->member circle->member
                         *                              page->page category->profile->member circle->member
                         *
                         */
                        //get child roles for current member
                        $child_roles_list = array();
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) ||
                            empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            count( array_intersect( array( 'children_own', 'children_assigned' ), $ownership ) ) ) {

                            $child_roles_list = $this->get_role_all_child( $this->get_office_role( $user_id ) );
                        }

                        $managing_members_array = array();
                        //current member can view assigned member's own files
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) &&
                            in_array( 'children_own', $ownership ) ) {
                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array();
                            foreach( $child_roles_list as $role ) {
                                if( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $managing_members_array[ $role ] =
                                    $this->get_available_members_by_role( $role, $user_id );

                                if ( !empty( $managing_members ) ) {
                                    //1) Member is page's author
                                    $result = $wpdb->get_col(
                                        "SELECT p.ID
                                    FROM {$wpdb->posts} p
                                    WHERE p.post_type = 'office_page' AND
                                          p.post_status IN('publish','trash','draft') AND
                                          p.post_author IN('" . implode( "','", $managing_members ) . "')"
                                    );
                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array_merge(
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'],
                                        $result
                                    );
                                }
                            }
                        }

                        //current member can view assigned member's assigned pages
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            in_array( 'children_assigned', $ownership ) ) {
                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array();
                            foreach ( $child_roles_list as $role ) {
                                if ( WO()->member_can_manage( $user_id, $capability, $role ) != 'assigned' )
                                    continue;

                                if( isset( $managing_members_array[ $role ] ) ) {
                                    $managing_members = $managing_members_array[ $role ];
                                } else {
                                    $managing_members = $this->get_available_members_by_role( $role, $user_id );
                                }

                                if ( !empty( $managing_members ) ) {
                                    //2) Page assigned to member page->member
                                    $result = WO()->get_assign_data_by_assign( 'office_page', 'member', $managing_members );
                                    if (!empty($result)) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                            $result
                                        );
                                    }

                                    //get member's profiles
                                    $member_profiles = WO()->get_assign_data_by_object( 'user', $managing_members, 'profile' );
                                    //5) Can add filter to feature page->profile->member circle->member
                                    //                             page->page category->profile->member circle->member
                                    $member_profiles = apply_filters( 'wpoffice_member_assigned_profiles', $member_profiles, $managing_members );

                                    //get files from profiles
                                    if ( !empty( $member_profiles ) ) {
                                        //3) Page assigned to member page->profile->member
                                        $result = WO()->get_assign_data_by_assign( 'office_page', 'profile', $member_profiles );
                                        if ( !empty( $result ) ) {
                                            $this->cache['content_ids'][$user_id][$object_type]['children_assigned'] = array_merge(
                                                $this->cache['content_ids'][$user_id][$object_type]['children_assigned'],
                                                $result
                                            );
                                        }

                                        //get categories in profile
                                        $categories = WO()->get_assign_data_by_assign( 'office_page_category', 'profile', $member_profiles );
                                        //select only file categories
                                        $categories = $wpdb->get_col(
                                            "SELECT DISTINCT o.id
                                        FROM {$wpdb->prefix}wpo_objects o
                                        WHERE o.type = 'office_page_category' AND
                                              o.id IN('" . implode("','", $categories) . "')"
                                        );

                                        //4) Page assigned to member page->page category->profile->member
                                        if ( !empty( $categories ) ) {
                                            $result = $wpdb->get_col(
                                                "SELECT DISTINCT p.ID
                                            FROM {$wpdb->posts} p
                                            LEFT JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = 'category_id' )
                                            WHERE p.post_type = 'office_page' AND
                                                  pm.meta_value IN('" . implode("','", $categories) . "')"
                                            );
                                            if ( !empty( $result ) ) {
                                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                                    $result
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif ( 'office_hub' == $object_type ) {
                    $core_object_type = true;

                    if ( !WO()->member_can( $user_id, $capability ) ) {
                        foreach( $types as $type ) {
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ][$type] = array();
                        }
                    } elseif ( WO()->member_can( $user_id, $capability ) == 'on' ) {
                        if( empty( $this->cache['content_ids']['all'][$object_type][ $cap_type ] ) ) {
                            $this->cache['content_ids']['all'][$object_type][ $cap_type ] =
                                $wpdb->get_col(
                                    "SELECT p.ID
                                    FROM {$wpdb->posts} p
                                    WHERE p.post_type = 'office_hub' AND
                                          p.post_status IN('publish','trash','draft')"
                                );
                        }
                        return $this->cache['content_ids']['all'][$object_type][ $cap_type ];
                    } else {
                        /*
                         * Block for getting member own pages
                         * 1) Member is page's author
                         * 2) Page assigned to member page->member
                         * 3) Page assigned to member page->profile->member
                         * 4) Page assigned to member page->page category->profile->member
                         *
                         * 5) Can add filter to feature page->profile->member circle->member
                         *                              page->page category->profile->member circle->member
                         *
                         */

                        //1) Member is page's author
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] ) &&
                            in_array( 'own', $ownership ) ) {
                            $result = $wpdb->get_col( $wpdb->prepare(
                                "SELECT p.ID
                                FROM {$wpdb->posts} p
                                WHERE p.post_type = 'office_hub' AND
                                      p.post_status IN('publish','trash','draft') AND
                                      p.post_author = %d",
                                $user_id
                            ) );
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] = $result;
                        }

                        if ( WO()->member_can( $user_id, $capability ) == 'assigned' ) {
                            //2) Page assigned to member page->member
                            if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                                in_array( 'personal_assigned', $ownership ) ) {
                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                    WO()->get_assign_data_by_assign( 'office_hub', 'member', $user_id );

                                //get member's profiles
                                $member_profiles = WO()->get_assign_data_by_object('user', $user_id, 'profile');
                                //5) Can add filter to feature page->profile->member circle->member
                                //                             page->page category->profile->member circle->member
                                $member_profiles = apply_filters('wpoffice_member_assigned_profiles', $member_profiles, $user_id);

                                //get files from profiles
                                if ( !empty( $member_profiles ) ) {
                                    //3) Page assigned to member page->profile->member
                                    $result = WO()->get_assign_data_by_assign('office_hub', 'profile', $member_profiles);
                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                            $result
                                        );
                                    }
                                }
                            }
                        }


                        /*
                         * Block for getting pages, which assigned to child members assigned to current member
                         * 1) Member is page's author member->member
                         * 2) Page assigned to member page->member
                         * 3) Page assigned to member page->profile->member
                         * 4) Page assigned to member page->page category->profile->member
                         *
                         * 5) Can add filter to feature page->profile->member circle->member
                         *                              page->page category->profile->member circle->member
                         *
                         */
                        //get child roles for current member
                        $child_roles_list = array();
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) ||
                            empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            count( array_intersect( array( 'children_own', 'children_assigned' ), $ownership ) ) ) {

                            $child_roles_list = $this->get_role_all_child( $this->get_office_role( $user_id ) );
                        }

                        $managing_members_array = array();
                        if ( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) &&
                            in_array( 'children_own', $ownership ) ) {
                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array();
                            foreach ( $child_roles_list as $role ) {
                                //current member can view assigned member's own files
                                if ( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $managing_members_array[$role] =
                                    $this->get_available_members_by_role( $role, $user_id );

                                if ( !empty( $managing_members ) ) {
                                    //1) Member is page's author
                                    $result = $wpdb->get_col(
                                        "SELECT p.ID
                                        FROM {$wpdb->posts} p
                                        WHERE p.post_type = 'office_hub' AND
                                              p.post_status IN('publish','trash','draft') AND
                                              p.post_author IN('" . implode("','", $managing_members) . "')"
                                    );
                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'],
                                            $result
                                        );
                                    }
                                }
                            }
                        }

                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            in_array( 'children_assigned', $ownership ) ) {

                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array();

                            foreach ( $child_roles_list as $role ) {
                                //current member can view assigned member's assigned pages
                                if ( !WO()->member_can_manage( $user_id, $capability, $role, 'assigned' ) )
                                    continue;

                                if( isset( $managing_members_array[ $role ] ) ) {
                                    $managing_members = $managing_members_array[ $role ];
                                } else {
                                    $managing_members = $this->get_available_members_by_role( $role, $user_id );
                                }

                                if ( !empty( $managing_members ) ) {
                                    //2) Page assigned to member page->member
                                    $result = WO()->get_assign_data_by_assign( 'office_hub', 'member', $managing_members );
                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                            $result
                                        );
                                    }

                                    //get member's profiles
                                    $member_profiles = WO()->get_assign_data_by_object( 'user', $managing_members, 'profile' );
                                    //5) Can add filter to feature page->profile->member circle->member
                                    //                             page->page category->profile->member circle->member
                                    $member_profiles = apply_filters( 'wpoffice_member_assigned_profiles', $member_profiles, $managing_members );

                                    //get files from profiles
                                    if ( !empty( $member_profiles ) ) {
                                        //3) Page assigned to member page->profile->member
                                        $result = WO()->get_assign_data_by_assign( 'office_hub', 'profile', $member_profiles );
                                        if ( !empty( $result ) ) {
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] = array_merge(
                                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'],
                                                $result
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }

                } elseif ( 'member' == $object_type ) {
                    $core_object_type = true;

                    if( empty( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                        in_array( 'personal_assigned', $ownership ) ) {

                        $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] = array();

                        $roles_list = $this->get_roles_list_member_main_cap( $user_id );
                        $roles_list = array_keys( $roles_list );

                        foreach ( $roles_list as $role ) {
                            if ( WO()->member_can_manage( $user_id, $capability, $role ) ) {
                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] = array_merge(
                                    $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'],
                                    $this->get_available_members_by_role( $role, $user_id )
                                );
                            }
                        }
                    }
                } elseif ( 'profile' == $object_type ) {
                    $core_object_type = true;

                    if ( !WO()->member_can( $user_id, $capability ) ) {
                        foreach( $types as $type ) {
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ][$type] = array();
                        }
                    } elseif ( WO()->member_can( $user_id, $capability, 'on' ) ) {
                        if( empty( $this->cache['content_ids']['all'][$object_type][ $cap_type ] ) ) {
                            $this->cache['content_ids']['all'][$object_type][ $cap_type ] =
                                $wpdb->get_col("SELECT DISTINCT o.id
                                    FROM {$wpdb->prefix}wpo_objects o
                                    WHERE o.type = 'profile'"
                                );
                        }
                        return $this->cache['content_ids']['all'][$object_type][ $cap_type ];
                    } else {

                        //1) Member is Profile's author
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] ) &&
                            in_array( 'own', $ownership ) ) {
                            $result = $wpdb->get_col( $wpdb->prepare(
                                "SELECT o.id
                                FROM {$wpdb->prefix}wpo_objects o
                                WHERE o.type = 'profile' AND
                                      o.author = %d",
                                $user_id
                            ) );
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] = $result;
                        }

                        if ( WO()->member_can( $user_id, $capability ) == 'assigned' ) {
                            //2) Member assigned to profile user->profile
                            if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                                in_array( 'personal_assigned', $ownership ) ) {

                                $user_profiles = WO()->get_assign_data_by_object( 'user', $user_id, 'profile' );
                                $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['personal_assigned'] =
                                    apply_filters( 'wpoffice_profiles_members', $user_profiles, $user_id );
                            }
                        }

                        $child_roles_list = array();
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) ||
                            empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            count( array_intersect( array( 'children_own', 'children_assigned' ), $ownership ) ) ) {

                            $child_roles_list = $this->get_role_all_child( $this->get_office_role( $user_id ) );
                        }

                        $managing_members_array = array();
                        if ( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) &&
                            in_array( 'children_own', $ownership ) ) {
                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array();
                            foreach ( $child_roles_list as $role ) {
                                //current member can view assigned member's own files
                                if ( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $managing_members_array[$role] =
                                    $this->get_available_members_by_role( $role, $user_id );

                                if ( !empty( $managing_members ) ) {
                                    //1) Member is Profile's author
                                    $result = $wpdb->get_col(
                                        "SELECT o.id
                                        FROM {$wpdb->prefix}wpo_objects o
                                        WHERE o.type = 'profile' AND
                                              o.author IN('" . implode( "','", $managing_members ) . "')"
                                    );

                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'],
                                            $result
                                        );
                                    }
                                }
                            }
                        }

                        //assigns members assigned to current member
                        if( empty( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['children_assigned'] ) &&
                            in_array( 'children_assigned', $ownership ) ) {

                            foreach ( $child_roles_list as $role ) {
                                if ( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $this->get_available_members_by_role( $role, $user_id );
                                $result = WO()->get_assign_data_by_object( 'user', $managing_members, 'profile' );
                                $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['children_assigned'] =
                                    apply_filters( 'wpoffice_profiles_members', $result, $managing_members );
                            }
                        }
                    }
                } elseif ( 'office_page_category' == $object_type ) {
                    $core_object_type = true;

                    if ( !WO()->member_can( $user_id, $capability ) ) {
                        foreach( $types as $type ) {
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ][$type] = array();
                        }
                    } elseif ( WO()->member_can( $user_id, $capability, 'on' ) ) {
                        if( empty( $this->cache['content_ids']['all'][$object_type][ $cap_type ] ) ) {
                            $this->cache['content_ids']['all'][$object_type][ $cap_type ] =
                                $wpdb->get_col("SELECT DISTINCT o.id
                                    FROM {$wpdb->prefix}wpo_objects o
                                    WHERE o.type = 'office_page_category'"
                                );
                        }
                        return $this->cache['content_ids']['all'][$object_type][ $cap_type ];
                    } else {

                        //1) Member is Page Category's author
                        if( !isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] ) &&
                            in_array( 'own', $ownership ) ) {
                            $result = $wpdb->get_col( $wpdb->prepare(
                                "SELECT o.id
                                FROM {$wpdb->prefix}wpo_objects o
                                WHERE o.type = 'office_page_category' AND
                                      o.author = %d",
                                $user_id
                            ) );
                            $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['own'] = $result;
                        }

                        if ( $this->member_can( $user_id, $capability ) == 'assigned' ) {
                            if( empty( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] ) &&
                                in_array( 'personal_assigned', $ownership ) ) {
                                $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] = array();
                                //get member's profiles
                                $member_profiles = WO()->get_assign_data_by_object( 'user', $user_id, 'profile' );
                                //5) Can add filter to feature page->profile->member circle->member
                                //                             page->page category->profile->member circle->member
                                $member_profiles = apply_filters( 'wpoffice_member_assigned_profiles', $member_profiles, $user_id );

                                //get files from profiles
                                if ( !empty( $member_profiles ) ) {
                                    //get categories in profile
                                    $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['personal_assigned'] =
                                        WO()->get_assign_data_by_assign( 'office_page_category', 'profile', $member_profiles );
                                }
                            }
                        }

                        $child_roles_list = array();
                        if( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) ||
                            empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_assigned'] ) &&
                            count( array_intersect( array( 'children_own', 'children_assigned' ), $ownership ) ) ) {

                            $child_roles_list = $this->get_role_all_child( $this->get_office_role( $user_id ) );
                        }

                        $managing_members_array = array();
                        if ( empty( $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] ) &&
                            in_array( 'children_own', $ownership ) ) {

                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array();
                            foreach ( $child_roles_list as $role ) {
                                //current member can view assigned member's own files
                                if ( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $managing_members_array[$role] =
                                    $this->get_available_members_by_role( $role, $user_id );

                                if ( !empty( $managing_members ) ) {
                                    //1) Member is Page Category's author
                                    $result = $wpdb->get_col(
                                        "SELECT o.id
                                        FROM {$wpdb->prefix}wpo_objects o
                                        WHERE o.type = 'office_page_category' AND
                                              o.author IN('" . implode( "','", $managing_members ) . "')"
                                    );

                                    if ( !empty( $result ) ) {
                                        $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'] = array_merge(
                                            $this->cache['content_ids'][$user_id][$object_type][ $cap_type ]['children_own'],
                                            $result
                                        );
                                    }
                                }
                            }
                        }

                        //assigns members assigned to current member
                        if( empty( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['children_assigned'] ) &&
                            in_array( 'children_assigned', $ownership ) ) {

                            foreach ( $child_roles_list as $role ) {
                                if ( !WO()->member_can_manage( $user_id, $capability, $role ) )
                                    continue;

                                $managing_members = $this->get_available_members_by_role( $role, $user_id );
                                //get member's profiles
                                $member_profiles = WO()->get_assign_data_by_object( 'user', $managing_members, 'profile' );
                                //5) Can add filter to feature page->profile->member circle->member
                                //                             page->page category->profile->member circle->member
                                $member_profiles = apply_filters( 'wpoffice_member_assigned_profiles', $member_profiles, $managing_members );

                                //get files from profiles
                                if ( !empty( $member_profiles ) ) {
                                    //get categories in profile
                                    $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ]['children_assigned'] =
                                        WO()->get_assign_data_by_assign( 'office_page_category', 'profile', $member_profiles );
                                }
                            }
                        }
                    }
                }

                $ids = array();
                foreach( $ownership as $owner ) {
                    if( isset( $this->cache['content_ids'][ $user_id ][ $object_type ][ $cap_type ][ $owner ] ) ) {
                        $ids = array_merge($ids, $this->cache['content_ids'][$user_id][$object_type][ $cap_type ][$owner]);
                    }
                }

                $ids = array_unique( $ids );
            }

            return $ids;
        }


        /**
         * Function for checking which roles member can manage by main cap
         *
         *
         * @param $member_id
         * @return array
         */
        function get_roles_list_member_main_cap( $member_id ) {
            if( empty( $member_id ) ) return array();

            $roles_list = WO()->get_settings( 'roles' );

            if( user_can( $member_id, 'administrator' ) ) {
                return $roles_list;
            }

            $child_roles_list = array();

            $user = get_userdata( $member_id );
            //capability in only child tabs
            foreach( $user->roles as $user_role ) {
                $child_roles_list = array_merge( $child_roles_list, WO()->get_role_all_child( $user_role ) );
            }

            $temp = array();
            foreach( $child_roles_list as $role ) {
                if( WO()->member_main_manage_cap( $member_id, $role ) ) {
                    $temp[$role] = $roles_list[$role];
                }
            }
            $roles_list = $temp;
            return $roles_list;
        }


        function get_available_members_by_role( $role, $user_id, $include_trash = false ) {
            if ( !isset( WO()->cache['available_members'][ $user_id ][ $role ][ $include_trash ] ) ) {
                global $wpdb;

                if ( !$this->member_main_manage_cap( $user_id, $role ) ) return array();

                $exclude = '';
                if ( !$include_trash ) {
                    $excluded_members = WO()->members()->get_excluded_members( false, $role, false );
                    if ( count( $excluded_members ) ) {
                        $exclude = " AND u.ID NOT IN('" . implode( "','", $excluded_members ) . "')";
                    }
                }

                $include = '';
                if ( $this->member_main_manage_cap( $user_id, $role ) == 'assigned' ) {
                    $assigned_users = WO()->get_assign_data_by_object( 'user', $user_id, 'member' );
                    $assigned_users = apply_filters( 'wpoffice_assigned_users', $assigned_users, $role, $user_id );
                    $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
                }

                $members = $wpdb->get_col(
                    "SELECT DISTINCT u.ID
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                    WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                          um.meta_value LIKE '%:\"{$role}\";%'
                          $include
                          $exclude"
                );
                WO()->cache['available_members'][ $user_id ][ $role ][ $include_trash ] = $members;
            }

            return WO()->cache['available_members'][ $user_id ][ $role ][ $include_trash ];
        }


        /**
         * Get role first level child roles
         *
         * @param $role string
         * @return array
         */
        function get_role_first_child( $role ) {
            $roles_list = WO()->get_settings( 'roles' );

            $child_roles = array();
            foreach( $roles_list as $key=>$value ) {
                if( !empty( $value['parent'] ) && $role == $value['parent'] ) {
                    $child_roles[] = $key;
                }
            }

            return $child_roles;
        }


        function get_all_parent_members( $member_id, $with_manage_cap = false ) {
            $user = get_userdata( $member_id );
            $parent_roles_array = array( 'administrator' );
            $members = array();
            if( is_object( $user ) ) {
                foreach( $user->roles as $user_role ) {
                    $parent_roles_array = array_merge( $parent_roles_array, $this->get_parent_roles( $user_role ) );
                }
                if( $with_manage_cap !== false ) {
                    $temp = array();
                    foreach( $parent_roles_array as $parent_role ) {
                        foreach( $user->roles as $user_role ) {
                            if( $this->role_can_manage( $parent_role, $with_manage_cap, $user_role ) ) {
                                $temp[] = $parent_role;
                            }
                        }
                    }
                    $parent_roles_array = $temp;
                }

                $members = get_users(array(
                    'role__in' => array_unique( $parent_roles_array ),
                    'field'    => 'ID'
                ));
            }
            return $members;
        }


        /**
         * Get roles which parent role can manage using capabilities
         *
         * @param $role string
         * @param $capability string|array
         * @return array
         */
        function get_parent_manage_roles( $role, $capability ) {
            $roles_list = WO()->get_settings( 'roles' );
            $parent = '';
            $array = array();
            if( !empty( $roles_list[ $role ]['parent'] ) ) {
                $parent = $roles_list[ $role ]['parent'];
            }

            if( is_string( $capability ) ) {
                $capability = array( $capability );
            }

            $i = 0;
            while( $parent != '' ) {
                foreach( $capability as $cap ) {
                    if (WO()->role_can_manage($parent, $cap, $role)) {
                        $array[] = $parent;
                        break;
                    }
                }
                $parent = isset( $roles_list[ $parent ]['parent'] ) ? $roles_list[ $parent ]['parent'] : '';
                if( $i > 1000 ) break;
                $i++;
            }
            return $array;
        }


        /**
         * Get role all parent roles
         *
         * @param $role string
         * @return array
         */
        function get_parent_roles( $role ) {
            $roles_list = WO()->get_settings( 'roles' );
            $parent = '';
            $array = array();
            if( !empty( $roles_list[ $role ]['parent'] ) ) {
                $parent = $roles_list[ $role ]['parent'];
            }
            $i = 0;
            while( $parent != '' ) {
                $array[] = $parent;
                $parent = isset( $roles_list[ $parent ]['parent'] ) ? $roles_list[ $parent ]['parent'] : '';
                if( $i > 1000 ) break;
                $i++;
            }
            return $array;
        }


        /**
         * Get all child roles for member by $user_id
         *
         * @param $user_id int
         * @return array
         */
        function get_child_roles_for_member( $user_id ) {
            $child_roles = isset( WO()->cache['child_roles'][ $user_id ] )
                    ? WO()->cache['child_roles'][ $user_id ]
                    : WO()->get_role_all_child( WO()->get_office_role( $user_id ) ) ;

            return $child_roles;
        }


        /**
         * Get role all child roles
         *
         * @param $role string
         * @return array
         */
        function get_role_all_child( $role ) {
            $all_child_roles = array();
            $child_roles = $this->get_role_first_child( $role );
            if( !empty( $child_roles ) ) {
                $all_child_roles = array_merge( $all_child_roles, $child_roles );

                foreach( $child_roles as $child_role ) {
                    $child_all = $this->get_role_all_child( $child_role );

                    if( !empty( $child_all ) ) {
                        $all_child_roles = array_merge( $all_child_roles, $child_all );
                    }
                }
            }

            return array_unique( $all_child_roles );
        }


        /**
         * Function for checking which roles member can manage by capability
         *
         *
         * @param $member_id
         * @param $capability string|array
         * @return array
         */
        function get_roles_member_can_manage( $member_id, $capability ) {

            if( empty( $member_id ) || empty( $capability ) ) return array();

            $roles_list = $this->get_settings( 'roles' );
            if( user_can( $member_id, 'administrator' ) ) {
                return array_keys( $roles_list );
            }

            if( is_string( $capability ) ) {
                $capability = array( $capability );
            }

            if( !( $user_role = $this->get_office_role( $member_id ) ) ) return array();

            $manage_roles_list = array();
            //role manage capabilities
            foreach( $capability as $cap ) {
                foreach( $roles_list as $role=>$value ) {
                    if( WO()->member_can_manage( $member_id, $cap, $role ) ) {
                        $manage_roles_list[] = $role;
                    }
                }
            }

            return array_unique( $manage_roles_list );

        }


        /**
         * Function for checking which roles current member can manage by capability
         *
         * @param $capability string|array
         * @return array
         */
        function get_roles_current_member_can_manage( $capability ) {
            return $this->get_roles_member_can_manage( get_current_user_id(), $capability );
        }


        /**
         * Function for checking if current member can manage another member by ID
         *
         *
         * @param $capability string|array
         * @param $member_id
         * @return bool
         */
        function current_member_can_manage_member( $capability, $member_id ) {
            if ( !is_user_logged_in() ) return false;
            if ( current_user_can('administrator') ) return true;

            $children_members = WO()->get_access_content_ids( get_current_user_id(), 'member' );

            if ( !in_array( $member_id, $children_members ) ) return false;

            if ( !( $role = $this->get_office_role( $member_id ) ) ) return false;

            if ( $this->member_can_manage( get_current_user_id(), $capability, $role ) ) {
                return true;
            }

            return false;
        }


        /**
         * Function for checking if current member can manage another member
         *
         *
         * @param $capability string
         * @param $role string
         * @param $cap_value string
         * @return bool|string
         */
        function current_member_can_manage( $capability, $role, $cap_value = '' ) {
            return $this->member_can_manage( get_current_user_id(), $capability, $role, $cap_value );
        }

        /**
         * Function checking if member with these roles can manage another role
         *
         *
         * @param $manager_roles string
         * @param $capability string
         * @param $role
         * @return bool
         */
        function role_can_manage( $manager_roles, $capability, $role ) {
            if( empty( $manager_roles ) || empty( $capability ) || empty( $role ) ) return false;

            if( !isset( $this->cache['manage_caps'][ $manager_roles ][ $capability ][ $role ] ) ) {
                $manage_caps = $this->get_settings( 'manage_capabilities_' . $manager_roles );
                if(isset($manage_caps[$role]['can_manage']) && $manage_caps[$role]['can_manage'] != 'off') {
                    if (isset($manage_caps[$role][$capability]) && $manage_caps[$role][$capability] != 'off') {
                        $cap = $manage_caps[$role][$capability];
                    }
                } else if ( isset($manage_caps['only_child']['can_manage']) && $manage_caps['only_child']['can_manage'] != 'off' &&
                    in_array($role, $this->get_role_first_child($manager_roles))) {
                    if (isset($manage_caps['only_child'][$capability]) && $manage_caps['only_child'][$capability] != 'off') {
                        $cap = $manage_caps['only_child'][$capability];
                    }
                } else if (isset($manage_caps['all']['can_manage']) && $manage_caps['all']['can_manage'] != 'off') {
                    //capability in all tabs
                    if (isset($manage_caps['all'][$capability]) && $manage_caps['all'][$capability] != 'off') {
                        $cap = $manage_caps['all'][$capability];
                    }
                }
                $this->cache['manage_caps'][ $manager_roles ][ $capability ][ $role ] = isset( $cap ) ? $cap : false;
            }

            return $this->cache['manage_caps'][ $manager_roles ][ $capability ][ $role ];
        }

        /**
         * Function for checking if member can manage another member
         *
         *
         * @param $member_id integer
         * @param $capability string
         * @param $role string
         * @param $cap_value string
         * @return bool|string
         */
        function member_can_manage( $member_id, $capability, $role, $cap_value = '' ) {
            if( empty( $member_id ) || empty( $capability ) || empty( $role ) ) return false;
            if( user_can($member_id, 'administrator') ) return empty( $cap_value ) ? 'on' : true;
            if( !empty( $cap_value ) && !in_array( $cap_value, $this->cap_types ) ) {
                return false;
            }

            if( !isset( $this->cache['manage_caps'][ $member_id ][ $capability ][ $role ] ) ) {
                $this->cache['manage_caps'][ $member_id ][ $capability ][ $role ] = false;
                if( !( $user_role = $this->get_office_role( $member_id ) ) ) return false;
                $this->cache['manage_caps'][ $member_id ][ $capability ][ $role ] = WO()->role_can_manage( $user_role, $capability, $role );
            }

            $current_cap = $this->cache['manage_caps'][ $member_id ][ $capability ][ $role ];
            if( empty( $cap_value ) ) {
                return $current_cap;
            } else {
                return ( $current_cap &&
                    array_search( $cap_value, $this->cap_types ) <= array_search( $current_cap, $this->cap_types ) );
            }
        }

        /**
         * Function checks current member has manage capability to children role
         *
         *
         * @param string $role
         * @param string $cap_value
         * @return bool|string
         */
        function current_member_main_manage_cap( $role, $cap_value = '' ) {
            return $this->member_main_manage_cap( get_current_user_id(), $role, $cap_value );
        }

        /**
         * Function checks role has manage capability to children role
         *
         *
         * @param string $manage_role
         * @param string $role
         * @param string $cap_value
         * @return bool|string
         */
        function role_main_manage_cap( $manage_role, $role, $cap_value = '' ) {
            if( empty( $manage_role ) || empty( $role ) ) return false;
            if( $manage_role == 'administrator' ) return empty( $cap_value ) ? 'on' : true;

            $only_child = WO()->get_role_first_child( $manage_role );
            $manage_caps = $this->get_settings('manage_capabilities_' . $manage_role);
            if ( isset($manage_caps[$role]['can_manage']) && $manage_caps[$role]['can_manage'] != 'off') {
                return empty( $cap_value ) ? $manage_caps[$role]['can_manage'] :
                    array_search( $cap_value, $this->cap_types ) <= array_search( $manage_caps[$role]['can_manage'], $this->cap_types );
            } elseif ( isset( $manage_caps['only_child']['can_manage'] ) && $manage_caps['only_child']['can_manage'] != 'off' && in_array( $role, $only_child ) ) {
                return empty( $cap_value ) ? $manage_caps['only_child']['can_manage'] :
                    array_search( $cap_value, $this->cap_types ) <= array_search( $manage_caps['only_child']['can_manage'], $this->cap_types );
            } elseif ( isset( $manage_caps['all']['can_manage']) && $manage_caps['all']['can_manage'] != 'off' ) {
                return empty( $cap_value ) ? $manage_caps['all']['can_manage'] :
                    array_search( $cap_value, $this->cap_types ) <= array_search( $manage_caps['all']['can_manage'], $this->cap_types );
            }

            return false;
        }


        /**
         * Function checks member has manage capability to children role
         *
         *
         * @param integer $member_id
         * @param string $role
         * @param string $cap_value
         * @return bool|string
         */
        function member_main_manage_cap( $member_id, $role, $cap_value = '' ) {
            if ( empty( $member_id ) || empty( $role ) ) return false;
            if ( user_can( $member_id, 'administrator' ) ) return empty( $cap_value ) ? 'on' : true;

            if ( !isset( $this->cache['manage_caps'][ $member_id ][ 'can_manage' ][ $role ] ) ) {
                $this->cache['manage_caps'][ $member_id ][ 'can_manage' ][ $role ] = false;
                if( !( $user_role = $this->get_office_role( $member_id ) ) ) return false;

                $only_child = WO()->get_role_first_child( $user_role );
                $manage_caps = $this->get_settings( 'manage_capabilities_' . $user_role );

                if ( isset( $manage_caps[$role]['can_manage']) && $manage_caps[$role]['can_manage'] != 'off' ) {
                    $cap = $manage_caps[$role]['can_manage'];
                } else if( isset( $manage_caps['only_child']['can_manage'] ) && $manage_caps['only_child']['can_manage'] != 'off' &&
                    in_array( $role, $only_child ) ) {
                    $cap = $manage_caps['only_child']['can_manage'];
                } else if( isset( $manage_caps['all']['can_manage']) && $manage_caps['all']['can_manage'] != 'off' ) {
                    $cap = $manage_caps['all']['can_manage'];
                }

                $this->cache['manage_caps'][ $member_id ][ 'can_manage' ][ $role ] = isset( $cap ) ? $cap : false;
            }

            $current_cap = $this->cache['manage_caps'][ $member_id ][ 'can_manage' ][ $role ];

            if( empty( $cap_value ) ) {
                return $current_cap;
            } else {
                return ( $current_cap &&
                    array_search( $cap_value, $this->cap_types ) <= array_search( $current_cap, $this->cap_types ) );
            }
        }


        /**
         * Function for checking if role has capability by role name
         *
         * @param $capability
         * @param $cap_value
         * @return array
         */
        function roles_can( $capability, $cap_value ) {
            $roles_can = array();

            $roles = $this->get_settings( 'roles' );
            foreach ( $roles as $role_key=>$role ) {
                $role_caps = $this->get_settings( 'capabilities_' . $role_key );
                if ( isset( $role_caps[$capability] ) && $role_caps[$capability] == $cap_value ) {
                    $roles_can[] = $role_key;
                }
            }

            return $roles_can;
        }

        /**
         * Function for checking if role has capability by role name
         *
         *
         * @param $role string
         * @param $capability string
         * @return bool|string
         */
        function role_can( $role, $capability ) {
            if( !isset( $this->cache['caps'][ $role ][ $capability ] ) ) {
                $this->cache['caps'][ $role ][ $capability ] = false;

                $user_caps = $this->get_settings('capabilities_' . $role);
                $cap = false;
                if (isset($user_caps[$capability]) && $user_caps[$capability] != 'off') {
                    $cap = $user_caps[$capability];
                }

                $this->cache['caps'][ $role ][ $capability ] = $cap;
            }
            return $this->cache['caps'][ $role ][ $capability ];
        }


        /**
         * Function for checking if member has capability by id
         *
         *
         * @param $member_id integer
         * @param $capability string
         * @param $cap_value string
         * @return bool|string
         */
        function member_can( $member_id, $capability, $cap_value = '' ) {
            if( empty( $member_id ) || empty( $capability ) ) return false;
            if( user_can($member_id, 'administrator') ) return empty( $cap_value ) ? 'on' : true;
            if( !empty( $cap_value ) && !in_array( $cap_value, $this->cap_types ) ) return false;

            if( !isset( $this->cache['caps'][ $member_id ][ $capability ] ) ) {
                $this->cache['caps'][ $member_id ][ $capability ] = false;
                if( !( $role = $this->get_office_role( $member_id ) ) ) return false;

                $this->cache['caps'][ $member_id ][ $capability ] = $this->role_can( $role, $capability );
            }

            $current_cap = $this->cache['caps'][ $member_id ][ $capability ];
            if( empty( $cap_value ) ) {
                return $current_cap;
            } else {

                return ( $current_cap &&
                    array_search( $cap_value, $this->cap_types ) <= array_search( $current_cap, $this->cap_types ) );
            }
        }


        /**
         * Function for checking if current member has capability
         *
         *
         * @param $capability string|array
         * @param $cap_value string
         * @return bool|string
         */
        function current_member_can( $capability, $cap_value = '' ) {
            return $this->member_can( get_current_user_id(), $capability, $cap_value );
        }


        function get_office_role( $user_id ) {
            $user = new WP_User( $user_id );
            if( isset( $user->roles ) ) {
                $roles_list = array_keys( $this->get_settings( 'roles' ) );
                $user_office_roles = array_intersect( $user->roles, $roles_list );
                return isset( $user_office_roles[0] ) ? $user_office_roles[0] : false;
            }
            return false;
        }


        /**
         * Get date/time with timezone.
         *
         * @param int $timestamp
         * @param string $datetime_type
         * @param string $format
         * @return string
         */
        function date( $timestamp, $datetime_type = 'date_time', $format = '' ) {
            if ( empty( $timestamp ) ) return '';

            if ( empty( $format ) ) {
                $datetime_type = ( 'date' == $datetime_type || 'time' == $datetime_type ) ? $datetime_type : 'date_time';

                $format = '';

                if ( 'date' == $datetime_type || 'date_time' == $datetime_type ) {
                    //Set date format
                    if( get_option( 'date_format' ) ) {
                        $format .= get_option( 'date_format' );
                    } else {
                        $format .= 'm/d/Y';
                    }
                }

                if ( 'date_time' == $datetime_type )
                    $format .= ' ';

                if ( 'time' == $datetime_type || 'date_time' == $datetime_type ) {
                    //Set time format
                    if( get_option( 'time_format' ) ) {
                        $format .= get_option( 'time_format' );
                    } else {
                        $format .= 'g:i:s A';
                    }
                }
            }

            $gmt_offset =  get_option( 'gmt_offset' );
            if ( false === $gmt_offset ) {
                //$timestamp = $timestamp;
                $timestamp = $timestamp - ( time() - current_time( 'timestamp' ) );
            } else {
                $timestamp = $timestamp + $gmt_offset * 3600;
            }
            return date_i18n( $format, $timestamp );
        }


        /**
         * Delete WPO objects
         *
         * @param $object_id array|int
         * @return bool|false|int
         */
        function delete_wpo_object( $object_id ) {
            global $wpdb;

            if( !is_numeric( $object_id ) && !is_array( $object_id ) ) {
                return false;
            }

            if( is_array( $object_id ) ) {
                $result = $wpdb->query(
                    "DELETE
                    FROM {$wpdb->prefix}wpo_objects,
                         {$wpdb->prefix}wpo_objectmeta
                    USING {$wpdb->prefix}wpo_objects
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta ON {$wpdb->prefix}wpo_objectmeta.object_id = {$wpdb->prefix}wpo_objects.id
                    WHERE {$wpdb->prefix}wpo_objects.id IN('" . implode( "','", $object_id ) . "')"
                );
            } else {
                $result = $wpdb->query( $wpdb->prepare(
                    "DELETE
                    FROM {$wpdb->prefix}wpo_objects,
                         {$wpdb->prefix}wpo_objectmeta
                    USING {$wpdb->prefix}wpo_objects
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta ON {$wpdb->prefix}wpo_objectmeta.object_id = {$wpdb->prefix}wpo_objects.id
                    WHERE {$wpdb->prefix}wpo_objects.id = %d",
                    $object_id
                ) );
            }

            return $result;
        }

        /**
         * Delete object assign
         *
         * @param $object_type
         * @param $object_id
         * @param $assign_type
         * @param $assign_id
         */
        function delete_object_assign( $object_type, $object_id, $assign_type, $assign_id ) {
            global $wpdb;

            if( !empty( $object_type ) && !empty( $object_id ) && !empty( $assign_type ) && !empty( $assign_id ) ) {

                $wpdb->delete(
                    "{$wpdb->prefix}wpo_objects_assigns",
                    array(
                        'object_type'   => $object_type,
                        'object_id'     => $object_id,
                        'assign_type' => $assign_type,
                        'assign_id' => $assign_id
                    )
                );
            }
        }

        /**
         * Delete all object assigns by assign type and assign id
         *
         * @param $assign_type
         * @param $assign_id
         */
        function delete_all_assign_assigns( $assign_type, $assign_id ) {
            global $wpdb;

            if( !empty( $assign_type ) && !empty( $assign_id ) ) {

                $wpdb->delete(
                    "{$wpdb->prefix}wpo_objects_assigns",
                    array(
                        'assign_type' => $assign_type,
                        'assign_id' => $assign_id
                    )
                );
            }
        }


        /**
         * Delete all object assigns by object type and object id
         *
         * @param $object_type
         * @param $object_id
         */
        function delete_all_object_assigns( $object_type, $object_id ) {
            global $wpdb;

            if( !empty( $object_type ) && !empty( $object_id ) ) {

                $wpdb->delete(
                    "{$wpdb->prefix}wpo_objects_assigns",
                    array(
                        'object_type'   => $object_type,
                        'object_id'     => $object_id
                    )
                );
            }
        }


        /**
         * Function to get assign data by assign type/id
         *
         * @param $object_type
         * @param string $assign_type
         * @param $assign_id
         * @param bool $ignore_archive
         * @return array
         */
        function get_assign_data_by_assign( $object_type, $assign_type = 'member', $assign_id, $ignore_archive = true ) {
            global $wpdb;
            $assign_id = is_array( $assign_id ) ? $assign_id : array( $assign_id );

            if( 'member' == $assign_type && $ignore_archive && 0 < count( $assign_id ) ) {
                $excluded_clients = WO()->members()->get_excluded_members( 'archived' );
                $assign_id = array_diff( $assign_id, $excluded_clients );
            }

            $response = array();
            if( isset( $object_type ) && !empty( $object_type ) ) {

                if( 0 < count( $assign_id ) ) {
                    $response = $wpdb->get_col( $wpdb->prepare(
                        "SELECT DISTINCT object_id
                        FROM {$wpdb->prefix}wpo_objects_assigns
                        WHERE object_type='%s' AND
                            assign_id IN('" . implode( "','", $assign_id ) . "') AND
                            assign_type='%s'",
                        $object_type,
                        $assign_type
                    ) );
                }

                $response = array_unique( $response );

                if( 'member' == $assign_type && $ignore_archive && 0 < count( $response ) ) {
                    $excluded_clients = WO()->members()->get_excluded_members( 'archived' );
                    $response = array_diff( $response, $excluded_clients );
                }

                return array_unique( $response );
            }
            return array();
        }


        /**
         * Function checking is object assigned by object and assign type/id
         *
         * @param $object_type string
         * @param $object_id int
         * @param $assign_type string
         * @param @param $assign_id int
         * @return array
         */
        function is_object_assigned( $object_type, $object_id, $assign_type, $assign_id ) {
            global $wpdb;

            if( !empty( $object_type ) && !empty( $object_id ) && !empty( $assign_type ) && !empty( $assign_id ) ) {

                $response = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(id)
                    FROM {$wpdb->prefix}wpo_objects_assigns
                    WHERE object_type = %s AND
                        object_id = %d AND
                        assign_type = %s AND
                        assign_id = %d",
                    $object_type,
                    $object_id,
                    $assign_type,
                    $assign_id
                ) );
                //var_dump($wpdb->last_query);

                return ( $response > 0 );
            }
            return false;
        }


        /**
         * Function to get assign data by object type/id
         *
         * @param $object_type
         * @param $object_id
         * @param string $assign_type
         * @param bool $ignore_archive
         * @return array
         */
        function get_assign_data_by_object( $object_type, $object_id, $assign_type = 'member', $ignore_archive = true ) {
            global $wpdb;
            $object_id = is_array( $object_id ) ? $object_id : array( $object_id );

            if( !empty( $object_type ) && !empty( $object_id ) ) {
                $response = $wpdb->get_col( $wpdb->prepare(
                    "SELECT assign_id
                    FROM {$wpdb->prefix}wpo_objects_assigns
                    WHERE object_type='%s' AND
                        object_id IN('" . implode( "','", $object_id ) . "') AND
                        assign_type='%s'",
                    $object_type,
                    $assign_type
                ) );

                if( 'member' == $assign_type && $ignore_archive && 0 < count( $response ) ) {
                    $excluded_clients = WO()->members()->get_excluded_members( 'archived' );
                    $response = array_diff( $response, $excluded_clients );
                }

                return ( isset( $response ) && !empty( $response ) ) ? array_unique( $response ) : array();
            }
            return array();
        }


        /**
         * Function to set reverse assign data
         *
         * @param $object_type
         * @param array $object_data
         * @param string $assign_type
         * @param $assign_id
         * @return int assigned count
         */
        function set_reverse_assign_data( $object_type, $object_data = array(), $assign_type = 'member', $assign_id ) {
            global $wpdb;

            if( !empty( $object_type ) && !empty( $assign_id ) ) {

                if ( current_user_can( 'administrator' ) ) {
                    $assigned_content = $this->get_access_content_ids( get_current_user_id(), $object_type );

                    if ( !empty( $assigned_content ) ) {
                        $in  = " AND object_id IN ('" . implode( "','", $assigned_content ) . "')";

                        if ( $assign_type == 'member' ) {
                            $in  = " AND assign_id != '" . get_current_user_id() . "'";
                        }

                        $wpdb->query( $wpdb->prepare(
                            "DELETE
                            FROM {$wpdb->prefix}wpo_objects_assigns
                            WHERE object_type = %s AND
                                assign_id = %s AND
                                assign_type = %s
                                $in",
                            $object_type,
                            $assign_id,
                            $assign_type
                        ) );
                    }
                } else {
                    $wpdb->delete(
                        "{$wpdb->prefix}wpo_objects_assigns",
                        array(
                            'object_type'   => $object_type,
                            'assign_type'   => $assign_type,
                            'assign_id'     => $assign_id,
                        )
                    );
                }

                if( is_array( $object_data ) && 0 < count( $object_data ) ) {
                    $values = '';
                    foreach( $object_data as $object_id ) {
                        $values .= "( '$object_type', '$object_id', '$assign_type', '$assign_id' ),";
                    }
                    $values = substr( $values, 0, -1 );
                    $wpdb->query(
                        "INSERT
                        INTO `{$wpdb->prefix}wpo_objects_assigns`(`object_type`,`object_id`,`assign_type`,`assign_id`)
                        VALUES $values"
                    );
                }

                return count( $object_data );

            }

            return 0;
        }


        /**
         * Function to set assign data
         *
         * @param $object_type
         * @param $object_id
         * @param string $assign_type
         * @param array $assign_data
         *
         * @return int
         */
        function set_assign_data( $object_type, $object_id, $assign_type = 'member', $assign_data = array() ) {
            global $wpdb;

            if( !empty( $object_type ) && !empty( $object_id ) ) {

                if ( !current_user_can( 'administrator' ) ) {
                    $assigned_types = $this->get_access_content_ids( get_current_user_id(), $assign_type );

                    if ( !empty( $assigned_types ) ) {
                        $in = " AND assign_id IN ('" . implode( "','", $assigned_types ) . "')";
                        if ( $assign_type == 'member' ) {
                            $in .= " AND assign_id != '" . get_current_user_id() . "'";
                        }
                        $wpdb->query( $wpdb->prepare(
                            "DELETE
                            FROM {$wpdb->prefix}wpo_objects_assigns
                            WHERE object_type = %s AND
                                object_id = %s AND
                                assign_type = %s
                                $in",
                                $object_type,
                                $object_id,
                                $assign_type
                        ) );
                    }
                } else {
                    if( 'member' == $assign_type ) {
                        $excluded_clients = WO()->members()->get_excluded_members( 'archived' );
                        $not_in = '';
                        if ( !empty( $excluded_clients ) ) {
                            $not_in  = " AND assign_id NOT IN ('" . implode( "','", $excluded_clients ) . "')";
                        }

                        $wpdb->query( $wpdb->prepare(
                            "DELETE
                        FROM {$wpdb->prefix}wpo_objects_assigns
                        WHERE object_type = %s AND
                            object_id = %s AND
                            assign_type = %s
                            $not_in",
                            $object_type,
                            $object_id,
                            $assign_type
                        ) );
                    } else {
                        $wpdb->delete(
                            "{$wpdb->prefix}wpo_objects_assigns",
                            array(
                                'object_type'   => $object_type,
                                'object_id'     => $object_id,
                                'assign_type'   => $assign_type,
                            )
                        );
                    }
                }

                $assign_data = is_array( $assign_data ) ? $assign_data : array( $assign_data );

                if( 0 < count( $assign_data ) ) {
                    $values = '';

                    foreach( $assign_data as $assign_id ) {
                        if (  0 != $assign_id ) {
                            $values .= "( '$object_type', '$object_id', '$assign_type', '$assign_id' ),";
                        }
                    }

                    if ( !empty( $values ) ) {
                        $values = substr( $values, 0, -1 );
                        $wpdb->query( "INSERT INTO `{$wpdb->prefix}wpo_objects_assigns`(`object_type`,`object_id`,`assign_type`,`assign_id`) VALUES $values" );

                        return count( $assign_data );
                    }
                }
            }

            return 0;
        }


        /**
         * Get page slug
         *
         * @param string $page_key
         * @param array $attrs
         * @param bool|true $with_end_slash
         * @param bool|true $full_url
         * @return mixed|string|void
         */
        function get_page_slug( $page_key, $attrs = array(), $with_end_slash = true, $full_url = true ) {

            $url = '';

            if ( empty( $page_key ) )
                return $url;

            $pages = WO()->get_settings( 'pages' );

            if ( !empty( $pages[$page_key]['id'] ) ) {
                $page = get_post( $pages[$page_key]['id'] );

                if ( !empty( $page ) ) {

                    //parent exist
                    if ( 0 < $page->post_parent ) {
                        $parent = get_post( $page->post_parent );
                        $url = $parent->post_name . '/';
                    }

                    $url .= $page->post_name;

                    if ( $full_url ) {
                        if ( is_multisite() ) {
                            $url = get_home_url( get_current_blog_id(), $url );
                        } else {
                            if ( WO()->permalinks ) {
                                $url = get_home_url( null, $url );
                            } else {
                                $url = _get_page_link( $page );
                            }
                        }
                    }
                }

            } else {

                if ( 'hub_page' == $page_key ) {
                    $url = trim( $pages['hub_page']['slug'] , '/' );
                } else {
                    $url = trim( $pages['hub_page']['slug'] , '/' ) . '/' . trim( $pages[$page_key]['slug'], '/' );
                }


                if ( $full_url ) {
                    if ( is_multisite() ) {
                        $url = get_home_url( get_current_blog_id(), $url );
                    } else {
                        if ( WO()->permalinks ) {
                            $url = get_home_url( null, $url );
                        } else {
                            // need fix for no permalinks
                            $url = '';
                        }

                    }
                }

            }

            //delete last slash
            $url = untrailingslashit( $url );

            //add get parametrs
            if ( !empty( $attrs ) ) {
                if ( WO()->permalinks ) {
                    $url .= '/' . implode( '/', $attrs );
                } else {
                    $url = add_query_arg( $attrs, $url );
                }
            }

            $query_params = parse_url( $url, PHP_URL_QUERY );
            if ( $with_end_slash && WO()->permalinks && !$query_params ) {
                //add slash to end
                $url = trailingslashit( $url );
            }

            //fix for build links in HTTPS AJAX
            if( defined('DOING_AJAX') && DOING_AJAX && is_ssl() ) {
                $url = str_replace( 'http://', 'https://', $url );
            }

            return $url;
        }


        /**
         * Function for getting private page types
         */
        function get_private_page_types() {
            return WO()->private_page_types;
        }

        /**
         * Getting WPOffice button
         *
         * button_args = array(
         *  primary = true|false,
         *  ajax = true|false,
         *  only_text = true|false
         *  disabled = true|false
         *  is_admin = true|false
         * )
         *
         *
         * @param string $title Button Title
         * @param array $html_atts HTML attributes
         * @param array $button_atts button attributes
         * @param bool|true $echo
         * @return string
         */
        function get_button( $title, $html_atts = array(), $button_atts = array(), $echo = true ) {
            $tag_atts = '';

            //frontend or backend
            $is_admin = is_admin() ? true : false;
            if( isset( $button_atts['is_admin'] ) ) {
                $is_admin = $button_atts['is_admin'];
            }

            $class = 'wpo_button';
            if( !empty( $button_atts['primary'] ) ) {
                $class .= ' wpo_primary_button';
            }
            if ( !$is_admin ) {
                $custom_style = WO()->get_settings( 'custom_style' );
                if ( !empty( $custom_style['disable_plugin_css'] ) && 'yes' == $custom_style['disable_plugin_css'] ) {
                    $class = 'button';
                    if( !empty( $button_atts['primary'] ) ) {
                        $class .= ' button-primary';
                    }
                }
            }

            if( !empty( $button_atts['ajax'] ) ) {
                $class .= ' wpo_ajax_button';
            }

            if( !empty( $button_atts['only_text'] ) ) {
                $class .= ' wpo_text_button';
            }

            if( !empty( $button_atts['disabled'] ) ) {
                $class .= ' wpo_disabled';
            }

            //build html attributes
            if( !empty( $html_atts['class'] ) ) {
                $class .= ' ' . $html_atts['class'];
            }

            $html_atts['class'] = $class;

            foreach( $html_atts as $att_name=>$att_value ) {
                $tag_atts .= " $att_name=\"$att_value\"";
            }

            ob_start(); ?>

            <?php if ( !$is_admin ) {
                $custom_style = WO()->get_settings( 'custom_style' );

                if ( !empty( $custom_style['disable_plugin_css'] ) && 'yes' == $custom_style['disable_plugin_css'] ) { ?>
                    <div class="wpo_default_button_wrapper">
                        <input type="button" <?php echo $tag_atts ?> value="<?php echo $title; ?>" />
                        <?php if( !empty( $button_atts['ajax'] ) ) { ?>
                            <div class="wpo_button_loading">
                                <?php echo $this->get_ajax_loader(24) ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div <?php echo $tag_atts ?>>
                        <?php if( !empty( $button_atts['ajax'] ) ) { ?>
                            <div class="wpo_button_loading">
                                <?php echo $this->get_ajax_loader(24) ?>
                            </div>
                        <?php }
                        echo $title; ?>
                    </div>
                <?php }
            } else { ?>
                <div <?php echo $tag_atts ?>>
                    <?php if( !empty( $button_atts['ajax'] ) ) { ?>
                        <div class="wpo_button_loading">
                            <?php echo $this->get_ajax_loader(24) ?>
                        </div>
                    <?php }
                    echo $title; ?>
                </div>
            <?php }

            $button = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }

            if( $echo ) {
                echo $button;
                return '';
            } else {
                return $button;
            }
        }


        /**
         * Function for get and create uploads dir
         *
         * @param string $dir
         * @param string $dir_access
         * @return string
         */
        function get_upload_dir( $dir = '', $dir_access = '', $create_dir = true ) {
            if( empty( WO()->upload_dir ) ) {
                $uploads            = wp_upload_dir();
                WO()->upload_dir   = str_replace( '/', DIRECTORY_SEPARATOR, $uploads['basedir'] . DIRECTORY_SEPARATOR );
            }

            $dir = str_replace( '/', DIRECTORY_SEPARATOR, $dir );

            //check and create folder
            if ( !empty( $dir ) && $create_dir ) {
                $folders = explode( DIRECTORY_SEPARATOR, $dir );
                $cur_folder = '';
                foreach( $folders as $folder ) {
                    $prev_dir = $cur_folder;
                    $cur_folder .= $folder . DIRECTORY_SEPARATOR;
                    if ( !is_dir( WO()->upload_dir . $cur_folder ) && wp_is_writable( WO()->upload_dir . $prev_dir ) ) {
                        mkdir( WO()->upload_dir . $cur_folder, 0777 );
                        if( 'wpoffice' == $folder ) {
                            $htp = fopen( WO()->upload_dir . $cur_folder . DIRECTORY_SEPARATOR . '.htaccess', 'w' );
                            fputs( $htp, 'deny from all' ); // $file being the .htpasswd file
                        } elseif( $dir_access == 'deny' ) {
                            $htp = fopen( WO()->upload_dir . $cur_folder . DIRECTORY_SEPARATOR . '.htaccess', 'w' );
                            fputs( $htp, 'deny from all' ); // $file being the .htpasswd file
                        } elseif( $dir_access == 'allow' ) {
                            $htp = fopen( WO()->upload_dir . $cur_folder . DIRECTORY_SEPARATOR . '.htaccess', 'w' );
                            fputs( $htp, 'allow from all' ); // $file being the .htpasswd file
                        }
                    }
                }
            }

            //return dir path
            return WO()->upload_dir . $dir;
        }


        /**
         * Sanitizes a text, stripping out unsafe characters..
         *
         * @since  1.0.0
         *
         * @param string $text The username to be sanitized.
         * @return string The sanitized text
         */
        function sanitize_text( $text ) {
            $text = wp_strip_all_tags( $text );
            $text = preg_replace( '|[^a-zA-Z0-9 _.\-@]|i', '', $text );

            return $text;
        }


        /**
         * Delete plugin settings data.
         *
         * @since  1.0.0
         *
         * @param  string $key   key of settings
         * @param  mixed $data   data for ser settings
         * @return mixed
         */
        function delete_settings( $key ) {
            if ( !empty( $key ) ) {
                return delete_option( 'wpoffice_settings_' . $key );
            }
            return false;
        }


        /**
         * Update plugin settings data.
         *
         * @since  1.0.0
         *
         * @param  string $key   key of settings
         * @param  array $new_data   data for update
         * @return mixed
         */
        function update_settings( $key, $new_data ) {
            if ( !empty( $key ) && is_array( $new_data ) ) {
                $full_key = 'wpoffice_settings_' . $key;

				$old_data = $this->get_settings( $key );
				if ( $old_data ) {
					$new_data = array_merge( $old_data, $new_data );
				}

                return update_option( $full_key, $new_data, false );
            }
            return false;
        }


        /**
         * Save plugin settings data.
         *
         * @since  1.0.0
         *
         * @param  string $key   key of settings
         * @param  mixed $data   data for ser settings
         * @return mixed
         */
        function set_settings( $key, $data ) {
            if ( !empty( $key ) ) {
                return update_option( 'wpoffice_settings_' . $key, $data, false );
            }
            return false;
        }


        /**
         * Get plugin settings data.
         *
         * @since  1.0.0
         *
         * @param  string $key      key of settings
         * @return mixed
         */
        function get_settings( $key, $default = array() ) {
            return apply_filters( 'wpoffice_pre_get_settings_' . $key, get_option( 'wpoffice_settings_' . $key, $default ) );
        }


        /**
         * Display  help tip.
         *
         * @since  1.0.0
         *
         * @param  string $tip        Help tip text
         * @param  bool   $allow_html Allow sanitized HTML if true or escape
         * @return string
         */
        function get_help_tip( $tip, $allow_html = false ) {
            wp_enqueue_script( 'jquery-ui-tooltip' );

            if ( $allow_html ) {

                $tip = htmlspecialchars( wp_kses( html_entity_decode( $tip ), array(
                    'br'     => array(),
                    'em'     => array(),
                    'strong' => array(),
                    'small'  => array(),
                    'span'   => array(),
                    'ul'     => array(),
                    'li'     => array(),
                    'ol'     => array(),
                    'p'      => array(),
                ) ) );

            } else {
                $tip = esc_attr( $tip );
            }


            ob_start();
            ?>

            <span class="wpo-help-tip dashicons dashicons-editor-help" title="<?php echo $tip ?>"></span>

            <?php if ( !isset( WO()->wpo_flags['tooltip_rendered'] ) ) {
                WO()->wpo_flags['tooltip_rendered'] = true;

                ?>

                <script type="text/javascript">
                    jQuery(document).ready(function () {
                        jQuery('.wpo-help-tip').tooltip({
                            tooltipClass: "wpo-help-tooltip",
                            content: function () {
                                return jQuery(this).attr('title');
                            }
                        });
                    });
                </script>

                <style>
                    .ui-tooltip.wpo-help-tooltip {
                        padding: 8px;
                        color: #ccc;
                        background-color: #333;
                        position: absolute;
                        z-index: 1000000;
                        max-width: 300px;
                        -webkit-box-shadow: 0 0 5px #aaa;
                        box-shadow: 0 0 5px #aaa;
                    }

                    .wpo-help-tip.dashicons-editor-help::before {
                        float: left;
                        font-size: 18px;
                    }
                    .wpo-help-tip {
                        color: #999;
                        cursor: pointer;
                        opacity: 0.5;
                    }
                    .wpo-help-tip:hover {
                        opacity: 1;
                    }

                </style>

                <?php
            }

            $tooltip = ob_get_clean();

            return $tooltip;

        }


        /**
         * Output any queued javascript code in the footer.
         */
        function print_custom_js() {
            if ( !empty( WO()->queued_js ) ) {

                echo "\n<script type=\"text/javascript\">\njQuery(function($) {";

                // Sanitize
                $queued_js = wp_check_invalid_utf8( WO()->queued_js );
                $queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $queued_js );
                $queued_js = str_replace( "\r", '', $queued_js );

                echo $queued_js . "});\n</script>\n";

                unset( WO()->queued_js );
            }
        }


        /**
         * Queue some JavaScript code to be output in the footer.
         *
         * @param string $script
         */
        function enqueue_custom_js( $script ) {
            if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                echo $script;
            } else {
                if ( empty( WO()->queued_js ) ) {
                    WO()->queued_js = '';
                }

                WO()->queued_js .= "\n" . $script . "\n";
            }
        }


        /**
         * Get colored HR separator
         *
         * @param string $margin
         * @return string
         */
        function hr( $margin = '10px 0' ) {
            return '<div class="wpo_hr" style="margin:' . $margin . ';"><div class="wpo_hr1"></div><div class="wpo_hr2"></div><div class="wpo_hr3"></div><div class="wpo_hr4"></div></div>';
        }

        /**
         * Get AJAX loader image
         *
         * @param $size
         * @return string
         */
        public function get_ajax_loader( $size ) {
            $id = uniqid();
            ob_start(); ?>

            <div align="center" class="cssload-fond<?php echo $id ?>">
                <div class="cssload-container-general">
                    <div class="cssload-internal"><div class="cssload-ballcolor cssload-ball_1"> </div></div>
                    <div class="cssload-internal"><div class="cssload-ballcolor cssload-ball_2"> </div></div>
                    <div class="cssload-internal"><div class="cssload-ballcolor cssload-ball_3"> </div></div>
                    <div class="cssload-internal"><div class="cssload-ballcolor cssload-ball_4"> </div></div>
                </div>
            </div>
            <style type="text/css">
                <?php if ( is_numeric( $size ) ) {
                $width = $height = $size;
                $ball_width = $ball_height = round( $size/2.2 );
                $delta_position = round( $size/1.8 );
                $animate_position = round( $size/3.6 ); ?>

                .cssload-fond<?php echo $id ?> {
                    position:relative;
                    margin: auto;
                }
                .cssload-fond<?php echo $id ?> .cssload-container-general {
                    animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                    -o-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                    -ms-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                    -webkit-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                    -moz-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                    width:<?php echo $width ?>px;
                    height:<?php echo $height ?>px;
                }
                .cssload-fond<?php echo $id ?> .cssload-internal {
                    width:<?php echo $width ?>px;
                    height:<?php echo $height ?>px;
                    position:absolute;
                }
                .cssload-fond<?php echo $id ?> .cssload-ballcolor {
                    width: <?php echo $ball_width ?>px;
                    height: <?php echo $ball_height ?>px;
                    border-radius: 50%;
                }
                .cssload-fond<?php echo $id ?> .cssload-ball_1,
                .cssload-fond<?php echo $id ?> .cssload-ball_2,
                .cssload-fond<?php echo $id ?> .cssload-ball_3,
                .cssload-fond<?php echo $id ?> .cssload-ball_4 {
                    position: absolute;
                    animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    -o-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    -ms-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    -webkit-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    -moz-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                }
                .cssload-fond<?php echo $id ?> .cssload-ball_1 {
                    background-color:rgb(203,32,37);
                    top:0; left:0;
                }
                .cssload-fond<?php echo $id ?> .cssload-ball_2 {
                    background-color:rgb(248,179,52);
                    top:0; left:<?php echo $delta_position ?>px;
                }
                .cssload-fond<?php echo $id ?> .cssload-ball_3 {
                    background-color:rgb(0,160,150);
                    top:<?php echo $delta_position ?>px; left:0;
                }
                .cssload-fond<?php echo $id ?> .cssload-ball_4 {
                    background-color:rgb(151,191,13);
                    top:<?php echo $delta_position ?>px; left:<?php echo $delta_position ?>px;
                }

                @keyframes cssload-animball_one<?php echo $id ?>
                {
                    0%{ position: absolute;}
                    50%{top:<?php echo $animate_position ?>px; left:<?php echo $animate_position ?>px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                }

                @-o-keyframes cssload-animball_one<?php echo $id ?>
                {
                    0%{ position: absolute;}
                    50%{top:<?php echo $animate_position ?>px; left:<?php echo $animate_position ?>px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                }

                @-ms-keyframes cssload-animball_one<?php echo $id ?>
                {
                    0%{ position: absolute;}
                    50%{top:<?php echo $animate_position ?>px; left:<?php echo $animate_position ?>px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                }

                @-webkit-keyframes cssload-animball_one<?php echo $id ?>
                {
                    0%{ position: absolute;}
                    50%{top:<?php echo $animate_position ?>px; left:<?php echo $animate_position ?>px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                }

                @-moz-keyframes cssload-animball_one<?php echo $id ?>
                {
                    0%{ position: absolute;}
                    50%{top:<?php echo $animate_position ?>px; left:<?php echo $animate_position ?>px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                }

                @keyframes cssload-animball_two<?php echo $id ?>
                {
                    0%{transform:rotate(0deg) scale(1);}
                    50%{transform:rotate(360deg) scale(1.3);}
                    100%{transform:rotate(720deg) scale(1);}
                }

                @-o-keyframes cssload-animball_two<?php echo $id ?>
                {
                    0%{-o-transform:rotate(0deg) scale(1);}
                    50%{-o-transform:rotate(360deg) scale(1.3);}
                    100%{-o-transform:rotate(720deg) scale(1);}
                }

                @-ms-keyframes cssload-animball_two<?php echo $id ?>
                {
                    0%{-ms-transform:rotate(0deg) scale(1);}
                    50%{-ms-transform:rotate(360deg) scale(1.3);}
                    100%{-ms-transform:rotate(720deg) scale(1);}
                }

                @-webkit-keyframes cssload-animball_two<?php echo $id ?>
                {
                    0%{-webkit-transform:rotate(0deg) scale(1);}
                    50%{-webkit-transform:rotate(360deg) scale(1.3);}
                    100%{-webkit-transform:rotate(720deg) scale(1);}
                }

                @-moz-keyframes cssload-animball_two<?php echo $id ?>
                {
                    0%{-moz-transform:rotate(0deg) scale(1);}
                    50%{-moz-transform:rotate(360deg) scale(1.3);}
                    100%{-moz-transform:rotate(720deg) scale(1);}
                }


                <?php } else if ( $size == 'small' ) { ?>
                    .cssload-fond<?php echo $id ?> {
                        position:relative;
                        margin: auto;
                    }

                    .cssload-fond<?php echo $id ?> .cssload-container-general {
                        animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -o-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -ms-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -webkit-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -moz-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        width:24px; height:24px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-internal {
                        width:24px; height:24px; position:absolute;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ballcolor {
                        width: 11px;
                        height: 11px;
                        border-radius: 50%;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1,
                    .cssload-fond<?php echo $id ?> .cssload-ball_2,
                    .cssload-fond<?php echo $id ?> .cssload-ball_3,
                    .cssload-fond<?php echo $id ?> .cssload-ball_4 {
                        position: absolute;
                        animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -o-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -ms-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -webkit-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -moz-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1 {
                        background-color:rgb(203,32,37);
                        top:0; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_2 {
                        background-color:rgb(248,179,52);
                        top:0; left:13px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_3 {
                        background-color:rgb(0,160,150);
                        top:13px; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_4 {
                        background-color:rgb(151,191,13);
                        top:13px; left:13px;
                    }

                    @keyframes cssload-animball_one<?php echo $id ?>
                    {
                    0%{ position: absolute;}
                    50%{top:7px; left:7px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                    }

                    @-o-keyframes cssload-animball_one<?php echo $id ?>
                    {
                    0%{ position: absolute;}
                    50%{top:7px; left:7px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                    }

                    @-ms-keyframes cssload-animball_one<?php echo $id ?>
                    {
                    0%{ position: absolute;}
                    50%{top:7px; left:7px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                    }

                    @-webkit-keyframes cssload-animball_one<?php echo $id ?>
                    {
                    0%{ position: absolute;}
                    50%{top:7px; left:7px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                    }

                    @-moz-keyframes cssload-animball_one<?php echo $id ?>
                    {
                    0%{ position: absolute;}
                    50%{top:7px; left:7px; position: absolute;opacity:0.5;}
                    100%{ position: absolute;}
                    }

                    @keyframes cssload-animball_two<?php echo $id ?>
                    {
                    0%{transform:rotate(0deg) scale(1);}
                    50%{transform:rotate(360deg) scale(1.3);}
                    100%{transform:rotate(720deg) scale(1);}
                    }

                    @-o-keyframes cssload-animball_two<?php echo $id ?>
                    {
                    0%{-o-transform:rotate(0deg) scale(1);}
                    50%{-o-transform:rotate(360deg) scale(1.3);}
                    100%{-o-transform:rotate(720deg) scale(1);}
                    }

                    @-ms-keyframes cssload-animball_two<?php echo $id ?>
                    {
                    0%{-ms-transform:rotate(0deg) scale(1);}
                    50%{-ms-transform:rotate(360deg) scale(1.3);}
                    100%{-ms-transform:rotate(720deg) scale(1);}
                    }

                    @-webkit-keyframes cssload-animball_two<?php echo $id ?>
                    {
                    0%{-webkit-transform:rotate(0deg) scale(1);}
                    50%{-webkit-transform:rotate(360deg) scale(1.3);}
                    100%{-webkit-transform:rotate(720deg) scale(1);}
                    }

                    @-moz-keyframes cssload-animball_two<?php echo $id ?>
                    {
                    0%{-moz-transform:rotate(0deg) scale(1);}
                    50%{-moz-transform:rotate(360deg) scale(1.3);}
                    100%{-moz-transform:rotate(720deg) scale(1);}
                    }
                <?php } elseif ( $size == 'medium' ) { ?>
                    .cssload-fond<?php echo $id ?> {
                        position:relative;
                        margin: auto;
                    }

                    .cssload-fond<?php echo $id ?> .cssload-container-general
                    {
                        animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -o-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -ms-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -webkit-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -moz-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        width:38px; height:38px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-internal
                    {
                        width:38px; height:38px; position:absolute;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ballcolor
                    {
                        width: 17px;
                        height: 17px;
                        border-radius: 50%;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1,
                    .cssload-fond<?php echo $id ?> .cssload-ball_2,
                    .cssload-fond<?php echo $id ?> .cssload-ball_3,
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        position: absolute;
                        animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -o-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -ms-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -webkit-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -moz-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1
                    {
                        background-color:rgb(203,32,37);
                        top:0; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_2
                    {
                        background-color:rgb(248,179,52);
                        top:0; left:21px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_3
                    {
                        background-color:rgb(0,160,150);
                        top:21px; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        background-color:rgb(151,191,13);
                        top:21px; left:21px;
                    }





                    @keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:10px; left:10px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-o-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:10px; left:10px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-ms-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:10px; left:10px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-webkit-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:10px; left:10px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-moz-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:10px; left:10px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{transform:rotate(0deg) scale(1);}
                        50%{transform:rotate(360deg) scale(1.3);}
                        100%{transform:rotate(720deg) scale(1);}
                    }

                    @-o-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-o-transform:rotate(0deg) scale(1);}
                        50%{-o-transform:rotate(360deg) scale(1.3);}
                        100%{-o-transform:rotate(720deg) scale(1);}
                    }

                    @-ms-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-ms-transform:rotate(0deg) scale(1);}
                        50%{-ms-transform:rotate(360deg) scale(1.3);}
                        100%{-ms-transform:rotate(720deg) scale(1);}
                    }

                    @-webkit-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-webkit-transform:rotate(0deg) scale(1);}
                        50%{-webkit-transform:rotate(360deg) scale(1.3);}
                        100%{-webkit-transform:rotate(720deg) scale(1);}
                    }

                    @-moz-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-moz-transform:rotate(0deg) scale(1);}
                        50%{-moz-transform:rotate(360deg) scale(1.3);}
                        100%{-moz-transform:rotate(720deg) scale(1);}
                    }
                <?php } elseif ( $size == 'large' ) { ?>
                    .cssload-fond<?php echo $id ?> {
                        position:relative;
                        margin: auto;
                    }

                    .cssload-fond<?php echo $id ?> .cssload-container-general
                    {
                        animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -o-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -ms-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -webkit-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        -moz-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        width:77px; height:77px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-internal
                    {
                        width:77px; height:77px; position:absolute;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ballcolor
                    {
                        width: 35px;
                        height: 35px;
                        border-radius: 50%;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1,
                    .cssload-fond<?php echo $id ?> .cssload-ball_2,
                    .cssload-fond<?php echo $id ?> .cssload-ball_3,
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        position: absolute;
                        animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -o-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -ms-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -webkit-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                        -moz-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1
                    {
                        background-color: rgb(225, 82, 38);
                        top:0; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_2
                    {
                        background-color: rgb(105, 134, 194);
                        top:0; left:42px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_3
                    {
                        background-color: rgb(226, 171, 54);
                        top:42px; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        background-color: rgb(110, 160, 63);
                        top:42px; left:42px;
                    }





                    @keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:21px; left:21px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-o-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:21px; left:21px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-ms-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:21px; left:21px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-webkit-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:21px; left:21px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-moz-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:21px; left:21px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{transform:rotate(0deg) scale(1);}
                        50%{transform:rotate(360deg) scale(1.3);}
                        100%{transform:rotate(720deg) scale(1);}
                    }

                    @-o-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-o-transform:rotate(0deg) scale(1);}
                        50%{-o-transform:rotate(360deg) scale(1.3);}
                        100%{-o-transform:rotate(720deg) scale(1);}
                    }

                    @-ms-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-ms-transform:rotate(0deg) scale(1);}
                        50%{-ms-transform:rotate(360deg) scale(1.3);}
                        100%{-ms-transform:rotate(720deg) scale(1);}
                    }

                    @-webkit-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-webkit-transform:rotate(0deg) scale(1);}
                        50%{-webkit-transform:rotate(360deg) scale(1.3);}
                        100%{-webkit-transform:rotate(720deg) scale(1);}
                    }

                    @-moz-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-moz-transform:rotate(0deg) scale(1);}
                        50%{-moz-transform:rotate(360deg) scale(1.3);}
                        100%{-moz-transform:rotate(720deg) scale(1);}
                    }
                <?php } else { ?>
                    .cssload-fond<?php echo $id ?> {
                        position:relative;
                        margin: auto;
                    }

                    .cssload-fond<?php echo $id ?> .cssload-container-general
                    {
                        animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                            -o-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                            -ms-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                            -webkit-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                            -moz-animation:cssload-animball_two<?php echo $id ?> 1.15s infinite;
                        width:43px; height:43px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-internal
                    {
                        width:43px; height:43px; position:absolute;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ballcolor
                    {
                        width: 19px;
                        height: 19px;
                        border-radius: 50%;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1,
                    .cssload-fond<?php echo $id ?> .cssload-ball_2,
                    .cssload-fond<?php echo $id ?> .cssload-ball_3,
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        position: absolute;
                        animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                            -o-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                            -ms-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                            -webkit-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                            -moz-animation:cssload-animball_one<?php echo $id ?> 1.15s infinite ease;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_1
                    {
                        background-color:rgb(203,32,37);
                        top:0; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_2
                    {
                        background-color:rgb(248,179,52);
                        top:0; left:23px;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_3
                    {
                        background-color:rgb(0,160,150);
                        top:23px; left:0;
                    }
                    .cssload-fond<?php echo $id ?> .cssload-ball_4
                    {
                        background-color:rgb(151,191,13);
                        top:23px; left:23px;
                    }





                    @keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:12px; left:12px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-o-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:12px; left:12px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-ms-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:12px; left:12px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-webkit-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:12px; left:12px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @-moz-keyframes cssload-animball_one<?php echo $id ?>
                    {
                        0%{ position: absolute;}
                        50%{top:12px; left:12px; position: absolute;opacity:0.5;}
                        100%{ position: absolute;}
                    }

                    @keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{transform:rotate(0deg) scale(1);}
                        50%{transform:rotate(360deg) scale(1.3);}
                        100%{transform:rotate(720deg) scale(1);}
                    }

                    @-o-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-o-transform:rotate(0deg) scale(1);}
                        50%{-o-transform:rotate(360deg) scale(1.3);}
                        100%{-o-transform:rotate(720deg) scale(1);}
                    }

                    @-ms-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-ms-transform:rotate(0deg) scale(1);}
                        50%{-ms-transform:rotate(360deg) scale(1.3);}
                        100%{-ms-transform:rotate(720deg) scale(1);}
                    }

                    @-webkit-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-webkit-transform:rotate(0deg) scale(1);}
                        50%{-webkit-transform:rotate(360deg) scale(1.3);}
                        100%{-webkit-transform:rotate(720deg) scale(1);}
                    }

                    @-moz-keyframes cssload-animball_two<?php echo $id ?>
                    {
                        0%{-moz-transform:rotate(0deg) scale(1);}
                        50%{-moz-transform:rotate(360deg) scale(1.3);}
                        100%{-moz-transform:rotate(720deg) scale(1);}
                    }
                <?php } ?>
            </style>
            <?php $loader = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }

            return $loader;
        }

        /**
         * Get prepared search text for sql request
         *
         * @global object $wpdb
         * @param string $text
         * @param array $sql_fields
         * @return string
         */
        public function get_prepared_search( $text, $sql_fields ) {
            $text = strtolower( trim( $text ) );

            $string = $return = '';
            foreach ( $sql_fields as $field ) {
                $string .= 'LOWER(' . $field . ') LIKE \'%%%1$s%%\' OR ';
            }

            $string = substr( $string, 0, -4 );

            if ( !empty( $string ) ) {
                global $wpdb;
                $return = $wpdb->prepare( ' AND ( ' . $string . ' )', $text );
            }

            return $return;
        }

        /**
         * Get object meta
         *
         * @param string $id
         * @param string $meta_key
         *
         * @return string
         */
        public function get_object_meta( $id, $meta_key ) {
            global $wpdb;
            $meta = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value
                FROM {$wpdb->prefix}wpo_objectmeta
                WHERE object_id = %d AND meta_key = %s", $id, $meta_key ) );

            return $meta;
        }

        /**
         * Change object meta
         *
         * @param string $id
         * @param string $meta_key
         * @param string $meta_value
         *
         * @return boolean
         */
        public function update_object_meta( $id, $meta_key, $meta_value ) {
            global $wpdb;

            if ( is_array( $meta_value ) ) {
                $meta_value = serialize( $meta_value );
            }
            $isset_meta = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id)
                FROM {$wpdb->prefix}wpo_objectmeta
                WHERE object_id = %d AND meta_key = %s", $id, $meta_key ) );
            if( $isset_meta == 0 ) {
                $wpdb->insert( $wpdb->prefix . 'wpo_objectmeta',
                    array(
                        'object_id' => $id,
                        'meta_key' => $meta_key,
                        'meta_value' => $meta_value
                    )
                );
            } else {
                $wpdb->update( $wpdb->prefix . 'wpo_objectmeta',
                    array(
                        'meta_value' => $meta_value
                    ),
                    array(
                        'object_id' => $id,
                        'meta_key' => $meta_key
                    )
                );
            }
            return true;
        }

        /**
         * Get object data
         *
         * @param array $data
         *
         * @return mixed
         */
        public function update_object( $data ) {
            global $wpdb;

            if( isset( $data['id'] ) ) {
                $isset_object = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}wpo_objects WHERE id = %d", $data['id'] ) );
                if( (int)$isset_object === 0 ) {
                    return false;
                }
                $update_array = array();
                if( isset( $data['title'] ) ) {
                    $update_array['title'] = $data['title'];
                }
                if( isset( $data['type'] ) ) {
                    $update_array['type'] = $data['type'];
                }

                if ( !empty( $update_array ) ) {
                    $wpdb->update( $wpdb->prefix . 'wpo_objects',
                        $update_array,
                        array(
                            'id' => $data['id']
                        )
                    );
                }
            } else {
                $wpdb->insert( $wpdb->prefix . 'wpo_objects',
                    array(
                        'title'         => $data['title'],
                        'type'          => $data['type'],
                        'creation_date' => !empty( $data['creation_date'] ) ? $data['creation_date'] : time(),
                        'author'        => !empty( $data['author'] ) ? $data['author'] : get_current_user_id()
                    )
                );
                $data['id'] = $wpdb->insert_id;
            }

            foreach( $data as $key=>$val ) {
                if( !in_array( $key, array( 'id','title','type','creation_date', 'author' ) ) ) {
                    $this->update_object_meta( $data['id'], $key, $val );
                }
            }

            return $data['id'];
        }

        /**
         * Get object data
         *
         * @param string $id
         * @param string $type
         *
         * @return array
         */
        public function get_object( $id, $type = '' ) {
            global $wpdb;
            $where = '';
            if( !empty( $type ) ) {
                $where = " AND type = '" . esc_sql( $type ) . "'";
            }
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpo_objects WHERE id = %d $where", $id ), ARRAY_A );
            if( count( $result ) ) {
                $meta = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->prefix}wpo_objectmeta WHERE object_id = %d", $id ), ARRAY_A );
                foreach( $meta as $val ) {
                    $result[ $val['meta_key'] ] = $val['meta_value'];
                }
            }
            return $result;
        }

        /**
         * Get ajax routed URL
         *
         * @param string $route
         * @param string $method
         *
         * @return string
         */
        public function get_ajax_route( $route, $method ) {
            $route = str_replace( array( '\\', '/' ), '!', $route );
            //return get_admin_url() . 'admin-ajax.php?action=wpo_ajax&wpo_ajax_side=common&route=' . $route . '&method=' . $method;

            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
            $user_id = get_current_user_id();
            $nonce = wp_create_nonce( $ip . $user_id . $route . $method );
            if( is_admin() ) {
                $url = add_query_arg( array(
                    'action' => 'wpo_api',
                    'wpo_ajax_side' => 'common',
                    'wpo_action' => 'route',
                    'wpo_resource' => $route,
                    'wpo_method' => $method,
                    'wpo_verify' => $nonce
                ), get_admin_url( null, 'admin-ajax.php') );
            } else if( get_option( 'permalink_structure' ) ) {
                $url = get_site_url( null, 'wpo-api/route/' . $route . '/' . $method . '/' . $nonce );
            } else {
                $url = add_query_arg( array(
                    'wpo_page' => 'api',
                    'wpo_action' => 'route',
                    'wpo_resource' => $route,
                    'wpo_method' => $method,
                    'wpo_verify' => $nonce
                ), get_site_url() );
            }
            return $url;
        }


        /**
         * Get AJAX URL for different side
         *
         * @param string $action
         * @param string $side
         *
         * @return string
         */
        public function get_ajax_url( $action = 'ajax', $side = 'common' ) {

            if ( !in_array( $side, array( 'admin', 'user', 'common' ) ) )
                $side = 'common';

            return get_admin_url() . 'admin-ajax.php?action=wpo_' . $action . '&wpo_ajax_side=' . $side;
        }


        public function wrong_page_checkout( $context ) {
            $url = apply_filters( 'wpoffice_wrong_page_checkout', $this->get_page_slug('hub_page'), $context );
            $this->redirect( $url );
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

        /**
         * return included feature or no
         *
         * @param string $name
         * @return boolean
         */
        public function included_feature( $name ) {
            $exclude_features = $this->get_settings( 'exclude_features' );
            return empty( $exclude_features[ $name ] ) ;
        }


        /**
         * Get extensions list.
         * @return array
         */
        public function get_extensions_list() {
            return apply_filters( 'wpoffice_extensions', array() );
        }


        /**
         * Get the path.
         * @param string $path_name
         * @return string
         */
        public function path( $path_name = '' ) {
            $path = WO()->plugin_dir;
            if( !empty( $path_name ) ) {
                $path = apply_filters( 'wpoffice_path_' . $path_name, $path );
            }
            return trailingslashit( $path );
        }


        /**
         * Method returns expected path for template
         *
         * @access public
         * @param string $location
         * @param string $template_name
         * @param string $path (default: '')
         * @return string
         */
        function get_template_file( $location, $template_name, $path = '' ) {
            $template_path = '';
            switch( $location ) {
                case 'theme':
                    $template_path = trailingslashit( get_stylesheet_directory() . '/wp-office/' . $path ) . $template_name;
                    break;
                case 'plugin':
                    $template_path = $this->path( $path ) . 'templates/' . $template_name;
                    break;
            }

            return apply_filters( 'wpoffice_template_location', $template_path, $location, $template_name, $path );
        }


        /**
         * Locate a template and return the path for inclusion.
         *
         * @access public
         * @param string $template_name
         * @param string $path (default: '')
         * @return string
         */
        function locate_template( $template_name, $path = '' ) {
            $template_path = 'wp-office/' . $path;

            $template = locate_template(
                array(
                    trailingslashit( $template_path ) . $template_name
                )
            );

            if( !$template ) {
                $template_path = $this->path( $path ) . 'templates/';
                $template = $template_path . $template_name;
            }
            // Return what we found.
            return apply_filters( 'wpoffice_locate_template', $template, $template_name, $path );
        }

        /**
         * Get other templates (e.g. files table) passing attributes and including the file.
         *
         * @access public
         * @param string $template_name
         * @param string $path (default: '')
         * @param array $args (default: array())
         */
        function get_template( $template_name, $path = '', $args = array() ) {
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args );
            }

            $located = $this->locate_template( $template_name, $path );

            if ( ! file_exists( $located ) ) {
                _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
                return;
            }

            // Allow 3rd party plugin filter template file from their plugin.
            $located = apply_filters( 'wpoffice_get_template', $located, $template_name, $path, $args );

            do_action( 'wpoffice_before_template_part', $template_name, $path, $located, $args );
            include( $located );
            do_action( 'wpoffice_after_template_part', $template_name, $path, $located, $args );
        }

        function setPost( $key ) {
            if( isset( $_REQUEST[ $key ] ) ) {
                $data = base64_decode( str_replace( '-', '+', $_REQUEST[ $key ] ) );
                if( !empty( $data ) ) {
                    parse_str( $data, $array );
                    $_POST = array_merge( $_POST, $array );
                    $_REQUEST = array_merge( $_REQUEST, $array );
                }
                unset( $_REQUEST[ $key ] );
                unset( $_POST[ $key ] );
            }
        }

        /**
         * Checking that it's our roles
         *
         * @param array $roles  containing keys of roles
         *
         * @return bool
         */
        public function is_our_role( $roles ) {
            if ( empty( $roles ) )
                return false;

            if ( ! is_array( $roles ) )
                $roles = array( $roles );

            $roles_list = WO()->get_settings( 'roles' );

            if ( !empty( $roles_list ) ) {
                foreach( $roles as $role ) {
                    if ( !empty( $roles_list[$role] ) )
                        return true;
                }
            }

            return false;
        }

        function get_content_types() {
            return $this->content_types;
        }

        /**
         * What type of request is this?
         *
         * @param string $type String containing name of request type (ajax, frontend, cron or admin)
         *
         * @return bool
         */
        public function is_request( $type ) {
            switch ( $type ) {
                case 'admin' :
                    return ( ! defined( 'DOING_AJAX' ) && is_admin() );
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
            }

            return false;
        }

    } //end class
}
