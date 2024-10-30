<?php
/**
 * Template Name: Profile Form
 * Template Description: - Displays Member Profile Form fields
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/profile-from.php
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
__( 'Profile Form', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Member Profile Form fields', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<form method="post" class="wpo_profile_form wpo_frontend_form">

    <?php
    /*wpo_hook_
        hook_name: wpoffice_profile_form_header
        hook_title: Template Profile Form Header
        hook_description: Hook runs in profile form header.
        hook_type: action
        hook_in: wp-office
        hook_location profile-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_profile_form_header' );

    ?>

    <div class="wpo_form_message"></div>

    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="username" data-title="<?php _e( 'Username', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php _e( 'Username', WP_OFFICE_TEXT_DOMAIN ) ?></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" id="username" class="wpo_input wpo_input_text" value="<?php echo !empty( $member_data->data->user_login ) ? $member_data->data->user_login : '' ?>" disabled />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="user_email" data-title="<?php _e( 'Email', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php _e( 'Email', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
        </div>
        <div class="wpo_row_field">
            <input type="email" class="wpo_input wpo_input_email" name="user_email" id="user_email" autocomplete="off" data-wpo-valid="required email" value="<?php echo !empty( $member_data->data->user_email ) ? $member_data->data->user_email : '' ?>" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="first_name"><?php _e( 'First Name', WP_OFFICE_TEXT_DOMAIN ) ?></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" class="wpo_input wpo_input_text" name="first_name" id="first_name" autocomplete="off" value="<?php echo get_user_meta( $member_data->ID, 'first_name', true ) ? get_user_meta( $member_data->ID, 'first_name', true ) : '' ?>" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="last_name"><?php _e( 'Last Name', WP_OFFICE_TEXT_DOMAIN ) ?></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" class="wpo_input wpo_input_text" name="last_name" id="last_name" autocomplete="off" value="<?php echo get_user_meta( $member_data->ID, 'last_name', true ) ? get_user_meta( $member_data->ID, 'last_name', true ) : '' ?>" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="pass1_text" data-title="<?php _e( 'Password', 'wp-office' ) ?>"><?php _e( 'Password', 'wp-office' ) ?></label>
        </div>
        <div class="wpo_row_field" style="position:relative;">
            <?php echo WO()->members()->frontend_build_password_form( true, 'user_pass' ); ?>
        </div>
    </div>

    <?php
    /*wpo_hook_
        hook_name: wpoffice_profile_form
        hook_title: Template Profile Form
        hook_description: Hook runs in profile form.
        hook_type: action
        hook_in: wp-office
        hook_location profile-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_profile_form' );

    ?>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            &nbsp;
        </div>
        <div class="wpo_row_field">
            <input type="hidden" name="wpo_member_id" value="<?php echo $member_data->ID ?>" />
            <?php wp_nonce_field( 'wpofficeprofileform-' . $member_data->ID );
            WO()->get_button( __( 'Update', WP_OFFICE_TEXT_DOMAIN ), array( 'class'=>'wpo_save_form wpo_button_submit wpo_profile_button' ), array( 'primary' => true, 'ajax'=> true, 'is_admin' => false ) ); ?>
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">&nbsp;</div>
        <div class="wpo_row_field">
            <div class="wpo_validation_info"></div>
        </div>
    </div>
    <div class="clear"></div>

    <?php

    /*wpo_hook_
        hook_name: wpoffice_profile_form_footer
        hook_title: Template Profile Form Footer
        hook_description: Hook runs in profile form footer.
        hook_type: action
        hook_in: wp-office
        hook_location profile-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_profile_form_footer' );

    ?>

</form>
