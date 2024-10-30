<?php
namespace wpo\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Install {

    private $ver_updates = array(
//            '1.0.1',
//            '1.0.3',
    );

    /**
    * PHP 5 constructor
    **/
    function __construct() {

    }


    function uninstall() {
        global $wpdb, $wp_post_types;
        define( 'WP_UNINSTALL_PLUGIN', '1' );

        //deactivate the plugin
        $plugins = get_option( 'active_plugins' );
        if ( is_array( $plugins ) && 0 < count( $plugins ) ) {
            $basename = plugin_basename( WO()->plugin_dir . 'wp-office.php' );
            foreach( $plugins as $key=>$plugin ) {
                if ( $basename == $plugin ) {
                    unset( $plugins[ $key ] );
                }
            }

            update_option( 'active_plugins', $plugins );
        }


        //remove upload directory
        WO()->rrmdir( WO()->get_upload_dir( 'wpoffice/' ) );

        //remove custom posts and post types
        $post_types = WO()->get_post_types();
        foreach( $post_types as $post_type=>$data ) {
            $posts = get_posts( array(
                'numberposts'   => -1,
                'post_type'     => $post_type
            ) );
            if ( is_array( $posts ) && 0 < count( $posts ) ) {
                foreach( $posts as $post ) {
                    wp_delete_post($post->ID);

                }
            }

            if ( isset( $wp_post_types[ $post_type ] ) ) {
                unset( $wp_post_types[ $post_type ] );
            }
        }

        //remove own pages
        $pages = WO()->get_settings( 'pages' );
        foreach( $pages as $page ) {
            if( isset( $page['id'] ) && is_int( $page['id'] ) ) {
                wp_delete_post( $page['id'] );
            }
        }

        //remove roles and members
        $roles = WO()->get_settings( 'roles' );

        foreach( $roles as $key=>$role ) {
            $ids = get_users( array( 'role' => $key, 'fields' => 'ID' ) );
            if ( is_array( $ids ) && 0 < count( $ids ) ) {
                foreach( $ids as $user_id ) {
                    if( is_multisite() ) {
                        wpmu_delete_user( $user_id );
                    } else {
                        wp_delete_user( $user_id );
                    }
                }
            }
            remove_role( $key );
        }

        //remove all tables
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}wpo_%'");
        foreach( $tables as $key ) {
            $wpdb->query( "DROP TABLE {$key}" );
        }

        //remove settings
        $options = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'wpoffice_settings_%'");
        foreach( $options as $option ) {
            delete_option( $option );
        }

        //remove user meta
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wpo_%'");

        //remove version
        delete_option( 'wpoffice_ver' );

        //delete deactivation status
        delete_option( 'wpoffice_activation' );

        do_action('wpoffice_uninstall');

