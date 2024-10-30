<?php
/**
 * Template Name: Billing Information
 * Template Description: Checkout Billing Information Form
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/checkout/billing.php
 *
 * HOWEVER, on occasion WP-Office will need to update template files and you (the theme developer)
 * will need to copy the new files to your theme to maintain compatibility. We try to do this
 * as little as possible, but it does happen. When this occurs the version of the template file will
 * be bumped and the readme will list any important changes.
 *
 * @author  WP-Office
 * @version 1.0.0
 */

//needs for translation
__( 'Billing Information', WP_OFFICE_TEXT_DOMAIN );
__( 'Checkout Billing Information Form', WP_OFFICE_TEXT_DOMAIN );

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<div class="wpo-billing-fields">
    <h3><?php _e( 'Billing Details', WP_OFFICE_TEXT_DOMAIN ); ?></h3>

    <p class="wpo_half" id="billing_first_name_field">
        <label for="billing_first_name"><?php _e( 'First Name', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input type="text" data-wpo-valid="required" name="billing_first_name" id="billing_first_name" placeholder="" autocomplete="given-name" value="<?php echo $data['billing_first_name']; ?>">
    </p>
    <p class="wpo_half" id="billing_last_name_field">
        <label for="billing_last_name"><?php _e( 'Last Name', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input type="text" data-wpo-valid="required" name="billing_last_name" id="billing_last_name" placeholder="" autocomplete="family-name" value="<?php echo $data['billing_last_name']; ?>">
    </p><div class="clear"></div>
    <p class="wpo_half" id="billing_email_field">
        <label for="billing_email"><?php _e( 'Email Address', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input type="email" data-wpo-valid="required email" name="billing_email" id="billing_email" placeholder="" autocomplete="email" value="<?php echo $data['billing_email']; ?>">
    </p>
    <p class="wpo_half" id="billing_phone_field">
        <label for="billing_phone"><?php _e( 'Phone', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input type="tel" data-wpo-valid="required phone" name="billing_phone" id="billing_phone" placeholder="" autocomplete="tel" value="<?php echo $data['billing_phone']; ?>">
    </p>
    <div class="clear"></div>
    <p id="billing_country_field">
        <label for="billing_country" class=""><?php _e( 'Country', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <select data-wpo-valid="required" name="billing_country" id="billing_country" autocomplete="country" class="country_to_state country_select " tabindex="-1" title="Country *">
            <option value=""><?php _e( 'Select a country', WP_OFFICE_TEXT_DOMAIN ) ?></option>
            <?php foreach ( $data['countries'] as $ckey => $cvalue ) {
                    echo '<option value="' . esc_attr( $ckey ) . '" '. selected( $data['billing_country'], $ckey, false ) . '>'. __( $cvalue, WP_OFFICE_TEXT_DOMAIN ) .'</option>';
                } ?>
        </select>
    </p>
    <p id="billing_address_1_field">
        <label for="billing_address_1"><?php _e( 'Address', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input data-wpo-valid="required" type="text" name="billing_address_1" id="billing_address_1" placeholder="<?php _e( 'Street address', WP_OFFICE_TEXT_DOMAIN ); ?>" autocomplete="address-line1" value="<?php echo $data['billing_address_1']; ?>">
    </p>
    <p id="billing_address_2_field">
        <input type="text" name="billing_address_2" id="billing_address_2" placeholder="<?php _e( 'Apartment, suite, unit etc. (optional)', WP_OFFICE_TEXT_DOMAIN ); ?>" autocomplete="address-line2" value="<?php echo $data['billing_address_2']; ?>">
    </p>
    <p id="billing_city_field">
        <label for="billing_city"><?php _e( 'City', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input data-wpo-valid="required" type="text" name="billing_city" id="billing_city" placeholder="" autocomplete="address-level2" value="<?php echo $data['billing_city']; ?>">
    </p>
    <p class="validate-state wpo_half" id="billing_state_field">
        <label for="billing_state"><?php _e( 'State / Region', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input id="billing_state">
    </p>
    <p class="wpo_half" id="billing_postcode_field">
        <label for="billing_postcode"><?php _e( 'Postcode / ZIP', WP_OFFICE_TEXT_DOMAIN ); ?><span class="wpo_required">*</span></label>
        <input data-wpo-valid="required postcode" type="text" name="billing_postcode" id="billing_postcode" placeholder="" autocomplete="postal-code" value="<?php echo $data['billing_postcode']; ?>">
    </p>
    <div class="clear"></div>
</div>