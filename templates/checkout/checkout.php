<?php
/**
 * Template Name: Checkout
 * Template Description: - Displays Checkout Form
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/checkout/checkout.php
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
__( 'Checkout', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Checkout Form', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form name="checkout" method="post" id="wpo_checkout_payment" class="wpo-checkout-payment">

        <?php do_action( 'wpoffice_checkout_before_billing_details' ); ?>

        <div id="wpo_billing_details">
            <?php WO()->get_template( 'checkout/billing.php', '', array( 'data' => $billing_data ) ); ?>
        </div>

        <?php do_action( 'wpoffice_checkout_after_billing_details' ); ?>

        <?php do_action( 'wpoffice_checkout_before_order_info' ); ?>
        <h3 id="order_review_heading"><?php _e( 'Your order', WP_OFFICE_TEXT_DOMAIN ); ?></h3>
        <div id="wpo_order_info">
            <?php WO()->get_template( 'checkout/order-info.php', '', array( 'order' => $order ) ); ?>
        </div>
        <?php do_action( 'wpoffice_checkout_after_order_info' ); ?>

        <?php if ( $order['total'] > 0 ) { ?>
            <span class="wpo_required">*</span>
            <ul class="wpo_payment_methods" data-wpo-valid="required">
                <?php
                    if ( ! empty( $available_gateways ) ) {
                        foreach ( $available_gateways as $gateway ) {
                            WO()->get_template( 'checkout/payment-method.php', '', array( 'gateway' => $gateway ) );
                        }
                    } else {
                        echo '<li>' . apply_filters( 'wpoffice_no_available_payment_methods_message',
                                __( 'Please fill in your details above to see available payment methods.', WP_OFFICE_TEXT_DOMAIN ) ) . '</li>';
                    }
                ?>
            </ul>
        <?php } ?>
            <div class="wpo_clear"></div>
        <input type="submit" class="" name="wpoffice_checkout_pay" id="checkout_pay" value="<?php echo esc_attr( $checkout_button_text ) ?>" data-value="<?php echo esc_attr( $checkout_button_text ) ?>">
        <?php wp_nonce_field( 'wpoffice-process_checkout' ); ?>

</form>

