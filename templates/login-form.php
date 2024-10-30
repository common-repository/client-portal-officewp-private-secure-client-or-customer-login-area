<?php
/**
 * Template Name: Login Form
 * Template Description: - Displays Login Form fields
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/login-form.php
 *
 * HOWEVER, on occasion WP-Office will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @author  WP-Office
 * @version 1.0.0
 */

//needs for translation
__( 'Login Form', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Login Form fields', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<form method="post" class="wpo_login_form wpo_frontend_form <?php if( empty( $custom_style['disable_plugin_css'] ) || 'yes' != $custom_style['disable_plugin_css'] ) { ?>wpo_designed<?php } ?> wpo_login_<?php echo $action ?>_form">

    <?php

    /*wpo_hook_
        hook_name: wpoffice_login_form_header
        hook_title: Template Login Form Header
        hook_description: Hook runs in login form header.
        hook_type: action
        hook_in: wp-office
        hook_location login-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_login_form_header' );

    ?>

    <div class="<?php echo $message_class ?>"><?php if ( !empty( $message ) ) echo wptexturize( $message ) ?></div>

    <?php switch ( $action ) {
        case 'lostpassword' :
        case 'retrievepassword' : { ?>

            <div class="wpo_form_row">
                <div class="wpo_row_label">
                    <label class="wpo_label" for="user_login" ><?php _e( 'Username or Email', WP_OFFICE_TEXT_DOMAIN ) ?>
                </div>
                <div class="wpo_row_field">
                    <input type="text" name="user_login" id="user_login" class="wpo_input wpo_input_text" value="" size="20" placeholder="<?php _e('Username or Email', WP_OFFICE_TEXT_DOMAIN) ?>" /></label>
                    <div class="dashicons dashicons-admin-users username-icon"></div>
                </div>
            </div>
            <?php
            /**
             * Fires inside the lostpassword form tags, before the hidden fields.
             *
             * @since 2.1.0
             */
            do_action( 'lostpassword_form' ); ?>

            <div class="wpo_form_row">
                <?php WO()->get_button( __( 'Get New Password', WP_OFFICE_TEXT_DOMAIN ), array( 'class'=>'wpo_save_form wpo_button_submit wpo_get_new_pass_button' ), array( 'primary' => true, 'is_admin' => false ) ); ?>
            </div>

            <div class="lost_password">
                <a class="wpo_link" href="<?php echo esc_url( $login_url ); ?>"><?php _e( 'Remember your password?', WP_OFFICE_TEXT_DOMAIN ) ?></a>
            </div>

            <?php break;
        }
    case 'resetpass' :
    case 'rp' : {
        $rp_key = !empty( $_GET['key'] ) ? $_GET['key'] : '';
        $rp_login = !empty( $_GET['login'] ) ? $_GET['login'] : ''; ?>
            <div class="wpo_form_row">
                <div class="wpo_row_label">
                    <label class="wpo_label" for="pass1" ><?php _e('New password', WP_OFFICE_TEXT_DOMAIN) ?>
                </div>
                <div class="wpo_row_field">
                    <?php echo WO()->members()->frontend_build_password_form( false, 'user_pass', true ); ?>
                </div>
            </div>

            <div class="wpo_form_row">
                <input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
                <input type="hidden" id="user_login" value="<?php echo urldecode( esc_attr( $rp_login ) ); ?>" autocomplete="off" />
                <?php WO()->get_button( __('Reset Password', WP_OFFICE_TEXT_DOMAIN), array( 'class'=>'wpo_save_form wpo_button_submit wpo_reset_pass_button' ), array( 'primary' => true, 'is_admin' => false ) ); ?>
            </div>
            <div class="clear"></div>

        <?php
        /**
         * Fires following the 'Strength indicator' meter in the user password reset form.
         *
         * @since 3.9.0
         *
         * @param WP_User $user User object of the user whose password is being reset.
         */
        do_action( 'resetpass_form', get_user_by( 'user_login', $rp_login ) );
        break;
    }
    case 'login' :
    default : { ?>
        <div class="wpo_form_row">
            <div class="wpo_row_label">
                <label class="wpo_label" for="username"><?php _e( 'Username or email', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
            </div>
            <div class="wpo_row_field">
                <input type="text" class="wpo_input wpo_input_text" name="username" id="username" placeholder="<?php _e( 'Username or email', WP_OFFICE_TEXT_DOMAIN ) ?>" />
                <div class="dashicons dashicons-admin-users username-icon"></div>
            </div>
        </div>
        <div class="wpo_form_row">
            <div class="wpo_row_label">
                <label class="wpo_label" for="password"><?php _e( 'Password', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
            </div>
            <div class="wpo_row_field">
                <input class="wpo_input wpo_input_password" type="password" name="password" id="password" placeholder="<?php _e( 'Password', WP_OFFICE_TEXT_DOMAIN ) ?>"/>
                <div class="dashicons dashicons-lock password-icon"></div>
            </div>
        </div>
        <div class="clear"></div>

        <?php
        /*wpo_hook_
                hook_name: wpoffice_login_form
                hook_title: Template Login Form
                hook_description: Hook runs in login form.
                hook_type: action
                hook_in: wp-office
                hook_location login-form.php
                hook_param:
                hook_since: 1.0.0
            */
        do_action( 'wpoffice_login_form' );

        ?>

        <div class="wpo_form_row">
            <label for="rememberme" class="wpo_label">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever" class="wpo_input wpo_input_checkbox" /> <?php _e( 'Remember me', WP_OFFICE_TEXT_DOMAIN ) ?>
            </label>
            <?php wp_nonce_field( 'wpofficeloginform' );
            WO()->get_button( __('Login', WP_OFFICE_TEXT_DOMAIN), array( 'class'=>'wpo_save_form wpo_button_submit wpo_login_button' ), array( 'primary' => true, 'is_admin' => false ) ); ?>
        </div>
        <div class="lost_password">
            <a class="wpo_link" href="<?php echo esc_url( wp_lostpassword_url() ) ?>"><?php _e( 'Lost your password?', WP_OFFICE_TEXT_DOMAIN ) ?></a>
        </div>

        <div class="clear"></div>

        <?php
        /*wpo_hook_
                hook_name: wpoffice_login_form_footer
                hook_title: Template Login Form Footer
                hook_description: Hook runs in login form footer.
                hook_type: action
                hook_in: wp-office
                hook_location login-form.php
                hook_param:
                hook_since: 1.0.0
            */
        do_action( 'wpoffice_login_form_footer' );
        break;
    }
    } ?>

</form>