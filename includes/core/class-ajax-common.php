<?php
namespace wpo\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Api;

class AJAX_Common {

    /**
    * PHP 5 constructor
    **/
    function __construct() {
        // wpo_EVENT => nopriv
        $ajax_actions = array(
            'generate_password'     => true,
            'api'                   => false
        );

        foreach ( $ajax_actions as $action => $nopriv ) {
            add_action( 'wp_ajax_wpo_' . $action, array( $this, $action ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_wpo_' . $action, array( $this, $action ) );
            }
        }

    }


    /**
     * AJAX generate_password function
     */
    public function generate_password() {
        exit( json_encode( array( 'status' => true, 'password' => apply_filters( 'wpoffice_generate_password', wp_generate_password( 10 ) ) ) ) );
    }

    function api() {
        $api = new Api();
        $api->init_backend_actions();
    }

    //end class
}