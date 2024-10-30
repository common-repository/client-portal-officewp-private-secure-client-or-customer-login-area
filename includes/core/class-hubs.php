<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Hubs {

    /**
     * PHP 5 constructor
     **/
    function __construct() {

    }


    /**
     * Delete Pages
     *
     * @param $page_ids int|array Pages IDs
     * @return bool deleted count
     */
    function delete( $page_ids ) {
        if ( !( is_numeric( $page_ids ) || is_array( $page_ids ) ) ) {
            return false;
        }

        if ( is_numeric( $page_ids ) ) {
            $page_ids = array( $page_ids );
        }

        $deleted_count = 0;
        foreach ( $page_ids as $page_id ) {
            $postdata = get_post( $page_id );
            if ( !$postdata ) {
                continue;
            }

            if ( $postdata->post_status != 'trash' )
                continue;

            if ( wp_delete_post( $page_id, true ) ) {
                WO()->delete_all_object_assigns( 'office_hub', $page_id );
                $deleted_count++;
            }
        }

        return $deleted_count;
    }


    /**
     * Restore from Archive Pages
     *
     * @param $page_ids int|array Pages IDs
     * @return bool Result
     */
    function restore( $page_ids ) {
        if( !( is_numeric( $page_ids ) || is_array( $page_ids ) ) ) {
            return false;
        }

        if( is_numeric( $page_ids ) ) {
            $page_ids = array( $page_ids );
        }

        $restored_count = 0;
        foreach( $page_ids as $page_id ) {
            if( !get_post( $page_id ) ) {
                continue;
            }

            if( wp_untrash_post( $page_id ) ) {
                $restored_count++;
            }
        }

        return $restored_count;
    }


    /**
     * Move to Trash Pages
     *
     * @param $page_ids int|array Pages IDs
     * @return bool Result
     */
    function trash( $page_ids ) {
        if( !( is_numeric( $page_ids ) || is_array( $page_ids ) ) ) {
            return false;
        }

        if( is_numeric( $page_ids ) ) {
            $page_ids = array( $page_ids );
        }

        $trashed_count = 0;
        foreach( $page_ids as $page_id ) {
            if( !get_post( $page_id ) ) {
                continue;
            }

            if( wp_trash_post( $page_id ) ) {
                $trashed_count++;
            }
        }

        return $trashed_count;
    }


    //end class
}