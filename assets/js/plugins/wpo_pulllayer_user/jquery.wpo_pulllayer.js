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
                options.level = $('.wpo_pulllayer.wpo_pulllayer_opened').length + 1;
                $( this ).data( 'options', options );

                //build layers wrappers HTML
                methods.build_layer.apply( this, [$( this ).data('options')] );

                //init links clicks for show layer
                $( this ).click( function(e) {
                    if ( $(this).hasClass('wpo_pulllayer_link_opening') ) {
                        return false;
                    }

                    $( '.wpo_pulllayer_link_opened_' + $( this ).data('options').level ).removeClass( 'wpo_pulllayer_link_opened_' + $( this ).data('options').level );

                    $(this).addClass('wpo_pulllayer_link_opened_' + $( this ).data('options').level ).addClass('wpo_pulllayer_link_opened').addClass('wpo_pulllayer_link_opening');

                    if( $( this ).data('options').level == 1 ) {
                        if ($.browser.msie) {
                            jQuery('.wpo_pulllayer_background').show();
                        } else {
                            jQuery('.wpo_pulllayer_background').fadeTo('slow', 1);
                        }
                    }

                    methods.showLoader.apply( this );

                    methods.show.apply( this, [$( this ).data('options')] );
                    e.stopPropagation();
                });
            });
        },
        showLoader: function() {
            jQuery( '.wpo_pulllayer_background' ).append( templates.ajax_loading );
        },
        hideLoader: function() {
            jQuery( '.wpo_pulllayer_ajax_loader_wrapper' ).remove();
        },
        build_layer : function( settings ) {
            if( !methods.is_builded.apply( this, [settings] ) ) {
                //add to body isset layer class
                var obj = this;
                var wrapper = $( 'body' );
                wrapper.addClass( 'wpo_pulllayer-' + settings.level );

                if( settings.level == '1' ) {
                    wrapper.append('<div class="wpo_pulllayer_background"></div>');

                    //hide Layer on background click
                    wrapper.on( 'click', '.wpo_pulllayer_background', function(e) {
                        var pulllayer = $( '.wpo_pulllayer.wpo_pulllayer_opened:last' );
                        methods.close.apply( pulllayer.data( 'obj' ), [pulllayer] );
                    });
                }

                //append layer content
                wrapper.append(
                    '<div class="wpo_pulllayer" id="wpo_pulllayer-' + settings.level + '">' +
                        '<div class="wpo_pulllayer_topnav">'+
                            '<div class="wpo_pulllayer_top_controls">' +
                                '<div class="wpo_pulllayer_close" title="Close"></div>' +
                            '</div>' +
                            '<div class="wpo_pulllayer_title"><h2></h2></div>' +
                            wpo_ajax_loader.line +
                        '</div>' +
                        '<div class="wpo_pulllayer_content">' +
                            templates.ajax_loading +
                        '</div>' +
                    '</div>'
                );


                //hide layer on close button
                $( 'body' ).on( 'click', '#wpo_pulllayer-' + settings.level + ' > .wpo_pulllayer_topnav .wpo_pulllayer_close', function(e) {
                    var pulllayer = $(this).parents( '#wpo_pulllayer-' + settings.level );

                    var previous_pulllayer = false;
                    if( settings.level > 1 ) {
                        previous_pulllayer = $( '#wpo_pulllayer-' + ( settings.level*1 - 1 ) );
                    }

                    methods.close.apply( pulllayer.data( 'obj' ), [pulllayer, previous_pulllayer] );
                    e.stopPropagation();
                });


                $( 'body' ).on( 'click', '#wpo_pulllayer-' + settings.level , function(e) {
                    e.stopPropagation();
                });

                // init resize window action for resize layer
                $( window ).resize( function() {
                    $('.wpo_pulllayer').each( function() {
                        methods.resize.apply( obj, [$(this)] );
                    });
                });
            }
        },
        is_builded : function( settings ) {
            //return layer already exists
            return $('body').hasClass( 'wpo_pulllayer-' + settings.level );
        },
        loadContent: function( current_layer ) {
            //clear title/content - show loading
            current_layer.find( '.wpo_pulllayer_title h2').first().html( '' );
            current_layer.find( '.wpo_pulllayer_content' ).first().html( templates.ajax_loading );

            var obj = this;
            var settings = $(obj).data('options');

            var ajax_data = settings.ajax_data;
            if( typeof settings.changeAjaxData === "function" ) {
                ajax_data = settings.changeAjaxData.apply( obj, [settings.ajax_data] );
            }

            jQuery.ajax({
                type: "POST",
                url: settings.ajax_url,
                data: ajax_data,
                dataType: 'json',
                timeout: 20000,
                success: function( data ) {
                    //hide loader at background
                    methods.hideLoader.apply( obj );

                    //insert current title
                    if( typeof data.title != 'undefined' ) {
                        current_layer.find( '.wpo_pulllayer_title h2' ).first().html( data.title );
                    }

                    //insert content
                    current_layer.find( '.wpo_pulllayer_content' ).first().append( data.content );
                    $(obj).removeClass('wpo_pulllayer_link_opening');

                    /*if( !current_layer.hasClass( 'wpo_pulllayer_opened' ) ) {
                        //newly layer open
                        current_layer.addClass('wpo_pulllayer_opened').show();

                        if( typeof settings.onOpenAnimateEnd === "function" ) {
                            settings.onOpenAnimateEnd.apply( obj );
                        }
                    }*/
                    current_layer.show();
                    if( typeof settings.onOpenAnimateEnd === "function" ) {
                        settings.onOpenAnimateEnd.apply( obj );
                    }

                    if( typeof settings.onOpenContentLoad === "function" ) {
                        settings.onOpenContentLoad.apply( obj, [data] );
                    }

                    methods.resize.apply( obj, [current_layer] );
                    current_layer.show();
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
            current_layer.data('obj', this).addClass('wpo_pulllayer_opened');

            methods.loadContent.apply( this, [current_layer] );

            if( settings.level > 1 ) {
                $( '#wpo_pulllayer-' + ( settings.level - 1 ) ).hide();
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

            pulllayer.find( '.wpo_pulllayer_title h2' ).html( '' );
            pulllayer.find( '.wpo_pulllayer_content' ).html( templates.ajax_loading );
            pulllayer.removeClass('wpo_pulllayer_opened').hide();
            if( typeof settings.onCloseAnimateEnd === "function" ) {
                settings.onCloseAnimateEnd.apply( $( obj ) );
            }

            if( settings.level > 1 ) {
                wrapper = typeof wrapper === "undefined" ? $( '#wpo_pulllayer-' + ( settings.level*1 - 1 ) ) : wrapper;
                wrapper.show();
            } else {
                if ($.browser.msie) {
                    jQuery('.wpo_pulllayer_background').hide();
                } else {
                    jQuery('.wpo_pulllayer_background').fadeTo('slow', 0, function() {
                        jQuery(this).hide();
                    });
                }
            }

            if( pulllayer.find( '.wpo_pulllayer_opened' ).length > 0 ) {
                pulllayer.trigger( 'click' );
            }
        },
        refresh : function() {
            var pulllayer = jQuery( '.wpo_pulllayer.wpo_pulllayer_opened' );
            methods.loadContent.apply( jQuery( '.wpo_pulllayer.wpo_pulllayer_opened' ).get( 0 ), [pulllayer] );
        },
        resize: function( current_layer ) {
            //10% left/right margins if width more than window width
            current_layer.css({
                'width': $(window).width() * 0.9 + 'px',
                'height': $(window).height() * 0.9 + 'px'
            });

            current_layer.css({
                'left': ( $(window).width() - current_layer.width() ) / 2 + 'px',
                'top': ( $(window).height() - current_layer.height() ) / 2 + 'px'
            });
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
        //options.level = $( '.wpo_pulllayer' ).length + 1;
        options.level = $('.wpo_pulllayer.wpo_pulllayer_opened' ).length + 1;
        $( settings.object ).data( 'options', options );
        console.log(options.level);
        methods.build_layer.apply( settings.object, [options] );

        if ( $( settings.object ).hasClass('wpo_pulllayer_link_opening') ) {
            return false;
        }

        $( '.wpo_pulllayer_link_opened_' + options.level ).removeClass( 'wpo_pulllayer_link_opened_' + options.level );
        $( settings.object ).addClass( 'wpo_pulllayer_link_opened_' + options.level ).addClass('wpo_pulllayer_link_opened').addClass('wpo_pulllayer_link_opening');

        if( options.level == 1 ) {
            if ($.browser.msie) {
                jQuery('.wpo_pulllayer_background').show();
            } else {
                jQuery('.wpo_pulllayer_background').fadeTo('slow', 1);
            }
        }

        methods.showLoader.apply( settings.object );
        methods.show.apply( settings.object, [options] );
    }

})( jQuery );