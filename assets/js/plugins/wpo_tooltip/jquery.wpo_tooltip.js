jQuery( document ).ready( function() {
    var offsetfromcursorY = 15;
    var body = jQuery('body');

    body.append( '<div id="wpo_tooltip"></div>' );

    body.on('mousemove', "[data-wpo_tooltip]", function( eventObject ) {
        var tipobj = jQuery("#wpo_tooltip");

        $data_tooltip = jQuery(this).attr( "data-wpo_tooltip" );

        var curX= eventObject.clientX /*+ body.scrollLeft()*/;
        var curY= eventObject.clientY /*+ body.scrollTop()*/;
        var winwidth = jQuery(window).innerWidth() - 20;
        var winheight = jQuery(window).innerHeight() - 20;
        var rightedge= winwidth - eventObject.clientX;
        var bottomedge= winheight - eventObject.clientY - offsetfromcursorY;

        tipobj.html( $data_tooltip ).show();

        var left;
        var top;
        if ( rightedge < tipobj.outerWidth() )
            left = curX - tipobj.outerWidth() + "px";
        else
            left = curX + "px";

        if ( bottomedge < tipobj.outerHeight() )
            top = curY - tipobj.outerHeight() - offsetfromcursorY + "px";
        else
            top = curY + offsetfromcursorY + "px";

        tipobj.css({
            "top" : top,
            "left" : left,
            "max-width" : ( winwidth / 3 ) + 'px'
        });

    }).on('mouseout', "[data-wpo_tooltip]", function () {

        jQuery("#wpo_tooltip").hide()
            .html("")
            .css({
                "top" : 0,
                "left" : 0,
                "max-width" : 'auto'
            });
    });
});