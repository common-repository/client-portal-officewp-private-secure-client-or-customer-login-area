<?php
/**
 * Template Name: Thank You Page
 * Template Description: - Displays Thank You Page
 *
 * This template can be overridden by copying it to your_current_theme/wp-office/checkout/thank_you.php
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
__( 'Thank You Page', WP_OFFICE_TEXT_DOMAIN );
__( 'Displays Thank You Page', WP_OFFICE_TEXT_DOMAIN );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="wpo_thank_you_page">
    <?php _e( 'Thank you for the payment.', WP_OFFICE_TEXT_DOMAIN ) ?>
    <?php printf( __( 'To continue click %shere%s.', WP_OFFICE_TEXT_DOMAIN ),
            '<a href="' . WO()->get_page_slug( 'hub_page' ) . '">', '</a>' ); ?>
</div>

