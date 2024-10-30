<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Common {

    /**
    * PHP 5 constructor
    **/
    function __construct() {

        add_action( 'init', array( &$this, '_add_post_type' ) );
        add_action( 'init', array( &$this, '_set_private_page_types' ) );

        //add query vars
        add_filter( 'query_vars', array( &$this, '_add_query_vars' ), 0 );

        //add rewrite rules
        add_filter( 'rewrite_rules_array', array( &$this, '_add_rewrite_rules' ) );
    }


    function _set_private_page_types() {
        //default array
        $private_page_type = array(
            'office_page'
        );

        //our_hook
        $private_page_type = apply_filters( 'wpoffice_private_page_types', $private_page_type );

        WO()->private_page_types = $private_page_type;
    }


    /*
    * Register post types
    *
    * @return void
    */
    function _add_post_type() {
        $our_post_types = WO()->get_post_types();

        if ( is_array( $our_post_types ) ) {
            foreach( $our_post_types as $key => $value ) {
                register_post_type( $key, $value );
            }
        }

    }

    /**
     * Adding a new rules
     *
     * @param array $rules
     * @return array
     **/
    function _add_rewrite_rules( $rules ) {
        $newrules = array();

        $pages = WO()->get_settings( 'pages' );

        foreach( $pages as $key => $value ) {
            if ( !( !empty( $value['id'] ) || 'office_page' == $key || 'hub_page' == $key ) ) {

                $newrules[WO()->get_page_slug( $key, array(), false, false ) . '/(.+?)/?$'] = 'index.php?wpo_page=hub_page&wpo_page_key=' . $key . '&wpo_page_value=$matches[1]';
                $newrules[WO()->get_page_slug( $key, array(), false, false ) . '/?$'] = 'index.php?wpo_page=hub_page&wpo_page_key=' . $key;

            } elseif ( 'hub_page' == $key ) {
                //temp for new HUB
                $newrules[WO()->get_page_slug( $key, array(), false, false ) . '/?$'] = 'index.php?wpo_page=hub_page&wpo_page_key=hub_page';

            }
        }

        $newrules['wpo-api/ipn/([^/]+)/([^/]+)/?$'] = 'index.php?wpo_page=api&wpo_action=ipn&wpo_gateway=$matches[1]&wpo_id=$matches[2]';
        $newrules['wpo-api/([^/]+)/([^/]+)/([0-9]+)/([^/]+)/?$'] = 'index.php?wpo_page=api&wpo_action=$matches[1]&wpo_resource=$matches[2]&wpo_id=$matches[3]&wpo_verify=$matches[4]';
        $newrules['wpo-api/stream/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?wpo_page=api&wpo_action=stream&wpo_resource=$matches[1]&wpo_id=$matches[2]&wpo_verify=$matches[3]';
        $newrules['wpo-api/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$'] = 'index.php?wpo_page=api&wpo_action=$matches[1]&wpo_resource=$matches[2]&wpo_method=$matches[3]&wpo_verify=$matches[4]';

        //our hook
        $newrules = apply_filters( 'wpoffice_new_rewrite_rules', $newrules );
        return $newrules + $rules;
    }


    /**
     * Adding the query var for our plugin page
     *
     * @param array $vars
     * @return array
     **/
    function _add_query_vars( $vars ) {

        $vars[] = 'wpo_page';
        $vars[] = 'wpo_page_key';
        $vars[] = 'wpo_page_value';
        $vars[] = 'wpo_action';
        $vars[] = 'wpo_resource';
        $vars[] = 'wpo_method';
        $vars[] = 'wpo_id';
        $vars[] = 'wpo_verify';
        $vars[] = 'wpo_gateway';

        return $vars;
    }


}
