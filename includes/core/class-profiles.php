<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Profiles {

    function delete( $ids ) {
        if( !( is_numeric( $ids ) || is_array( $ids ) ) ) {
            return false;
        }

        if( is_numeric( $ids ) ) {
            $ids = array( $ids );
        }

        foreach ( $ids as $id ) {
            WO()->delete_all_assign_assigns( 'profile', $id );
        }

        return WO()->delete_wpo_object( $ids );
    }

    //end class
}