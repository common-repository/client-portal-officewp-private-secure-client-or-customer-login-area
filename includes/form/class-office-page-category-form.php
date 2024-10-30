<?php
namespace wpo\form;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use wpo\core\Admin_Form;

if ( !class_exists('wpo\form\Office_Page_Category_Form') ) {
    class Office_Page_Category_Form extends Admin_Form {

        function __construct( $options = array() ) {
            parent::__construct( $options );

            $fields = $this->get_fields();
            if( is_wp_error( $fields ) ) {
                exit( $fields->get_error_message() );
            }

            $this->add_fields( apply_filters( 'wpoffice_page_category_form_fields', $fields ) );
        }

        function js_on_save_success() {
            echo 'reset_template[list_table_uniqueid] = true; load_content( list_table_uniqueid );';
        }

        function ajax_save_form() {
            global $wpdb;
            if( !empty( $_POST['id'] ) ) {
                $id = $_POST['id'];
                $isset_category = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT( id )
                    FROM {$wpdb->prefix}wpo_objects
                    WHERE type = 'office_page_category' AND
                          id = %d",
                    $id
                ) );
                if( $isset_category == 0 ) {
                    exit( json_encode( array( 'status' => false, 'message' => __( 'Category does not exists', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }

                $edit_category_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category', 'edit' );
                if ( !in_array( $id, $edit_category_ids ) ) {
                    exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }
            } else {
                if ( !WO()->current_member_can( 'create_office_page_category' ) ) {
                    exit( json_encode( array( 'status' => false, 'message' => __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }
            }

            $validation = $this->validate();
            if( !empty( $validation ) ) {
                exit( json_encode( array(
                    'status'    => false,
                    'validation_message'   => $validation
                ) ) );
            }

            $title = $_POST['title'];

            $edit = false;
            if( isset( $id ) ) {
                $current_title = $wpdb->get_var( $wpdb->prepare(
                    "SELECT title
                    FROM {$wpdb->prefix}wpo_objects
                    WHERE type='office_page_category' AND
                          id=%s",
                    $id
                ) );

                if ( $current_title != $_POST['title'] ) {
                    $page_category_exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(id)
                        FROM {$wpdb->prefix}wpo_objects
                        WHERE type='office_page_category' AND
                              title=%s",
                        $title
                    ) );
                    if ( !empty( $page_category_exists ) ) {
                        exit( json_encode( array( 'status' => false, 'message' => __( 'Office Page Category already exists', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                    }
                }

                $wpdb->update( $wpdb->prefix . 'wpo_objects',
                    array(
                        'title' => $title
                    ),
                    array(
                        'id'    => $id
                    )
                );
                $edit = true;
            } else {
                $page_category_exists = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(id)
                        FROM {$wpdb->prefix}wpo_objects
                        WHERE type='office_page_category' AND
                              title=%s",
                    $_POST['title']
                ) );
                if ( !empty( $page_category_exists ) ) {
                    exit( json_encode( array( 'status' => false, 'message' => __( 'Office Page Category already exists', WP_OFFICE_TEXT_DOMAIN ) ) ) );
                }

                $wpdb->insert( $wpdb->prefix . 'wpo_objects',
                    array(
                        'title'         => $title,
                        'type'          => 'office_page_category',
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
                $id = $wpdb->insert_id;
            }

            $items = WO()->assign()->parse_assign_value( $_POST['assigns'] );
            foreach( $items as $key=>$inner_items ) {
                WO()->set_assign_data( 'office_page_category', $id, $key, $inner_items );
            }

            if( $edit ) {
                exit( json_encode( array( 'status' => true,  'close' => true, 'message' => __( 'Office Page Category was edited', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            } else {
                exit( json_encode( array( 'status' => true,  'close' => true, 'message' => __( 'Office Page Category was added', WP_OFFICE_TEXT_DOMAIN ) ) ) );
            }
        }

        function before_form() {
            if ( isset( $_POST['id'] ) ) { ?>
                <input type="hidden" name="id" value="<?php echo $_POST['id'] ?>"/>
            <?php }
        }

        function get_field_values() {
            $title = '';
            $category_id = '';
            $assigned_category = '';

            if( empty( $_POST['id'] ) ) {
                if( !WO()->current_member_can( 'create_office_page_category' ) ) {
                    return new \WP_Error('permission_denied',
                        __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) );
                }
            } else {
                $data = WO()->get_object( $_POST['id'] );

                $edit_page_category_ids = WO()->get_access_content_ids( get_current_user_id(), 'office_page_category', 'edit' );
                if ( !in_array( $_POST['id'], $edit_page_category_ids ) ) {
                    return new \WP_Error('permission_denied',
                        __( 'You don\'t have capabilities for this action', WP_OFFICE_TEXT_DOMAIN ) );
                }

                if( is_array( $data ) && count( $data ) ) {
                    $title = $data['title'];
                    $category_id = $_POST['id'];

                    $assigned_category = array();
                    $category_form_args = WO()->assign()->office_page_category_form_args( array() );
                    foreach( $category_form_args['office_page_category'] as $key=>$settigns ) {

                        $assigned = WO()->get_assign_data_by_object( 'office_page_category', $_POST['id'], $key );

                        if( $key == 'member' ) {
                            foreach( $settigns as $role=>$set ) {
                                $data = base64_encode( json_encode( array(
                                    'key' => $key,
                                    'role' => $role,
                                    'type' => !empty( $set['type'] ) ? $set['type'] : 'checkbox'
                                ) ) );

                                $assigned_users = array();
                                if ( count( $assigned ) ) {
                                    $assigned_users = get_users( array(
                                        'role' => $role,
                                        'include' => $assigned,
                                        'fields' => 'ids'
                                    ) );
                                }

                                $assigned_category[$data] = $assigned_users;
                            }
                        } else {
                            $data = base64_encode( json_encode( array(
                                'key' => $key,
                                'type' => !empty( $settigns['type'] ) ? $settigns['type'] : 'checkbox'
                            ) ) );

                            $assigned_category[$data] = $assigned;
                        }
                    }

                    $assigned_category = base64_encode( json_encode( $assigned_category ) );
                }
            }
            return array(
                'title' => $title,
                'assign' => $assigned_category,
                'category_id' => $category_id
            );
        }

        /**
         * @return \WP_Error|array
         */
        function get_fields() {
            $field_values = $this->get_field_values();

            if( is_wp_error( $field_values ) ) {
                return $field_values;
            }

            $fields = array(
                'title' => array(
                    'tag' => 'input',
                    'type' => 'text',
                    'label' => __( 'Title', WP_OFFICE_TEXT_DOMAIN ),
                    'name' => 'title',
                    'value' => $field_values['title'],
                    'validation' => array(
                        'required',
                    ),
                    'description' => __( 'Category Title must be unique', WP_OFFICE_TEXT_DOMAIN )
                ),
                'assign' => array(
                    'tag'       => 'assign_link',
                    'label'     => __( 'Assigned', WP_OFFICE_TEXT_DOMAIN ),
                    'ajax'      => false,
                    'object'    => 'office_page_category',
                    'object_id' => $field_values['category_id'],
                    'name'      => 'assigns',
                    'value' => $field_values['assign']
                )
            );

            if( !empty( $field_values['category_id'] ) ) {
                $fields['id'] = array(
                    'tag' => 'hidden',
                    'name' => 'id',
                    'value' => $field_values['category_id']
                );
            }

            return $fields;
        }

        function display() {
            ob_start();
            parent::display();
            $content = ob_get_clean();

            exit( json_encode( array(
                'title' => '<span>' . ( !empty( $_REQUEST['id'] ) ? __( 'Edit Office Page Category', WP_OFFICE_TEXT_DOMAIN ) : __( 'Add Office Page Category', WP_OFFICE_TEXT_DOMAIN ) ) . '</span>',
                'content' => $content
            ) ) );
        }
    }
}