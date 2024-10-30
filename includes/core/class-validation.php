<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AdminForm
 */
class Validation {

    var $error_messages;

    function __construct() {
        $this->error_messages = apply_filters( 'wpoffice_validation_messages', array(
            'required' => __( "Field is required", WP_OFFICE_TEXT_DOMAIN ),
            'email' => __( "Invalid email", WP_OFFICE_TEXT_DOMAIN ),
            'phone' => __( "Invalid phone", WP_OFFICE_TEXT_DOMAIN ),
            'postcode' => __( "Invalid postcode", WP_OFFICE_TEXT_DOMAIN ),
            'username_exists' => __( "Username already exists", WP_OFFICE_TEXT_DOMAIN ),
            'email_exists' => __( "Email already exists", WP_OFFICE_TEXT_DOMAIN ),
            'wp_redirect' => __( "Invalid URL for Login Redirect", WP_OFFICE_TEXT_DOMAIN ),
            'profile_exists' => __( 'Profile already exists', WP_OFFICE_TEXT_DOMAIN ),
            'circle_exists' => __( 'Circle already exists', WP_OFFICE_TEXT_DOMAIN )
        ) );
    }

    public function __call( $name, $arguments ) {
        if ( strpos( $name, 'is_' ) === 0 ) {
            array_unshift( $arguments, true );
            return apply_filters_ref_array( 'wpoffice_validation_rule_' . substr( $name, 3 ), $arguments );
        }
        return true;
    }

    function is_circle_exists( $value, $args ) {
        global $wpdb;
        $where = !empty( $args['circle_id'] ) ? 'AND id != ' . esc_sql( $args['circle_id'] ) : '';

        $profile_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id)
            FROM {$wpdb->prefix}wpo_objects
            WHERE type='circle' AND
                  title=%s $where",
            $value
        ) );
        return (int)$profile_exists == 0;
    }

    function is_profile_exists( $value, $args ) {
        global $wpdb;
        $where = !empty( $args['profile_id'] ) ? 'AND id != ' . esc_sql( $args['profile_id'] ) : '';

        $profile_exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id)
            FROM {$wpdb->prefix}wpo_objects
            WHERE type='profile' AND
                  title=%s $where",
            $value
        ) );
        return (int)$profile_exists == 0;
    }

    function is_wp_redirect( $value, $args ) {
        if( isset( $args['type'] ) && ( $args['type'] == 'first_login' || $args['type'] == 'login' ) ) {
            if( !wp_validate_redirect( $value, false ) ) {
                return false;
            }
        }
        return true;
    }

    function is_email_exists( $value, $args ) {
        $user_id = email_exists( $value );
        if( $user_id && ( empty( $args['user_id'] ) || ( !empty( $args['user_id'] ) && $user_id != $args['user_id'] ) ) ) {
            return false;
        }
        return true;
    }

    function is_username_exists( $value, $args ) {
        $user_id = username_exists( $value );
        if( $user_id && ( empty( $args['user_id'] ) || ( !empty( $args['user_id'] ) && $user_id != $args['user_id'] ) ) ) {
            return false;
        }
        return true;
    }

    function is_postcode( $value ) {
        return preg_match( '/^[\s\-A-Za-z0-9]+$/', $value );
    }

    function is_phone( $value ) {
        return preg_match( '/^[\s\#0-9_\-\+\(\)]+$/', $value );
    }

    function is_email( $value ) {
        return is_email( $value ) !== false;
    }

    function is_required( $value ) {
        return $value != '';
    }


    /**
     * Validation process
     *
     * @param array $data Array values for validation
     * @param array $validation_rules
     * @param array $args Additional arguments for validation
     * @return array Errors
     */
    function process( $data, $validation_rules, $args = array() ) {
        $errors = array();

        foreach( $validation_rules as $field_name => $rules ) {
            if( !count( $rules ) ) {
                continue;
            }

            foreach( $rules as $rule ) {
                if( !isset( $data[ $field_name ] ) ||
                        !call_user_func( array( &$this, 'is_' . $rule ), $data[ $field_name ], $args ) ) {
                    $errors[ $field_name ][ $rule ] = $this->error_messages[ $rule ];
                }
            }
        }

        return $errors;
    }

}
