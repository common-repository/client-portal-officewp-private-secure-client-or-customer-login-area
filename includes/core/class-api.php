<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Api {


    function init_backend_actions() {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        $user_id = get_current_user_id();
        if( empty( $_REQUEST['wpo_action'] ) ) {
            exit( __( 'Wrong action', WP_OFFICE_TEXT_DOMAIN ) );
        }
        if( empty( $_REQUEST['wpo_resource'] ) ) {
            exit( __( 'Wrong resource', WP_OFFICE_TEXT_DOMAIN ) );
        }

        if ($_REQUEST['wpo_action'] == 'route') {
            $verify = wp_verify_nonce( $_REQUEST['wpo_verify'], $ip . $user_id . $_REQUEST['wpo_resource'] . $_REQUEST['wpo_method'] );
        } else if( $_REQUEST['wpo_action'] == 'google_view' ) {
            list( $hash, $ext ) = explode('.', $_REQUEST['wpo_verify'] );
            list( $hash, $user_id ) = explode('_', $hash );
            $verify = $hash == md5( $_REQUEST['wpo_id'] . $user_id . date('Y-m-d') . NONCE_SALT );
        } else if ($_REQUEST['wpo_action'] == 'download' || $_REQUEST['wpo_action'] == 'view') {
            $verify = wp_verify_nonce( $_REQUEST['wpo_verify'], $ip . $user_id . $_REQUEST['wpo_action'] . $_REQUEST['wpo_resource'] . $_REQUEST['wpo_id'] );
        } else {
            $verify = wp_verify_nonce( $_REQUEST['wpo_verify'], $ip . $user_id . $_REQUEST['wpo_action'] . $_REQUEST['wpo_resource'] );
        }

        if( $verify ) {
            if ($_REQUEST['wpo_action'] == 'download' || $_REQUEST['wpo_action'] == 'view') {
                WO()->downloader()->set_type( $_REQUEST['wpo_action'] )->process( array(
                    'id' => $_REQUEST['wpo_id'],
                    'resource' => $_REQUEST['wpo_resource'],
                    'action' => $_REQUEST['wpo_action']
                ) );
            } else if ($_REQUEST['wpo_action'] == 'route') {
                $this->request_process( array(
                    'route' => $_REQUEST['wpo_resource'],
                    'method' => $_REQUEST['wpo_method']
                ) );
            }
        } else {
            exit( __( 'Wrong nonce', WP_OFFICE_TEXT_DOMAIN ) );
        }
    }

    function init_frontend_actions() {
        //global $wp_query;
        //var_dump($wp_query->query_vars);
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        $user_id = get_current_user_id();
        if( !get_query_var('wpo_action') ) {
            exit( __( 'Wrong action', WP_OFFICE_TEXT_DOMAIN ) );
        }
        if( !get_query_var('wpo_resource') && 'ipn' !== get_query_var( 'wpo_action' ) ) {
            exit( __( 'Wrong resource', WP_OFFICE_TEXT_DOMAIN ) );
        }

        if ( 'ipn' === get_query_var( 'wpo_action' ) ) {
            //validation will in each payment gateway
            $verify = true;
        } else if( get_query_var( 'wpo_action' ) == 'route' ) {
            $verify = wp_verify_nonce( get_query_var('wpo_verify'), $ip . $user_id . get_query_var( 'wpo_resource' ) . get_query_var( 'wpo_method' ) );
        } else if( get_query_var( 'wpo_action' ) == 'google_view' ) {
            list( $hash, $ext ) = explode( '.', get_query_var( 'wpo_verify' ) );
            list( $hash, $user_id ) = explode( '_', $hash );
            $verify = $hash == md5( get_query_var( 'wpo_id' ) . $user_id . date( 'Y-m-d' ) . NONCE_SALT );
        } else {
            $verify = wp_verify_nonce( get_query_var( 'wpo_verify' ), $ip . $user_id . get_query_var( 'wpo_action' ) . get_query_var( 'wpo_resource' ) . get_query_var( 'wpo_id' ) );
        }

        if( $verify ) {
            if ( in_array( get_query_var('wpo_action'), array( 'download', 'view', 'google_view' ) ) ) {
                WO()->downloader()->set_type( get_query_var('wpo_action') )->process( array(
                    'id' => get_query_var('wpo_id'),
                    'resource' => get_query_var('wpo_resource'),
                    'action' => get_query_var('wpo_action')
                ) );
            } else if (get_query_var('wpo_action') == 'stream') {
                WO()->downloader()->set_type( get_query_var('wpo_action') )->process_stream( array(
                    'ids' => get_query_var('wpo_id'),
                    'resource' => get_query_var('wpo_resource'),
                    'action' => get_query_var('wpo_action')
                ) );
            } else if (get_query_var('wpo_action') == 'route') {
                $this->request_process( array(
                    'route' => get_query_var('wpo_resource'),
                    'method' => get_query_var('wpo_method')
                ) );
            } else if ( 'ipn' === get_query_var('wpo_action') ) {
                $this->request_process( array(
                    'route' => 'wpo!gateways!Payment_Gateways',
                    'method' => '_ipn'
                ) );
            }
        } else {
            exit( __( 'Wrong nonce', WP_OFFICE_TEXT_DOMAIN ) );
        }
    }

    function request_process( $params ) {
        if( !empty( $params['route'] ) && !empty( $params['method'] ) ) {
            $route = str_replace( array( '!', '/' ), '\\', $params['route'] );
            if( class_exists( $route ) ) {
                if ( method_exists( $route, 'instance' ) ) {
                    $object = $route::instance();
                } else {
                    $object = new $route();
                }
                if (method_exists($object, $params['method'])) {
                    call_user_func( array( &$object, $params['method'] ) );
                }
            }
        }
    }

}