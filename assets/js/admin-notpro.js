jQuery( document ).ready( function() {

    jQuery( '.wpo_get_pro_button').mouseover( function() {
        jQuery( '#wpo_admin_header').addClass( 'wpo_admin_header_pro' );
    });

    jQuery( '.wpo_get_pro_button').mouseout( function() {
        jQuery( '#wpo_admin_header').removeClass( 'wpo_admin_header_pro' );
    });


});