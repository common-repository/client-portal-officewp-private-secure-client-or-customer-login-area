<?php

namespace wpo\list_table;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_List_Table;

class List_Table_Email_Notifications extends Admin_List_Table {

    function __construct(){
        $args = array(
            'singular'          => __( 'Email Notification', WP_OFFICE_TEXT_DOMAIN ),
            'plural'            => __( 'Email Notifications', WP_OFFICE_TEXT_DOMAIN ),
            'no_items_message'  => ''
        );

        parent::__construct( $args );

        $this->_ID_column = 'id';
        $this->uniqid = uniqid();

        $this->set_bulk_actions( array() );

        $columns = array(
            'subject' => array(
                'title'     => '',
            )
        );

        $this->set_columns_data( $columns );
    }


    /**
     * Delete email notification template handler
     *
     */
    public function delete_templates() {
        $deleted = false;
        if( !empty( $_REQUEST['id'] ) ) {
            if( !is_numeric( $_REQUEST['id'] ) ) {
                $_REQUEST['id'] = json_decode( $_REQUEST['id'] );
            }

            if ( !current_user_can( 'administrator' ) ) {
                exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                $ids = $_REQUEST['id'];
                if( 0 == count( $ids ) ) {
                    exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }

                global $wpdb;
                if ( is_numeric( $ids ) ) {
                    $ids = array( $ids );
                }
                $sending_rules_ids = $wpdb->get_col(
                    "SELECT o.id
                    FROM {$wpdb->prefix}wpo_objects o
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id=o.id AND om.meta_key='notification_id'
                    WHERE o.type = 'sending_rule' AND
                          om.meta_value IN('" . implode( "','", $ids ) . "')"
                );
                $ids = array_merge( $ids, $sending_rules_ids );

                $deleted = WO()->delete_wpo_object( $ids );
            }
        }

        exit( json_encode( array( 'status' => true, 'refresh' => $deleted, 'message' => __( 'Email Template(s) was deleted!', WP_OFFICE_TEXT_DOMAIN ) ) ) );
    }


    /**
     * AJAX Build ListTable Data
     */
    public function list_table_data() {
        global $wpdb;

        $paged = 1;
        $per_page = 99999;

        $search = '';
        if ( !empty( $_REQUEST['search'] ) ) {
            $search = WO()->get_prepared_search( $_REQUEST['search'], array(
                'o.title',
            ) );
        }

        $order_string = $this->get_order_string();

        $filter = '';

        $items_count = $wpdb->get_var(
            "SELECT COUNT( DISTINCT o.id )
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id
            WHERE o.type = 'email_notification'
                $search
                $filter"
        );

        $pagination = array(
            'current_page'  => $paged,
            'start'         => $per_page * ( $paged - 1 ) + 1,
            'end'           => ( $per_page * ( $paged - 1 ) + $per_page < $items_count ) ? $per_page * ( $paged - 1 ) + $per_page : $items_count,
            'count'         => $items_count,
            'pages_count'   => ceil( $items_count/$per_page )
        );

        $wpdb->query("SET SESSION SQL_BIG_SELECTS=1");
        $email_notifications = $wpdb->get_results(
            "SELECT o.*,
                    om.meta_value AS body,
                    om2.meta_value AS notification_id,
                    om3.meta_value AS notification_action,
                    om4.meta_value AS doer,
                    om5.meta_value AS recipient,
                    om6.meta_value AS active
            FROM {$wpdb->prefix}wpo_objects o
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id AND om.meta_key='body'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om2 ON om2.meta_value = o.id AND om2.meta_key='notification_id'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om3 ON om3.object_id = om2.object_id AND om3.meta_key='action'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om4 ON om4.object_id = om2.object_id AND om4.meta_key='doer'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om5 ON om5.object_id = om2.object_id AND om5.meta_key='recipient'
            LEFT JOIN {$wpdb->prefix}wpo_objectmeta om6 ON om6.object_id = o.id AND om6.meta_key='active'
            WHERE o.type = 'email_notification'
                  $search
                  $filter
            ORDER BY $order_string
            LIMIT ". ( ( $paged - 1 )*$per_page ). "," . $per_page,
        ARRAY_A );

        $notification_actions = WO()->get_rule_actions();

        $items = array();
        $actions = array();
        foreach ( $email_notifications as $k=>$email_notification ) {
            $items[$email_notification[ $this->_ID_column ]][$this->_ID_column] = $email_notification[ $this->_ID_column ];
            $items[$email_notification[ $this->_ID_column ]]['edit_rel'] = $email_notification[ $this->_ID_column ] . '_' . md5( 'wpoprofileedit_' . $email_notification[ $this->_ID_column ] );
            $items[$email_notification[ $this->_ID_column ]]['delete_rel'] = $email_notification[ $this->_ID_column ] . '_' . md5( 'wpoprofiledelete_' . $email_notification[ $this->_ID_column ] );

            $items[$email_notification[ $this->_ID_column ]]['notification_action'] = '';
            if ( !empty( $email_notification['notification_action'] ) ) {
                $actions[$email_notification[ $this->_ID_column ]][] = $notification_actions[$email_notification['notification_action']];
                $items[$email_notification[ $this->_ID_column ]]['notification_action'] = implode( ', ', array_unique( $actions[$email_notification[ $this->_ID_column ]] ) );
            }

            $items[$email_notification[ $this->_ID_column ]]['subject'] = $email_notification['title'];
            $items[$email_notification[ $this->_ID_column ]]['active'] = ( !empty( $email_notification['active'] ) && 'no' == $email_notification['active'] ) ? 'inactive' : 'active';
        }
        $items = array_values( $items );

        $this->set_pagination_args( array( 'total_items' => $items_count, 'per_page' => $per_page ) );

        $template = array();
        if ( !empty( $_REQUEST['reset_template'] ) ) {
            $this->prepare_items();
            $template['content_sample'] = $this->sample();
            $template['headers_sample'] = $this->headers_sample();
        }

        exit( json_encode( array(
            'status'        => true,
            'data'          => $items,
            'pagination'    => $pagination,
            'filters_line'  => array(),
            'template'      => $template
        ) ) );
    }


