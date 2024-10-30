<?php
/**
 * Template Name: Payment method
 * Template Description: Output a single payment method
 *
 * This template can be overridden by copying it to yourtheme/wp-office/checkout/payment-method.php.
 *
 * HOWEVER, on occasion WP-Office will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author 	WP-Office
 * @version     1.0.0
 */

//needs for translation
__( 'Payment method', WP_OFFICE_TEXT_DOMAIN );
__( 'Output a single payment method', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="wpo_payment_method payment_method_<?php echo $gateway->id; ?>">
    <input id="payment_method_<?php echo $gateway->id; ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

    <label for="payment_method_<?php echo $gateway->id; ?>">
        <?php echo $gateway->get_title(); ?> <?php //echo $gateway->get_icon(); ?>
    </label>
    <?php if ( $gateway->has_fields() || $gateway->get_description() ) { ?>
        <div class="payment_box payment_method_<?php echo $gateway->id; ?>" <?php if ( false && ! $gateway->chosen ) : ?>style="display:none;"<?php endif; ?>>
            <?php $gateway->payment_fields(); ?>
        </div>
    <?php } ?>
</li>
