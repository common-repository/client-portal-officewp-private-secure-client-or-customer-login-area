<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\list_table\List_Table_Payments;
$ListTable = new List_Table_Payments();
$ListTable->display();