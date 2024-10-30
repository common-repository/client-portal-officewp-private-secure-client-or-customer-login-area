<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'wpoffice_settings_gateways', array( WO()->hooks(), 'wpo\gateways\paypal\Paypal->get_info' ) );
add_filter( 'wpoffice_pre_get_settings_paypal', array( WO()->hooks(), 'wpo\gateways\paypal\Paypal>>prepare_settings' ) );
if ( is_admin() ) {
}
