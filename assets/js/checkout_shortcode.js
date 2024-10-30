jQuery( document ).ready( function() {
    var id_form = '#wpo_checkout_payment';

    //change states for different countries
    jQuery( '#billing_country').change( function() {
        var country = jQuery('#billing_country').val();

        jQuery.ajax({
            type: 'POST',
            url: data.ajax_url_get_states,
            data: 'country=' + country,
            dataType: "json",
            success: function( data ) {
                if ( data.html ) {
                    //add new field
                    jQuery( '#billing_state').after( data.html );
                    //remove previos errors for old field
                    jQuery( '#billing_state').siblings('.wpo_validation_error').remove();
                    //remove old field
                    jQuery( '#billing_state').remove();
                    //including new field for validation

                    if( jQuery( id_form ).wpo_validation('is_init') ) {
                        jQuery( id_form ).wpo_validation('start');
                    } else {

                        jQuery( id_form ).wpo_validation();
                    }
                }
            }
        });
    }).change();//set states for first load

    //submit form
    jQuery( 'body' ).on( 'submit', id_form, function(e) {
        e.preventDefault();

        var validation = jQuery( id_form ).wpo_validation('validate_submit');
        if( !validation ) return false;

        var $obj = jQuery( this );
        var fields_data = jQuery( id_form ).serialize();

        $obj.attr( 'data-loading', '1' );
        jQuery( '#wpo_form_message' ).removeClass( 'wpo_form_error_message' ).html( '' );
        jQuery.ajax({
            type: "POST",
            url: data.ajax_url_submit,
            data: fields_data + '&order_id=' + data.order_id,
            dataType: 'json',
            timeout: 20000,
            success: function( data ) {
                if ( data.status ) {
                    if ( '' != data.redirect ) {
                        window.location = data.redirect;
                    }
                } else {
                    if( typeof data.error_message != 'undefined' ) {
                        jQuery('#wpo_form_message').addClass('wpo_form_error_message').html( data.error_message );
                    } else if( typeof data.validation_message != 'undefined' ) {
                        var error_message, $field;
                        for( name in data.validation_message ) {
                            $field = $obj.closest( id_form ).find('*[name="' + name + '"]');
                            error_message = Object.keys( data.validation_message[ name ] )[0];

                            if( typeof $field.data('wpo-valid') == 'undefined' ) {
                                $field = $field.closest('[data-wpo-valid]');
                            }

                            jQuery(this).closest( id_form ).wpo_validation( 'show_validation_message', $field.get(0), error_message );
                        }
                    }
                }

                $obj.removeAttr( 'data-loading' );
            }
        });
    });
});