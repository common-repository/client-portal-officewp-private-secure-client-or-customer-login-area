<?php
/**
 * Template Name: Order Info
 * Template Description: Order Information Table on Checkout page
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/checkout/order-info.php.
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
__( 'Order Info', WP_OFFICE_TEXT_DOMAIN );
__( 'Order Information Table on Checkout page', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<table class="wpoffice-checkout-order-info-table wpo_no_border" style="border-collapse: collapse;">
    <thead style="border-bottom: 3px solid #DDD; text-transform: uppercase;">
        <tr>
            <th class="product-name"><?php _e( 'Product', WP_OFFICE_TEXT_DOMAIN ); ?></th>
            <th class="product-total wpo_right_text"><?php _e( 'Total', WP_OFFICE_TEXT_DOMAIN ); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr class="order-total">
            <th><?php echo $order['order_title']; ?></th>
            <td class="wpo_right_text"><?php echo $order['nice_total']; ?></td>
        </tr>
    </tbody>
</table>
