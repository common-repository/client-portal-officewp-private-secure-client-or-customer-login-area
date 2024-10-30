(function( $, undefined ) {
    var default_options = {
        strength_level_default : function( strength ) {
            var options = $( this ).data( 'wpo_password_strength' ),
                $strengthResult = options.block_indicator,
                $confirm_weak = jQuery('.wpo_confirm_weak');
            $strengthResult.addClass( 'bad' ).html( wpo_password_strength.text.strength.very_weak );
            $confirm_weak.show();
        }
    };

    var methods = {
        init : function( settings ) {
            var options = $( this ).data( 'wpo_password_strength' ),
                field_name = $(this).prop('name'),
                field_id = $(this).prop('id');

            if( typeof options == 'undefined' ) {
                options = {};
            }
            options = $.extend( {}, default_options, options, settings );

            $( this ).data( 'wpo_password_strength', options );

            if( typeof options.pre_init == 'function' ) {
                options.pre_init.apply( this );
            }

            if( typeof options.create_password != 'undefined' && options.create_password ) {
                methods.generate_password.apply( this );
            } else {
                var $password_wrapper = $(this).closest('.wpo_password_wrapper'),
                    validation = $password_wrapper.data('wpo-valid');
                    $password_wrapper.data('wpo-temp-valid', validation);
                    $password_wrapper.removeAttr('data-wpo-valid');
                    $(this).closest('form').wpo_validation('start');
            }

            $(this).after('<input type="text" id="' + field_id + '_text" name="' + field_name + '_text" autocomplete="off" />');

            $(this).on( 'keyup', function() {
                $(this).next().val( jQuery(this).val() );
                $(this).wpo_password_strength('validate_process');
            });

            $(this).next().on( 'keyup', function() {
                $(this).prev().val( jQuery(this).val() );
                $(this).prev().wpo_password_strength('validate_process');
            });

            $( '.wpo_cancel_password' ).on( 'click', function() {
                jQuery('.wpo_password_wrapper').hide();
                jQuery('.wpo_generate_password').show();
                jQuery('#pw_weak').prop('checked',false);
            });

            $( '.wpo_generate_password' ).on( 'click', function() {
                var $obj = $(this);
                $obj.attr('data-loading', '1');
                $('.wpo_password_wrapper').find('input[type="password"]').wpo_password_strength('generate_password', function() {
                    jQuery('.wpo_password_wrapper').show();
                    $obj.removeAttr('data-loading').hide();
                } );
                var $password_wrapper = $(this).closest('.wpo_button_password_wrapper').siblings('.wpo_password_wrapper'),
                    validation = $password_wrapper.data('wpo-temp-valid');
                    $password_wrapper.attr('data-wpo-valid', validation);
                    $(this).closest('form').wpo_validation('start');
            });

            $( '.wpo_toggle_password' ).on( 'click', function() {
                $(this).parents('.wpo_password_wrapper').find('.wpo_password_input_wrapper').toggleClass('wpo_show_password');
                $(this).toggleClass('wpo_password_visible');
                if( $(this).hasClass('wpo_password_visible') ) {
                    $(this).html( $(this).data('hide_text') );
                } else {
                    $(this).html( $(this).data('show_text') );
                }
            });
            if( typeof options.after_init == 'function' ) {
                options.after_init.apply( this );
            }
        },
        validate_process : function() {
            var options = $( this ).data( 'wpo_password_strength' ),
                $strengthResult = options.block_indicator,
                pass = $( this ).val(),
                blacklistArray = wp.passwordStrength.userInputBlacklist(),
                $confirm_weak = jQuery('.wpo_confirm_weak');

            $strengthResult.removeClass( 'short bad good strong' );
            $confirm_weak.hide();
            if( typeof options.pre_validate == 'function' ) {
                options.pre_validate.apply( this );
            }

            if( typeof options.blacklist_filter == 'function' ) {
                blacklistArray = blacklistArray.concat( options.blacklist_filter.apply( this ) );
            }


            var strength = wp.passwordStrength.meter( pass, blacklistArray, pass );
            if( typeof options.strength_filter == 'function' ) {
                strength = options.strength_filter.apply( this, [ strength, pass, blacklistArray ] );
            }

            switch ( strength ) {
                case 2:
                    $strengthResult.addClass( 'bad' ).html( wpo_password_strength.text.strength.weak );
                    $confirm_weak.show();

                    if( typeof options.strength_level_2 == 'function' ) {
                        options.strength_level_2.apply( this );
                    }
                    break;
                case 3:
                    $strengthResult.addClass( 'good' ).html( wpo_password_strength.text.strength.medium );
                    if( typeof options.strength_level_3 == 'function' ) {
                        options.strength_level_3.apply( this );
                    }
                    break;
                case 4:
                    $strengthResult.addClass( 'strong' ).html( wpo_password_strength.text.strength.strong );
                    if( typeof options.strength_level_3 == 'function' ) {
                        options.strength_level_3.apply( this );
                    }
                    break;
                default:
                    if( typeof options.strength_level_default == 'function' ) {
                        options.strength_level_default.apply( this, [ strength ] );
                    }
            }

            return strength;
        },
        generate_password : function( func ) {
            var obj = this, 
                $obj = $(this);
            jQuery.ajax({
                type     : 'POST',
                dataType : 'json',
                url      : wpo_password_strength.generate_password_url,
                success: function( data ){
                    $obj.val( data.password );
                    $obj.next().val( data.password );
                    methods.validate_process.apply( obj );

                    if( typeof func !== 'undefined' ) {
                        func.apply( obj );
                    }

                    if ( true == wpo_password_strength.hide_show_button ) {
                        jQuery('.wpo_password_wrapper').show();
                    }
                }
            });
        },
        settings : function( args ) {
            var options = $( this ).data( 'wpo_password_strength' );
            if( typeof options == 'undefined' ) {
                options = {};
            }
            $.extend( options, args );
            $( this ).data( 'wpo_password_strength', options );
        }
    };

    $.fn.wpo_password_strength = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for $.wpo_password_strength plugin' );
        }
    };

})( jQuery );