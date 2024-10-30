<?php
namespace wpo\gateways\paypal;

/**
 * PayPal Standard Payment Gateway.
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		Paypal
 * @version		1.0
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\gateways\Gateway;

class Paypal extends Gateway {

    /**
     * @var Paypal The single instance of the class
     * @since 1.0
     */
    protected static $_instance;


    /**
     * Email
     * @var string
     */
    public $email;

    /**
     * Main Paypal Instance
     *
     * Ensures only one instance of Paypal is loaded or can be loaded.
     *
     * @since 1.0
     * @static
     * @see Paypal()
     * @return Paypal - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    private function __construct() {
        $this->id                   = 'paypal';
        $this->has_fields           = false;
        $this->order_button_text    = __( 'Proceed to PayPal', WP_OFFICE_TEXT_DOMAIN );
        $this->supports             = array(
                'products',
                'refunds',
        );
        // Define user set variables.
        $this->init_property();
    }


    /**
     * Get the state to send to paypal.
     * @param  string $cc
     * @param  string $state
     * @return string
     */
    protected function get_paypal_state( $cc, $state ) {
        if ( 'US' === $cc ) {
            return $state;
        }

        $gateways = \wpo\gateways\Payment_Gateways::instance();

        $states = $gateways->get_states( $cc );

        if ( isset( $states[ $state ] ) ) {
            return $states[ $state ];
        }

        return $state;
    }


    function update_status( $new_status, $note = '' ) {
        WO()->update_object_meta( $this->payment_id, 'status', $new_status );
        if ( $note ) {
            WO()->update_object_meta( $this->payment_id, 'status_note', $note );
        }

        return true;

    }


    /**
     * Check receiver email from PayPal. If the receiver email in the IPN is different than what is stored in.
     * Office -> Settings -> Gateways -> PayPal, it will log an error about it.
     * @param array $order
     * @param string $receiver_email
     */
    private function validate_receiver_email( $receiver_email ) {
        $current_paypal_email = $this->email;
        if ( strcasecmp( trim( $receiver_email ), trim( $current_paypal_email ) ) != 0 ) {
            error_log( "IPN Response is for another account: {$receiver_email}. Your email is {$current_paypal_email}" );

            // Put this order on-hold for manual checking.
            $this->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s).', WP_OFFICE_TEXT_DOMAIN ), $receiver_email ) );
            exit;
        }
    }


    /**
     * Check payment amount from IPN matches the order.
     * @param array $order
     * @param int $amount
     */
    private function validate_amount( $order, $amount ) {
        if ( number_format( $order['total'], 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
            error_log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

            // Put this order on-hold for manual checking.
            $this->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', WP_OFFICE_TEXT_DOMAIN ), $amount ) );
            exit;
        }
    }


    /**
     * Check currency from IPN matches the order.
     * @param array $order
     * @param string $currency
     */
    private function validate_currency( $order, $currency ) {
        if ( $order['currency'] != $currency ) {
            error_log( 'Payment error: Currencies do not match (sent "' . $order['currency'] . '" | returned "' . $currency . '")' );

            // Put this order on-hold for manual checking.
            $this->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', WP_OFFICE_TEXT_DOMAIN ), $currency ) );
            exit;
        }
    }


    /**
     * Check for a valid transaction type.
     * @param string $txn_type
     */
    private function validate_transaction_type( $txn_type ) {
        $accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money' );

        if ( !in_array( strtolower( $txn_type ), $accepted_types ) ) {
            error_log( 'Aborting, Invalid type:' . $txn_type );
            exit;
        }
    }


    /**
     * Handle a cancelled reveral.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_canceled_reversal( $order, $posted ) {
        $this->send_ipn_email_notification(
                sprintf( __( 'Reversal cancelled for order %s', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'] ), sprintf( __( 'Order %s has had a reversal cancelled.', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'] )
        );
    }


    /**
     * Handle a reveral.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_reversed( $order, $posted ) {
        $this->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', WP_OFFICE_TEXT_DOMAIN ), sanitize_text_field( $posted['payment_status'] ) ) );

        $this->send_ipn_email_notification(
                sprintf( __( 'Payment for order %s reversed', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'] ), sprintf( __( 'Order %s has been marked on-hold due to a reversal - PayPal reason code: %s', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'], sanitize_text_field( $posted['reason_code'] ) )
        );
    }


    /**
     * Handle a refunded order.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_refunded( $order, $posted ) {
        // Only handle full refunds, not partial.
        if ( $order['total']== ( $posted['mc_gross'] * -1 ) ) {

            // Mark order as refunded.
            $this->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', WP_OFFICE_TEXT_DOMAIN ), strtolower( $posted['payment_status'] ) ) );

            $this->send_ipn_email_notification(
                    sprintf( __( 'Payment for order %s refunded', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'] ), sprintf( __( 'Order %s has been marked as refunded - PayPal reason code: %s', WP_OFFICE_TEXT_DOMAIN ), $order['order_title'], sanitize_text_field( $posted['reason_code'] ) )
            );
        }
    }


    /**
     * Handle a voided payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_voided( $order, $posted ) {
        $this->payment_status_failed( $order, $posted );
    }


    /**
     * Handle an expired payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_expired( $order, $posted ) {
        $this->payment_status_failed( $order, $posted );
    }


    /**
     * Handle a denied payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_denied( $order, $posted ) {
        $this->payment_status_failed( $order, $posted );
    }


    /**
     * Handle a failed payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_failed( $order, $posted ) {
        $this->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', WP_OFFICE_TEXT_DOMAIN ), sanitize_text_field( $posted['payment_status'] ) ) );
    }


    /**
     * Handle a pending payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_pending( $order, $posted ) {
        $this->payment_status_completed( $order, $posted );
    }


    /**
     * Handle a completed payment.
     * @param array $order
     * @param array $posted
     */
    private function payment_status_completed( $order, $posted ) {
        $completed = self::is_already_complete( $order['order_id'] );
        if ( $completed ) {
            exit;
        }

        $this->validate_transaction_type( $posted['txn_type'] );
        $this->validate_currency( $order, $posted['mc_currency'] );
        $this->validate_amount( $order, $posted['mc_gross'] );
        $this->validate_receiver_email( $posted['receiver_email'] );

        if ( 'completed' === $posted['payment_status'] ) {
            $this->update_status( 'completed', sprintf( __( 'Payment %s via IPN.', WP_OFFICE_TEXT_DOMAIN ), sanitize_text_field( $posted['payment_status'] ) ) );
        } else {
            $this->update_status( 'on-hold', sprintf( __( 'Payment pending: %s', WP_OFFICE_TEXT_DOMAIN ), $posted['pending_reason'] ) );
        }
    }


    /**
     * Get phone number args for paypal request.
     * @param  array $data
     * @return array
     */
    protected function get_phone_number_args( $data ) {
        if ( in_array( $data['billing_country'], array( 'US','CA' ) ) ) {
            $phone_number = str_replace( array( '(', '-', ' ', ')', '.' ), '', $data['billing_phone'] );
            $phone_number = ltrim( $phone_number, '+1' );
            $phone_args   = array(
                'night_phone_a' => substr( $phone_number, 0, 3 ),
                'night_phone_b' => substr( $phone_number, 3, 3 ),
                'night_phone_c' => substr( $phone_number, 6, 4 ),
                'day_phone_a' 	=> substr( $phone_number, 0, 3 ),
                'day_phone_b' 	=> substr( $phone_number, 3, 3 ),
                'day_phone_c' 	=> substr( $phone_number, 6, 4 )
            );
        } else {
            $phone_args = array(
                'night_phone_b' => $data['billing_phone'],
                'day_phone_b' 	=> $data['billing_phone']
            );
        }
        return $phone_args;
    }


    function ipn( $order_id ) {
        $order = self::get_order_data_by_order_id( $order_id );

        if ( isset( $_POST ) && $order ) {
            $posted = wp_unslash( $_POST );

            if( !$this->check_ipn_notification( $posted ) ) {
                wp_die( 'PayPal IPN Request Failure', 'PayPal IPN', array( 'response' => 500 ) );
            }


            if( !$this->data_verification( $posted, $order ) ) {
                die('IPN verification failed 2');
            }
/*
            $payment_data = array();
            $payment_data['transaction_status'] = isset( $_POST["payment_status"] ) ? $_POST["payment_status"] : '';
            $payment_data['transaction_id'] = null;
            $payment_data['subscription_id'] = null;
            $payment_data['subscription_status'] = null;
            $payment_data['parent_txn_id'] = null;
            $payment_data['transaction_type'] = '';

            $_POST["txn_type"] = isset( $_POST["txn_type"] ) ? $_POST["txn_type"] : '';

            switch( $_POST["txn_type"] ) {
                case 'recurring_payment':
                    $payment_data['transaction_type'] = 'subscription_start';
                    $payment_data['transaction_id'] = $_POST['txn_id'];
                    $payment_data['subscription_id'] = isset( $order['subscription_id'] ) ? $order['subscription_id'] : null;

                    if ( 'Completed' == $payment_data['transaction_status'] ) {
                        $payment_data['subscription_status'] = 'active';
                    } else {
                        $payment_data['subscription_status'] = 'pending';
                    }

                    break;

                case 'subscr_signup':
                    $payment_data['transaction_type']   = 'subscription_start';
                    $payment_data['subscription_id']    = $_POST['subscr_id'];
                    break;

                case 'subscr_cancel': //seems is deprecated
                case 'recurring_payment_profile_cancel':
                    $payment_data['transaction_type']       = 'subscription_canceled';
                    $payment_data['subscription_id']        = $_POST['subscr_id'];
                    $payment_data['subscription_status']    = 'canceled';
//                    $payment_data['transaction_id']     = $_POST['txn_id'];
                    break;

                case 'recurring_payment_suspended':
                    $payment_data['transaction_type']       = 'subscription_suspended';
                    $payment_data['subscription_id']        = $_POST['recurring_payment_id'];
                    $payment_data['subscription_status']    = 'suspended';
//                    $payment_data['transaction_id']     = $_POST['txn_id'];
                    break;

                case 'subscr_payment':
                    $subscription_id = isset( $_POST['subscr_id'] ) ? $_POST['subscr_id'] : '';
                    $payment_data['transaction_type']   = 'subscription_payment';
                    $payment_data['transaction_id']     = $_POST['txn_id'];
                    $payment_data['subscription_id']    = $subscription_id;
                    $payment_data['amount']             = !empty( $_POST['amount'] ) ? $_POST['amount'] : '';

                    if ( 'Completed' == $payment_data['transaction_status'] ) {
                        $payment_data['subscription_status'] = 'active';
                    } else {
                        $payment_data['subscription_status'] = 'pending';
                    }

                    break;

                case 'web_accept':
                case 'express_checkout':

                    if ( 'Completed' == $payment_data['transaction_status'] ) {
                        $payment_data['transaction_type'] = 'paid';
                    } else {
                        $payment_data['transaction_type'] = 'pending';
                    }

                    $payment_data['transaction_id'] = $_POST['txn_id'];
                    break;

                default:
                    if( 'refunded' == strtolower( $payment_data['transaction_status'] ) ) {

                        $payment_data['transaction_type'] = 'refunded';
                        $payment_data['transaction_id'] = $_POST['parent_txn_id'];

                        if ( isset( $_POST['subscr_id'] ) ) {
                            $payment_data['subscription_id']        = $_POST['subscr_id'];
                            $payment_data['subscription_status']    = 'canceled';
                        }

                    }
            }
            if( isset($_GET['debug']) ) {
                var_dump( $payment_data );
//              exit;
            }

            $wpc_payments_core->order_update( $order['id'], $payment_data );*/

        }
    }


    /**
     * There was a valid response.
     * @param  array $posted Post data after wp_unslash
     */
    private function data_verification( $posted, $order ) {
        // Lowercase returned variables.
        $posted['payment_status'] = strtolower( $posted['payment_status'] );

        // Sandbox fix.
        if ( isset( $posted['test_ipn'] ) && 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status'] ) {
            $posted['payment_status'] = 'completed';
        }

        //call to save ipn data
        if ( method_exists( $this, 'payment_status_' . $posted['payment_status'] ) ) {
            $this->create_payment( $order );
            call_user_func( array( $this, 'payment_status_' . $posted['payment_status'] ), $order, $posted );

            do_action( 'wpoffice_after_payment_' . $order['type'], $this->payment_id, $order, $posted );
        }
    }


    /**
     * Check for PayPal IPN Response.
     */
    private function check_ipn_notification( $posted ) {
        // Get received values from post data
        $validate_ipn = array( 'cmd' => '_notify-validate' );
        $validate_ipn += $posted;

        // Send back post vars to paypal
        $params = array(
                'body'        => $validate_ipn,
                'timeout'     => 60,
                'httpversion' => '1.1',
                'compress'    => false,
                'decompress'  => false,
        );

        // Post back to get a response.
        $response = wp_safe_remote_post( $this->testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $params );

        return ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' );
    }


    /**
     * Get PayPal Args for passing to PP.
     * @param  data $data
     * @return array
     */
    protected function get_paypal_args( $data ) {
        $order_data = self::get_order_data();
        $order_id = isset( $order_data['order_id'] ) ? $order_data['order_id'] : '' ;
        return apply_filters( 'wpoffice_paypal_args', array_merge(
            array(
                'cmd'           => '_ext-enter',
                'redirect_cmd'  => '_xclick',
                'item_name'     => isset( $order_data['order_title'] ) ? $order_data['order_title'] : '',
                'currency_code' => isset( $order_data['currency'] ) ? $order_data['currency'] : 'USD',
                'amount'        => isset( $order_data['total'] ) ? $order_data['total'] : 0,
                'business'      => $this->email,
                'item_number'   => $order_id,
                'no_shipping'   => 1,
                'no_note'       => 1,
                'custom'        => json_encode( array( 'order_id' => $order_id ) ),
//                'invoice'       => $this->gateway->get_option( 'invoice_prefix' ) . $order['order_title'],
                'notify_url'    => $this->get_notify_url( $order_id ),
                'return'        => esc_url_raw( get_home_url() ),
                'cancel_return' => esc_url_raw( get_home_url() ),

                'email'         => $data['billing_email'],
                'first_name'    => $data['billing_first_name'],
                'last_name'     => $data['billing_last_name'],
//                'company'       => $data['billing_company'],
                'address1'      => $data['billing_address_1'],
                'address2'      => $data['billing_address_2'],
                'country'       => $data['billing_country'],
                'city'          => $data['billing_city'],
                'state'         => $this->get_paypal_state( $data['billing_country'], $data['billing_state'] ),
                'zip'           => $data['billing_postcode'],


//                'charset'       => 'utf-8',
//                'rm'            => is_ssl() ? 2 : 1,
//                'upload'        => 1,
//                'page_style'    => $this->gateway->get_option( 'page_style' ),
//                'paymentaction' => $this->gateway->get_option( 'paymentaction' ),
//                'bn'            => 'WooThemes_Cart',
            ),
            $this->get_phone_number_args( $data )
        ), $data );
    }


    /**
     * Get the PayPal request URL for an order.
     * @param  array $data
     * @param  bool $sandbox
     * @return string
     */
    public function get_request_url( $data ) {
        $paypal_args = http_build_query( $this->get_paypal_args( $data ), '', '&' );
        $url = $this->testmode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?test_ipn=1&' . $paypal_args
                : 'https://www.paypal.com/cgi-bin/webscr?' . $paypal_args;

        return $url;
    }


//    /**
//     * Check if this gateway is enabled and available in the user's country.
//     * @return bool
//     */
//    public function is_valid_for_use() {
//        return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_paypal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) );
//    }
//
//
//    /**
//     * Admin Panel Options.
//     * - Options for bits like 'title' and availability on a country-by-country basis.
//     *
//     * @since 1.0.0
//     */
//    public function admin_options() {
//        if ( $this->is_valid_for_use() ) {
//            parent::admin_options();
//        } else {
/*            ?>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', WP_OFFICE_TEXT_DOMAIN ); ?></strong>: <?php _e( 'PayPal does not support your store currency.', WP_OFFICE_TEXT_DOMAIN ); ?></p></div>
            <?php
//        }
//    }
//
//
//    /**
//     * Initialise Gateway Settings Form Fields.
//     */
//    public function init_form_fields() {
//        $this->form_fields = include( 'includes/settings-paypal.php' );
//    }


    /**
     * Process the payment and return the result.
     * @param  array $data
     * @return array
     */
    public function process_payment( $data ) {
        $order_id = filter_input( INPUT_POST, 'order_id' );
        if ( empty( $order_id ) ) {
            return;
        }

        return array(
            'result'   => 'success',
            'redirect' => $this->get_request_url( $data )
        );
    }


    /**
     * Get PayPal images for a country.
     * @param  string $country
     * @return array of image URLs
     */
    protected function get_icon_image( $country ) {
        switch ( $country ) {
            case 'US' :
            case 'NZ' :
            case 'CZ' :
            case 'HU' :
            case 'MY' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo/AM_mc_vs_dc_ae.jpg';
                break;
            case 'TR' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_odeme_secenekleri.jpg';
                break;
            case 'GB' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/Logo/AM_mc_vs_ms_ae_UK.png';
                break;
            case 'MX' :
                $icon = array(
                    'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_visa_mastercard_amex.png',
                    'https://www.paypal.com/es_XC/Marketing/i/banner/paypal_debit_card_275x60.gif'
                );
                break;
            case 'FR' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_paypal_moyens_paiement_fr.jpg';
                break;
            case 'AU' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_AU/mktg/logo/Solutions-graphics-1-184x80.jpg';
                break;
            case 'DK' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/logo_PayPal_betalingsmuligheder_dk.jpg';
                break;
            case 'RU' :
                $icon = 'https://www.paypalobjects.com/webstatic/ru_RU/mktg/business/pages/logo-center/AM_mc_vs_dc_ae.jpg';
                break;
            case 'NO' :
                $icon = 'https://www.paypalobjects.com/webstatic/mktg/logo-center/banner_pl_just_pp_319x110.jpg';
                break;
            case 'CA' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_CA/mktg/logo-image/AM_mc_vs_dc_ae.jpg';
                break;
            case 'HK' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_HK/mktg/logo/AM_mc_vs_dc_ae.jpg';
                break;
            case 'SG' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_SG/mktg/Logos/AM_mc_vs_dc_ae.jpg';
                break;
            case 'TW' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_TW/mktg/logos/AM_mc_vs_dc_ae.jpg';
                break;
            case 'TH' :
                $icon = 'https://www.paypalobjects.com/webstatic/en_TH/mktg/Logos/AM_mc_vs_dc_ae.jpg';
                break;
            case 'JP' :
                $icon = 'https://www.paypal.com/ja_JP/JP/i/bnr/horizontal_solution_4_jcb.gif';
                break;
            default :
                $icon = WC_HTTPS::force_https_url( WC()->plugin_url() . '/includes/gateways/paypal/assets/images/paypal.png' );
                break;
        }
        return apply_filters( 'woocommerce_paypal_icon', $icon );
    }


    /**
     * Get the link for an icon based on country.
     * @param  string $country
     * @return string
     */
    protected function get_icon_url( $country ) {
        $url           = 'https://www.paypal.com/' . strtolower( $country );
        $home_counties = array( 'BE', 'CZ', 'DK', 'HU', 'IT', 'JP', 'NL', 'NO', 'ES', 'SE', 'TR' );
        $countries     = array( 'DZ', 'AU', 'BH', 'BQ', 'BW', 'CA', 'CN', 'CW', 'FI', 'FR', 'DE', 'GR', 'HK', 'IN', 'ID', 'JO', 'KE', 'KW', 'LU', 'MY', 'MA', 'OM', 'PH', 'PL', 'PT', 'QA', 'IE', 'RU', 'BL', 'SX', 'MF', 'SA', 'SG', 'SK', 'KR', 'SS', 'TW', 'TH', 'AE', 'GB', 'US', 'VN' );

        if ( in_array( $country, $home_counties ) ) {
            return $url . '/webapps/mpp/home';
        } else if ( in_array( $country, $countries ) ) {
            return $url . '/webapps/mpp/paypal-popup';
        } else {
            return $url . '/cgi-bin/webscr?cmd=xpt/Marketing/general/WIPaypal-outside';
        }
    }


//    /** @var bool Whether or not logging is enabled */
//    public static $log_enabled = false;
//
//    /** @var WC_Logger Logger instance */
//    public static $log = false;
//
//
//    /**
//     * Constructor for the gateway.
//     */
//    public function __construct() {
//        $this->method_title       = __( 'PayPal', WP_OFFICE_TEXT_DOMAIN );
//        $this->method_description = sprintf( __( 'PayPal standard sends customers to PayPal to enter their payment information. PayPal IPN requires fsockopen/cURL support to update order statuses after payment. Check the %ssystem status%s page for more details.', WP_OFFICE_TEXT_DOMAIN ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );
//        $this->supports           = array(
//            'products',
//            'refunds'
//        );
//
//        // Load the settings.
//        $this->init_form_fields();
//        $this->init_settings();
//
//        // Define user set variables.
//        $this->title          = $this->get_option( 'title' );
//        $this->description    = $this->get_option( 'description' );
//        $this->testmode       = 'yes' === $this->get_option( 'testmode', 'no' );
//        $this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
//        $this->email          = $this->get_option( 'email' );
//        $this->receiver_email = $this->get_option( 'receiver_email', $this->email );
//        $this->identity_token = $this->get_option( 'identity_token' );
//
//        self::$log_enabled = $this->debug;
//
//        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
//
//        if ( !$this->is_valid_for_use() ) {
//            $this->enabled = 'no';
//        } else {
//            include_once( 'includes/class-wc-gateway-paypal-ipn-handler.php' );
//            new WC_Gateway_Paypal_IPN_Handler( $this->testmode, $this->receiver_email );
//
//            if ( $this->identity_token ) {
//                include_once( 'includes/class-wc-gateway-paypal-pdt-handler.php' );
//                new WC_Gateway_Paypal_PDT_Handler( $this->testmode, $this->identity_token );
//            }
//        }
//    }
//
//
//    /**
//     * Logging method.
//     * @param string $message
//     */
//    public static function log( $message ) {
//        if ( self::$log_enabled ) {
//            if ( empty( self::$log ) ) {
//                self::$log = new WC_Logger();
//            }
//            self::$log->add( 'paypal', $message );
//        }
//    }


    /**
     * Get gateway icon.
     * @return string
     */
    public function get_icon() {
        $icon_html = '';
        $icon      = (array) $this->get_icon_image( WC()->countries->get_base_country() );

        foreach ( $icon as $i ) {
            $icon_html .= '<img src="' . esc_attr( $i ) . '" alt="' . esc_attr__( 'PayPal Acceptance Mark', WP_OFFICE_TEXT_DOMAIN ) . '" />';
        }

        $icon_html .= sprintf( '<a href="%1$s" class="about_paypal" onclick="javascript:window.open(\'%1$s\',\'WIPaypal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PayPal?', WP_OFFICE_TEXT_DOMAIN ) . '">' . esc_attr__( 'What is PayPal?', WP_OFFICE_TEXT_DOMAIN ) . '</a>', esc_url( $this->get_icon_url( WC()->countries->get_base_country() ) ) );

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }


    /**
     * Prepare settings of PayPal
     *
     * @param array $settings
     * @return array
     */
    static function prepare_settings( $settings ) {

        $prepare['active'] = !empty( $settings['active'] );

        $prepare['title'] = isset( $settings['title'] )
                && is_string( $settings['title'] ) ? esc_attr( $settings['title'] )
                :  __( 'PayPal', WP_OFFICE_TEXT_DOMAIN );

        $prepare['description'] = isset( $settings['description'] )
                && is_string( $settings['description'] ) ? esc_attr( $settings['description'] )
                : __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', WP_OFFICE_TEXT_DOMAIN ) ;

        $prepare['email'] = isset( $settings['email'] )
                && is_email( $settings['email'] ) ? $settings['email'] : get_option( 'admin_email' );

        $prepare['testmode'] = !empty( $settings['testmode'] );

        $prepare['api_username'] = isset( $settings['api_username'] )
                && is_string( $settings['api_username'] ) ? esc_attr( $settings['api_username'] ) : '';

        $prepare['api_password'] = isset( $settings['api_password'] )
                && is_string( $settings['api_password'] ) ? esc_attr( $settings['api_password'] ) : '';

        $prepare['api_signature'] = isset( $settings['api_signature'] )
                && is_string( $settings['api_signature'] ) ? esc_attr( $settings['api_signature'] ) : '';

        return $prepare;
    }


    /**
     *
     *
     */
    function get_info( $gateways ) {
        $settings = WO()->get_settings( $this->id );
        $active = ( $settings['active'] ? '' : 'in' ) . 'active';

        $gateways[] = array(
//            'id' => 1,
            'title' => __( 'Paypal', WP_OFFICE_TEXT_DOMAIN ),
            'gateway'  => $this->id,
            'class_name' => __CLASS__,
            'order' => 5,
            'active' => $active,
            'description' => __( 'PayPal standard sends customers to PayPal to enter their payment information. PayPal IPN requires fsockopen/cURL support to update order statuses after payment.', WP_OFFICE_TEXT_DOMAIN ),
        );

        return $gateways;
    }


    public function init_property() {
        $settings = WO()->get_settings( $this->id );

        $this->title          = $settings['title'];
        $this->description    = $settings['description'];
        $this->testmode       = $settings['testmode'];
        $this->email          = $settings['email'];
    }


