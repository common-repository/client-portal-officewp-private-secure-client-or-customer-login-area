<?php
namespace wpo\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hooks {

    /**
    * PHP 5 constructor
    **/
    function __call( $name, $arguments ) {
        //for static method
        if ( strpos( $name, '>>' ) ) {
            $func = explode( '>>', $name );
            $class = $func[0];

            return forward_static_call_array( array( $class, $func[1] ), $arguments );
        }
        $func = explode( '->', $name );
        $class = $func[0];

        if ( method_exists( $class, 'instance' ) ) {
            $object = $class::instance();
        } else {
            $object = new $class();
        }

        return call_user_func_array( array( $object, $func[1] ), $arguments );
    }

//end class
}