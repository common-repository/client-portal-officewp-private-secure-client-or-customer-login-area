/*
 * Pull Layer Plugin
 * Open Layer By Click on Link or any block
 */

(function( $, undefined ) {
    var options;

    var default_options = {
        level         : '1',
        ajax_url      : '',
        ajax_data     : ''
    };

    var templates = {
        'ajax_loading'  : '<div class="wpo_pulllayer_ajax_loader_wrapper">' +
                            '<div class="wpo_pulllayer_ajax_loader">' +
                                wpo_ajax_loader.loader +
                            '</div>' +
                        '</div>'
    };

    var link_opened;

    var methods = {
        init : function( settings ) {
            //merge default & current options
            options = $.extend( {}, default_options, settings );

            this.each( function() {
                options.level = $(this).parents('.wpo_pulllayer').length + 1;
                $( this ).data( 'options', options );

                //build layers wrappers HTML
                methods.build_layer.apply( this, [$( this ).data('options')] );

                //init links clicks for show layer
                $( this ).click( function(e) {
                    if ( $(this).hasClass('wpo_pulllayer_link_opening') ) {
                        return false;
                    }
                    /*if ( $(this).hasClass('wpo_pulllayer_link_opened_' + $( this ).data('options').level ) ) {
                        return false;
                    }*/
                    $( '.wpo_pulllayer_link_opened_' + $( this ).data('options').level ).removeClass( 'wpo_pulllayer_link_opened_' + $( this ).data('options').level );

                    //close more then level opened layers
                    if ( $( this ).data('options').level < $('.wpo_pulllayer_opened').length ) {
                        var item = $('.wpo_pulllayer_opened').length;
                        while( $( this ).data('options').level < item ) {
                            methods.close.apply( jQuery( '#wpo_pulllayer-' + item ).get( 0 ) );
                            item--;
                        }
                    }

                    $(this).addClass('wpo_pulllayer_link_opened_' + $( this ).data('options').level ).addClass('wpo_pulllayer_link_opened').addClass('wpo_pulllayer_link_opening');
                    if( $( this ).data('options').level > 1 ) {
                        var wrapper = $( '#wpo_pulllayer-' + ( $( this ).data( 'options' ).level*1 - 1 ) );

                        if( wrapper.find( '.wpo_pulllayer_opened' ).length == 0 ) {
                            wrapper.animate({
                                width: wrapper.parent().width()*9 / 10,
                                left: $( window ).width() - wrapper.parent().width()*9 / 10
                            }, 1000 );
                        }
                    }

                    methods.show.apply( this, [$( this ).data('options')] );
                    e.stopPropagation();
                });
            });
        },
        build_layer : function( settings ) {
            if( !methods.is_builded.apply( this, [settings] ) ) {
                //add to body isset layer class
                $('body').addClass( 'wpo_pulllayer-' + settings.level );

                var custom_class = typeof settings.custom_class != 'undefined' ? settings.custom_class : '';

                //append layer content
                var wrapper = ( settings.level == '1' ) ? $( '#wpcontent' ) : $( '#wpo_pulllayer-' + ( settings.level*1 - 1 ) );
                wrapper.append(
                    '<div class="wpo_pulllayer ' + custom_class + '" id="wpo_pulllayer-' + settings.level + '">' +
                        '<div class="wpo_pulllayer_topnav">'+
                            '<div class="wpo_pulllayer_top_controls">' +
                                '<div class="wpo_pulllayer_close" title="Close Layer"></div>' +
                            '</div>' +
                            '<div class="wpo_pulllayer_title"><h2></h2></div>' +
                            wpo_ajax_loader.line +
                        '</div>' +
                        '<div class="wpo_pulllayer_content">' +
                            templates.ajax_loading +
                        '</div>' +
                        '<div class="wpo_pulllayer_left_controls"></div>' +
                    '</div>'
                );

                //init layer size
                if( settings.level > 1 ) {
                    $('#wpo_pulllayer-' + settings.level).height($(window).height()).width(wrapper.parent().width() * 3 / 4).css('left', wrapper.parent().width());
                } else {
                    $('#wpo_pulllayer-' + settings.level).height($(window).height()).width(wrapper.width() * 3 / 4).css('left', $(window).width());
                }


                //init layer action buttons
                $( 'body' ).on( 'click', '#wpo_pulllayer-' + settings.level + ' > .wpo_pulllayer_left_controls', function(e) {
                    var pulllayer = $(this).parents( '#wpo_pulllayer-' + settings.level );

                    if ( pulllayer.hasClass( 'wpo_pulllayer_wide' ) ) {
                        if( pulllayer.find( '.wpo_pulllayer_opened' ).length > 0 ) {
                            pulllayer.animate({
                                width: pulllayer.parent().width()*9 / 10,
                                left: $( window ).width() - pulllayer.parent().width()*9 / 10
                            }, 500 );
                        } else {
                            if( settings.level > 1 ) {
                                pulllayer.animate({
                                    width: pulllayer.parent().parent().width()*3 / 4,
                                    left: $( window ).width() - pulllayer.parent().parent().width()*3 / 4
                                }, 500 );
                            } else {
                                pulllayer.animate({
                                    width: pulllayer.parent().width()*3 / 4,
                                    left: $( window ).width() - pulllayer.parent().width()*3 / 4
                                }, 500 );
                            }
                        }
                    } else {
                        pulllayer.animate({
                            width: pulllayer.parent().width(),
                            left: $( window ).width() - pulllayer.parent().width()
                        }, 500 );
                    }
                    pulllayer.toggleClass( 'wpo_pulllayer_wide' );
                    e.stopPropagation();
                });


                //hide layer on close button
                $( 'body' ).on( 'click', '#wpo_pulllayer-' + settings.level + ' > .wpo_pulllayer_topnav .wpo_pulllayer_close', function(e) {
                    var pulllayer = $(this).parents( '#wpo_pulllayer-' + settings.level );

                    var wrapper = false;
                    if( settings.level > 1 ) {
                        wrapper = $(this).parents( '#wpo_pulllayer-' + ( settings.level*1 - 1 ) );
                    }

                    methods.close.apply( pulllayer.data( 'obj' ), [pulllayer, wrapper] );
                    e.stopPropagation();
                });


                //hide Layer on body click
                $( 'body' ).on( 'click', '#' + wrapper.attr('id'), function(e) {
                    var pulllayer = wrapper.find( '#wpo_pulllayer-' + settings.level );

                    if( pulllayer.hasClass('wpo_pulllayer_opened') ) {
                        methods.close.apply( pulllayer.data( 'obj' ), [pulllayer, wrapper] );

                        if( settings.level > 1 ) {
                            e.stopPropagation();
                        }
                    }
                });


                $( 'body' ).on( 'click', '#wpo_pulllayer-' + settings.level , function(e) {
                    e.stopPropagation();
                });

                // init resize window action for resize layer
                $( window ).resize( function() {
                    $('.wpo_pulllayer').each( function() {
                        if( $(window).width() > 583 ) {
                            jQuery('.wpo_pulllayer').css('padding', "");
                        }

                        if( $(this).hasClass( 'wpo_pulllayer_wide' ) ) {
                            $(this).height( $(window).height()).width( $(this).parent().width());
                            if( $(this).hasClass( 'wpo_pulllayer_opened' ) ) {
                                $(this).css('left', $( window ).width() - $(this).parent().width() );
                            }
                        } else {
                            if( $(this).find( '.wpo_pulllayer_opened' ).length > 0 ) {
                                $(this).height( $(window).height()).width( $(this).parent().width()*9 / 10 );
                                if( $(this).hasClass( 'wpo_pulllayer_opened' ) ) {
                                    $(this).css('left', $( window ).width() - $(this).parent().width()*9 / 10 );
                                }
                            } else {
                                if( $(this).attr('id') != 'wpo_pulllayer-1' ) {
                                    $(this).height($(window).height()).width($(this).parent().parent().width() * 3 / 4);
                                    if( $(this).hasClass( 'wpo_pulllayer_opened' ) ) {
                                        $(this).css('left', $( window ).width() - $(this).parent().parent().width() * 3 / 4 );
                                    }
                                } else {
                                    $(this).height($(window).height()).width($(this).parent().width() * 3 / 4);
                                    if( $(this).hasClass( 'wpo_pulllayer_opened' ) ) {
                                        $(this).css('left', $( window ).width() - $(this).parent().width() * 3 / 4 );
                                    }
                                }
                            }
                        }
                    });

                    $(document).trigger('scroll');
                });

                if( settings.level == 1 ) {
                    $(document).scroll(function () {
                        if ($(window).width() <= 583) {
                            var scrolled = window.pageYOffset || document.documentElement.scrollTop;

                            if ( scrolled < 46 ) {
                                jQuery('.wpo_pulllayer').css('padding', ( 46 - scrolled ) + 'px 0 0 0');
                            } else {
                                jQuery('.wpo_pulllayer').css('padding', '0');
                            }
                        }
                    });
                }
            }
        },
        is_builded : function( settings ) {
            //return layer already exists
            return $('body').hasClass( 'wpo_pulllayer-' + settings.level );
        },
        loadContent: function( current_layer ) {
            current_layer.find( '.wpo_pulllayer_title h2').first().html( '' );
            current_layer.find( '.wpo_pulllayer_content' ).first().html( templates.ajax_loading );
            current_layer.find( '.wpo_pulllayer_ajax_loader_wrapper' ).first().show();

            current_layer.find('.wpo_help_button').remove();
            current_layer.find('.wpo_help_box_wrap').remove();

            var obj = this;
            var settings = $(obj).data('options');

            var ajax_data = settings.ajax_data;
            if( typeof settings.changeAjaxData === "function" ) {
                ajax_data = settings.changeAjaxData.apply( obj, [settings.ajax_data] );
            }

            if( typeof settings.beforeContentLoad === "function" ) {
                settings.beforeContentLoad.apply( obj );
            }

            jQuery.ajax({
                type: "POST",
                url: settings.ajax_url,
                data: ajax_data,
                dataType: 'json',
                timeout: 20000,
                success: function( data ) {

                    if( typeof data.title != 'undefined' ) {
                        current_layer.find( '.wpo_pulllayer_title h2' ).first().html( data.title );
                    }

                    if( typeof data.help != 'undefined' && data.help != '' ) {
                        var flag = typeof data.show_help != 'undefined' && data.show_help == '1';
                        current_layer.find( '.wpo_pulllayer_topnav' )
                            .append('<a title="Help Mode" href="javascript: void(0);" class="wpo_right_button wpo_help_button ' + ( flag ? 'wpo_active' : '' ) + '"></a>')
                            .append('<div class="wpo_help_box_wrap ' + ( flag ? 'visible' : '' ) + '"><div class="wpo_help_box">' + data.help + '</div>' +
                                wpo_ajax_loader.line + '</div>');

                        current_layer.find( '.wpo_pulllayer_topnav .wpo_help_button' ).click( function() {
                            jQuery.ajax({
                                type: "POST",
                                url: wpo_ajax_loader.save_help_flag_url,
                                data: 'flag=' + ( jQuery(this).hasClass('wpo_active') ? '0' : '1' ),
                                dataType: 'json'
                            });

                            jQuery('.wpo_help_button').toggleClass('wpo_active');
                            jQuery('.wpo_help_box_wrap').toggleClass('visible');

                            if ( jQuery('.wpo_help_box_wrap').hasClass('visible') ) {
                                jQuery('.wpo_pulllayer_content').css( 'height', 'calc( 100% - ' + ( 80 + jQuery('.wpo_help_box_wrap').height()*1 ) + 'px )' );
                            } else {
                                jQuery('.wpo_pulllayer_content').css( 'height', 'calc( 100% - 90px )' );
                            }
                        });
                    }

                    if ( jQuery('.wpo_help_box_wrap').hasClass('visible') ) {
                        jQuery('.wpo_pulllayer_content').css( 'height', 'calc( 100% - ' + ( 80 + jQuery('.wpo_help_box_wrap').height()*1 ) + 'px )' );
                    } else {
                        jQuery('.wpo_pulllayer_content').css( 'height', 'calc( 100% - 90px )' );
                    }

                    current_layer.find( '.wpo_pulllayer_content' ).first().append( data.content );
                    current_layer.find( '.wpo_pulllayer_ajax_loader_wrapper' ).first().hide();

                    $(obj).removeClass('wpo_pulllayer_link_opening');

                    if( typeof settings.onOpenContentLoad === "function" ) {
                        settings.onOpenContentLoad.apply( obj, [data] );
                    }
                }
            });
        },
        show: function( settings ) {
            link_opened = $( this );

            var current_layer = $( '#wpo_pulllayer-' + settings.level );
            current_layer.find( '.wpo_pulllayer_left_controls *').remove();
            current_layer.data( 'options', settings );

            if( typeof settings.onOpen === "function" ) {
                settings.onOpen.apply( this, [settings] );
            }

            var obj = this;
            current_layer.data('obj', this);

            if( current_layer.hasClass( 'wpo_pulllayer_opened' ) ) {
                //layer already opened
                methods.loadContent.apply( this, [current_layer] );
            } else {
                //newly layer open
                current_layer.show().animate({
                    left: $( window ).width() - current_layer.width()
                }, 1000, function() {
                    current_layer.addClass('wpo_pulllayer_opened').show();

                    if( typeof settings.onOpenAnimateEnd === "function" ) {
                        settings.onOpenAnimateEnd.apply( obj );
                    }

                    methods.loadContent.apply( obj, [current_layer] );
                });

                $(document).trigger('scroll');
            }
        },
        close: function( pulllayer, wrapper ) {
            var obj = this;

            //var settings = $(obj).data('options');
            if( typeof pulllayer === "undefined" ) {
                pulllayer = jQuery( '.wpo_pulllayer_opened:last' );
            }
            var settings = pulllayer.data('options');

            $('.wpo_pulllayer_link_opened_' + settings.level).removeClass('wpo_pulllayer_link_opened_' + settings.level ).removeClass('wpo_pulllayer_link_opened');

            if( typeof settings.onClose === "function" ) {
                settings.onClose.apply( $( this ) );
            }

            if( typeof pulllayer === "undefined" ) {
                pulllayer = jQuery( '#wpo_pulllayer-' + settings.level );
            }

            wrapper = typeof wrapper === "undefined" ?
                ( ( settings.level == '1' ) ? $( '#wpcontent' ) : $( '#wpo_pulllayer-' + ( settings.level*1 - 1 ) ) ) :
                wrapper;

            pulllayer.removeClass('wpo_pulllayer_opened');
            pulllayer.animate({
                left: $(window).width()
            }, 1000, function() {
                pulllayer.find( '.wpo_pulllayer_title h2' ).html( '' );
                pulllayer.find( '.wpo_pulllayer_content' ).html( templates.ajax_loading );
                pulllayer.find('.wpo_help_box_wrap').remove();
                pulllayer.hide();
                if( typeof settings.onCloseAnimateEnd === "function" ) {
                    settings.onCloseAnimateEnd.apply( $( obj ) );
                }
            });

            if( settings.level > 1 ) {
                if( !wrapper.hasClass( 'wpo_pulllayer_wide' ) ) {
                    wrapper.animate({
                        width: wrapper.parent().width()*3 / 4,
                        left: $( window ).width() - wrapper.parent().width()*3 / 4
                    }, 1000 );
                }
            }

            if( pulllayer.find( '.wpo_pulllayer_opened' ).length > 0 ) {
                pulllayer.trigger( 'click' );
            }
        },
        refresh : function() {
            var pulllayer = jQuery(this);
            methods.loadContent.apply( this, [pulllayer] );

            //old
            //link_opened.trigger('click');
        },
        get_query_paams : function( url ) {
            var a = document.createElement('a');
            a.href = url;
            var params = a.search.substr(1).split('&');
            var b = {};
            for (var i = 0; i < params.length; ++i)
            {
                var p=params[i].split('=', 2);
                if (p.length == 1)
                    b[p[0]] = "";
                else
                    b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
            }
            return b;
        }
    };

    $.fn.pulllayer = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for jQuery.wpo_pulllayer plugin' );
        }
    };

    $.pulllayer = function( settings ) {
        options = $.extend( {}, default_options, settings );
        options.level = $( settings.object ).parents( '.wpo_pulllayer' ).length + 1;
        $( settings.object ).data( 'options', options );

        methods.build_layer.apply( settings.object, [options] );

        if ( $( settings.object ).hasClass('wpo_pulllayer_link_opening') ) {
            return false;
        }
        /*if ( $( settings.object ).hasClass('wpo_pulllayer_link_opened_' + options.level ) ) {
            return false;
        }*/
        $( '.wpo_pulllayer_link_opened_' + options.level ).removeClass( 'wpo_pulllayer_link_opened_' + options.level );

        //close more then level opened layers
        if ( options.level < $('.wpo_pulllayer_opened').length ) {
            var item = $('.wpo_pulllayer_opened').length;
            while( options.level < item ) {
                methods.close.apply( jQuery( '#wpo_pulllayer-' + item ).get( 0 ) );
                item--;
            }
        }

        $( settings.object ).addClass( 'wpo_pulllayer_link_opened_' + options.level ).addClass('wpo_pulllayer_link_opened').addClass('wpo_pulllayer_link_opening');
        if( options.level > 1 ) {
            var wrapper = $( '#wpo_pulllayer-' + ( options.level*1 - 1 ) );

            if( wrapper.find( '.wpo_pulllayer_opened' ).length == 0 ) {
                wrapper.animate({
                    width: wrapper.parent().width()*9 / 10,
                    left: $( window ).width() - wrapper.parent().width()*9 / 10
                }, 1000 );
            }
        }

        methods.show.apply( settings.object, [options] );
    }

})( jQuery );