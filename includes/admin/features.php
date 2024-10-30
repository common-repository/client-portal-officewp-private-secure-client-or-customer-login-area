<?php

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$exclude_features = WO()->get_settings( 'exclude_features' );

if ( isset( $_POST['wpo_disable_features'] ) ) {

    //if turn on feature
    if ( !empty( $_POST['wpo_features'] ) ) {
        foreach ( $_POST['wpo_features'] as $key => $val ) {
            if ( isset( $exclude_features[ $key ] ) ) {
                unset( $exclude_features[ $key ] );
            }
        }
    }

    //if turn off feature
    $disable_features = !empty( $_POST['wpo_disable_features'] ) ? explode( ',', $_POST['wpo_disable_features'] ) : array();
    $disable_features = array_unique( $disable_features );
    foreach ( $disable_features as $title ) {
        if ( !empty( $title ) ) {
            $exclude_features[ $title ] = true;
        }
    }

    WO()->set_settings( 'exclude_features', $exclude_features );

    WO()->redirect( add_query_arg( 'msg', 'u' ) );
}

wp_enqueue_style( 'wpo-features-style', WO()->plugin_url . 'assets/css/admin-features.css', array(), WP_OFFICE_VER );

$available_features = apply_filters( 'wpoffice_features', array() );

//$buyable_active_plugins = apply_filters( 'wpoffice_active_plugins', array() );

$buyable_plugins = $pre_sale = $pre_dev = array();

$empty_text = __( 'New features will be added soon.', WP_OFFICE_TEXT_DOMAIN );


$all_features = array(

    'available' => array(
        'bg_color' => '#6ea03f',
        'title' => __('Available Features', WP_OFFICE_TEXT_DOMAIN),
        'features' => $available_features
    ),

    'buy' => array(
        'bg_color' => '#0087be',
        'title' => __('Buyable Extensions', WP_OFFICE_TEXT_DOMAIN),
        'features' => $buyable_plugins,
    ),

    'presale' => array(
        'bg_color' => '#ffcc00',
        'title' => __('Available for pre-sale (25% discount)', WP_OFFICE_TEXT_DOMAIN),
        'features' => $pre_sale,
    ),

    'before_developed' => array(
        'bg_color' => '#be2c00',
        'title' => __('Available for pre-developing (50% discount)', WP_OFFICE_TEXT_DOMAIN),
        'features' => $pre_dev,
    ),

);

echo '<form action="" method="post">';

foreach ( $all_features as $key => $block) {
    echo '<div class="wpo_table wpo_features_table">';
        echo '<div class="wpo_thead"' . ( isset( $block['bg_color'] ) ? ' style="background-color: ' . $block['bg_color'] . '"' : '' ) .'>';
            echo '<div class="wpo_tr">';
                echo '<div class="wpo_th wpo_main_title"'

                    . '>' . $block['title'] . '</div>';
            echo '</div>';
            echo '<div class="wpo_tr">';
                echo '<div class="wpo_th wpo_title_hr">' . WO()->hr( '0 0 0 0' ) . '</div>';
            echo '</div>';
        echo '</div>';
        echo '<div class="wpo_tbody">';
        if ( 'available' == $key ) {
            foreach ( $block['features'] as $title => $data ) {
                echo '<div class="wpo_tr' . ( !empty( $data['hide'] ) ? ' wpo_hide' : '' ) . '">';
                    echo '<div class="wpo_td"><b>' . $data['title'] . '</b><br><div class="wpo_features_description">' . $data['description'] . '</div></div>';
                    echo '<div class="wpo_td wpo_features_status' . ( !empty( $data['value'] ) ? ' wpo_active_status' : '' ) . '">';
                        echo '<input type="checkbox" ' . checked( $data['value'], true, false )
                        . 'class="wpo_toggle_switch" id="wpo_' . $title . '" data-name="' . $title . '" name="wpo_features[' . $title . ']" value="1">'
                        . '<label for="wpo_' . $title . '"></label>'
                        . '<label class="wpo_toggle_status">' . wpo_features_get_status_feature( $data['value'] ) . '</label>'
                        . '<label class="wpo_hide">' . wpo_features_get_status_feature( !$data['value'] ) . '</label>'
                        . '</div>';
                echo '</div>';
            }
            echo '<div class="wpo_tr">';

                echo '<div class="wpo_td wpo_save_features wpo_center_block">';
                WO()->get_button(
                    __( 'Save', WP_OFFICE_TEXT_DOMAIN ),
                    array( 'id' => 'wpo_save_features' ),
                    array( 'only_text' => true, 'primary' => true ),
                    true
                );
                echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="wpo_tr">';
                echo '<div class="wpo_td wpo_center_block"><b>' . $empty_text . '</b></div>';
            echo '</div>';
        }
        echo '</div>';
    echo '</div>';
}

echo '</form>';


$msg = '';
if ( !empty( $_GET['msg'] ) ) {
    switch ( $_GET['msg'] ) {
        case 'u':
            $msg =  __( 'Features has been Updated Successfully!', WP_OFFICE_TEXT_DOMAIN );
            break;
    }
}

?>
<script text="text/javascript">

    jQuery( document ).ready( function() {
        jQuery('.wpo_toggle_switch').click( function() {
            var toggle_status = jQuery( this ).siblings( '.wpo_toggle_status' );
            var old_text = toggle_status.text();
            toggle_status.text( toggle_status.next().text() );
            toggle_status.next().text( old_text );
            jQuery( this ).parent().toggleClass( 'wpo_active_status' );
        });


        jQuery('#wpo_save_features').click( function() {
            var enable_features = '';
            var name = '';
            jQuery( '.wpo_toggle_switch:not(:checked)' ).each( function() {
                name = jQuery( this ).data('name');
                enable_features += name + ',';
            });

            jQuery( this ).after( '<input type="hidden" name="wpo_disable_features" value="' + enable_features + '">' );
            jQuery( this ).closest( 'form' ).submit();
        });
    });

    var msg = '<?php echo $msg ?>';
    if ( msg ) {
        jQuery( this ).wpo_notice({
            message : msg,
            type : 'update'
        });
    }
</script>

<?php

/**
* Get title of status of feature
*
* @param boolean $active
* @return string
*/
function wpo_features_get_status_feature( $active ) {
   return $active ? __('Active', WP_OFFICE_TEXT_DOMAIN) : __('Inactive', WP_OFFICE_TEXT_DOMAIN) ;
}
