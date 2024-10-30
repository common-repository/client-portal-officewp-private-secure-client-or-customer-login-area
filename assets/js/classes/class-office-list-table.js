/*
 * List Table Plugin
 */
var list_table_inited = false;

var hash_data = {};
var reset_template = [];

(function( $, undefined ) {
    var options;

    var default_options = {
    };

    var unique_table = false;

    var methods = {
        init : function( settings ) {
            //merge default & current options
            options = $.extend( {}, default_options, settings );
            var object = jQuery(this);
            jQuery(this).data('list_table_localize', options);

            var body = jQuery('body');
            var unique = jQuery(this).data('id');
            reset_template[unique] = true;

            //set filters line current link
            var temp = parse_hash( unique );
            jQuery(this).find('.wpo_list_table_filters_line li a').removeClass('wpo_current');
            if( jQuery(this).find('.wpo_list_table_filters_line').find( '.' + temp.filters_tab + ' a').length > 0 ) {
                jQuery(this).find('.wpo_list_table_filters_line').find( '.' + temp.filters_tab + ' a').addClass('wpo_current');
            } else {
                jQuery(this).find('.wpo_list_table_filters_line').find( 'li:first a').addClass('wpo_current');
            }

            //first page load
            hash_data[unique] = {};
            hash_data[unique].filters_tab = jQuery(this).find('.wpo_list_table_filter_line_item.wpo_current').data('tab');
            load_content( unique );
            hash_data[jQuery(this).data('id')] = parse_hash( unique );

            //resize
            if( jQuery(this).width() <= 782 ) {
                jQuery(this).addClass( 'wpo_mobile' );
            } else {
                jQuery(this).removeClass( 'wpo_mobile' );
            }

            if ( !list_table_inited ) {
                list_table_inited = true;

                body.on( 'click', '.wpo-toggle-row', function() {
                    jQuery(this).parents( '.wpo_single_row' ).toggleClass( 'is_expanded' );
                });

                body.on( 'click', '.wpo_column.column-cb input[type="checkbox"]:not(.wpo_cb-select-all)', function() {

                    var table = jQuery(this).parents( '.wpo_list_table' );
                    var body = jQuery(this).parents( '.wpo_list_table_body' );
                    var check_all = jQuery(this).parents( '.wpo_list_table' ).find( '.wpo_cb-select-all' );

                    if( body.find( '.wpo_column.column-cb input[type="checkbox"]:checked' ).length == body.find( '.wpo_column.column-cb input[type="checkbox"]' ).length ) {
                        check_all.prop( 'checked', true );
                    } else {
                        check_all.prop( 'checked', false );
                    }

                    if( body.find( '.wpo_column.column-cb input[type="checkbox"]:checked' ).length > 0 ) {
                        table.find( '.wpo_bulk_action_apply' ).val( object.data('list_table_localize').texts.apply ).prop( 'disabled', false );
                    } else {
                        table.find( '.wpo_bulk_action_apply' ).val( object.data('list_table_localize').texts.no_items_selected ).prop( 'disabled', true );
                    }
                });

                body.on( 'click', '.wpo_cb-select-all', function(e) {
                    var table = jQuery(this).parents( '.wpo_list_table' );

                    if( jQuery( this ).prop( 'checked' ) ) {
                        jQuery( '.wpo_cb-select-all' ).prop( 'checked', true );
                        table.find( '.wpo_column.column-cb input[type="checkbox"]' ).prop( 'checked', true );
                        table.find( '.wpo_bulk_action_apply' ).val( object.data('list_table_localize').texts.apply );

                        if( table.find( '.wpo_bulk_action_selector li.wpo_selected' ).length > 0 ) {
                            table.find( '.wpo_bulk_action_apply' ).prop( 'disabled', false );
                        } else {
                            table.find( '.wpo_bulk_action_apply' ).prop( 'disabled', true );
                        }
                    } else {
                        jQuery( '.wpo_cb-select-all' ).prop( 'checked', false );
                        table.find( '.wpo_column.column-cb input[type="checkbox"]' ).prop( 'checked', false );
                        table.find( '.wpo_bulk_action_apply' ).val( object.data('list_table_localize').texts.no_items_selected ).prop( 'disabled', true );
                    }

                    e.stopPropagation();
                });


                body.on( 'click', '.wpo_bulk_actions', function(e) {
                    e.preventDefault();
                    var obj = jQuery(this);
                    if( obj.hasClass( 'wpo_dropdowned' ) ) {
                        return false;
                    }

                    jQuery( '.wpo_bulk_actions').removeClass( 'wpo_dropdowned' );

                    var win_h = jQuery( document ).height();

                    var bottom_border = obj.offset().top + obj.height();
                    var dropdown_h = obj.parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_actions_dropdown' ).height();

                    obj.toggleClass( 'wpo_dropdowned' );
                    if ( dropdown_h > win_h - bottom_border ) {
                        obj.parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_actions_dropdown' ).css( 'bottom', obj.height() + 1 + parseInt( obj.css('margin-bottom') ) );
                        obj.addClass( 'wpo_dropdowned_top' );
                    } else {
                        obj.parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_actions_dropdown' ).css( 'top', obj.height() + 1 + parseInt( obj.css('margin-top') ) );
                        obj.addClass( 'wpo_dropdowned_bottom' );
                    }

                    e.stopPropagation();

                    if( obj.hasClass( 'wpo_dropdowned' ) ) {
                        obj.parents( '.wpo_list_table_wrapper').parent().bind( 'click', function( event ) {
                            obj.removeClass( 'wpo_dropdowned' ).removeClass( 'wpo_dropdowned_bottom' ).removeClass( 'wpo_dropdowned_top' );
                            obj.parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_action_apply' ).prop( 'disabled', true );
                            obj.parents( '.wpo_bulk_actions_wrapper' ).find( 'li' ).removeClass( 'wpo_selected' );
                            obj.parents( '.wpo_list_table_wrapper').parent().unbind( event );
                        });
                    }
                });

                body.on( 'click', '.wpo_bulk_actions_dropdown', function(e) {
                    e.stopPropagation();
                });

                body.on( 'click', '.wpo_bulk_action_selector li', function() {
                    if( body.find( '.wpo_column.column-cb input[type="checkbox"]:checked').length == 0 ) {
                        return false;
                    }

                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper' ).data('id');

                    var hash = window.location.hash.substring( 1, window.location.hash.length );
                    if( hash !== '' ) {
                        hash = '&' + hash;
                    }

                    var obj = jQuery(this);
                    obj.siblings('li').removeClass( 'wpo_selected' );
                    obj.toggleClass( 'wpo_selected' );

                    if( obj.data('confirm') ) {
                        jQuery.wpo_confirm({
                            message : object.data('list_table_localize').texts.default_confirm + obj.html() + '?',
                            onYes: function() {
                                if( !obj.data('custom') ) {
                                    var ids = [];
                                    var serialize_ids = body.find( '.wpo_column.column-cb input[type="checkbox"]:checked' ).serializeArray();
                                    for( var index in serialize_ids ) {
                                        ids.push( serialize_ids[index].value*1 );
                                    }

                                    jQuery.ajax({
                                        type: "POST",
                                        url: object.data('list_table_localize').bulk_ajax_url,
                                        data: 'bulk_action=' + obj.data('value') + '&id=' + JSON.stringify(ids) + hash,
                                        dataType: 'json',
                                        timeout: 20000,
                                        success: function( data ) {
                                            if( data.refresh ) {
                                                reset_template[unique] = true;
                                                load_content( unique );
                                            }

                                            if ( data.message ) {
                                                jQuery(this).wpo_notice({
                                                    message: data.message,
                                                    type: data.status ? 'update' : 'error'
                                                });
                                            }

                                            obj.parents( '.wpo_list_table' ).find( '.wpo_cb-select-all' ).prop( 'checked', false );
                                            obj.siblings('li').removeClass( 'wpo_selected' );
                                        }
                                    });
                                } else {
                                    obj.trigger( 'wpo_bulk_action_selector_' + obj.data('value') );
                                }
                            },
                            object:this
                        });
                    } else {
                        if( !obj.data('custom') ) {
                            var ids = [];
                            var serialize_ids = body.find( '.wpo_column.column-cb input[type="checkbox"]:checked' ).serializeArray();
                            for( var index in serialize_ids ) {
                                ids.push( serialize_ids[index].value*1 );
                            }

                            jQuery.ajax({
                                type: "POST",
                                url: object.data('list_table_localize').bulk_ajax_url,
                                data: 'bulk_action=' + obj.data('value') + '&id=' + JSON.stringify(ids) + hash,
                                dataType: 'json',
                                timeout: 20000,
                                success: function( data ) {

                                    if( data.refresh ) {
                                        reset_template[unique] = true;
                                        load_content( unique );
                                    }

                                    if ( data.message ) {
                                        jQuery(this).wpo_notice({
                                            message: data.message,
                                            type: data.status ? 'update' : 'error'
                                        });
                                    }
                                    
                                    obj.parents( '.wpo_list_table' ).find( '.wpo_cb-select-all' ).prop( 'checked', false );
                                    obj.siblings('li').removeClass( 'wpo_selected' );
                                }
                            });
                        } else {
                            obj.trigger( 'wpo_bulk_action_selector_' + obj.data('value') );
                        }
                    }
                });

                jQuery( window ).resize( function() {
                    jQuery( '.wpo_bulk_actions').each( function() {
                        if ( jQuery(this).hasClass('wpo_dropdowned_top') ) {
                            jQuery(this).parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_actions_dropdown' ).css( 'bottom', jQuery(this).height() );
                        } else if( jQuery(this).hasClass('wpo_dropdowned_bottom') ) {
                            jQuery(this).parents( '.wpo_bulk_actions_wrapper' ).find( '.wpo_bulk_actions_dropdown' ).css( 'top', jQuery(this).height() );
                        }
                    });

                    jQuery('.wpo_list_table_wrapper').each( function() {
                        if( jQuery(this).width() <= 782 ) {
                            jQuery(this).addClass( 'wpo_mobile' );
                        } else {
                            jQuery(this).removeClass( 'wpo_mobile' );
                        }
                    });
                });

                body.on('keyup', '.wpo_search_line', function(e) {
                    if ( jQuery(this).val() != '' ) {
                        jQuery(this).parents('.wpo_search_box').addClass( 'wpo_search_filled' ).data('not_hide',false);
                    } else if ( jQuery(this).val() == '' && !jQuery(this).parents('.wpo_search_box').data('not_hide') ) {
                        jQuery(this).parents('.wpo_search_box').data('not_hide',true);
                    } else {
                        jQuery(this).parents('.wpo_search_box').removeClass( 'wpo_search_filled' ).data('not_hide',false);
                    }
                });

                body.on('change', '.wpo_search_line', function(e) {
                    if( jQuery(this).val() != '' ) {
                        jQuery(this).parents('.wpo_search_box').addClass( 'wpo_search_filled' ).data('not_hide',false);
                    } else {
                        jQuery(this).parents('.wpo_search_box').removeClass( 'wpo_search_filled' );
                    }
                });

                //searching
                body.on('click', '.wpo_search_submit', function(e) {
                    e.preventDefault();
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    if( hash_data[unique].search == jQuery(this).parents('.wpo_list_table_wrapper').find('.wpo_search_line').val() ) {
                        return false;
                    }

                    hash_data[unique].search = jQuery(this).parents('.wpo_list_table_wrapper').find('.wpo_search_line').val();
                    hash_data[unique].paged = 1;

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();
                    return false;
                });

                body.on('keypress', '.wpo_search_line', function(e) {
                    if ( e.which == '13' ) {
                        jQuery(this).parents('.wpo_list_table_wrapper').find('.wpo_search_submit').trigger('click');
                    }
                });

                //pagination
                body.on('change', '.wpo_pagination_current_page', function() {
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    if( jQuery(this).val() < 1 ) {
                        jQuery(this).val('1');
                    }

                    if( jQuery(this).val() > jQuery(this).data('max_page') ) {
                        jQuery(this).val(jQuery(this).data('max_page'));
                    }

                    hash_data[unique].paged = jQuery(this).val();

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();
                });
                body.on('click', '.wpo_first-page,.wpo_prev-page,.wpo_next-page,.wpo_last-page', function() {
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    hash_data[unique].paged = jQuery(this).data('page');

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();
                });

                //sorting
                body.on( 'click', '.wpo_list_table_column.wpo_sortable a, .wpo_list_table_column.wpo_sorted a', function(e) {
                    e.preventDefault();

                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    jQuery(this).parent().toggleClass('asc').toggleClass('desc');
                    if( !jQuery(this).parent().hasClass( 'wpo_sorted' ) ) {
                        jQuery(this).parents('.wpo_list_table').find('.wpo_list_table_header .wpo_list_table_column, .wpo_list_table_footer .wpo_list_table_column').removeClass('wpo_sorted').addClass('wpo_sortable');
                        jQuery(this).parent().addClass( 'wpo_sorted').removeClass('wpo_sortable');
                    }

                    hash_data[unique].order_by = jQuery(this).parent().data('column');
                    hash_data[unique].order = jQuery(this).parent().hasClass('asc') ? 'asc' : 'desc';

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();
                });

                //filters line
                body.on('click', '.wpo_list_table_filter_line_item', function(e) {
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    if( jQuery(this).hasClass('wpo_current') || jQuery(this).data('is_href') == 'true' ) {
                        return false;
                    }
                    if( jQuery(this).data('is_href') == true ) {
                        return true;
                    }

                    reset_template[unique] = true;

                    jQuery(this).parents('.wpo_list_table_filters_line').find( '.wpo_list_table_filter_line_item').removeClass('wpo_current');
                    jQuery(this).addClass('wpo_current');

                    hash_data[unique].filters_tab = jQuery(this).data('tab');
                    hash_data[unique].paged = 1;
                    hash_data[unique].search = '';

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();
                });

                //filters buttons
                body.on( 'click', '.wpo_filter', function(e) {
                    jQuery(this).toggleClass( 'filter_opened' );
                    e.stopPropagation();

                    var obj = jQuery(this);
                    obj.parents( '.wpo_list_table_wrapper').parent().bind( 'click', function( event ) {
                        if( jQuery('.wpo_filter_block').find( '.' + jQuery( event.target ).attr('class').replace( ' ', '.' ) ).length == 0 ) {
                            jQuery( '.wpo_filter' ).removeClass( 'filter_opened' );
                            obj.parents( '.wpo_list_table_wrapper' ).parent().unbind( event );
                        }
                    });
                });

                body.on( 'click', '.wpo_filter_wrapper', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                });


                body.on( 'change', '.wpo_filter_by', function() {
                    var obj = jQuery(this);

                    var hash = window.location.hash.substring( 1, window.location.hash.length );
                    if( hash !== '' ) {
                        hash = '&' + hash;
                    }

                    obj.parents('.wpo_filter_wrapper').find( '.wpo_ajax_content' ).addClass( 'wpo_is_loading' );
                    jQuery.ajax({
                        type: 'POST',
                        url: object.data('list_table_localize').filter_ajax_url,
                        data: window.location.search.replace("?", "") + hash + '&by=' + jQuery(this).val(),
                        dataType: "json",
                        success: function( data ){
                            if( !data.status ) {
                                alert( data.message );
                            } else {
                                //filters data
                                if( jQuery('.' + object.data('list_table_localize').filters_sample).length > 0 ) {
                                    obj.parents('.wpo_filter_wrapper').find( '.wpo_filter_selectors' ).html(jQuery('.' + object.data('list_table_localize').filters_sample).render([data]));
                                }

                                obj.parents('.wpo_filter_wrapper').find( '.wpo_ajax_content' ).removeClass( 'wpo_is_loading' );
                            }
                        }
                    });
                });

                //add filter
                body.on( 'click', '.wpo_add_filter', function() {
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    hash_data[unique][jQuery( '.wpo_filter_by' ).val()] = jQuery('.wpo_filter_value').val();
                    hash_data[unique].paged = 1;

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();

                    jQuery('.wpo_filter').removeClass( 'filter_opened' );
                });

                //remove filters
                body.on( 'click', '.wpo_remove_filter', function() {
                    var unique = jQuery(this).parents( '.wpo_list_table_wrapper').data('id');

                    if( typeof hash_data[unique][jQuery(this).parents( '.wpo_active_filter_wrapper' ).data('filter_by')] !== "undefined" ) {
                        delete hash_data[unique][jQuery(this).parents( '.wpo_active_filter_wrapper' ).data('filter_by')];
                    }
                    hash_data[unique].paged = 1;

                    unique_table = unique;

                    clear_hash();

                    window.location.hash = get_hash_string();

                    jQuery(this).parents( '.wpo_active_filter_wrapper' ).remove();
                });

                //history events when back/forward and change window.location.hash
                window.addEventListener("popstate", function(e) {
                    var unique = unique_table;

                   /* var temp_filters_tab = hash_data[unique].filters_tab;
                    hash_data[unique] = parse_hash( unique );

                    if( hash_data[unique].filters_tab != temp_filters_tab ) {
                        reset_template[unique] = true;
                    }

                    load_content( unique );*/


                    if ( unique ) {
                        var temp_filters_tab = hash_data[unique].filters_tab;
                        hash_data[unique] = parse_hash( unique );

                        if( hash_data[unique].filters_tab != temp_filters_tab ) {
                            reset_template[unique] = true;
                        }

                        load_content( unique );

                        unique_table = false;
                    }
                });

                jQuery(window).resize( function() {
                    jQuery( '.wpo_list_table_wrapper').each( function() {
                        list_table_resize( jQuery(this).data('id') );
                    })
                });
            }
        }
    };

    $.fn.listTable = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for jQuery.wpo_listtable plugin' );
        }
    };
})( jQuery );


