<?php

namespace wpo\gateways;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base Payment Gateway class.
 * Extended by individual payment gateways to handle payments.
 *
 * @class       Gateway
 * @version     1.0.0
 */
abstract class Gateway {
//
    /**
     * Set if the place order button should be renamed on selection.
     * @var string
     */
    public $order_button_text;

    /**
     * based on whether the method is enabled.
     * @var bool
     */
    public $enabled;

    /**
     * name of gateway
     * @var string
     */
    public $id;

    /**
     * information about current order
     * @var array
     */
    private static $order_data;

    /**
     * Payment method title for the frontend.
     * @var string
     */
    public $title;

    /**
     * Payment method description for the frontend.
     * @var string
     */
    public $description;

    /**
     * Chosen payment method id.
     * @var bool
     */
    public $chosen;
//
//    /**
//     * Gateway title.
//     * @var string
//     */
//    public $method_title = '';
//
//    /**
//     * Gateway description.
//     * @var string
//     */
//    public $method_description = '';

    /**
     * True if the gateway shows fields on the checkout.
     * @var bool
     */
    public $has_fields = false;

    /**
     * Countries this gateway is allowed for.
     * @var array
     */
    private static $countries;

//    /**
//     * Available for all counties or specific.
//     * @var string
//     */
//    public $availability;
//
//    /**
//     * Icon for the gateway.
//     * @var string
//     */
//    public $icon;

    /**
     * Supported features such as 'default_credit_card_form', 'refunds'.
     * @var array
     */
    public $supports = array();

    /**
     * Maximum transaction amount, zero does not define a maximum.
     * @var int
     */
    public $max_amount = 0;


    /**
     * Send a notification to the user handling orders.
     * @param string $subject
     * @param string $message
     */
    protected function send_ipn_email_notification( $subject, $message ) {
        mail( $this->email,
                strip_tags( $subject ),
                wpautop( wptexturize( $message ) )
        );
    }


    /**
     * Create payment
     *
     * @global object $wpdb
     * @param array $order
     */
    function create_payment( $order ) {
        global $wpdb;
        $this->payment_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpo_objects WHERE type='payment' AND title=%s", $order['order_id'] ) );
        if ( !$this->payment_id ) {
            $data = array(
                'type' => 'payment',
                'author' => $order['user_id'],
                'title' => $order['order_id'],

                'function' => $order['type'],
                'currency' => $order['currency'],
                'total' => $order['total'],
                'payment_type' => $order['payment_type'],
                'note' => $order['order_title'],
                'status' => 'pending',
                'method' => $this->id,
                'data' => array(
                ),
            );

            $this->payment_id = $data['payment_id'] = WO()->update_object( $data );

            do_action( 'wpoffice_after_payment_created_' . $order['type'], $data, $order );
        }
    }


    /**
     * Check if a gateway supports a given feature.
     *
     * Gateways should override this to declare support (or lack of support) for a feature.
     * For backward compatibility, gateways support 'products' by default, but nothing else.
     *
     * @param string $feature string The name of a feature to test support for.
     * @return bool True if the gateway supports the feature, false otherwise.
     */
    public function supports( $feature ) {
        return apply_filters( 'wpoffice_payment_gateway_supports', in_array( $feature, $this->supports ), $feature, $this );
    }


    /**
     * If There are no payment fields show the description if set.
     * Override this in your gateway if you have some.
     */
    public function payment_fields() {
        if ( $description = $this->get_description() ) {
            echo wpautop( wptexturize( $description ) );
        }
    }


    /**
     * Set as current gateway.
     *
     * Set this as the current gateway.
     */
    public function set_current() {
        $this->chosen = true;
    }


    /**
     * Return the gateway's description.
     *
     * @return string
     */
    public function get_description() {
        return apply_filters( 'wpoffice_gateway_description', $this->description, $this->id );
    }


    /**
     * Return the gateway's title.
     *
     * @return string
     */
    public function get_title() {
        return apply_filters( 'wpoffice_gateway_title', $this->title, $this->id );
    }


    private function get_enabled() {
        if ( is_null( $this->enabled ) ) {
            $settings = WO()->get_settings( $this->id );
            $this->enabled = $settings['active'];
        }

        return $this->enabled;
    }


    /**
     * Check if the gateway has fields on the checkout.
     *
     * @return bool
     */
    public function has_fields() {
        return $this->has_fields;
    }


