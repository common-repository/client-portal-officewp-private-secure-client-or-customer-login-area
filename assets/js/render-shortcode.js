(function( $ ){
    var methods = {
        init : function( options ) {
            var settings = $.extend( {}, options );
            $(this).data( 'wpoRenderShortcode', settings );

            methods.start.apply( this );
        },
        init_handlers : function() {
            $(this).find('.wpo_search_submit').off('click').on('click', function(e) {
                jQuery(this).closest('.wpo_shortcode_block').wpoRenderShortcode('start');
                e.stopPropagation();
            });
            $(this).find('.wpo_search_line').off('keypress').on('keypress', function(e) {
                if( e.which == 13 ) {
                    jQuery(this).closest('.wpo_shortcode_block').wpoRenderShortcode('start');
                    e.stopPropagation();
                }
            });

            $(this).find('.wpo_frontend_pagination a').off('click').on('click', function(e) {
                jQuery(this).closest('.wpo_shortcode_block').find('input[name="paged"]').val( jQuery(this).data('page') );
                jQuery(this).closest('.wpo_shortcode_block').wpoRenderShortcode('start');
                e.stopPropagation();
            });
        },
        start : function() {
            var fields_data = jQuery.base64Encode( $(this).find(':input').serialize() ).split('+').join('-');
            var settings = $(this).data( 'wpoRenderShortcode' );
            var $obj = $(this);
            var obj = this;

            methods.showLoading.apply( this );
            jQuery.ajax({
                type: "POST",
                url: settings.ajax_url,
                data: 'wpo_form_data=' + fields_data,
                dataType: 'json',
                timeout: 20000,
                success: function( data ) {
                    methods.hideLoading.apply( obj );
                    if( data.status ) {
                        $obj.html( data.message );
                    } else {
                        alert( data.message );
                    }
                    methods.init_handlers.apply( obj );
                    if( typeof settings != 'undefined' && typeof settings.success == 'function' ) {
                        settings.success.apply( obj );
                    }
                }
            });
        },
        settings : function( wpo_args ) {
            var settings = $(this).data( 'wpoRenderShortcode' );
            $.extend( settings, wpo_args );
        },
        showLoading : function() {
            if( jQuery(this).find('.wpo_ajax_loader_bg').length > 0 ) {
                jQuery(this).find('.wpo_ajax_loader_bg').remove();
            }
            jQuery(this).append('<div class="wpo_ajax_loader_bg">' + wpo_render_shortcode.loader + '</div>');
            jQuery(this).addClass('wpo_loading');
        },
        hideLoading : function() {
            var $l = jQuery(this).find('.wpo_ajax_loader_bg');
            if( $l.length > 0 ) {
                $l.remove();
            }
            jQuery(this).removeClass('wpo_loading');
        }
    };

    $.fn.wpoRenderShortcode = function ( method ) {
        if ( methods[ method ] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || !method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error('Methid ' + method + ' does not exists in jQuery.wpoRenderShortcode');
        }
    };

})( jQuery );