        wp_redirect( get_admin_url() . 'plugins.php' );
        exit;
    }

    /**
     * Create default Email notifications
     */
    function set_default_notifications() {
        $default_notifications = array(
            'self_registration' => array(
                'subject' => 'Welcome to Your Private Portal!',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>Your selected password</strong></p>
                              <p>Your private and secure Portal has been created. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'self_registration',
                        'doer'              => 'all',
                        'recipient'         => 'doer',
                    )
                )
            ),
            'self_registration2' => array(
                'subject' => 'Member was registered at {wpo_site_title}',
                'content' => '<p>His Username is : <strong>{wpo_member_login}</strong></p>
                              <p>His Email is : <strong>{wpo_member_email}</strong></p>
                              <p>Contact with this member and provide more information for it. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'self_registration',
                        'doer'              => 'all',
                        'recipient'         => 'all_selected_roles',
                        'recipient_select'  => array( 'administrator' ),
                    )
                )
            ),
            'self_profile_update' => array(
                'subject' => 'Member {wpo_member_login} updated his profile at {wpo_site_title}',
                'content' => '<p>His Display Name is : <strong>{wpo_member_name}</strong></p>
                              <p>His Email is : <strong>{wpo_member_email}</strong></p>
                              <p>You can login to getting more information by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'self_profile_update',
                        'doer'              => 'all',
                        'recipient'         => 'all_selected_roles',
                        'recipient_select'  => array( 'administrator' ),
                    )
                )
            ),
            'create_office_manager' => array(
                'subject' => 'Welcome to Your Private Portal!',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>{wpo_member_password}</strong></p>
                              <p>Your private and secure Portal has been created. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'create_wpoffice_manager',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'create_office_client' => array(
                'subject' => 'Welcome to Your Private Portal!',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>{wpo_member_password}</strong></p>
                              <p>Your private and secure Portal has been created. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'create_wpoffice_client',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator', 'wpoffice_manager' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'update_office_manager' => array(
                'subject' => 'Your profile was updated at {wpo_site_title}',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>{wpo_member_password}</strong></p>
                              <p>Your private and secure Portal has been created. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'update_wpoffice_manager_profile',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'update_office_client' => array(
                'subject' => 'Your profile was updated at {wpo_site_title}',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>{wpo_member_password}</strong></p>
                              <p>Your private and secure Portal has been created. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'update_wpoffice_client_profile',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator', 'wpoffice_manager' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'approve_office_manager' => array(
                'subject' => 'Your profile was approved at {wpo_site_title}',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>Your selected password</strong></p>
                              <p>Your was approved at {wpo_site_title}. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'approve_wpoffice_manager',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'approve_office_client' => array(
                'subject' => 'Your profile was approved at {wpo_site_title}',
                'content' => '<p>Hello {wpo_member_name},<br /> <br /> Your Username is : <strong>{wpo_member_login}</strong> and Password is : <strong>Your selected password</strong></p>
                              <p>Your was approved at {wpo_site_title}. You can login by clicking <strong><a href="{wpo_login_url}">HERE</a></strong></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'approve_wpoffice_client',
                        'doer'              => 'selected',
                        'doer_select'       => array( 'administrator', 'wpoffice_manager' ),
                        'recipient'         => 'member',
                    )
                )
            ),
            'reset_password' => array(
                'subject' => 'Reset password at {wpo_site_title}',
                'content' => '<p>Hi {wpo_member_login},</p>
                              <p>You have requested to reset your password.</p>
                              <p>Please follow the link below.</p>
                              <p><a href="{wpo_reset_password_url}">Reset Your Password</a></p>
                              <p>Thanks,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'reset_password',
                        'doer'              => 'all',
                        'recipient'         => 'doer',
                    )
                )
            ),
            'update_office_page' => array(
                'subject' => __( 'Your Office Page has been updated', WP_OFFICE_TEXT_DOMAIN ),
                'content' => '<p>Your Office Page, {wpo_office_page_title} has been updated | <a href="{wpo_office_page_url}">Click HERE to visit</a></p>
                              <p>Thanks, and please contact us if you experience any difficulties,</p>
                              <p>{wpo_contact_name}</p>',
                'sending_rules' => array(
                    array(
                        'action'            => 'update_office_page',
                        'doer'              => 'all',
                        'recipient'         => 'object_author',
                    ),
                    array(
                        'action'            => 'update_office_page',
                        'doer'              => 'all',
                        'recipient'         => 'assigned_selected_roles',
                        'recipient_select'  => array( 'wpoffice_manager', 'wpoffice_client' ),
                    )
                )
            ),
        );

        $default_notifications = apply_filters( "wpoffice_set_default_notifications", $default_notifications );

        foreach ( $default_notifications as $notification ) {
            $notification_id = WO()->update_object( array(
                'title'         => $notification['subject'],
                'author'        => get_current_user_id(),
                'type'          => 'email_notification',
                'creation_date' => time(),
                'body'          => $notification['content'],
                'active'        => 'yes'
            ) );

            $select_key = WO()->valid_ajax_encode( array(
                'key' => 'plugin_roles',
                'type' => 'checkbox'
            ) );

            foreach ( $notification['sending_rules'] as $sending_rule ) {

                $doer_select = '';
                if ( !empty( $sending_rule['doer_select'] ) ) {
                    $doer_select[$select_key] = $sending_rule['doer_select'];
                    $doer_select = serialize( $doer_select );
                }

                $recipient_select = '';
                if ( !empty( $sending_rule['recipient_select'] ) ) {
                    $recipient_select[$select_key] = $sending_rule['recipient_select'];
                    $recipient_select = serialize( $recipient_select );
                }


                WO()->update_object( array(
                    'type'              => 'sending_rule',
                    'title'             => '',
                    'author'            => get_current_user_id(),
                    'creation_date'     => time(),
                    'notification_id'   => $notification_id,
                    'action'            => $sending_rule['action'],
                    'doer'              => $sending_rule['doer'],
                    'doer_select'       => $doer_select,
                    'recipient'         => $sending_rule['recipient'],
                    'recipient_select'  => $recipient_select,
                ) );
            }
        }
    }

    /**
     * Set data for first install (only once)
     *
     */
    function first_install() {
        global $wp_roles, $wpdb;

        /*
         * Add default roles
         */

        

        //add Client role
        $wp_roles->add_role( 'wpoffice_client', 'Office Client ', array(
            'read'
        ) );

        //create HUB default
        WO()->get_hub_default();

        $this->set_default_notifications();

        $settings = array();

        $settings['roles']['wpoffice_client'] = array(
            'title'     => 'Office Client',
            'parent'    => 'wpoffice_manager',
        );

        $settings['capabilities_wpoffice_client'] = array(
            'upload_file' => 'on',
            'edit_file' => 'own',
            'delete_file' => 'own',
            'view_message' => 'own',
            'send_message' => 'own',
            'delete_message' => 'own',
            'view_office_page' => 'assigned',
            'view_office_page_category' => 'assigned',
        );

        

        

        //set default settings
        foreach( $settings as $key => $value ) {
            WO()->set_settings( $key, $value );
        }

        $endpoints = WO()->get_endpoints();
        $pages = array(
            'hub_page' => array(
                'slug' => 'office-hub'
            )
        );
        foreach( $endpoints as $key=>$val ) {
            $pages[ $key ] = array(
                'slug' => $val['slug'],
            );
        }
        WO()->set_settings( 'pages', $pages );

        //for update rewrite rules
        WO()->reset_rewrite_rules();

        //for do not run it again
        update_option( 'wpoffice_ver', WP_OFFICE_VER );
    }

    /**
     * Update plugin data to new version
     *
     * @param string $ver String containing plugin version
     *
     * @return string empty;
     */
    function update( $ver ) {

        global $wpdb;

        if ( '0.0.0' == $ver ) {
            $this->first_install();

            return '';
        }

        foreach( $this->ver_updates as $ver_update ) {
            if ( version_compare( $ver, $ver_update, '<' ) ) {
                //include update changes
                if ( file_exists( WO()->plugin_dir . 'includes/updates/update-' . $ver_update . '.php' ) ) {
                    include_once WO()->plugin_dir . 'includes/updates/update-' . $ver_update . '.php';

                    update_option( 'wpoffice_ver', $ver_update );
                }
            }
        }

        update_option( 'wpoffice_ver', WP_OFFICE_VER );

        return '';
    }

    /**
    * Create DB tables
    **/
    function create_db() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = '';

        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        // specific tables.
        $tables = "CREATE TABLE {$wpdb->prefix}wpo_objects_assigns (
id bigint(20) NOT NULL AUTO_INCREMENT,
object_id bigint(20) NULL,
assign_type enum('member','profile','circle') NOT NULL,
assign_id bigint(20) NULL,
PRIMARY KEY  (id),
KEY objectid_assignid (object_id,assign_id),
KEY objectid (object_id),
KEY assignid (assign_id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}wpo_objects (
id int(11) unsigned NOT NULL AUTO_INCREMENT,
title text,
type varchar(1024) DEFAULT NULL,
creation_date text NOT NULL,
author int(11) unsigned NOT NULL,
PRIMARY KEY  (id)
) $charset_collate;
CREATE TABLE {$wpdb->prefix}wpo_objectmeta (
id int(11) unsigned NOT NULL AUTO_INCREMENT,
object_id int(11) unsigned NOT NULL,
meta_key varchar(255) DEFAULT NULL,
meta_value longtext,
PRIMARY KEY  (id),
KEY object_id (object_id),
KEY meta_key (meta_key)
) $charset_collate;
";

        dbDelta( $tables );

    }

//end class
}