    /**
     * Get notify url for gateway ipn
     *
     * @param string $order_id
     * @return string
     */
    function get_notify_url( $order_id ) {
        if( get_option( 'permalink_structure' ) ) {
            $url = get_site_url( null, 'wpo-api/ipn/' . $this->id . '/' . $order_id  );
        } else {
            $url = add_query_arg( array(
                'wpo_page' => 'api',
                'wpo_action' => 'ipn',
                'wpo_gateway' => $this->id,
                'wpo_id' => $order_id
            ), get_site_url() );
        }

        return $url;
    }


    /**
     * Check if the gateway is available for use.
     *
     * @return bool
     */
    public function is_available() {
        $is_available = $this->get_enabled();

        if ( 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
            $is_available = false;
        }

        return $is_available;
    }


    protected static function is_already_complete( $order_id ) {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "SELECT o.title
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON ( om.object_id = o.id AND om.meta_key = 'order_id' )
            WHERE o.type = 'payment' AND om.meta_value = %s", $order_id ) );

        return $result === 'completed';
    }


    protected static function get_order_data_by_order_id( $order_id ) {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "SELECT om.meta_value
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON ( om.object_id = o.id AND om.meta_key = 'order_data' )
            WHERE o.type = 'order' AND o.title = %s", $order_id ) );

        if ( $result ) {
            $order_data = maybe_unserialize( $result );

            $default_data = array(
                'total' => 0,
                'order_id' => $order_id,
                'currency' => 'USD',
            );

            $order_data = is_array( $order_data ) ? array_merge( $default_data, $order_data )
                    : $default_data ;
        } else {
            $order_data = array();
        }

        return $order_data;
    }


    /**
     * Set information about current order
     *
     * @global object $wp_query
     * @global object $wpdb
     */
    private static function set_order_data() {
        $order_id = filter_input( INPUT_POST, 'order_id' );
        if ( !$order_id ) {
            global $wp_query;
            $order_id = !empty( $wp_query->query_vars['wpo_page_value'] )
                    ? $wp_query->query_vars['wpo_page_value'] : null ;
        }

        $order_data = self::get_order_data_by_order_id( $order_id );

        self::$order_data = $order_data;
    }


    /**
     * Get information about current order
     *
     * @return array
     */
    public static function get_order_data() {
        if ( is_null( self::$order_data ) ) {
            self::set_order_data();
        }

        return self::$order_data;
    }


    /**
     * Get the order total in checkout and pay_for_order.
     *
     * @return float
     */
    private static function get_order_total() {
        $order_data = self::get_order_data();

        return $order_data['total'];
    }


    /**
     * Get information for billing details
     *
     * @global object $wpdb
     * @return array
     */
    public static function get_billing_data() {
        $user_info = get_userdata( get_current_user_id() );

        $fields = array(
            'billing_first_name'    => 'first_name',
            'billing_last_name'     => 'last_name',
            'billing_email'         => 'user_email',
            'billing_phone'         => '',
            'billing_country'       => '',
            'billing_address_1'     => '',
            'billing_address_2'     => '',
            'billing_city'          => '',
            'billing_state'         => '',
            'billing_postcode'      => '',
        );

        $billing_data = array();

        global $wpdb;
        $all = $wpdb->get_results( "SELECT meta_key as `key`, meta_value as `value` FROM {$wpdb->usermeta} "
            . "WHERE user_id = {$user_info->ID} AND meta_key IN ('" . implode( "','", array_keys( $fields ) ) . "')", ARRAY_A );

        $all_billing_data = array();
        foreach ( $all as $field ) {
            $all_billing_data[ $field['key'] ] = $field['value'];
        }

        foreach ( $fields as $key => $field ) {
            $billing_data[ $key ] = !empty( $all_billing_data[ $key ] ) ? $all_billing_data[ $key ] : '';
            if ( empty( $billing_data[ $key ] ) && !empty( $field )
                    && !empty( $user_info->$field )) {
                $billing_data[ $key ] = $user_info->$field;
            }
        }

        return $billing_data;
    }
//
//    /**
//     * Optional URL to view a transaction.
//     * @var string
//     */
//    public $view_transaction_url = '';
//
//    /**
//     * Optional label to show for "new payment method" in the payment
//     * method/token selection radio selection.
//     * @var string
//     */
//    public $new_method_label = '';
//
//    /**
//     * Contains a users saved tokens for this gateway.
//     * @var array
//     */
//    protected $tokens = array();


    /**
     * Get the allowed countries in current Gateway
     *
     * @return array
     */
    public static function get_countries() {
        $all_countries = Payment_Gateways::get_all_countries();
        $countries = is_null( self::$countries )
                ? $all_countries
                : array_intersect( self::$countries, $all_countries );

        return $countries;
    }


}