//    /**
//     * Can the order be refunded via PayPal?
//     * @param  array $order
//     * @return bool
//     */
//    public function can_refund_order( $order ) {
//        return $order && $order->get_transaction_id();
//    }
//
//
//    /**
//     * Process a refund if supported.
//     * @param  int    $order_id
//     * @param  float  $amount
//     * @param  string $reason
//     * @return bool True or false based on success, or a WP_Error object
//     */
//    public function process_refund( $order_id, $amount = null, $reason = '' ) {
//        $order = wc_get_order( $order_id );
//
//        if ( !$this->can_refund_order( $order ) ) {
//            $this->log( 'Refund Failed: No transaction ID' );
//            return new WP_Error( 'error', __( 'Refund Failed: No transaction ID', WP_OFFICE_TEXT_DOMAIN ) );
//        }
//
//        include_once( 'includes/class-wc-gateway-paypal-refund.php' );
//
//        WC_Gateway_Paypal_Refund::$api_username  = $this->get_option( 'api_username' );
//        WC_Gateway_Paypal_Refund::$api_password  = $this->get_option( 'api_password' );
//        WC_Gateway_Paypal_Refund::$api_signature = $this->get_option( 'api_signature' );
//
//        $result = WC_Gateway_Paypal_Refund::refund_order( $order, $amount, $reason, $this->testmode );
//
//        if ( is_wp_error( $result ) ) {
//            $this->log( 'Refund Failed: ' . $result->get_error_message() );
//            return new WP_Error( 'error', $result->get_error_message() );
//        }
//
//        $this->log( 'Refund Result: ' . print_r( $result, true ) );
//
//        switch ( strtolower( $result['ACK'] ) ) {
//            case 'success':
//            case 'successwithwarning':
//                $order->add_order_note( sprintf( __( 'Refunded %s - Refund ID: %s', WP_OFFICE_TEXT_DOMAIN ), $result['GROSSREFUNDAMT'], $result['REFUNDTRANSACTIONID'] ) );
//                return true;
//                break;
//        }
//
//        return isset( $result['L_LONGMESSAGE0'] ) ? new WP_Error( 'error', $result['L_LONGMESSAGE0'] ) : false;
//    }


}
