jQuery( document ).ready( function() {
    jQuery('.wpo_registration_form').wpo_validation();

    jQuery( 'body' ).on( 'click', '.wpo_registration_button', function(e) {
        e.preventDefault();

        var validation = jQuery( '.wpo_registration_form' ).wpo_validation('validate_submit');
        if( !validation ) return false;

        var $obj = jQuery( this );
        var fields_data = jQuery( '.wpo_registration_form' ).serialize();

        $obj.attr( 'data-loading', '1' );
        jQuery( '.wpo_form_message' ).removeClass( 'wpo_form_error_message' ).html( '' );

        jQuery.ajax({
            type: "POST",
            url: wpo_form_registration.registration_process_url,
            data: fields_data,
            dataType: 'json',
            timeout: 20000,
            success: function( data ) {
                if ( data.status ) {
                    if ( '' != data.redirect ) {
                        window.location = data.redirect;
                    }
                } else {
                    if( typeof data.error_message != 'undefined' ) {
                        jQuery('.wpo_form_message').addClass('wpo_form_error_message').html(data.error_message);
                    } else if( typeof data.validation_message != 'undefined' ) {
                        var error_message, $field;
                        for( name in data.validation_message ) {
                            $field = $obj.closest(".wpo_frontend_form").find('*[name="' + name + '"]');
                            error_message = Object.keys( data.validation_message[ name ] )[0];

                            if( typeof $field.data('wpo-valid') == 'undefined' ) {
                                $field = $field.closest('[data-wpo-valid]');
                            }

                            jQuery(this).closest(".wpo_frontend_form").wpo_validation( 'show_validation_message', $field.get(0), error_message );
                        }
                    }
                }

                $obj.removeAttr( 'data-loading' );
            }
        });

    });
});