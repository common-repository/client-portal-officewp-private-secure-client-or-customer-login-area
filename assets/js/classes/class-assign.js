var assign_ajax_data = {};
var reset_assign = true;

var wpo_assign_selected_items = {};
var wpo_all_assigned_items = {};

var is_ajax = true;
var custom_assign_query = '';

jQuery( document ).ready( function() {
    var body = jQuery('body');

    //click on assign link
    body.on( 'click', '.wpo_assign_link', function(e) {
        if ( jQuery(this).hasClass( 'wpo_disabled' ) ) {
            return false;
        }

        var object = jQuery(this).data('object');
        var object_id = jQuery(this).data('object_id');

        if( typeof jQuery(this).data('ajax') == 'undefined' ) {
            is_ajax = false;
            var value = jQuery(this).next().val();
        } else {
            is_ajax = true;
        }

        var args_list = 'all';
        if( typeof jQuery(this).data('args_list') != 'undefined' ) {
            args_list = jQuery(this).data('args_list');
        }

        jQuery.pulllayer({
            ajax_url    : wpo_assign_data.ajax_url,
            ajax_data   : 'object=' + object + '&object_id=' + object_id + '&args_list=' + args_list + '&is_ajax=' + is_ajax + custom_assign_query,
            object      : this,
            onOpenContentLoad : function(data) {
                if( !is_ajax ) {
                    if( value != '' ) {
                        wpo_assign_selected_items = JSON.parse( jQuery.base64Decode( value ) );

                        if( wpo_assign_selected_items != '' ) {
                            for( key in wpo_assign_selected_items ) {
                                jQuery( '.wpo_assign_filters_line_link[data-data="' + key + '"]').find( '.wpo_assign_count').html( '(' + wpo_assign_selected_items[key].length + ')' );
                            }
                        } else {
                            wpo_assign_selected_items = {};
                            jQuery( '.wpo_assign_filters_line_link').each( function() {
                                wpo_assign_selected_items[jQuery(this).data('data')] = [];
                            });
                        }
                    } else {
                        wpo_assign_selected_items = {};
                        jQuery( '.wpo_assign_filters_line_link').each( function() {
                            wpo_assign_selected_items[jQuery(this).data('data')] = [];
                        });
                    }
                } else {
                    wpo_assign_selected_items = JSON.parse( jQuery.base64Decode( data.value ) );
                }
                jQuery('.wpo_assign_filters_line_link:first').trigger('click');
            },
            onClose : function() {
                wpo_assign_selected_items = {};
                wpo_all_assigned_items = {};
            }
        });
        e.stopPropagation();
    });


    //click on filters line tabs
    body.on( 'click', '.wpo_assign_filters_line_link', function() {
        jQuery( '.wpo_assign_filters_line_link').removeClass('wpo_current');
        jQuery(this).addClass('wpo_current');

        assign_ajax_data.additional_data = jQuery(this).data('data');
        assign_ajax_data.paged = 1;
        assign_ajax_data.search = '';
        reset_assign = true;

        load_assign_content();
    });


    //click on items
    body.on( 'click', '.wpo_assign_form_values input', function() {
        var current_tab = jQuery( '.wpo_assign_filters_line_link.wpo_current' );

        if( typeof wpo_assign_selected_items[current_tab.data( 'data' )] === 'undefined' ) {
            wpo_assign_selected_items[current_tab.data( 'data' )] = [];
        }

        if( jQuery(this).attr('type') == 'checkbox' ) {
            if( jQuery(this).prop('checked') ) {
                wpo_assign_selected_items[current_tab.data( 'data' )].push( jQuery(this).val() );
            } else {
                wpo_assign_selected_items[current_tab.data( 'data' )].splice( wpo_assign_selected_items[current_tab.data( 'data' )].indexOf( jQuery(this).val() ), 1 );
            }

            jQuery.uniqueSort( wpo_assign_selected_items[current_tab.data( 'data' )] );

            //change select all on this page
            if( jQuery('.wpo_assign_form_values input:checked').length == jQuery('.wpo_assign_form_values input').length ) {
                jQuery('.wpo_assign_all_page').prop('checked', true);
            } else {
                jQuery('.wpo_assign_all_page').prop('checked', false);
            }

            //change select all
            if( wpo_assign_selected_items[current_tab.data( 'data' )].length == wpo_all_assigned_items[current_tab.data('data')].length ) {
                jQuery('.wpo_assign_all').prop('checked', true);
            } else {
                jQuery('.wpo_assign_all').prop('checked', false);
            }
        } else if ( jQuery(this).attr('type') == 'radio' ) {
            if( jQuery(this).prop('checked') ) {
                wpo_assign_selected_items[current_tab.data( 'data' )] =  [ jQuery(this).val() ];
            } else {
                wpo_assign_selected_items[current_tab.data( 'data' )] =  [];
            }
        }

        //change count checked assigns
        current_tab.find('.wpo_assign_count').html('(' + wpo_assign_selected_items[current_tab.data( 'data' )].length + ')');
    });


    //select all items on page
    body.on( 'click', '.wpo_assign_all_page', function() {
        if( jQuery(this).prop('checked') ) {
            jQuery('.wpo_assign_form_values input').prop('checked', false).trigger('click');
        } else {
            jQuery('.wpo_assign_form_values input').prop('checked', true).trigger('click');
        }
    });


    //select all items in tab
    body.on( 'click', '.wpo_assign_all', function() {
        var current_tab = jQuery( '.wpo_assign_filters_line_link.wpo_current' );

        if( typeof wpo_assign_selected_items[current_tab.data( 'data' )] === 'undefined' ) {
            wpo_assign_selected_items[current_tab.data( 'data' )] = [];
        }

        if( jQuery(this).prop('checked') ) {
            for( key in wpo_all_assigned_items[current_tab.data('data')] ) {
                wpo_assign_selected_items[current_tab.data( 'data' )].push( wpo_all_assigned_items[current_tab.data('data')][key] );
            }
            jQuery('.wpo_assign_form_values input').prop('checked', true);
        } else {
            for( key in wpo_all_assigned_items[current_tab.data('data')] ) {
                wpo_assign_selected_items[current_tab.data( 'data' )].splice( wpo_assign_selected_items[current_tab.data( 'data' )].indexOf( wpo_all_assigned_items[current_tab.data('data')][key] ), 1 );
            }
            //wpo_assign_selected_items[current_tab.data( 'data' )] = [];
            jQuery('.wpo_assign_form_values input').prop('checked', false);
        }

        jQuery.uniqueSort( wpo_assign_selected_items[current_tab.data( 'data' )] );

        //change select all on this page
        if( jQuery('.wpo_assign_form_values input:checked').length == jQuery('.wpo_assign_form_values input').length ) {
            jQuery('.wpo_assign_all_page').prop('checked', true);
        } else {
            jQuery('.wpo_assign_all_page').prop('checked', false);
        }

        current_tab.find('.wpo_assign_count').html('(' + wpo_assign_selected_items[current_tab.data( 'data' )].length + ')');
    });


    //pagination
    body.on('change', '.wpo_assign_pagination_current_page', function() {
        if( jQuery(this).val() < 1 ) {
            jQuery(this).val('1');
        }

        if( jQuery(this).val() > jQuery(this).data('max_page') ) {
            jQuery(this).val(jQuery(this).data('max_page'));
        }

        assign_ajax_data.paged = jQuery(this).val();
        load_assign_content();
    });

    body.on('click', '.wpo_assign_first-page,.wpo_assign_prev-page,.wpo_assign_next-page,.wpo_assign_last-page', function() {
        if( jQuery(this).hasClass('wpo_disabled') ) {
            return false;
        }

        assign_ajax_data.paged = jQuery(this).data('page');
        load_assign_content();
    });


    //searching
    body.on('change', '.wpo_assign_search input', function() {
        if( assign_ajax_data.search == jQuery(this).val() ) {
            return false;
        }

        assign_ajax_data.paged = 1;
        assign_ajax_data.search = jQuery(this).val();
        load_assign_content();
    });


    //assign button click
    body.on( 'click', '.wpo_assign_button', function() {
        var object = jQuery('.wpo_assign_object').val();
        var object_id = jQuery('.wpo_assign_object_id').val();
        var args_list = jQuery('.wpo_assign_args_list').val();

        if( is_ajax ) {
            jQuery.ajax({
                type        : "POST",
                url         : wpo_assign_data.ajax_assign_items,
                data        : 'object=' + object + '&object_id=' + object_id + '&args_list=' + args_list + '&items=' + jQuery.base64Encode( JSON.stringify( wpo_assign_selected_items ) ),
                dataType    : 'json',
                timeout     : 20000,
                success     : function( data ) {
                    if( data.status ) {
                        if ( data.message ) {
                            jQuery(this).wpo_notice({
                                message: data.message,
                                type: 'update'
                            });

                            var tooltip_html = '';
                            var count_users = 0;
                            var count_categories = 0;
                            var is_users = false;
                            //var is_categories = false;
                            for( var k in wpo_assign_selected_items ) {
                                var dat = JSON.parse( jQuery.base64Decode( k ) );
                                if ( dat.key == 'member' || dat.key == 'user' ) {
                                    count_users += wpo_assign_selected_items[k].length;
                                    is_users = true;
                                } else {
                                    tooltip_html += dat.key.charAt(0).toUpperCase() + dat.key.substr( 1, dat.key.length - 1 ) + 's - ' + wpo_assign_selected_items[k].length + '<br />';
                                }
                            }

                            if ( is_users ) {
                                tooltip_html = wpo_assign_data.texts.members + ' - ' + count_users + '<br />' + tooltip_html;
                            }

                            jQuery('.wpo_assign_link.wpo_pulllayer_link_opened .wpo_assign_link_count').html(' (' + data.count + ')').attr('data-wpo_tooltip', tooltip_html);
                            jQuery('.wpo_assign_link.wpo_pulllayer_link_opened').pulllayer('close');

                            wpo_assign_selected_items = {};
                            wpo_all_assigned_items = {};
                        }
                    }
                }
            });
        } else {
            var count = 0;
            var tooltip_html = '';
            var count_users = 0;
            var is_users = false;
            for( var k in wpo_assign_selected_items ) {
                var data = JSON.parse( jQuery.base64Decode( k ) );
                if ( data.key == 'member' || data.key == 'user' ) {
                    count_users += wpo_assign_selected_items[k].length;
                    is_users = true;
                } else {
                    tooltip_html += data.key.charAt(0).toUpperCase() + data.key.substr( 1, data.key.length - 1 ) + 's - ' + wpo_assign_selected_items[k].length + '<br />';
                }

                count += wpo_assign_selected_items[k].length;
            }

            if ( is_users ) {
                tooltip_html = 'Members - ' + count_users + '<br />' + tooltip_html;
            }

            jQuery('.wpo_assign_link.wpo_pulllayer_link_opened').next()
                .val( count != 0 ? jQuery.base64Encode( JSON.stringify( wpo_assign_selected_items ) ) : '' );
            jQuery('.wpo_assign_link.wpo_pulllayer_link_opened .wpo_assign_link_count').html(' (' + count + ')').attr('data-wpo_tooltip', tooltip_html);
            jQuery('.wpo_assign_link.wpo_pulllayer_link_opened').pulllayer('close');

            wpo_assign_selected_items = {};
            wpo_all_assigned_items = {};
        }
    });
});


