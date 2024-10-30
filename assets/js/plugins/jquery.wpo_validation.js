(function( $, undefined ) {
    var methods = {},
        initial_methods = {
        init : function( args ) {
            if( typeof args == 'object' && args.length > 0 ) {
                methods.settings.apply( this, args );
            }
            $(this).data( 'wpo_validate_init_flag', true );
            methods.start.apply( this );
        },
        is_init : function() {
            return $(this).data( 'wpo_validate_init_flag' ) === true;
        },
        start : function() {
            var validate_blocks = methods.get_validate_blocks.apply( this );
            $(this).data( 'wpo_validate_blocks', validate_blocks );

            var item;
            for( key in validate_blocks ) {
                item = validate_blocks[ key ];
                if( typeof item.is_single != undefined && item.is_single ) {
                    item.$objects.data('wpo_form', this).data('wpo_valid_options', item);

                    item.$objects.off( item.validation_event ).on( item.validation_event , function() {
                        var options = $(this).data('wpo_valid_options'),
                            form = $(this).data('wpo_form');
                        if( $(this).data('wpo_start_validate') !== true ) return true;

                        var value = $(form).wpo_validation( 'get_value_by_' + options.value_type, this );

                        var result = $(form).wpo_validation( 'validate_value', this, value );
                        $(form).wpo_validation( 'remove_validation_messages', this );
                        if( result !== true ) {
                            $(form).wpo_validation( 'show_validation_message', this, result );
                        }
                    });
                }
            }
        },
        get_validate_blocks : function() {
            var validate_blocks = [],
                obj = this,
                validation_keys;
            $(this).find('*[data-wpo-valid]').each(function() {
                validation_keys = $(this).data('wpo-valid');
                if( validation_keys != '' ) {
                    validation_keys = $(this).data('wpo-valid').split(' ');
                    validate_blocks.push({
                        $objects   : $(this),
                        validation_event : methods.get_block_validation_event.apply( obj, [ this ] ),
                        value_type : methods.get_block_value_type.apply( obj, [ this ] ),
                        validation_keys : validation_keys,
                        is_single : true
                    });
                }
                //if( $(this).is(':input') ) {
                    /*validate_blocks.push({
                        $objects   : $(this),
                        validation_event : methods.get_block_validation_event.apply( obj, [ this ] ),
                        value_type : methods.get_block_value_type.apply( obj, [ this ] ),
                        validation_keys : validation_keys,
                        is_single : true
                    });*/
                /*} else {
                    validate_blocks.push({
                        parent     : this,
                        $objects   : $(this).find(':input'),
                        validation_event : methods.get_block_validation_event.apply( obj, [ this ] ),
                        validation_keys : validation_keys
                    });
                }*/
            });
            return validate_blocks;
        },
        get_block_validation_event : function( obj ) {
            var event,
                checkboxes_count = $(obj).find(':checkbox').length,
                radio_count = $(obj).find(':radio').length,
                input_count = $(obj).find(':input').length;
            $(obj).data('wpo_start_validate', true);
            if( ( checkboxes_count > 0 && checkboxes_count == input_count ) ||
                ( radio_count > 0 && radio_count == input_count ) ) {
                event = 'check_group.wpo_validation';
                $(obj).find(':input').each(function() {
                    $(this).data( 'wpo_valid_parent_obj', obj );
                }).off('click').on('click', function() {
                    var obj = $(this).data('wpo_valid_parent_obj');
                    $(obj).trigger('check_group.wpo_validation');
                });
            } else if( $(obj).is(':checkbox') || $(obj).is(':radio') ) {
                event = 'click.wpo_validation';
            } else if( $(obj).is('select') ) {
                event = 'change.wpo_validation';
            } else if( $(obj).hasClass('wpo_password_wrapper') ) {
                event = 'change_password.wpo_validation';

                $(obj).data('wpo_start_validate', false);
                $(obj).find('input[type="password"], input[type="text"]').off( 'keyup.wpo_validation')
                .on( 'keyup.wpo_validation', function() {
                    $(this).closest('.wpo_password_wrapper').data('wpo_start_validate', true);
                });

                $(obj).find('input[type="password"], input[type="text"]').off('blur.wpo_validation')
                .on('blur.wpo_validation', function() {
                    $(this).closest('.wpo_password_wrapper').trigger('change_password.wpo_validation');
                });
            } else {
                $(obj).data('wpo_start_validate', false);
                $(obj).off( 'keyup.wpo_validation').on( 'keyup.wpo_validation', function() {
                    $(this).data('wpo_start_validate', true);
                });
                event = 'blur.wpo_validation';
            }
            return event;
        },
        get_block_value_type : function( obj ) {
            var type,
                checkboxes_count = $(obj).find(':checkbox').length,
                radio_count = $(obj).find(':radio').length,
                input_count = $(obj).find(':input').length;
            if( ( checkboxes_count > 0 && checkboxes_count == input_count ) ||
                ( radio_count > 0 && radio_count == input_count ) ) {
                type = 'check_group';
            } else if( $(obj).is(':checkbox') || $(obj).is(':radio') ) {
                type = 'checked';
            } else if( $(obj).hasClass('wpo_password_wrapper') ) {
                type = 'password_block';
            } else {
                type = 'value';
            }
            return type;
        },
        get_value_by_check_group : function( obj ) {
            var result = [];
            $(obj).find(':input:checked').each(function() {
                result.push( $(this).val() );
            });
            return result;
        },
        get_value_by_password_block : function( obj ) {
            return $(obj).find('input[type="password"]').val();
        },
        get_value_by_value : function( obj ) {
            return $(obj).val();
        },
        get_value_by_checked : function( obj ) {
            return $(obj).is(':checked') ? $(obj).val() : undefined;
        },
        validate_submit : function() {
            var validate_blocks = $(this).data( 'wpo_validate_blocks' );
            if( validate_blocks == '' ) {
                validate_blocks = methods.get_validate_blocks.apply( this );
                $(this).data( 'wpo_validate_blocks', validate_blocks );
            }

            var validation_flag = true, item, value, result;
            for( key in validate_blocks ) {
                item = validate_blocks[ key ];
                if( typeof item.is_single != undefined && item.is_single ) {
                    if( !item.$objects.data('wpo_form') ) {
                        item.$objects.data('wpo_form', this);
                    }
                    if( !item.$objects.data('wpo_valid_options') ) {
                        item.$objects.data('wpo_valid_options', item);
                    }

                    value = methods[ 'get_value_by_' + item.value_type ].apply( this, [ item.$objects.get(0) ] );

                    result = methods.validate_value.apply( this, [ item.$objects.get(0), value ] );

                    methods.remove_validation_messages.apply( this, [ item.$objects.get(0) ] );
                    if( result !== true ) {
                        validation_flag = false;
                        methods.show_validation_message.apply( this, [ item.$objects.get(0), result ] );
                    }
                }
            }
            return validation_flag;
        },
        validate_value : function( obj, value ) {
            var options = $(obj).data('wpo_valid_options'),
                form = $(obj).data('wpo_form'),
                result;
            for( key in options.validation_keys ) {
                if( typeof methods[ 'validate_case_' + options.validation_keys[ key ] ] != 'function' ) continue;
                result = methods[ 'validate_case_' + options.validation_keys[ key ] ].apply( form, [ obj, value ] );
                if( result !== true ) return options.validation_keys[ key ];
            }
            return true;
        },
        validate_case_required : function( obj, value ) {
            if( typeof value != 'undefined' && Array.isArray( value ) ) {
                return value.length > 0;
            } else if( typeof value == 'undefined' || value == '' ) {
                return false;
            }
            return true;
        },
        validate_case_email : function( obj, value ) {
            if( /^([\w-'+\.]+@([\w-]+\.)+[\w-]{2,})?$/.test( value ) ) {
                return true;
            }
            return false;
        },
        validate_case_phone : function( obj, value ) {
            return /^[\s\#0-9_\-\+\(\)]+$/.test( value );
        },
        validate_case_postcode : function( obj, value ) {
            return /^[\s\-A-Za-z0-9]+$/.test( value );
        },
        show_validation_message : function( obj, error_key ) {
            var id = 'wpo_' + methods.uniqid.apply( this ),
                message = typeof $(obj).data('wpo_tooltip') != 'undefined' ? $(obj).data('wpo_tooltip') : '';
            if( $(obj).hasClass('wpo_password_wrapper') ) {
                $(obj).find('.wpo_password_input_wrapper')
                    .attr('data-wpo_tooltip', message + ' ' + wpo_validation.error[ error_key ] )
                    .addClass('wpo_validation_wrapper');
            } else {
                $(obj).attr('data-wpo_tooltip', message + ' ' + wpo_validation.error[ error_key ] )
                .addClass('wpo_validation_error_field').wrap('<span class="wpo_validation_wrapper"></span>');
            }

                //.after('<span id="' + id + '" class="wpo_validation_error">' + wpo_validation.error[ error_key ] + '</span>');
            var messages = $(obj).data('wpo_validation_messages');
            if( typeof messages != 'object' ) {
                messages = [];
            }
            messages.push( id );
            $(obj).data( 'wpo_validation_messages', messages );
        },
        remove_validation_messages : function( obj ) {
            if( $(obj).parent().hasClass('wpo_validation_wrapper') ) {
                $(obj).unwrap();
                $(obj).removeAttr('data-wpo_tooltip');
            } else if( $(obj).hasClass('wpo_password_wrapper') ) {
                $(obj).find('.wpo_password_input_wrapper').removeClass('wpo_validation_wrapper');
                $(obj).find('.wpo_password_input_wrapper').removeAttr('data-wpo_tooltip');
            }
            $(obj).removeClass('wpo_validation_error_field');

            /*var messages = $(obj).removeClass('wpo_validation_error_field').data('wpo_validation_messages');
            if( typeof messages == 'object' ) {
                for( key in messages ) {
                    $( '#' + messages[ key ] ).remove();
                }
            }*/
        },
        uniqid: function() {
            var ts=String(new Date().getTime()), i = 0, out = '';
            for(i=0;i<ts.length;i+=2) {
               out+=Number(ts.substr(i, 2)).toString(36);
            }
            return out;
        },
        settings : function( args ) {
            if( typeof args != 'object' ) {
                args = {};
            }

            var stored_methods = $(this).data('wpo_validation_methods');
            if( typeof stored_methods != 'object' ) {
                stored_methods = {};
            }

            var additional_methods = {};
            for( key in args ) {
                if( typeof( args[ key ] ) == 'function' ) {
                    additional_methods[ key ] = args[ key ];
                }
            }
            $(this).data('wpo_validation_methods', $.extend({}, stored_methods, additional_methods));
            methods = $.extend({}, initial_methods, stored_methods, additional_methods);
        }
    };

    $.fn.wpo_validation = function( method ) {
        var additional_methods = $(this).data('wpo_validation_methods');
        if( typeof additional_methods != 'object' ) {
            additional_methods = {};
        }
        methods = $.extend( {}, initial_methods, additional_methods );

        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for $.wpo_validation plugin' );
        }
    };

})( jQuery );