//load AJAX content
function load_content( unique ) {
    //set if need to reset all templates
    var additional_string = '';
    var reset = reset_template[unique];

    if( reset_template[unique] ) {
        reset_template[unique] = false;
        additional_string += '&reset_template=' + true;
    }

    var table_wrapper = jQuery('.wpo_list_table_wrapper[data-id="' + unique + '"]');
    var table_data = table_wrapper.data('list_table_localize');

    if( typeof( table_data.additional_params ) !== 'undefined' ) {
        for( key in table_data.additional_params ) {
            additional_string += '&' + key + '=' + table_data.additional_params[ key ];
        }
    }

    //set filters tab
    var temp_hash = parse_hash( unique );
    var hash_array = [];
    for( var index in temp_hash ) {
        hash_array.push( index + '=' + temp_hash[index] );
    }

    var hash = hash_array.join('&');
    if( hash !== '' ) {
        hash = '&' + hash;
    }
    if( typeof temp_hash.filters_tab !== "undefined" ) {
        table_wrapper.find('.wpo_list_table_filter_line_item').removeClass('wpo_current');
        table_wrapper.find('.wpo_list_table_filter_line_item[data-tab="' + temp_hash.filters_tab + '"]').addClass('wpo_current');
    }

    //show loaders
    if( reset ) {
        table_wrapper.find('.wpo_list_table_content_wrapper').hide();
        table_wrapper.find('.wpo_search_box').hide();
        table_wrapper.find('.wpo_pagination_wrapper').hide();
        table_wrapper.find('.wpo_list_table > .wpo_list_table_ajax_loader_wrapper').show();
    } else {
        table_wrapper.find('.wpo_list_table_rows_wrapper').hide();
        table_wrapper.find('.wpo_list_table_body > .wpo_list_table_ajax_loader_wrapper').show();
    }

    jQuery.ajax({
        type: "POST",
        url: table_data.ajax_url,
        data: window.location.search.replace("?", "") + hash + additional_string,
        dataType: 'json',
        timeout: 20000,
        success: function( data ) {

            jQuery( document ).trigger( "wpo_list_table_data_loaded", [ data ] );

            if( reset ) {
                //load new templates
                if( jQuery('.' + table_data.sample).length > 0 ) {
                    jQuery('.' + table_data.sample).replaceWith( data.template.content_sample );
                } else {
                    jQuery('body').append( data.template.content_sample );
                }

                if( jQuery('.' + table_data.headers_sample).length > 0 ) {
                    jQuery('.' + table_data.headers_sample).replaceWith( data.template.headers_sample );
                } else {
                    jQuery('body').append( data.template.headers_sample );
                }

                //render header/footer
                table_wrapper.find('.wpo_list_table_header').html( jQuery('.' + table_data.headers_sample).render( {"header":true} ) );
                table_wrapper.find('.wpo_list_table_footer').html( jQuery('.' + table_data.headers_sample).render( {"header":false} ) );
            }

            if( data.data.length === 0 ) {
                //table data
                table_wrapper.find('.wpo_list_table_rows_wrapper').html( table_data.no_items ).show();

                //search box
                if( hash.search( new RegExp( "&search=.", "g") ) === -1 ) {
                    table_wrapper.find('.wpo_search_box').hide();
                } else {
                    table_wrapper.find('.wpo_search_box').show();
                }
            } else {
                //table data
                table_wrapper.find('.wpo_list_table_rows_wrapper').html( jQuery('.' + table_data.sample ).render( data.data ) ).show();
                //search box
                table_wrapper.find('.wpo_search_box').show();
            }

            //remove not capabilities actions
            table_wrapper.find( '.wpo_list_table_row_actions > span').find( 'a[data-hide="1"]').parent().remove();
            table_wrapper.find( '.wpo_list_table_row_actions > span:last-child').find('.wpo_action_separator').remove();

            //fix for remove separator from actions
            table_wrapper.find('.wpo_list_table_rows_wrapper .wpo_list_table_row_actions > span:last-child .wpo_list_table_action_separator').remove();

            if( reset ) {
                //show list table content
                table_wrapper.find( '.wpo_list_table_content_wrapper' ).show();
                //hide loader
                table_wrapper.find('.wpo_list_table > .wpo_list_table_ajax_loader_wrapper').hide();
            } else {
                //hide loader
                table_wrapper.find('.wpo_list_table_body > .wpo_list_table_ajax_loader_wrapper').hide();
            }

            //filters line data
            if( table_wrapper.find('.' + table_data.filters_line_sample).length > 0 ) {
                table_wrapper.find('.wpo_list_table_filters_line').html(jQuery('.' + table_data.filters_line_sample).render(data.filters_line));
            }

            //build filters field
            if( typeof data.available_filters !== "undefined" && data.available_filters.length > 0 ) {
                var selected = false;
                table_wrapper.find('.wpo_filter_by').find('option').each( function() {
                    if( jQuery.inArray( jQuery(this).attr('value'), data.available_filters ) != -1 ) {
                        jQuery(this).show();
                        if( !selected ) {
                            selected = true;
                            jQuery(this).parent().val( jQuery(this).attr('value') );
                        }
                    } else {
                        jQuery(this).hide();
                    }
                });

                //trigger filters
                table_wrapper.find('.wpo_filter_by').trigger( 'change' );
                //show filters
                table_wrapper.find('.wpo_filter_block').show();
            } else {
                table_wrapper.find('.wpo_filter_block').hide();
            }

            //build active filters
            if( typeof data.active_filters !== "undefined" && data.active_filters.length > 0 ) {
                if( table_wrapper.find('.' + table_data.active_filters_sample).length > 0 ) {
                    table_wrapper.find('.wpo_active_filters_wrapper').html(jQuery('.' + table_data.active_filters_sample).render(data.active_filters));
                }
            }

            //pagination render
            table_wrapper.find( '.' + table_data.pagination_sample ).each( function() {
                jQuery( this ).parent().find( '.wpo_pagination_wrapper' ).remove();
                jQuery( jQuery(this).render( data.pagination ) ).appendTo( jQuery(this).parent() );
            });

            //set search value
            var search_value = hash_data[unique].search ? decodeURIComponent( hash_data[unique].search ) : '';
            table_wrapper.find('.wpo_search_line').val( search_value ).trigger('change');

            //set sorting
            if( typeof temp_hash.order_by !== "undefined" && typeof temp_hash.order !== "undefined" ) {
                table_wrapper.find('.wpo_list_table_column.wpo_sorted').removeClass('wpo_sorted').addClass('wpo_sortable');
                table_wrapper.find('.wpo_list_table_column.wpo_sortable').addClass('desc').removeClass('asc');
                table_wrapper.find('.wpo_list_table_column.wpo_sortable[data-column="' + hash_data[unique].order_by + '"]').addClass(hash_data[unique].order).addClass('wpo_sorted').removeClass('wpo_sortable');
            }

            list_table_resize( unique );

            if( typeof wpo_load_content_callback == 'function' ) {
                wpo_load_content_callback();
            }
        }
    });
}

