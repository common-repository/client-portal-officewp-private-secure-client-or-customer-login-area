<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Members {

    /**
     * PHP 5 constructor
     **/
    function __construct() {

    }


    function avatar_remove() {
        if ( ! empty( $_POST['id'] ) ) {

            if ( ! empty( $_POST['temp'] ) && strpos( $_POST['temp'], 'temp_' ) == '0' ) {
                $upload_dir = wp_upload_dir();

                if ( file_exists( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_temp_avatars" . DIRECTORY_SEPARATOR . $_POST['temp'] ) ) {
                    unlink( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_temp_avatars" . DIRECTORY_SEPARATOR . $_POST['temp'] );
                }
            }

            $current_avatar = $this->get_avatar_src( $_POST['id'] );
            $current_avatar = html_entity_decode( $current_avatar );

            exit( json_encode( array( 'status' => true, 'current_avatar' => $current_avatar, 'is_gravatar' => $this->is_avatar_gravatar( $_POST['id'] ) ) ) );
        } else {
            if ( ! empty( $_POST['temp'] ) && strpos( $_POST['temp'], 'temp_' ) == '0' ) {
                $upload_dir = wp_upload_dir();

                if ( file_exists( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_temp_avatars" . DIRECTORY_SEPARATOR . $_POST['temp'] ) ) {
                    unlink( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_temp_avatars" . DIRECTORY_SEPARATOR . $_POST['temp'] );
                }

                exit( json_encode( array( 'status' => true, 'current_avatar' => WO()->plugin_url . 'assets/images/avatars/empty_avatar.png' ) ) );
            }

            exit( json_encode( array( 'status' => false, 'message' => __( 'Wrong data', WPC_CLIENT_TEXT_DOMAIN ) ) ) );
        }
    }

    function save_help_flag() {
        if( isset( $_REQUEST['flag'] ) ) {
            update_user_meta( get_current_user_id(), 'wpo_show_help', $_REQUEST['flag'] == '1' ? '1' : '0' );
        }
    }


    public function registration() {
        if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['wpo_role'] ) && WO()->is_our_role( $_POST['wpo_role'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wpofficeregistrationform' . $_POST['wpo_role'] ) ) {

            $validation_array = apply_filters( 'wpoffice_registration_form_validations', array(
                'user_login' => array(
                    'required',
                    'username_exists',
                ),
                'user_email' => array(
                    'required',
                    'email',
                    'email_exists',
                ),
                'user_pass' => array(
                    'required',
                )
            ) );

            $validation_errors = WO()->validation()->process( $_POST, $validation_array );

            if( isset( $validation_errors ) && count( $validation_errors ) ) {
                exit ( json_encode( array( 'status' => false, 'validation_message' => $validation_errors ) ) );
            }

            $userdata['user_login'] = !empty( $_POST['user_login'] ) ? trim( $_POST['user_login'] ) : '';
            $userdata['user_email'] = !empty( $_POST['user_email'] ) ? trim( $_POST['user_email'] ) : '';
            $userdata['user_pass']  = !empty( $_POST['user_pass'] ) ? trim( $_POST['user_pass'] ) : '';
            $userdata['first_name'] = !empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
            $userdata['last_name']  = !empty( $_POST['last_name'] ) ? trim( $_POST['last_name'] ) : '';
            $userdata['role']       = $_POST['wpo_role'];
            $userdata['_form']      = 'registration';


            $member_id = WO()->members()->save_userdata( $userdata );

            if ( ! is_numeric( $member_id ) ) {

                if ( is_array( $member_id )) {
                    $error_message = '';
                    foreach ( $member_id as $error ) {
                        $error_message .= $error . '<br />';
                }

                    die ( json_encode( array( 'status' => false, 'error_message' => $error_message ) ) );
                }

                die ( json_encode( array( 'status' => false, 'error_message' => $member_id ) ) );

            }

            /*wpo_hook_
                hook_name: wpoffice_member_registered
                hook_title: Member Registered
                hook_description: Hook runs after member was registered by registration from.
                hook_type: action
                hook_in: wp-office
                hook_location class-member.php
                hook_param: int member_id
                hook_since: 1.0.0
            */
            do_action( 'wpoffice_member_registered', $member_id );

            /*wpo_hook_
                hook_name: wpoffice_autologin
                hook_title: Member Autologin after Registeration
                hook_description: Hook runs after member was registered by registration from.
                hook_type: action
                hook_in: wp-office
                hook_location class-member.php
                hook_param: bool autologin, int member_id, array userdata
                hook_since: 1.0.0
            */
            $autologin = apply_filters( 'wpoffice_autologin', true, $member_id, $userdata );
            $redirect_url = '';
            if ( true === $autologin ) {
                wp_set_auth_cookie( $member_id, true );
                $redirect_url = WO()->get_page_slug( 'hub_page' );
            }

            //send notification
            WO()->send_notification(
                'self_registration',
                array(
                    'doer' => $member_id
                ),
                array(
                    'member_id' => $member_id,
                    'object_type' => 'member',
                )
            );

            //our_hook
            $registration_redirect_url = apply_filters( 'wpoffice_registration_redirect_url', $redirect_url, $member_id );

            die ( json_encode( array( 'status' => true, 'redirect' => $registration_redirect_url ) ) );

        }
        die ( json_encode( array( 'status' => false, 'error_message' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    public function profile() {

        //get data from encode string
        if ( !empty( $_POST['wpo_form_data'] ) ) {
            parse_str( $_REQUEST['wpo_form_data'], $array );
            $_POST = array_merge( $_POST, $array );
            unset( $_POST['wpo_form_data'] );
        }

        if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['wpo_member_id'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'wpofficeprofileform-' . $_POST['wpo_member_id'] ) ) {

            $validation_array = apply_filters( 'wpoffice_profile_form_validations', array(
                'user_email' => array(
                    'required',
                    'email',
                    'email_exists',
                )
            ) );
            $validation_errors = WO()->validation()->process( $_POST, $validation_array, array(
                'user_id' => $_POST['wpo_member_id']
            ) );

            if( !empty( $validation_errors ) ) {
                exit ( json_encode( array( 'status' => false, 'validation_message' => $validation_errors ) ) );
            }

            $member_data = get_userdata( $_POST['wpo_member_id'] );

            if ( !$member_data )
                die ( json_encode( array( 'status' => false, 'error_message' => __( 'Wrong Member!', WP_OFFICE_TEXT_DOMAIN ) ) ) );

            if ( !WO()->is_our_role( $member_data->roles ) )
                die ( json_encode( array( 'status' => false, 'error_message' => __( 'Wrong Member Role!', WP_OFFICE_TEXT_DOMAIN ) ) ) );

                $userdata['ID']         = $_POST['wpo_member_id'];
                $userdata['user_email'] = !empty( $_POST['user_email'] ) ? trim( $_POST['user_email'] ) : '';
                $userdata['first_name'] = !empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
                $userdata['last_name']  = !empty( $_POST['last_name'] ) ? trim( $_POST['last_name'] ) : '';
                $userdata['_form']      = 'profile';

                if ( !empty( $_POST['user_pass'] ) ) {
                    $userdata['user_pass'] = trim( $_POST['user_pass'] );
                }

                $member_id = WO()->members()->save_userdata( $userdata );

                if ( ! is_numeric( $member_id ) ) {

                    if ( is_array( $member_id )) {
                        $error_message = '';
                        foreach ( $member_id as $error ) {
                            $error_message .= $error . '<br />';
                            }

                        die ( json_encode( array( 'status' => false, 'error_message' => $error_message ) ) );
                    }

                    die ( json_encode( array( 'status' => false, 'error_message' => $member_id ) ) );
                }

                /*wpo_hook_
                    hook_name: wpoffice_member_registered
                    hook_title: Member Registered
                    hook_description: Hook runs after member was registered by registration from.
                    hook_type: action
                    hook_in: wp-office
                    hook_location class-member.php
                    hook_param: int member_id
                    hook_since: 1.0.0
                */
                do_action( 'wpoffice_member_updated', $member_id );

                //send notification
                WO()->send_notification(
                    'self_profile_update',
                    array(
                        'doer' => get_current_user_id()
                    ),
                    array(
                        'member_id' => get_current_user_id(),
                        'object_type' => 'member',
                    )
                );

                die ( json_encode( array( 'status' => true, 'message' => __( 'Profile Updated!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }

        die ( json_encode( array( 'status' => false, 'error_message' => __( 'Wrong Data!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }



    public function get_excluded_members( $what = false, $current_role = false, $with_assign = true ) {
        $excluded_clients = array();

        $assigned_users = array();
        if ( $with_assign && !current_user_can( 'administrator' ) && WO()->member_main_manage_cap( get_current_user_id(), $current_role ) == 'assigned' ) {
            $assigned_users = WO()->get_available_members_by_role( $current_role, get_current_user_id() );
        }

        if ( $current_role ) {
            if ( ! $what || 'archived' == $what ) {
                $excluded_clients = get_users( array( 'role' => $current_role, 'include' => $assigned_users, 'meta_key' => 'wpoffice_archived', 'meta_value' => '1', 'fields' => 'ID' ) );
            }
        } else {

            $roles_list = WO()->get_settings( 'roles' );
            $roles_list = array_keys( $roles_list );

            foreach ( $roles_list as $role ) {
                if ( ! $what || 'archived' == $what ) {
                    $clients = get_users( array( 'role' => $role, 'include' => $assigned_users, 'meta_key' => 'wpoffice_archived', 'meta_value' => '1', 'fields' => 'ID' ) );
                    $excluded_clients = array_merge( $excluded_clients, $clients );
                }
            }

        }

        //our hook
        return apply_filters( 'wpoffice_excluded_clients', $excluded_clients, $what, $current_role, $assigned_users );
    }


    public function upload_avatar() {
        if ( !ini_get( 'safe_mode' ) ) {
            @set_time_limit(0);
        }

        $avatars_dir = WO()->get_upload_dir( 'wpoffice/_temp_avatars/', 'allow' );

        //delete temp files
        $files = scandir( $avatars_dir );
        foreach ( $files as $file ) {
            if ( $file != "." && $file != ".." ) {
                if ( filemtime( $avatars_dir . $file ) < time() - 60*60*2 ) {
                    @unlink( $avatars_dir . $file );
                }
            }
        }

        $fileinfo = pathinfo( $_FILES['Filedata']['name'] );

        $new_name       = 'temp_' . time() . '_' . uniqid() . '.' . $fileinfo['extension'];
        $target_path    = $avatars_dir . DIRECTORY_SEPARATOR . $new_name;

        $image_sizes = getimagesize( $_FILES['Filedata']['tmp_name'] );

        if ( isset( $image_sizes['mime'] ) && ( $image_sizes['mime'] == 'image/png' || $image_sizes['mime'] == 'image/jpeg' ) ) {
            $width = $image_sizes[0];
            $height = $image_sizes[1];

            $image = wp_get_image_editor( $_FILES['Filedata']['tmp_name'] );
            if ( ! is_wp_error( $image ) ) {
                if( $width == $height ) {
                    $image->resize( 128, 128, false );
                } else {
                    if( $width > $height ) {
                        $image->crop( ceil( ( $width - $height ) / 2 ), 0, $height, $height );
                        $image->resize( 128, 128, false );
                    } else {
                        $image->crop( 0, ceil( ( $height - $width ) / 2 ), $width, $width );
                        $image->resize( 128, 128, false );
                    }
                }

                $image->save( $target_path );
            } else {
                if ( ! move_uploaded_file( $_FILES['Filedata']['tmp_name'], $target_path ) ) {
                    $msg = __( 'There was an error uploading the file, please try again!', WP_OFFICE_TEXT_DOMAIN );
                    exit( $msg );
                }
            }
        } else {
            $msg = __( 'Your file is not image, please try again!', WP_OFFICE_TEXT_DOMAIN );
            exit( $msg );
        }

        echo $new_name;
        exit;
    }


    public function is_avatar_gravatar( $user_id ) {
        $current_avatar = get_user_meta( $user_id, 'wpo_avatar', true );
        $upload_dir = wp_upload_dir();

        if( !empty( $current_avatar ) && file_exists( $upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_avatars" . DIRECTORY_SEPARATOR . $current_avatar ) ) {
            return true;
        } else {
            $request = get_user_meta( $user_id, 'wpo_gravatar_request', true );

            if( !empty( $request ) ) {
                if( $request['date'] > time() - 60*60*12 && isset( $request['is_gravatar'] ) ) {
                    return $request['is_gravatar'];
                } else {
                    $user = get_userdata( $user_id );
                    $hash = md5( strtolower( trim( $user->get('user_email') ) ) );
                    $profile = wp_remote_post('http://www.gravatar.com/' . $hash . '.php', array(
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0'
                    ));

                    if( !( is_array( $profile ) && isset( $profile['response']['code'] ) && $profile['response']['code'] == 404 ) ) {
                        $is_gravatar = true;
                    } else {
                        $is_gravatar = false;
                    }
                    update_user_meta( $user_id, 'wpo_gravatar_request', array( 'is_gravatar' => $is_gravatar, 'date'=>time() ) );
                }
            } else {
                $user = get_userdata( $user_id );
                if( !empty( $user ) ) {
                    $hash = md5(strtolower(trim($user->get('user_email'))));
                    $profile = wp_remote_post('http://www.gravatar.com/' . $hash . '.php', array(
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0'
                    ));
                    if (!(is_array($profile) && isset($profile['response']['code']) && $profile['response']['code'] == 404)) {
                        $is_gravatar = true;
                    } else {
                        $is_gravatar = false;
                    }
                    update_user_meta( $user_id, 'wpo_gravatar_request', array( 'is_gravatar' => $is_gravatar, 'date' => time() ) );
                } else {
                    return true;
                }
            }

            return $is_gravatar;
        }
    }


    public function user_avatar( $user_id = '' ) {
        if ( empty( $user_id ) )
            $user_id = get_current_user_id();

        if ( (int)$user_id <= 0 )
            return '';

        ob_start(); ?>
        <div class="wpo_avatar_output">
            <?php $src = ( $user_id ) ? $this->get_avatar_src( $user_id ) : WO()->plugin_url . "assets/images/avatars/empty_avatar.png"; ?>

            <img class="wpo_user_avatar_image" src="<?php echo $src ?>" />

            <?php //default avatar
            if( !$this->is_avatar_gravatar( $user_id ) ) {
                $user = get_user_by( 'id', $user_id ); ?>
                <div class="wpo_user_avatar_literal"><?php echo substr( $user->user_login, 0, 1 ) ?></div>
            <?php } ?>
        </div>

        <?php $avatar = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }
        return $avatar;
    }


    public function build_avatar_field( $field_name, $field_value = false, $user_id = false ) {
        if( !( isset( $field_name ) && is_string( $field_name ) && !empty( $field_name ) ) ) {
            return '';
        }

        $upload_dir = wp_upload_dir();
        if( $user_id ) {
            $user = get_user_by( 'id', $user_id );
        } else {
            $user = false;
        }

        $src = ( $user_id ) ? $this->get_avatar_src( $user_id ) : WO()->plugin_url . "assets/images/avatars/empty_avatar.png";

        $can_remove = ( $user_id && false === strpos( $src, WO()->plugin_url . "assets/images/avatars/" ) ) ? true : false;
        //if user ID false, then build field for Add user, else for Edit user
        ob_start(); ?>

        <input type="hidden" class="hidden_value_avatar" name="<?php echo $field_name ?>" value="<?php echo ( isset( $field_value ) ) ? $field_value : ''  ?>" />
        <div id="wpo_avatar_preview_wrapper" data-user_id="<?php echo ( $user_id ) ? $user_id : '' ?>">

            <img class="wpo_avatar_preview" src="<?php echo $src ?>" />
            <div class="wpo_avatar_literal" <?php if( $this->is_avatar_gravatar( $user_id ) ) { ?>style="display:none;"<?php } ?>><?php echo ( isset( $user ) && is_object( $user ) ) ? substr( $user->user_login, 0, 1 ) : ''; ?></div>

            <div class="wpo_avatar_top_bubble_wrap">
                <div class="wpo_avatar_top_bubble">
                    <div class="wpo_avatar_delete <?php echo ( ! $can_remove ) ? 'add' : 'edit' ?>" title="<?php _e( 'Remove Avatar', WP_OFFICE_TEXT_DOMAIN ) ?>">
                        &times;
                    </div>
                </div>
            </div>
            <div class="wpo_avatar_bottom_bubble_wrap">
                <div class="wpo_avatar_bottom_bubble">
                    <div class="wpo_avatar_upload">
                        <input type="file" name="Filedata" id="wpo_avatar_uploader" />
                    </div>
                </div>
            </div>
        </div>


        <script type="text/javascript">
            jQuery( document ).ready( function() {
                jQuery( '.wpo_avatar_delete').click( function() {
                    var user_id = jQuery('#wpo_avatar_preview_wrapper').data('user_id');
                    var temp = jQuery('.hidden_value_avatar').val();

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo WO()->get_ajax_route( get_class( $this ), 'avatar_remove' ) ?>',
                        data: 'id=' + user_id + '&temp=' + temp,
                        dataType: "json",
                        success: function( data ) {
                            if ( !data.status ) {
                                alert( data.message );
                            } else {
                                jQuery( '.wpo_avatar_preview' ).attr( 'src', data.current_avatar );

                                if ( typeof( user_id ) !== 'undefined' && user_id != '' ) {
                                    if ( data.current_avatar.indexOf('<?php echo WO()->plugin_url ?>assets/images/avatars/') != -1 ) {
                                        jQuery( '.wpo_avatar_delete' ).addClass('add');
                                        jQuery( '.hidden_value_avatar' ).val('');
                                    } else {
                                        jQuery( '.wpo_avatar_delete' ).addClass('add');
                                        jQuery( '.hidden_value_avatar' ).val('wpo_delete_avatar');
                                        jQuery( '.wpo_avatar_preview' ).attr('src', '<?php echo WO()->plugin_url . "assets/images/avatars/empty_avatar.png" ?>' );
                                    }

                                    if ( ! data.is_gravatar ) {
                                        jQuery('.wpo_avatar_literal').show();
                                    }
                                } else {
                                    jQuery( '.hidden_value_avatar' ).val('');
                                    jQuery( '.wpo_avatar_delete').addClass('add');
                                }
                            }
                        }
                    });
                });

                jQuery('#wpo_avatar_uploader').uploadifive({
                    'formData'          : {},
                    'fileType'          : ['image/jpeg','image/png'],
                    'auto'              : true,
                    'dnd'               : true,
                    'multi'             : false,
                    'itemTemplate'      : '<div class="uploadifive-queue-item"><span class="filename"></span><span class="fileinfo"></span><div class="close"></div><div class="avatar_loading"><?php echo str_replace( "\r", "", str_replace( "\n", "",  WO()->get_ajax_loader(38) ) ) ?></div></div>',
                    'buttonText'        : '<?php _e( 'Upload New', WP_OFFICE_TEXT_DOMAIN ) ?>',
                    'queueID'           : 'wpo_avatar_preview_wrapper',
                    'uploadScript'      : '<?php echo WO()->get_ajax_route( get_class( $this ), 'upload_avatar' ) ?>',
                    'onUploadComplete'  : function( file, filename ) {
                        if( filename.indexOf( 'temp_' ) == 0 ) {
                            jQuery( '.uploadifive-queue-item.complete' ).remove();
                            jQuery( '.wpo_avatar_preview' ).attr( 'src', '<?php echo $upload_dir['baseurl'] . "/wpoffice/_temp_avatars/" ?>' + filename );
                            jQuery( '.hidden_value_avatar' ).val( filename );
                            jQuery( '.wpo_avatar_delete').removeClass('add');
                            jQuery( '.wpo_avatar_literal' ).hide();
                        } else {
                            jQuery( '.wpo_avatar_literal' ).show();
                            jQuery( '.uploadifive-queue-item.complete' ).remove();

                            jQuery(this).wpo_notice({
                                message: filename,
                                type: 'update'
                            });
                        }
                        return false;
                    },
                    'onError'      : function(errorType) {
                        jQuery(this).uploadifive('clearQueue');
                        jQuery( '.uploadifive-queue-item.error' ).remove();
                        jQuery('.wpo_avatar_literal').show();

                        var error = '';
                        if ( 'FORBIDDEN_FILE_TYPE' == errorType ) {
                            error = '<?php _e( 'Unsupported file type', WP_OFFICE_TEXT_DOMAIN ) ?>';
                        } else if ( 'QUEUE_LIMIT_EXCEEDED' == errorType ) {
                            error = '<?php _e( 'File is too large', WP_OFFICE_TEXT_DOMAIN ) ?>';
                        } else if ( 'UPLOAD_LIMIT_EXCEEDED' == errorType ) {
                            error = '<?php _e( 'File is too large', WP_OFFICE_TEXT_DOMAIN ) ?>';
                        } else if ( 'FILE_SIZE_LIMIT_EXCEEDED' == errorType ) {
                            error = '<?php _e( 'File is too large', WP_OFFICE_TEXT_DOMAIN ) ?>';
                        } else if ( '404_FILE_NOT_FOUND' == errorType ) {
                            error = '<?php _e( '404 - file not found', WP_OFFICE_TEXT_DOMAIN ) ?>';
                        }

                        jQuery(this).wpo_notice({
                            message: error,
                            type: 'error'
                        });
                    }
                });
            });
        </script>
        <?php $field = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $field;
    }


    public function get_avatar_src( $user_id, $size = '128' ) {
        $avatar = get_user_meta( $user_id, 'wpo_avatar', true );

        if( !empty( $avatar ) ) {
            $upload_dir = wp_upload_dir();

            if ( !file_exists($upload_dir['basedir'] . DIRECTORY_SEPARATOR . "wpoffice" . DIRECTORY_SEPARATOR . "_avatars" . DIRECTORY_SEPARATOR . $avatar) ) {
                $avatar = false;
            } else {
                $avatar = $upload_dir['baseurl'] . '/wpoffice/_avatars/' . $avatar;
            }
        }

        if( empty( $avatar ) ) {
            if( $this->is_avatar_gravatar( $user_id ) ) {
                $default = WO()->plugin_url . 'assets/images/avatars/' . $user_id%10 . '.png';
                $alt = false;

                if( get_option('show_avatars') ) {
                    $str = get_avatar( $user_id, $size, $default, $alt );
                } else {
                    $str = "<img alt='{$alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
                    $str = apply_filters( 'get_avatar', $str, $user_id, $size, $default, $alt );
                }

                if( $str != false ) {
                    preg_match('/<img[^>]*?src=[\"|\']([^\'\">]+)[\'|\"][^>]*?>/ims', $str, $regex_result);
                    if (isset($regex_result[1]) && !empty($regex_result[1])) {
                        $avatar = $regex_result[1];
                    }
                }
            } else {
                $avatar = WO()->plugin_url . 'assets/images/avatars/' . $user_id%10 . '.png';
            }
        }

        if( empty( $avatar ) ) {
            $avatar = WO()->plugin_url . 'assets/images/avatars/' . $user_id%10 . '.png';
        }

        return $avatar;
    }


    /**
     * @param string $edit_form
     * @param string $first_field_name
     * @param bool $hide_show_button
     * @return string
     */
    public function frontend_build_password_form( $edit_form, $first_field_name = 'pass1', $hide_show_button = false ) {
        //$settings = $this->cc_get_settings( 'clients_staff' );

        wp_register_script(
            'wpo-password-strength',
            WO()->plugin_url . 'assets/js/plugins/jquery.wpo_password_strength.js',
            array('password-strength-meter'),
            WP_OFFICE_VER,
            true
        );

        wp_localize_script('wpo-password-strength', 'wpo_password_strength',
            apply_filters( 'wpoffice_password_strength_localize', array(
                'text' => array(
                    'strength' => array(
                        'very_weak' => __( "Very Weak", WP_OFFICE_TEXT_DOMAIN ),
                        'weak' => __( "Weak", WP_OFFICE_TEXT_DOMAIN ),
                        'medium' => __( "Medium", WP_OFFICE_TEXT_DOMAIN ),
                        'strong' => __( "Strong", WP_OFFICE_TEXT_DOMAIN )
                    )
                ),
                'generate_password_url' => WO()->get_ajax_url( 'generate_password' ),
                'hide_show_button'      => $hide_show_button,
                'edit_form' => $edit_form
            ) )
        );

        wp_print_scripts( array(
            'wpo-password-strength'
        ) );

        ob_start(); ?>
        <!--pass_strength-->
        <style type="text/css">
            .wpo_confirm_weak {
                float:left;
                width:100%;
                margin: 10px 0 0 0;
            }

            .wpo_password_wrapper {
                display: none;
                width: 100%;
                float:left;
                margin: 0;
                padding:0;
            }

            .wpo_button_password_wrapper {
                width: 100%;
                float:left;
                margin: 0;
                padding:0;
            }

            .wpo_password_wrapper .hidden {
                display: none;
            }

            .wpo_password_input_wrapper #pass1_text,
            .wpo_password_input_wrapper.wpo_show_password #pass1 {
                display: none;
            }

            .wpo_password_input_wrapper #pass1,
            .wpo_password_input_wrapper.wpo_show_password #pass1_text {
                display: block;
            }

            .wpo_toggle_password .wpo_hide_text,
            .wpo_toggle_password .dashicons-hidden,
            .wpo_toggle_password.wpo_password_visible .wpo_show_text,
            .wpo_toggle_password.wpo_password_visible .dashicons-visibility {
                display: none;
            }

            .wpo_toggle_password .wpo_show_text,
            .wpo_toggle_password .dashicons-visibility,
            .wpo_toggle_password.wpo_password_visible .wpo_hide_text,
            .wpo_toggle_password.wpo_password_visible .dashicons-hidden {
                display: inline-block;
            }

            #pass1,
            #pass1_text {
                margin: 0;
                float:left;
                width: 100% !important;
            }

            #wpo_pass_strength {
                float:left;
                width:100%;
                border-style: solid;
                border-width: 1px;
                margin: 0;
                padding: 3px 5px;
                text-align: center;
                background-color: #eee;
                border-color: #ddd !important;
                display: block;
                box-sizing: border-box;
                -moz-box-sizing: border-box;
                -webkit-box-sizing: border-box;
            }

            #wpo_pass_strength.short {
                background-color: #f1adad;
                border-color: #e35b5b !important;
            }

            #wpo_pass_strength.bad {
                background-color: #fbc5a9;
                border-color: #f78b53 !important;
            }

            #wpo_pass_strength.good {
                background-color: #ffe399;
                border-color: #ffc733 !important;
            }

            #wpo_pass_strength.strong {
                background-color: #c1e1b9;
                border-color: #83c373 !important;
            }
            .wpo_hide_generate_button .wpo_toggle_password {
                float:right;
            }

        </style>
        <?php if ( false == $hide_show_button ) { ?>
            <div class="wpo_button_password_wrapper">
                <?php WO()->get_button(
                    ( ( $edit_form ) ? __( 'Generate Password', WP_OFFICE_TEXT_DOMAIN ) : __( 'Show Password', WP_OFFICE_TEXT_DOMAIN ) ),
                    array(
                        'class'=>'wpo_generate_password'
                    ),
                    array(
                        'only_text' => true,
                        'ajax' => true,
                        'is_admin' => false
                    )
                ); ?>
            </div>
        <?php } ?>
        <div class="wpo_password_wrapper hide-if-js" data-wpo-valid="required">
            <input class="hidden" value=" " />
            <div style="<?php if ( false == $hide_show_button ) { ?>width: 50%;<?php } else { ?>width: 65%;<?php } ?>float:left;margin: 0 10px 0 0;">
                <span class="wpo_password_input_wrapper wpo_show_password">
                    <input type="password" id="pass1" name="<?php echo $first_field_name ?>" autocomplete="off" />
                </span>
                <div id="wpo_pass_strength"></div>
            </div>
            <div <?php if ( true == $hide_show_button ) { ?>class="wpo_hide_generate_button"<?php } ?> style="<?php if ( false == $hide_show_button ) { ?>width: calc( 50% - 10px );float:left;<?php } else { ?>width: calc( 35% - 10px );float:right;<?php } ?>">
                <?php
                WO()->get_button( __( 'Hide', WP_OFFICE_TEXT_DOMAIN ), array(
                    'class' => 'wpo_toggle_password wpo_password_visible hide-if-no-js',
                    'data-hide_text' => __( 'Hide', WP_OFFICE_TEXT_DOMAIN ),
                    'data-show_text' => __( 'Show', WP_OFFICE_TEXT_DOMAIN )
                ), array('is_admin' => false) );

                if ( false == $hide_show_button ) {
                    WO()->get_button( __( 'Cancel', WP_OFFICE_TEXT_DOMAIN ), array(
                        'class' => 'wpo_cancel_password hide-if-no-js'
                    ), array('is_admin' => false, 'only_text' => true ) );
                } ?>
            </div>
            <div class="description strength_description"></div>
            <div class="wpo_confirm_weak">
                <label for="pw_weak" data-title="<?php _e( 'Confirm weak password', WP_OFFICE_TEXT_DOMAIN ); ?>">
                    <input type="checkbox" name="pw_weak" id="pw_weak" />
                    <?php _e( 'Confirm use of weak password', WP_OFFICE_TEXT_DOMAIN ); ?>
                </label>
            </div>
        </div>

        <?php do_action('wpoffice_before_build_password_field', $edit_form, $first_field_name); ?>

        <script type="text/javascript">
            jQuery('#pass1').wpo_password_strength({
                block_indicator : jQuery('#wpo_pass_strength'),
                create_password : <?php echo !$edit_form || $hide_show_button ? 1 : 0 ?>
            });
        </script>
        <?php $fields = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $fields;
    }


    /**
     * @param string $edit_form
     * @param string $first_field_name
     * @return string
     */
    public function backend_build_password_form( $edit_form, $first_field_name = 'pass1', $validation = '' ) {
        wp_register_script(
            'wpo-password-strength',
            WO()->plugin_url . 'assets/js/plugins/jquery.wpo_password_strength.js',
            array(),
            WP_OFFICE_VER,
            true
        );

        wp_localize_script('wpo-password-strength', 'wpo_password_strength',
            apply_filters( 'wpoffice_password_strength_localize', array(
                'text' => array(
                    'strength' => array(
                        'very_weak' => __( "Very Weak", WP_OFFICE_TEXT_DOMAIN ),
                        'weak' => __( "Weak", WP_OFFICE_TEXT_DOMAIN ),
                        'medium' => __( "Medium", WP_OFFICE_TEXT_DOMAIN ),
                        'strong' => __( "Strong", WP_OFFICE_TEXT_DOMAIN )
                    )
                ),
                'generate_password_url' => WO()->get_ajax_url( 'generate_password' ),
                'edit_form' => $edit_form
            ) )
        );
        wp_print_scripts( array(
            'wpo-password-strength'
        ) );

        ob_start(); ?>
        <div class="wpo_button_password_wrapper">
            <?php WO()->get_button(
                ( ( $edit_form ) ? __( 'Generate Password', WP_OFFICE_TEXT_DOMAIN ) : __( 'Show Password', WP_OFFICE_TEXT_DOMAIN ) ),
                array(
                    'class'=>'wpo_generate_password'
                ),
                array(
                    'only_text' => true,
                    'ajax' => true
                )
            ); ?>
        </div>
        <div class="wpo_password_wrapper hide-if-js" data-wpo-valid="<?php echo $validation; ?>">
            <input class="hidden" value=" " />
            <div style="width: 50%;float:left;margin: 0 10px 0 0;">
                <span class="wpo_password_input_wrapper wpo_show_password">
                    <input type="password" id="pass1" name="<?php echo $first_field_name ?>" autocomplete="off" />
                </span>
                <div id="wpo_pass_strength"></div>
            </div>
            <div style="width: calc( 50% - 10px );float:left;">
                <?php WO()->get_button( __( 'Hide', WP_OFFICE_TEXT_DOMAIN ), array(
                    'class' => 'wpo_toggle_password wpo_password_visible hide-if-no-js',
                    'data-hide_text' => __( 'Hide', WP_OFFICE_TEXT_DOMAIN ),
                    'data-show_text' => __( 'Show', WP_OFFICE_TEXT_DOMAIN )
                ) );
                WO()->get_button( __( 'Cancel', WP_OFFICE_TEXT_DOMAIN ), array(
                    'class' => 'wpo_cancel_password hide-if-no-js'
                ),array(
                    'only_text' => true,
                ) ); ?>
            </div>
            <div class="description strength_description"></div>
            <div class="wpo_confirm_weak">
                <label>
                    <input type="checkbox" name="pw_weak" />
                    <?php _e( 'Confirm use of weak password', WP_OFFICE_TEXT_DOMAIN ); ?>
                </label>
            </div>
        </div>

        <?php do_action('wpoffice_before_build_password_field', $edit_form, $first_field_name); ?>

        <script type="text/javascript">
            jQuery('#pass1').wpo_password_strength({
                block_indicator : jQuery('#wpo_pass_strength'),
                create_password : <?php echo $edit_form ? 0 : 1 ?>
            });

            //jQuery('#pass1').wpo_password_strength('generate_password');
        </script>

        <!--pass_strength-->
        <style type="text/css">
            .wpo_button_password_wrapper {
                width: 100%;
                float:left;
                margin: 0;
                padding:0;
            }

            .wpo_password_wrapper {
                width: 100%;
                float:left;
                margin: 0;
                padding:0;
            }

            .wpo_confirm_weak {
                float:left;
                width:100%;
                margin: 10px 0 0 0;
            }

            .wpo_password_input_wrapper #pass1_text,
            .wpo_password_input_wrapper.wpo_show_password #pass1 {
                display: none;
            }

            .wpo_password_input_wrapper #pass1,
            .wpo_password_input_wrapper.wpo_show_password #pass1_text {
                display: block;
            }

            .wpo_toggle_password .wpo_hide_text,
            .wpo_toggle_password .dashicons-hidden,
            .wpo_toggle_password.wpo_password_visible .wpo_show_text,
            .wpo_toggle_password.wpo_password_visible .dashicons-visibility {
                display: none;
            }

            .wpo_toggle_password .wpo_show_text,
            .wpo_toggle_password .dashicons-visibility,
            .wpo_toggle_password.wpo_password_visible .wpo_hide_text,
            .wpo_toggle_password.wpo_password_visible .dashicons-hidden {
                display: inline-block;
            }

            #pass1,
            #pass1_text {
                margin: 0;
                float:left;
                width: 100% !important;
            }

            #wpo_pass_strength {
                float:left;
                width:100%;
                border-style: solid;
                border-width: 1px;
                margin: 0;
                padding: 3px 5px;
                text-align: center;
                background-color: #eee;
                border-color: #ddd !important;
                display: block;
                box-sizing: border-box;
                -moz-box-sizing: border-box;
                -webkit-box-sizing: border-box;
            }

            #wpo_pass_strength.short {
                background-color: #f1adad;
                border-color: #e35b5b !important;
            }

            #wpo_pass_strength.bad {
                background-color: #fbc5a9;
                border-color: #f78b53 !important;
            }

            #wpo_pass_strength.good {
                background-color: #ffe399;
                border-color: #ffc733 !important;
            }

            #wpo_pass_strength.strong {
                background-color: #c1e1b9;
                border-color: #83c373 !important;
            }
        </style>

        <?php
        $fields = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $fields;
    }


    /**
     * Delete Member
     *
     * @param $member_ids int Members ID
     * @return bool deleted count
     */
    function delete_members( $member_ids ) {
        if( !( is_numeric( $member_ids ) || is_array( $member_ids ) ) ) {
            return false;
        }

        if( is_numeric( $member_ids ) ) {
            $member_ids = array( $member_ids );
        }

        $deleted_count = 0;
        foreach( $member_ids as $member_id ) {
            if( !$this->get_member_data( $member_id ) ) {
                continue;
            }

            if( wp_delete_user( $member_id ) ) {
                WO()->delete_all_object_assigns( 'user', $member_id );
                WO()->delete_all_assign_assigns( 'member', $member_id );

                $deleted_count++;
            }
        }

        return $deleted_count;
    }


    /**
     * Restore from Archive Member
     *
     * @param $member_ids int Members ID
     * @return bool Result
     */
    function restore_members( $member_ids ) {
        if( !( is_numeric( $member_ids ) || is_array( $member_ids ) ) ) {
            return false;
        }

        if( is_numeric( $member_ids ) ) {
            $member_ids = array( $member_ids );
        }

        $restored_count = 0;
        foreach( $member_ids as $member_id ) {
            if( !$this->get_member_data( $member_id ) ) {
                continue;
            }

            if( delete_user_meta( $member_id, 'wpoffice_archived' ) ) {
                $restored_count++;
            }
        }

        return $restored_count;
    }



    /**
     * Archive Member
     *
     * @param $member_ids int Members ID
     * @return bool Result
     */
    function archive_members( $member_ids ) {
        if( !( is_numeric( $member_ids ) || is_array( $member_ids ) ) ) {
            return false;
        }

        if( is_numeric( $member_ids ) ) {
            $member_ids = array( $member_ids );
        }

        $archived_count = 0;
        foreach( $member_ids as $member_id ) {
            if( !$this->get_member_data( $member_id ) ) {
                continue;
            }

            if( update_user_meta( $member_id, 'wpoffice_archived', true ) ) {
                $archived_count++;
            }
        }

        return $archived_count;
    }

    /**
     * Save member userdata
     *
     * @param $userdata
     * @return int|WP_Error
     */
    function save_userdata( $userdata ) {

        $errors = array();
        //update member
        if( !empty( $userdata['ID'] ) ) {
            if( ! is_email( $userdata['user_email'] ) ) {
                $errors['user_email'] = __( 'Invalid Email.', WP_OFFICE_TEXT_DOMAIN );
            } else {
                $userdata['user_email'] = apply_filters( 'pre_user_email', $userdata['user_email'] );

                if ( $user_id = email_exists( $userdata['user_email'] ) ) {
                    if ( $user_id && $user_id != $userdata['ID'] ) {
                        $errors['user_email'] = __( 'Email address already in use.', WP_OFFICE_TEXT_DOMAIN );
                    }
                }
            }

            if ( $errors = apply_filters( 'wpoffice_update_member_validation', $errors, $userdata ) ) {
                return $errors;
            }

            $member_id = wp_update_user( $userdata );

            if ( is_wp_error( $member_id ) ) {
                return $member_id->get_error_message();
            }
        }
        //create member
        else {
            if ( empty( $userdata['user_login'] ) ) { // empty username
                $errors['user_login'] = __( 'Username is required.', WP_OFFICE_TEXT_DOMAIN );
            } elseif( username_exists( $userdata['user_login'] ) ) { // username exist
                $errors['user_login'] = __( 'Username already exists.', WP_OFFICE_TEXT_DOMAIN );
            }

            $userdata['user_email'] = apply_filters( 'pre_user_email',
                isset( $userdata['user_email'] ) ? $userdata['user_email'] : '' );

            if( empty( $userdata['user_email'] ) ) { // empty email
                $errors['user_email'] = __( 'Email is required.', WP_OFFICE_TEXT_DOMAIN );
            } elseif( !is_email( $userdata['user_email'] ) ) { // not email
                $errors['user_email'] = __( 'Invalid Email.', WP_OFFICE_TEXT_DOMAIN );
            } else if ( email_exists( $userdata['user_email'] ) ) { // email already exists
                $errors['user_email'] = __( 'Email address already in use.', WP_OFFICE_TEXT_DOMAIN );
            }

            if ( empty( $userdata['user_pass'] ) ) {
                $errors['user_pass'] = __( 'Password is required.', WP_OFFICE_TEXT_DOMAIN );
            }

            //our_hook
            $errors = apply_filters( 'wpoffice_create_member_validation', $errors, $userdata );

            if ( $errors ) {
                return $errors;
            }

            //new member
            $member_id = wp_insert_user( $userdata );

            if ( is_wp_error( $member_id ) ) {
                return $member_id->get_error_message();
            }

        }


        //for assign avatar
        if ( ! empty( $userdata['user_avatar'] ) ) {
            $avatars_dir = WO()->get_upload_dir( 'wpoffice/_avatars/', 'allow' );


            //remove old avatar
            $avatar = get_user_meta( $member_id, 'wpo_avatar', true );

            if ( ! empty( $avatar ) ) {
                if ( file_exists( $avatars_dir . $avatar ) ) {
                    unlink( $avatars_dir . $avatar );
                }
            }

            delete_user_meta( $member_id, 'wpo_avatar' );

            //if uploaded new avatar and it situated at temp dir
            if ( strpos( $userdata['user_avatar'], 'temp_' ) === 0 ) {
                $temp_avatars_dir = WO()->get_upload_dir( 'wpoffice/_temp_avatars/', 'allow' );

                if ( file_exists( $temp_avatars_dir . $userdata['user_avatar'] ) ) {

                    //delete temp files
                    /*$files = scandir( $temp_avatars_dir );
                    $current_time = time();
                    foreach( $files as $file ) {
                        if( $file != "." && $file != ".." ) {
                            if( file_exists( $temp_avatars_dir . DIRECTORY_SEPARATOR . $file ) ) {
                                if( strpos( $file, 'temp_' ) === 0 ) {
                                    $name_array = explode( '_', $file );
                                    if( isset( $name_array[1] ) && is_numeric( $name_array[1] ) && ( $current_time - $name_array[1] ) > 60*60*24 ) {
                                        unlink( $temp_avatars_dir . DIRECTORY_SEPARATOR . $file );
                                    }
                                }

                                if( strpos( $file, md5( $member_id . 'wpo_avatar' ) ) === 0 ) {
                                    unlink( $temp_avatars_dir . DIRECTORY_SEPARATOR . $file );
                                }
                            }
                        }
                    }*/

                    //rename avatar from temp and save in user meta
                    $fileinfo = pathinfo( $temp_avatars_dir . $userdata['user_avatar'] );

                    $avatar_file = md5( $member_id . 'wpo_avatar' ) . time() . '.' . $fileinfo['extension'];
                    rename( $temp_avatars_dir . $userdata['user_avatar'] , $avatars_dir . $avatar_file );
                    update_user_meta( $member_id, 'wpo_avatar', $avatar_file );
                }
            }
        }

        /*wpo_hook_
            hook_name: wpoffice_member_saved
            hook_title: Member Saved
            hook_description: Hook runs after member data saved (for exist or new member).
            hook_type: action
            hook_in: wp-office
            hook_location class-member.php
            hook_param: int member_id, array userdata
            hook_since: 1.0.0
        */
        do_action( 'wpoffice_member_saved', $member_id, $userdata );

        if( !empty( $userdata['ID'] ) ) {
            /*wpo_hook_
                hook_name: wpoffice_member_updated
                hook_title: Member Updated
                hook_description: Hook runs after member data saved (updated) just of exist member.
                hook_type: action
                hook_in: wp-office
                hook_location class-member.php
                hook_param: int member_id, array userdata
                hook_since: 1.0.0
            */
            do_action( 'wpoffice_member_updated', $member_id, $userdata );
        } else {
            /*wpo_hook_
                hook_name: wpoffice_member_created
                hook_title: Member Updated
                hook_description: Hook runs after member data saved (created) just of new member.
                hook_type: action
                hook_in: wp-office
                hook_location class-member.php
                hook_param: int member_id, array userdata
                hook_since: 1.0.0
            */
            do_action( 'wpoffice_member_created', $member_id, $userdata );
        }

        return $member_id;
    }

    /**
     * Get all clents information
     *
     * @since 1.0.0
     * @param $id
     * @return bool|object
     */
    function get_member_data( $id ) {
        if( !empty( $id ) && is_numeric( $id ) ) {
            return get_userdata( $id );
        }

        return false;
    }

    //end class
}