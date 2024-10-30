jQuery(document).ready(function() {
    if( jQuery('#wp-content-media-buttons').length == 0 ) {
        jQuery('#wp-content-editor-tools').prepend('<div id="wp-content-media-buttons" class="wp-media-buttons"></div>');
    }
    jQuery('#wp-content-media-buttons').prepend("<button type=\"button\" id=\"wpo-add-shortcode-button\" class=\"button\">" +
            "<img height='20' style=\"padding: 0 4px 4px 0;\" src=\"" + shortcodes_popup.plugin_url + "assets/images/editor_button_icon.png\" alt=\"\" />" +
            shortcodes_popup.button_text +
        "</button>");
    jQuery('body').on('click', '#wpo-add-shortcode-button', function(e) {
        jQuery.pulllayer({
            ajax_url        : shortcodes_popup.ajax_url,
            object          : this,
            onOpen          : function( settings ) {
                jQuery('#wpo_pulllayer-' + settings.level + ' .wpo_pulllayer_left_controls').prepend('<style type="text/css" class="wpo_pulllayer-custom-style-' + settings.level + '">.wpo_pulllayer_left_controls{background:#f3f3f3 !important;}</style>');
            }
        });
        e.stopPropagation();
    });
});