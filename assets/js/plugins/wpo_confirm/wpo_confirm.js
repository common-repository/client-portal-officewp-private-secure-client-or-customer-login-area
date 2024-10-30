/*
 * WPO Confirm Plugin
 * Open dialog popup (YES/NO)
 */

(function( $, undefined ) {
    var options;

    var default_options = {
        'message' : '',
        'yes_label' : 'Yes',
        'no_label' : 'No'
    };

    var methods = {
        init : function( settings ) {
            //merge default & current options
            options = $.extend( {}, default_options, settings );

            $( this ).each( function() {

                $( this ).data( 'options', options );

                methods.build.apply( $( this ), [options] );

                //init links clicks for show confirm
                $( this ).click( function(e) {
                    var options = $( this ).data( 'options' );
                    $( '#wpo_confirm_message' ).html( options.message );
                    $( '#wpo_confirm_button_yes' ).html( options.yes_label );
                    $( '#wpo_confirm_button_no' ).html( options.no_label );

                    methods.show.apply( this );

                    e.stopPropagation();
                });

            });

        },
        build : function( settings ) {

            if( !methods.is_builded.apply( this ) ) {

                var obj = $( '<div id="wpo_confirm_block"></div>').appendTo( 'body' ).html( '<div class="wpo_confirm">' +
                    '<div id="wpo_confirm_title">Confirmation</div>' +
                '<div id="wpo_confirm_message"></div>' +
                    '<div id="wpo_confirm_buttons">' +
                    '<div id="wpo_confirm_button_yes" class="wpo_confirm_button">Yes</div>' +
                '<div id="wpo_confirm_button_no" class="wpo_confirm_button">No</div>' +
                '</div>' +
                '</div>' +
                '<div id="wpo_confirm_block_back"></div>' );

                $( document ).on( 'click', '#wpo_confirm_button_yes', function() {
                    var obj = $( '#wpo_confirm_block').data( 'obj' );
                    methods.yes.apply( obj );
                });

                $( document ).on( 'click', '#wpo_confirm_button_no', function() {
                    var obj = $( '#wpo_confirm_block').data( 'obj' );
                    methods.no.apply( obj );
                });

                $( document ).on( 'click', '#wpo_confirm_block_back', function() {
                    var obj = $( '#wpo_confirm_block').data( 'obj' );
                    methods.close.apply( obj );
                });


            }
        },
        is_builded : function() {
            //return confirm already exists
            return $('#wpo_confirm_block').length;
        },
        show : function() {
            $( '#wpo_confirm_block').data( 'obj', this ).show();
            var width = $('.wpo_confirm').width();
            var height = $('.wpo_confirm').height();
            $('.wpo_confirm').css('margin', '-' + height/2 + 'px 0 0 -' + width/2 + 'px' );
        },
        close : function() {
            var opt = $( this ).data( 'options' );

            $( '#wpo_confirm_message' ).html( '' );
            $( '#wpo_confirm_block' ).hide();

            if( typeof opt.onClose === "function" ) {
                opt.onClose.apply( this );
            }
        },
        yes : function() {
            var opt = $( this ).data( 'options' );

            var data = {};
            if( $( '#wpo_confirm_block').find('form').length ) {
                var temp = $( '#wpo_confirm_block').find('form').serializeArray();
                for( key in temp ) {
                    data[ temp[ key ]['name'] ] = temp[ key ]['value'];
                }
            }

            methods.close.apply( this );

            if( typeof opt.onYes === "function" ) {
                opt.onYes.apply( this, [ data ] );
            }
        },
        no : function() {
            var opt = $( this ).data( 'options' );

            var data = {};
            if( $( '#wpo_confirm_block').find('form').length ) {
                var temp = $( '#wpo_confirm_block').find('form').serializeArray();
                for( key in temp ) {
                    data[ temp[ key ]['name'] ] = temp[ key ]['value'];
                }
            }

            methods.close.apply( this );

            if( typeof opt.onNo === "function" ) {
                opt.onNo.apply( this, [ data ] );
            }
        }
    };

    $.fn.wpo_confirm = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for jQuery.wpo_confirm plugin' );
        }
    };

    $.wpo_confirm = function( settings ) {
        options = $.extend( {}, default_options, settings );
        $( settings.object ).data( 'options', options );


        methods.build.apply( $( settings.object ), [options] );
        $( '#wpo_confirm_message' ).html( options.message );
        $( '#wpo_confirm_button_yes' ).html( options.yes_label );
        $( '#wpo_confirm_button_no' ).html( options.no_label );
        methods.show.apply( settings.object );
    }

})( jQuery );