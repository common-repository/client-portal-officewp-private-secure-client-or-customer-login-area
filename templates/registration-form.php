<?php
/**
 * Template Name: Registration Form
 * Template Description: - Displays Registration Form fields
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/registration-form.php
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
__( 'Registration Form', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Member Profile Form fields', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<form method="post" class="wpo_registration_form wpo_frontend_form" autocomplete="off">

    <?php
    /*wpo_hook_
        hook_name: wpoffice_registration_form_header
        hook_title: Template Registration Form Header
        hook_description: Hook runs in registration form header.
        hook_type: action
        hook_in: wp-office
        hook_location registration-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_registration_form_header' );

    ?>

    <div class="wpo_form_message"></div>

    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="user_login" data-title="<?php _e( 'Username', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php _e( 'Username', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" class="wpo_input wpo_input_text" name="user_login" id="user_login" autocomplete="off" data-wpo-valid="required" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label for="user_email" class="wpo_label" data-title="<?php _e( 'Email', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php _e( 'Email', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
        </div>
        <div class="wpo_row_field">
            <input type="email" class="wpo_input wpo_input_email" name="user_email" id="user_email" autocomplete="off" data-wpo-valid="required email" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="first_name"><?php _e( 'First Name', WP_OFFICE_TEXT_DOMAIN ) ?></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" class="wpo_input wpo_input_text" name="first_name" id="first_name" autocomplete="off" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="last_name"><?php _e( 'Last Name', WP_OFFICE_TEXT_DOMAIN ) ?></label>
        </div>
        <div class="wpo_row_field">
            <input type="text" class="wpo_input wpo_input_text" name="last_name" id="last_name" autocomplete="off" />
        </div>
    </div>
    <div class="wpo_form_row">
        <div class="wpo_row_label">
            <label class="wpo_label" for="pass1_text" data-title="<?php _e( 'Password', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php _e( 'Password', WP_OFFICE_TEXT_DOMAIN ) ?> <span class="required">*</span></label>
        </div>
        <div class="wpo_row_field" style="position:relative;">
            <?php echo WO()->members()->frontend_build_password_form( false, 'user_pass' ); ?>
        </div>
    </div>
    <div class="clear"></div>

    <?php
    /*wpo_hook_
        hook_name: wpoffice_registration_form
        hook_title: Template Registration Form
        hook_description: Hook runs in registration form.
        hook_type: action
        hook_in: wp-office
        hook_location registration-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_registration_form' );

    ?>

    <div class="wpo_form_row">
        <div class="wpo_row_label">
            &nbsp;
        </div>
        <div class="wpo_row_field">
            <input type="hidden" name="wpo_role" value="<?php echo $role ?>" />
            <?php wp_nonce_field( 'wpofficeregistrationform' . $role );
            WO()->get_button( __( 'Submit Registration', WP_OFFICE_TEXT_DOMAIN ), array( 'class'=>'wpo_save_form wpo_button_submit wpo_registration_button' ), array( 'primary' => true, 'ajax'=> true, 'is_admin' => false ) ); ?>
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
        hook_name: wpoffice_registration_form_footer
        hook_title: Template Registration Form Footer
        hook_description: Hook runs in registration form footer.
        hook_type: action
        hook_in: wp-office
        hook_location registration-form.php
        hook_param:
        hook_since: 1.0.0
    */
    do_action( 'wpoffice_registration_form_footer' );

    ?>

</form>