    /**
     * Build HTML for subject column
     *
     * @return string
     */
    function column_subject() {
        $actions = $hide_actions = array();
        $actions['edit']    = '<a href="javascript:void(0);" data-id="{{>' . $this->_ID_column . '}}">' . __( 'Edit', WP_OFFICE_TEXT_DOMAIN ). '</a>';
        //our_hook
        $actions = apply_filters( 'wpoffice_list_table_email_notifications_actions', $actions, $this->_ID_column );

        return sprintf('%1$s %2$s</div></div>',
            '<div class="wpo_line_{{>active}}" title="{{>active}}">&nbsp;</div>
            <div style="width:calc( 100% - 30px );float:left;">
            <div class="wpo_bold" data-id="{{>' . $this->_ID_column  . '}}">{{>subject}}</div>
            <div>{{>notification_action}}</div>',
            $this->row_actions( $actions )
        );
    }


    /**
     * Build HTML for pulllayer settings layer
     */
    function ajax_display_settings_form() {
        ob_start();
        ?>
        <style>
            .wpo_list_table_footer {
                display: none;
            }

            .wpo_tablenav {
                display: none;
            }

            .wpo_admin_email_notifications .wpo_line_inactive_row {
                opacity: 0.5;
            }
        </style>
        <div class="wpo_admin_email_notifications wpo_admin_wrapper">
            <?php $this->display(); ?>
        </div>


        <script type="text/javascript">
            function wpo_load_content_callback() {
                jQuery('.wpo_admin_email_notifications .wpo_single_row').removeClass('wpo_line_inactive_row');
                jQuery('.wpo_line_inactive').parents('.wpo_single_row').addClass('wpo_line_inactive_row');
            }

            jQuery( document ).ready( function() {
                var $tiny_editor = {};
                var body = jQuery( 'body' );

                body.off( 'click', '.wpo_admin_email_notifications .wpo_add_notification' ).on( 'click', '.wpo_admin_email_notifications .wpo_add_notification', function(e) {
                    if ( $tiny_editor ) {
                        tinyMCE.triggerSave();
                        jQuery('.wpo_tiny_placeholder').replaceWith( jQuery( $tiny_editor ).html() );
                    }

                    jQuery.pulllayer({
                        ajax_url  : '<?php echo WO()->get_ajax_route( 'wpo\settings\Setting_Email_Notifications', 'ajax_display_settings_form' ); ?>',
                        custom_data : {
                            list_table_uniqueid : '<?php echo $this->uniqid ?>'
                        },
                        object    : this,
                        onOpenContentLoad: function() {
                            var id = 'wpo_notification_body';
                            var object = jQuery('#' + id);

                            tinyMCE.triggerSave();
                            tinymce.EditorManager.execCommand('mceRemoveEditor',true, id);
                            "4" === tinymce.majorVersion ? window.tinyMCE.execCommand("mceRemoveEditor", !0, id) : window.tinyMCE.execCommand("mceRemoveControl", !0, id);
                            $tiny_editor = jQuery('<div>').append( object.parents('#wp-' + id + '-wrap').clone() );
                            object.parents('#wp-' + id + '-wrap').replaceWith('<div class="wpo_tiny_placeholder"></div>');

                            jQuery( 'input[name="subject"]').parents( 'tr' ).after( '<tr><th scope="row"><?php _e( 'Body', WP_OFFICE_TEXT_DOMAIN ) ?></th><td>' + jQuery( $tiny_editor ).html() + '</td></tr>' );

                            var init;
                            if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
                                init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend( {}, tinyMCEPreInit.mceInit[ id ] );
                            } else {
                                init = tinyMCEPreInit.mceInit[ id ];
                            }
                            if ( typeof(QTags) == 'function' ) {
                                QTags( tinyMCEPreInit.qtInit[ id ] );
                                QTags._buttonsInit();
                            }
                            window.switchEditors.go( id );
                            tinymce.init( init );
                            tinymce.activeEditor.setContent( '' );
                            object.html('');

                            jQuery( 'body' ).on( 'click','.wp-switch-editor', function() {
                                var target = jQuery(this);

                                if ( target.hasClass( 'wp-switch-editor' ) ) {
                                    var mode = target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
                                    window.switchEditors.go( id, mode );
                                }
                            });
                        },
                        onClose: function() {
                            tinyMCE.triggerSave();
                            jQuery('.wpo_tiny_placeholder').replaceWith( jQuery( $tiny_editor ).html() );
                        }
                    });
                    e.stopPropagation();
                });

                body.off( 'click', '.wpo_admin_email_notifications .wpo_edit a' ).on( 'click', '.wpo_admin_email_notifications .wpo_edit a', function(e) {
                    var obj = jQuery(this);

                    if ( $tiny_editor ) {
                        tinyMCE.triggerSave();
                        jQuery('.wpo_tiny_placeholder').replaceWith( jQuery( $tiny_editor ).html() );
                    }

                    jQuery.pulllayer({
                        ajax_url  : '<?php echo WO()->get_ajax_route( 'wpo\settings\Setting_Email_Notifications', 'ajax_display_settings_form' ); ?>',
                        ajax_data : 'id=' + obj.data('id'),
                        custom_data : {
                            list_table_uniqueid : '<?php echo $this->uniqid ?>'
                        },
                        object    : this,
                        onOpenContentLoad: function( response ) {
                            var id = 'wpo_notification_body';
                            var object = jQuery('#' + id);

                            tinyMCE.triggerSave();
                            "4" === tinymce.majorVersion ? window.tinyMCE.execCommand("mceRemoveEditor", !0, id) : window.tinyMCE.execCommand("mceRemoveControl", !0, id);

                            $tiny_editor = jQuery('<div>').append( object.parents('#wp-' + id + '-wrap').clone() );
                            object.parents('#wp-' + id + '-wrap').replaceWith('<div class="wpo_tiny_placeholder"></div>');

                            jQuery( 'input[name="subject"]').parents( 'tr' ).after( '<tr><th scope="row"><?php _e( 'Body', WP_OFFICE_TEXT_DOMAIN ) ?></th><td>' + jQuery( $tiny_editor ).html() + '</td></tr>' );

                            var init;
                            if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
                                init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend( {}, tinyMCEPreInit.mceInit[ id ] );
                            } else {
                                init = tinyMCEPreInit.mceInit[ id ];
                            }
                            if ( typeof(QTags) == 'function' ) {
                                QTags( tinyMCEPreInit.qtInit[ id ] );
                                QTags._buttonsInit();
                            }

                            window.switchEditors.go( id );
                            tinymce.init( init );
                            tinymce.activeEditor.setContent( response.editor_content );
                            object.html(response.editor_content);


                            jQuery( 'body' ).on( 'click','.wp-switch-editor', function() {
                                var target = jQuery(this);

                                if ( target.hasClass( 'wp-switch-editor' ) ) {
                                    var mode = target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
                                    window.switchEditors.go( id, mode );
                                }
                            });
                        },
                        onClose: function() {
                            tinyMCE.triggerSave();
                            jQuery('.wpo_tiny_placeholder').replaceWith( jQuery( $tiny_editor ).html() );
                        }
                    });
                    e.stopPropagation();
                });

                body.off( 'click', '.wpo_admin_email_notifications .wpo_delete a' ).on( 'click', '.wpo_admin_email_notifications .wpo_delete a', function(e) {
                    var obj = jQuery(this);
                    jQuery.wpo_confirm({
                        message : '<?php _e( 'Are you sure delete this Template?', WP_OFFICE_TEXT_DOMAIN ) ?>',
                        onYes: function() {
                            jQuery.ajax({
                                type: "POST",
                                url: '<?php echo WO()->get_ajax_route( get_class( $this ), 'delete_templates' ) ?>',
                                data: 'id=' + obj.data('id'),
                                dataType: 'json',
                                timeout: 20000,
                                success: function( data ) {
                                    if( data.refresh ) {
                                        reset_template['<?php echo $this->uniqid ?>'] = true;
                                        load_content('<?php echo $this->uniqid ?>');
                                    }

                                    jQuery( this ).wpo_notice({
                                        message : data.status ? '<?php _e( 'Template was Deleted!', WP_OFFICE_TEXT_DOMAIN ) ?>' : data.message,
                                        type : data.status ? 'update' : 'error'
                                    });
                                }
                            });
                        },
                        object: this
                    });
                    e.stopPropagation();
                });
            });
        </script>
        <?php
        $content = ob_get_clean();

        $data = array(
            'title' => '<span>' . __( 'Email Notifications', WP_OFFICE_TEXT_DOMAIN ) . '</span>',
            'content' => $content
        );

        if( $help = WO()->help()->get_layer_help() ) {
            $data['help'] = $help;
            $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
        }

        exit( json_encode( $data ) );
    }


    /**
     * Insert content before List Table filters line
     *
     * @return mixed|void
     */
    function before_filters_line() {
        return apply_filters( 'wpoffice_add_email_notification_button', '' );
    }

    //end class
}
