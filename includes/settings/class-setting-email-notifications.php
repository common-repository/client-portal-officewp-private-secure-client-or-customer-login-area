<?php

namespace wpo\settings;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//use wpo\list_table\List_Table_Email_Notifications;

if ( !class_exists( 'wpo\settings\Setting_Email_Notifications' ) ) {

    class Setting_Email_Notifications extends Settings_Forms {

        function __construct() {

            parent::__construct( array() );

            add_filter( 'wpoffice_render_single_field_sending_rules', array( $this, 'field_sending_rules'), 10, 2 );

            $this->options['class'] = 'wpo_email_notifications';

            if ( !empty( $_POST['id'] ) ) {
                $email_notification = WO()->get_object( $_POST['id'], 'email_notification' );
                $active_field = array(
                    'tag'   => 'select',
                    'label' => __( 'Active', WP_OFFICE_TEXT_DOMAIN ),
                    'name'  => 'active',
                    'custom_attributes' => array(),
                    'value' => !empty( $email_notification['active'] ) ? $email_notification['active']: '',
                    'items' => array(
                        'yes'   => __( 'Yes', WP_OFFICE_TEXT_DOMAIN ),
                        'no'   => __( 'No', WP_OFFICE_TEXT_DOMAIN )
                    )
                );
            } else {
                $active_field = array(
                    'tag'   => 'hidden',
                    'name'  => 'active',
                    'value' => 'yes'
                );
            }

            $fields = array(
                'key' => array(
                    'tag'   => 'hidden',
                    'name'  => 'id',
                    'value' => !empty( $_POST['id'] ) ? $_POST['id']: '',
                ),
                'active' => $active_field,
                'subject' => array(
                    'tag'   => 'input',
                    'type'  => 'text',
                    'label' => __( 'Subject', WP_OFFICE_TEXT_DOMAIN ) . ' <span style="color: red;">*</span>',
                    'name'  => 'subject',
                    'value' => !empty( $email_notification['title'] ) ? $email_notification['title']: '',
                    'description' => __( 'Email Notification Subject', WP_OFFICE_TEXT_DOMAIN ),
                    'custom_attributes' => array(),
                    'validation' => array(
                        'required',
                    )
                ),
                'separator' => array(
                    'type'  => 'separator',
                ),
                'sending_rules' => array(
                    'tag'       => 'sending_rules',
                    'label'     => __( 'Sending Rules', WP_OFFICE_TEXT_DOMAIN ),
                )
            );

            $fields = apply_filters( 'wpoffice_email_notification_form_fields', $fields );

            $this->add_fields( $fields );
        }


        /**
         * Reinit WP_Editor after save add/edit notifications form
         */
        function js_before_build_ajax_data() {
            echo 'window.switchEditors.go( "wpo_notification_body" );window.switchEditors.go( "wpo_notification_body" );';
        }


        /**
         * Init List Table reload after save add/edit notifications form
         */
        function js_on_save_success() {
            echo 'reset_template[list_table_uniqueid] = true; load_content( list_table_uniqueid );';
        }


        /**
         * Save settings form
         */
        function ajax_save_form() {
            if ( !empty( $_REQUEST['action'] ) && !empty( $_REQUEST['subject'] ) && !empty( $_REQUEST['content'] ) ) {
                global $wpdb;

                if( !empty( $_POST['id'] ) ) {
                    $wpdb->update(
                        "{$wpdb->prefix}wpo_objects",
                        array(
                            'title' => $_POST['subject'],
                        ),
                        array(
                            'id'    => $_POST['id'],
                        )
                    );

                    $email_notification_id = $_POST['id'];
                } else {
                    $wpdb->insert(
                        "{$wpdb->prefix}wpo_objects",
                        array(
                            'title'         => $_POST['subject'],
                            'type'          => 'email_notification',
                            'creation_date' => time(),
                            'author'        => get_current_user_id()
                        ),
                        array(
                            '%s',
                            '%s',
                            '%s',
                            '%d'
                        )
                    );

                    $email_notification_id = $wpdb->insert_id;
                }

                WO()->update_object_meta( $email_notification_id, 'body', $_POST['content'] );
                WO()->update_object_meta( $email_notification_id, 'active', $_POST['active'] );

                do_action( 'wpoffice_form_email_notification_save', $email_notification_id );

                exit( json_encode( array(
                    'status'    => true,
                    'message'   => __( 'Email Notification Updated!', WP_OFFICE_TEXT_DOMAIN ),
                    'close' => true,
                ) ) );
            } else {
                exit( json_encode( array(
                    'status' => false,
                    'message' => __( 'All fields are required!', WP_OFFICE_TEXT_DOMAIN ) ,
                ) ) );
            }
        }


        /**
         * Display settings form content
         */
        function ajax_display_settings_form() {
            ob_start();

            $this->display();

            $content = ob_get_clean();

            if ( isset( $_POST['id'] ) ) {
                $email_notification = WO()->get_object( $_POST['id'], 'email_notification' );
            }

            $data =  array(
                'title' => '<span id="sub_setting_title"></span>',
                'content' => $content,
                'editor_content' => !empty( $email_notification['body'] ) ? $email_notification['body']: ''
            );

            if( $help = WO()->help()->get_layer_help() ) {
                $data['help'] = $help;
                $data['show_help'] = get_user_meta( get_current_user_id(), 'wpo_show_help', true );
            }

            exit( json_encode( $data ) );
        }


        /**
         * Render sending_rules field at Email Notification Templates form
         *
         * @param string $content
         * @param $field
         * @return string
         */
        function field_sending_rules( $content, $field ) {
            $rule_actions = WO()->get_rule_actions();
            $rule_recipients = WO()->get_rule_recipients();

            $sending_rules = array();
            if ( !empty( $_POST['id'] ) ) {
                global $wpdb;

                $wpdb->query("SET SESSION SQL_BIG_SELECTS=1");
                $sending_rules = $wpdb->get_results( $wpdb->prepare(
                    "SELECT o.id,
                            om2.meta_value AS rule_action,
                            om3.meta_value AS doer,
                            om4.meta_value AS recipient,
                            om5.meta_value AS doer_select,
                            om6.meta_value AS recipient_select
                    FROM {$wpdb->prefix}wpo_objects o
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om ON om.object_id = o.id AND om.meta_key = 'notification_id'
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om2 ON om2.object_id = o.id AND om2.meta_key='action'
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om3 ON om3.object_id = o.id AND om3.meta_key='doer'
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om4 ON om4.object_id = o.id AND om4.meta_key='recipient'
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om5 ON om5.object_id = o.id AND om5.meta_key='doer_select'
                    LEFT JOIN {$wpdb->prefix}wpo_objectmeta om6 ON om6.object_id = o.id AND om6.meta_key='recipient_select'
                    WHERE o.type = 'sending_rule' AND
                          om.meta_value = %d",
                    $_POST['id']
                ), ARRAY_A );
            }

            ob_start();

            echo apply_filters( 'wpoffice_before_sending_rules', '' ); ?>

            <div class="wpo_sending_rules">
                <table class="wpo_sending_rules_table">
                    <thead>
                        <tr>
                            <th class="wpo_sending_rule_td_action">
                                <?php _e( 'Action', WP_OFFICE_TEXT_DOMAIN ) ?>
                            </th>
                            <th class="wpo_sending_rule_td_doer">
                                <?php _e( 'Doer', WP_OFFICE_TEXT_DOMAIN ) ?>
                            </th>
                            <th class="wpo_sending_rule_td_recipient">
                                <?php _e( 'Recipient', WP_OFFICE_TEXT_DOMAIN ) ?>
                            </th>
                        </tr>
                    </thead>
                    <?php if ( empty( $sending_rules ) ) { ?>
                        <tr class="empty_rules">
                            <td colspan="4" style="text-align: center;">
                                <?php _e( 'Empty Rules', WP_OFFICE_TEXT_DOMAIN ) ?>
                            </td>
                        </tr>
                    <?php } else {
                        foreach ( $sending_rules as $k=>$rule ) {
                            $sending_rule_data = WO()->valid_ajax_encode( array(
                                'key'               => $k + 1,
                                'id'                => $rule['id'],
                                'action'            => $rule['rule_action'],
                                'doer'              => $rule['doer'],
                                'recipient'         => $rule['recipient'],
                                'doer_select'       => WO()->valid_ajax_encode( maybe_unserialize( $rule['doer_select'] ) ),
                                'recipient_select'  => WO()->valid_ajax_encode( maybe_unserialize( $rule['recipient_select'] ) )
                            ) ); ?>

                            <tr class="wpo_sending_rule_row" data-key="<?php echo $k + 1 ?>" data-id="<?php echo $rule['id'] ?>">
                                <td class="wpo_sending_rule_td_action">
                                    <?php echo $rule_actions[$rule['rule_action']] ?><br />
                                    <?php echo apply_filters( 'wpoffice_sending_rule_actions', '', $sending_rule_data, $k + 1 ); ?>
                                </td>
                                <td class="wpo_sending_rule_td_doer">
                                    <?php echo $rule['doer'] ?>
                                </td>
                                <td class="wpo_sending_rule_td_recipient">
                                    <?php echo $rule_recipients[$rule['rule_action']][$rule['recipient']] ?>
                                </td>
                            </tr>
                        <?php }
                    } ?>
                </table>
                <?php apply_filters( 'wpoffice_add_email_notification_rules_button', '' ); ?>
            </div>

            <?php $content = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }
            return $content;
        }


        /**
         * Render Submit button for Email Notification Form
         *
         * @param string $text
         * @return string
         */
        function submit_button( $text = '' ) {
            return WO()->get_button(
                ( !empty( $_REQUEST['id'] ) ? __( 'Update Notification', WP_OFFICE_TEXT_DOMAIN ) : __( 'Create Notification', WP_OFFICE_TEXT_DOMAIN ) ),
                array(
                    'class'=>'wpo_button_submit wpo_save_form' . $this->unique
                ),
                array(
                    'primary' => true,
                    'ajax' => true ), false
            );
        }

        //end class
    }
}