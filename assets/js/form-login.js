jQuery( document ).ready( function() {
    if( wpo_form_login.action == 'lostpassword' || wpo_form_login.action == 'retrievepassword' ) {
        jQuery('.wpo_get_new_pass_button').click(function () {
            if (!jQuery(this).hasClass('wpo_disabled')) {
                jQuery(this).parents('form').submit();
            }
        });
    } else if( wpo_form_login.action == 'resetpass' || wpo_form_login.action == 'rp' ) {
        jQuery( ".wpo_login_form" ).wpo_validation();

        jQuery( '.wpo_reset_pass_button').click( function() {
            var validation = jQuery( '.wpo_login_form' ).wpo_validation('validate_submit');
            if( !validation ) return false;

            jQuery( this).parents('form').submit();
        });
    } else {
        jQuery( '.wpo_login_button' ).click( function() {
            if ( !jQuery( this).hasClass('wpo_disabled') ) {
                jQuery( this).parents('form').submit();
            }
        });
    }
});