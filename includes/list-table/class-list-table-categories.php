<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;
use wpo\list_table\List_Table_Office_Pages_Categories;

class List_Table_Categories extends Admin_List_Table {

    function __construct( $args = array() ) {
        parent::__construct( $args );
    }

    function get_filters_line() {
        $listTable = new List_Table_Office_Pages_Categories();
        $filter_line_args = $listTable->categories_filters_line( array() );
        $filter_line_args = apply_filters( 'wpoffice_list_table_categories_filters_line', $filter_line_args );
        return $filter_line_args;
    }

    function get_this() {
        return $this;
    }

    //end class
}