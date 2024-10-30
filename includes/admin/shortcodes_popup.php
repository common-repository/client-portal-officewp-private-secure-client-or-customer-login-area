<style>
    .shortcodes_presets_list {
        width: 250px;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        box-sizing: border-box;
        padding: 0 10px;
        background: #f3f3f3;
        border: none;
        border-right: 1px solid #ccc;
        padding: 0;
    }

    .shortcodes_list {
        width: 100%;
        float: left;
        display: block;
        margin: 10px 0;
        padding: 0;
    }

    .shortcodes_list li {
        width: 100%;
        display: block;
        float: left;
        margin: 0;
        padding: 0;
    }

    .shortcodes_list span {
        display: block;
        float: left;
        width: 100%;
        box-sizing: border-box;
        padding: 3px 20px;
        margin: 0;
        color: #00a0d2;
        cursor: pointer;
    }

    .shortcodes_list span.active {
        color: #23282d;
    }

    .shortcodes_list span:hover {
        color: #0073aa;
        background: rgba(0,0,0,.04);
    }

    .wpo_separator {
        width: calc( 100% - 40px );
        float: left;
        box-sizing: border-box;
        display: block;
        margin: 0 20px;
        height: 0;
        border-bottom: 1px solid #ddd;
    }

    .shortcode_attributes {
        width: calc( 100% - 250px );
        height: 100%;
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        box-sizing: border-box;
        padding: 5px 10px;
    }
</style>
<div class="shortcodes_presets_list">
    <ul class="shortcodes_list">
        <?php foreach( $shortcodes as $shortcode=>$data ) {
            if( isset( $data['title'] ) ) { ?>
                <li>
                    <span data-code="<?php echo $shortcode; ?>"><?php echo $data['title']; ?></span>
                </li>
            <?php }
        } ?>
    </ul>
    <div class="wpo_separator"></div>
</div>
<div class="shortcode_attributes"></div>
<script type="text/javascript">
    jQuery('body').off('click', '.wpo_pulllayer_opened:last .wpo_button_submit').on('click', '.wpo_pulllayer_opened:last .wpo_button_submit', function() {
        var code = jQuery('.wpo_pulllayer_opened:last .shortcodes_list span.active').data('code');
        var attrs = '';
        if( jQuery(".wpo_pulllayer_opened:last .wpo_admin_form").length > 0 ) {
            var array = jQuery(".wpo_pulllayer_opened:last .wpo_admin_form").serializeArray();
            var temp = {};
            for( key in array ) {
                if( typeof temp[ array[ key ].name ] == 'undefined' ) {
                    temp[ array[ key ].name ] = [];
                }
                temp[ array[ key ].name ].push( array[ key ].value );
            }
            for( key in temp ) {
                attrs += ' ' + key + '="' + temp[ key ].join(',') + '"';
            }
        }

        var shortcode = '[' + code + attrs + ' /]';

        if ( tinyMCE.get('content') !== null ) {
            tinyMCE.get('content').insertContent( shortcode );
        } else {
            var content = jQuery('#content' ).val();
            jQuery('#content' ).val( content + shortcode );
        }

        jQuery( '#wpo-add-shortcode-button' ).pulllayer('close');
    });
    
    jQuery('body').off('click', '.shortcodes_list a').on('click', '.shortcodes_list span', function(e) {
        jQuery(this).parents('.shortcodes_list').find('span').removeClass('active');
        jQuery(this).addClass('active');
        var shortcode = jQuery(this).data('code');
        jQuery('.shortcode_attributes').html( '<div class="wpo_pulllayer_ajax_loader_wrapper">' +
            '<div class="wpo_pulllayer_ajax_loader">' +
                wpo_ajax_loader.loader +
            '</div>' +
        '</div>' );
        jQuery.ajax({
            type: "POST",
            url: '<?php echo WO()->get_ajax_route( 'wpo\core\Shortcodes', 'get_attributes_form' ) ?>',
            data: 'shortcode=' + shortcode,
            dataType: 'json',
            success: function( data ) {
                jQuery('.shortcode_attributes').html('');
                jQuery( '.wpo_pulllayer_opened:last .wpo_pulllayer_title:first > h2').html('');
                if( data.status ) {
                    jQuery('.shortcode_attributes').html( data.response );
                    jQuery( '.wpo_pulllayer_opened:last .wpo_pulllayer_title:first > h2').append("<span style=\"float:left;margin-right: 20px;\">" + data.title + "</span>");
                    jQuery( '.wpo_pulllayer_opened:last .wpo_pulllayer_title:first > h2').append(data.button);
                } else {
                    alert( data.response );
                }
            }
        });
        e.stopPropagation();
    });
</script>