function list_table_resize( unique ) {
    var another_width = 0;

    var list_table = jQuery( '.wpo_list_table_wrapper[data-id="' + unique + '"]' );
    list_table.find('.wpo_list_table_header .wpo_list_table_column:not(.wpo_primary)').each( function(){
        another_width += jQuery(this).outerWidth() + 1;
    });

    list_table.find( '.wpo_list_table_column.wpo_primary, .wpo_column.wpo_primary').css('width', 'calc( 100% - ' + another_width + 'px)' );
}

//get hash string from global variable
function get_hash_string() {
    var hash_array = [];
    for( var unique in hash_data ) {
        for( var index in hash_data[unique] ) {
            hash_array.push( index + '[' + unique + ']=' + hash_data[unique][index] );
        }
    }
    hash_string = hash_array.join('&');

    return '#' + hash_string;
}


//parse hash string to object
function parse_hash( unique ) {
    var hash_obj = {};
    var hash = window.location.hash.substring( 1, window.location.hash.length );

    if ( hash == '' ) {
        return hash_obj;
    }

    var hash_array = hash.split('&');

    for ( var index in hash_array ) {
        var temp = hash_array[index].split('=');
        if( temp[0].search( new RegExp( "\\[" + unique + "\\]$", "g" ) ) !== -1 ) {
            temp[0] = temp[0].replace( new RegExp( "\\[" + unique + "\\]$", "g" ), '' );
            hash_obj[temp[0]] = temp[1];
        }
    }

    return hash_obj;
}


function clear_hash( set_hash ) {
    jQuery.each( hash_data, function( unique ) {
        if ( !jQuery('.wpo_list_table_wrapper[data-id="' + unique + '"]').length ) {
            delete hash_data[unique];
        } else {
            jQuery.each( hash_data[unique], function( property ) {
                if ( hash_data[unique][property] == '' ) {
                    delete hash_data[unique][property];
                }
            });
        }
    });

    if ( set_hash ) {
        unique_table = false;
        window.location.hash = get_hash_string();
    }
}