function load_assign_content() {
    var current_tab = jQuery( '.wpo_assign_filters_line_link.wpo_current' );

    var ajax_data = assign_ajax_data;
    var ajax_array = [];
    for( var index in ajax_data) {
        ajax_array.push( index + '=' + ajax_data[index] );
    }
    ajax_data = ajax_array.join('&');

    var additional_string = '';
    var reset = reset_assign;
    if( reset_assign ) {
        reset_assign = false;
        additional_string += '&reset_template=' + true;
    }


    var object = jQuery( '.wpo_assign_object' ).val();
    var object_id = jQuery( '.wpo_assign_object_id' ).val();
    var args_list = jQuery('.wpo_assign_args_list').val();

    additional_string += '&object=' + object + '&object_id=' + object_id + '&args_list=' + args_list;

    //show loaders
    if( reset ) {
        jQuery('.wpo_assign_form_wrapper').hide();
        jQuery('.wpo_assign_search').hide().find('input').val('');
        jQuery('.wpo_assign_pagination_wrapper').hide();
        jQuery('.wpo_assign_form > .wpo_assign_items_ajax_loader_wrapper').show();
    } else {
        jQuery('.wpo_assign_items').hide();
        jQuery('.wpo_assign_form_wrapper > .wpo_assign_items_ajax_loader_wrapper').show();
    }

    jQuery('.wpo_assign_bulk_actions').hide();
    jQuery('.wpo_assign_items').html('');
    jQuery.ajax({
        type        : "POST",
        url         : wpo_assign_data.ajax_reload_form,
        data        : ajax_data + additional_string,
        dataType    : 'json',
        timeout     : 20000,
        success     : function( data ) {
            if( data.data.items ) {
                if( jQuery('.wpo_assign_form_sample').length > 0 ) {
                    var min_items_in_col = Math.floor( data.data.items.length / 4 );
                    var ost = data.data.items.length - min_items_in_col*4;
                    var items_col_array = [];
                    var ii = 4;
                    while ( ii > 0 ) {
                        if ( data.data.items.length > ( 4 - ii ) ) {
                            items_col_array.push( min_items_in_col );
                        }
                        ii--;
                    }

                    if ( ost > 0 ) {
                        ii = 0;
                        while( ost > 0 ) {
                            items_col_array[ii]++;
                            ii++;
                            ost--;
                        }
                    }

                    var br_index = [0];
                    var iii = 0;
                    jQuery.each( items_col_array, function(e) {
                        if( e > 0 ) {
                            var count = 0;
                            var sppp = items_col_array.slice( 0, e );
                            jQuery.each(sppp, function(index) {
                                count += parseInt( sppp[index] );
                            });

                            br_index.push( count );
                        }
                        iii++;
                    });

                    var tmpl =  jQuery.templates({
                        markup: ".wpo_assign_form_sample",
                        helpers: {
                            'indexes' : br_index
                        }
                    });

                    jQuery('.wpo_assign_items').html( tmpl.render( data.data ) );
                }
            } else {
                jQuery('.wpo_assign_items').html('<div class="wpo_assign_empty">' + wpo_assign_data.texts.empty + '</div>');
            }

            if( data.data.items ) {
                jQuery('.wpo_assign_search').show();
            } else if( jQuery('.wpo_assign_search').find('input').val() == '' ) {
                jQuery('.wpo_assign_search').hide();
            }

            if( data.data.items && data.data.type == 'checkbox' ) {
                jQuery('.wpo_assign_bulk_actions').show();
            }

            //pagination render
            jQuery( '.wpo_assign_pagination_wrapper' ).html( jQuery( '.wpo_assign_pagination_sample' ).render( data.pagination ) ).show();

            if( reset ) {
                jQuery('.wpo_assign_form_wrapper').show();
                jQuery('.wpo_assign_pagination_wrapper').show();
                jQuery('.wpo_assign_form > .wpo_assign_items_ajax_loader_wrapper').hide();
            } else {
                jQuery('.wpo_assign_items').show();
                jQuery('.wpo_assign_form_wrapper > .wpo_assign_items_ajax_loader_wrapper').hide();
            }

            if( data.all_ids.length ) {
                wpo_all_assigned_items[assign_ajax_data.additional_data] = data.all_ids;
            }

            jQuery('.wpo_assign_form_values').each( function() {
                if( typeof wpo_assign_selected_items[current_tab.data( 'data' )] !== 'undefined' && jQuery.inArray( jQuery(this).find('input').val(), wpo_assign_selected_items[current_tab.data( 'data' )] ) != -1 ) {
                    jQuery(this).find('input').prop('checked', true);
                } else {
                    jQuery(this).find('input').prop('checked', false);
                }
            });

            if( jQuery('.wpo_assign_form_values input:checked').length == jQuery('.wpo_assign_form_values input').length ) {
                jQuery('.wpo_assign_all_page').prop('checked', true);
            } else {
                jQuery('.wpo_assign_all_page').prop('checked', false);
            }

            if( typeof wpo_assign_selected_items[current_tab.data( 'data' )] !== 'undefined' && typeof wpo_all_assigned_items[current_tab.data( 'data' )] !== 'undefined' ) {
                var merges = 0;
                for ( var i = 0; i < wpo_assign_selected_items[current_tab.data( 'data' )].length; i++) {
                    if( jQuery.inArray( wpo_assign_selected_items[current_tab.data( 'data' )][i], wpo_all_assigned_items[current_tab.data( 'data' )] ) != -1 ) {
                        merges++;
                    }
                }
                if( merges == wpo_all_assigned_items[current_tab.data( 'data' )].length ) {
                    jQuery('.wpo_assign_all').prop('checked', true);
                } else {
                    jQuery('.wpo_assign_all').prop('checked', false);
                }
            } else {
                jQuery('.wpo_assign_all').prop('checked', false);
            }
        }
    });
}