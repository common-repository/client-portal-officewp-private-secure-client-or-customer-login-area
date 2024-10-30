<?php
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$dashboard = WO()->admin_dashboard();

?>

<div class="wpo_admin_content" id="wpo_admin_dashboard">
    <div class="wpo_dashboard">
        <?php
        //our_hook
        echo apply_filters( 'wpoffice_dashboard_before_widgets', '' );

        wp_enqueue_script( 'masonry' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_register_style( 'wpo-dashboard', WO()->plugin_url . 'assets/css/admin-dashboard.css', array(), WP_OFFICE_VER );
        wp_enqueue_style( 'wpo-dashboard', false, array(), WP_OFFICE_VER );

        $widgets_list = $def_widgets_list = array_keys( $dashboard->widgets );

        $user_widgets = get_user_meta( get_current_user_id(), 'wpo_widgets_order', true );
        if( isset( $user_widgets ) && !empty( $user_widgets ) ) {
            $widgets_list = array_merge( $user_widgets, array_diff( $widgets_list, $user_widgets ) );
            $widgets_list = array_intersect( $widgets_list, $def_widgets_list );
        } ?>

        <script type="text/javascript">
            jQuery( document ).ready( function() {
                var is_loaded = 0;
                var count_widgets = <?php echo count( $widgets_list ) ?>;
                var ajax_loading = '<div class="wpo_dashboard_ajax_loader"><?php echo str_replace( "\r", "", str_replace( "\n", "", WO()->get_ajax_loader( 77 ) ) ); ?></div>';

                var body = jQuery( 'body' );
                var active_widgets = jQuery('.wpo_dashboard .active_widgets');

                body.on( 'click', '.widget_reload', function() {
                    var obj = jQuery(this);
                    var obj_widget = obj.parents( '.halfwidth_place' );

                    var widget = obj_widget.data( 'widget' );
                    if ( typeof obj_widget.data( 'action' ) != 'undefined' ) {
                        widget = obj_widget.data( 'action' );
                    }

                    var role = obj_widget.data( 'role' );

                    obj.attr('title', '<?php _e( 'Reloading', WP_OFFICE_TEXT_DOMAIN ) ?>');

                    obj_widget.addClass( 'widget_reloading' );

                    var data_string = 'widget=' + widget;
                    if ( typeof role != 'undefined' ) {
                        data_string += '&role=' + role
                    }

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'load_widget' ) ?>',
                        data: data_string,
                        dataType: 'html',
                        success: function( data ) {
                            if( data != '0' ) {
                                obj_widget.html( data );
                            }

                            jQuery('.wpo_dashboard .active_widgets').masonry( 'reloadItems' ).masonry( 'layout' );
                            obj_widget.removeClass( 'widget_reloading' );
                        }
                    });
                });

                <?php if( is_array( $widgets_list ) && count( $widgets_list ) > 0 ) {
                    foreach( $widgets_list as $widget ) {
                        $widget_options = get_user_meta( get_current_user_id(), 'wpo_' . $widget, true );

                        if( empty( $widget_options ) ) {
                            $widget_options = $dashboard->widgets[$widget];
                            update_user_meta( get_current_user_id(), 'wpo_' . $widget, $widget_options );
                        }

                        $widget_options['collapsed'] = ( isset( $widget_options['collapsed'] ) && true == $widget_options['collapsed'] ) ? true : false;

                        if ( $widget_options['collapsed'] ) { ?>
                            jQuery('.wpo_dashboard .collapsed_widgets').show().append( '<div class="<?php echo $widget ?> halfwidth_place <?php echo ( $widget_options['collapsed'] ) ? 'collapsed' : '' ?>" data-widget="<?php echo $widget ?>" <?php if ( isset( $widget_options['action'] ) ) { ?>data-action="<?php echo $widget_options['action'] ?>"<?php } ?> <?php if ( !empty( $widget_options['role'] ) ) { ?>data-role="<?php echo $widget_options['role'] ?>"<?php } ?>>' + ajax_loading + '</div>' );
                        <?php } else { ?>
                            active_widgets.append( '<div class="<?php echo $widget ?> halfwidth_place" data-widget="<?php echo $widget ?>" <?php if ( isset( $widget_options['action'] ) ) { ?>data-action="<?php echo $widget_options['action'] ?>"<?php } ?> <?php if ( !empty( $widget_options['role'] ) ) { ?>data-role="<?php echo $widget_options['role'] ?>"<?php } ?>>' + ajax_loading + '</div>' );
                        <?php } ?>


                        jQuery( '.halfwidth_place' ).css({
                            'background':'#f1f1f1',
                            'height':'200px',
                            'box-sizing':'border-box',
                            'border':'1px dashed #cccccc',
                            'width':'calc( 33% - 10px )',
                            'margin':'5px'
                        });

                        <?php $url_data = 'widget=' . ( isset( $widget_options['action'] ) ? $widget_options['action'] : $widget );
                        if ( !empty( $widget_options['role'] ) ) {
                            $url_data .= '&role=' . $widget_options['role'];
                        } ?>

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'load_widget' ) ?>',
                            data: '<?php echo $url_data ?>',
                            dataType: 'html',
                            success: function( data ) {
                                if( data != '0' ) {
                                    jQuery( '.<?php echo $widget ?>' ).css({
                                        'background':'transparent',
                                        'height':'auto',
                                        'border':'none',
                                        'width':'33%',
                                        'margin':'0'
                                    }).html( data );

                                    is_loaded ++;

                                    if( is_loaded == count_widgets ) {
                                        jQuery('.wpo_dashboard .active_widgets').masonry({
                                            // options
                                            itemSelector: '.halfwidth_place',
                                            // use element for option
                                            columnWidth: '.grid-sizer',
                                            percentPosition: true
                                        });
                                    }
                                }
                            }
                        });
                <?php }
                } ?>

                active_widgets.sortable({
                    handle: ".tile_title",
                    //placeholder: "widget_placeholder ui-corner-all",
                    update: function() {
                        var sort_array = [];
                        jQuery( '.halfwidth_place' ).each( function() {
                            sort_array.push( jQuery(this).data('widget') );
                        });

                        jQuery.ajax({
                            type: 'POST',
                            url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'update_widgets_order' ) ?>',
                            data: 'order=' + sort_array,
                            dataType: 'html',
                            success: function( data ) {}
                        });
                    },
                    start: function (e, ui) {
                        ui.item.removeClass( 'masonry-item' );
                        jQuery('.wpo_dashboard .active_widgets').masonry( 'reloadItems' ).masonry( 'layout' );
                    },
                    change: function () {
                        jQuery( '.wpo_dashboard .active_widgets' ).masonry('reloadItems');
                    },
                    stop: function (e, ui) {
                        ui.item.addClass( 'masonry-item' );
                        jQuery( '.wpo_dashboard .active_widgets' ).masonry('reloadItems').masonry( 'layout' );
                    }
                });

                //custom selectbox handlers
                body.on( 'click', '.widget_custom_palette', function(e) {
                    jQuery( '.widget_custom_palette' ).not( this ).removeClass( 'is_focus' );
                    jQuery( '.widget_custom_selectbox' ).removeClass( 'is_focus' );
                    jQuery(this).toggleClass( 'is_focus' );

                    e.stopPropagation();

                    jQuery( 'body' ).bind( 'click', function( event ) {
                        jQuery( '.widget_custom_palette' ).removeClass( 'is_focus' );
                        jQuery( 'body' ).unbind( event );
                    });
                });


                body.on( 'change', '.widget_custom_palette', function() {
                    var color = jQuery( this ).find( '.widget_colorize' ).data( 'value' );
                    var widget = jQuery(this).parents( '.halfwidth_place' ).data( 'widget' );

                    var obj = jQuery(this);

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'update_widgets_color' ) ?>',
                        data: 'color=' + color + '&widget=' + widget,
                        dataType: 'html',
                        success: function() {
                            obj.parents( '.tiles' ).removeClass().addClass( 'tiles' ).addClass( color );
                        }
                    });
                });


                body.on( 'click', '.widget_custom_palette .widget_color:not(.selected)', function() {
                    jQuery(this).parents( '.widget_custom_palette' ).find( '.widget_colorize' ).data( 'value', jQuery(this).data('value') );
                    jQuery(this).parents( '.widget_custom_palette' ).find( '.widget_color' ).removeClass( 'selected' );
                    jQuery(this).addClass( 'selected' );
                    jQuery( this ).parents( '.widget_custom_palette' ).trigger( 'change' );
                });


                body.on( 'click', '.widget_toggle', function() {
                    jQuery(this).parents( '.halfwidth_place' ).toggleClass( 'collapsed' );
                    var collapsed = jQuery(this).parents( '.halfwidth_place' ).hasClass( 'collapsed' );
                    var widget = jQuery(this).parents( '.halfwidth_place' ).data( 'widget' );
                    var $outerhtml = '';
                    if( collapsed ) {
                        $outerhtml = jQuery( '<div>' ).append( jQuery(this).parents( '.halfwidth_place' ).css({
                            'height': 'auto',
                            'box-sizing': 'border-box',
                            'border': 'none',
                            'width': '33%',
                            'margin': '0px',
                            'background': 'transparent',
                            'top': '0px',
                            'left': '0px',
                            'position': 'static'
                        }).clone() ).html();

                        jQuery(this).parents( '.halfwidth_place' ).remove();
                        jQuery('.wpo_dashboard .collapsed_widgets').append( $outerhtml );
                    } else {
                        $outerhtml = jQuery( '<div>' ).append( jQuery(this).parents( '.halfwidth_place' ).clone() ).html();
                        jQuery(this).parents( '.halfwidth_place' ).remove();
                        jQuery('.wpo_dashboard .active_widgets').append( $outerhtml );
                    }

                    if( jQuery('.wpo_dashboard .collapsed_widgets .halfwidth_place').length > 0 ) {
                        jQuery('.wpo_dashboard .collapsed_widgets').show();
                    } else {
                        jQuery('.wpo_dashboard .collapsed_widgets').hide();
                    }

                    active_widgets.masonry( 'reloadItems' ).masonry( 'layout' );

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'collapse_widget' ) ?>',
                        data: 'collapse=' + collapsed + '&widget=' + widget,
                        dataType: 'html',
                        success: function() {}
                    });

                    var sort_array = [];
                    jQuery( '.halfwidth_place' ).each( function() {
                        sort_array.push( jQuery(this).data('widget') );
                    });

                    jQuery.ajax({
                        type: 'POST',
                        url: '<?php echo WO()->get_ajax_route( get_class( $dashboard ), 'update_widgets_order' ) ?>',
                        data: 'order=' + sort_array,
                        dataType: 'html',
                        success: function() {}
                    });
                });
            });
        </script>

        <div class="active_widgets"><div class="grid-sizer"></div></div>
        <div class="collapsed_widgets"><hr /></div>

        <?php do_action( 'wpoffice_dashboard_widgets_footer'  );

        echo apply_filters( 'wpoffice_dashboard_after_widgets', '' ); ?>
    </div>
</div>