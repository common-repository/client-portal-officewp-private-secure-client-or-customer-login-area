<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AdminForm
 */
class Admin_Form {
    protected $unique;

    protected $fields = array();
    protected $options = array(
        'autocomplete' => 'off',
        'method' => 'POST',
        'id' => '',
        'name' => '',
        'description' => ''
    );

    protected $validation_array = array();

    public $include_save_handler = true;

    function __construct($options = array())
    {
        WO()->setPost('wpo_form_data');
        $this->options = array_merge($this->options, $options);
        $this->unique = uniqid();
    }


    /**
     * Validation fields
     *
     * @param array $args
     * @return array
     */
    function validate( $args = array() ) {
        //prepare validation array
        $this->get_validation_fields();

        $validation_errors = WO()->validation()->process( $_REQUEST, $this->validation_array, $args );

        return $validation_errors;
    }

    function render_wpo_select($field)
    {
        $attributes = array();
        $attributes_keys = array('multiple', 'class', 'name', 'style', 'id');
        $field['class'] = isset($field['class']) ? $field['class'] : 'short';
        $field['name'] = isset($field['name']) ? $field['name'] : (isset($field['id']) ? $field['id'] : '');
        $field['value'] = isset( $field['value'] ) ? $field['value'] : '';
        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }
        ?>

        <select <?php echo implode(' ', $attributes); ?>>
            <?php
            if (!empty($field['items']) && is_array($field['items'])) {
                foreach ($field['items'] as $key => $value) { ?>
                    <option
                        value="<?php echo $key ?>" <?php selected( is_array( $field['value'] ) ? in_array( $key, $field['value'] ) : ( $field['value'] == $key ) ) ?>><?php echo $value ?></option>
                <?php }
            } ?>
        </select>
        <?php
    }


    function render_wpo_radio($field)
    {
        $wrapper_attributes = array();
        $wrapper_attributes_keys = array('class', 'id', 'style');

        foreach ($wrapper_attributes_keys as $key) {
            if (isset($field['wrapper'][$key])) {
                $wrapper_attributes[] = esc_attr($key) . '="' . esc_attr($field['wrapper'][$key]) . '"';
            }
        }

        if (!empty($field['wrapper']['custom_attributes']) && is_array($field['wrapper']['custom_attributes'])) {
            foreach ($field['wrapper']['custom_attributes'] as $attribute => $value) {
                $wrapper_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }

        $attributes = array();
        $attributes_keys = array('class', 'style');

        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }

        ?>
        <div <?php echo implode(' ', $wrapper_attributes); ?>>
            <?php
            if (!empty($field['items']) && is_array($field['items'])) {
                foreach ($field['items'] as $key => $value) { ?>
                    <label>
                        <input type="radio"
                               name="<?php echo isset($field['name']) ? $field['name'] : '' ?>" <?php checked( !empty( $value['checked'] ) );
                        disabled($value['disabled']);
                        echo implode(' ', $attributes); ?> />
                        <?php echo $value['label']; ?>
                    </label>
                <?php }
            } ?>
        </div>
        <?php
    }


    function render_wpo_checkbox($field)
    {
        $wrapper_attributes = array();
        $wrapper_attributes_keys = array( 'class', 'id', 'style' );

        foreach ($wrapper_attributes_keys as $key) {
            if (isset($field['wrapper'][$key])) {
                $wrapper_attributes[] = esc_attr($key) . '="' . esc_attr($field['wrapper'][$key]) . '"';
            }
        }

        if (!empty($field['wrapper']['custom_attributes']) && is_array($field['wrapper']['custom_attributes'])) {
            foreach ($field['wrapper']['custom_attributes'] as $attribute => $value) {
                $wrapper_attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }

        $attributes = array();
        $attributes_keys = array('class', 'name', 'style' , 'value' );

        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }

        ?>
        <div <?php echo implode(' ', $wrapper_attributes); ?>>
            <?php
            if ( !empty( $field['items'] ) && is_array( $field['items'] ) ) {
                foreach ( $field['items'] as $key=>$value ) {
                    $value['disabled'] = !empty( $value['disabled'] ) ? $value['disabled'] : false; ?>
                    <label>
                        <input type="checkbox" <?php checked( !empty( $value['checked'] ) );
                        disabled( $value['disabled'] );
                        echo implode(' ', $attributes); ?> />
                        <?php echo $value['label']; ?>
                    </label>
                <?php }
            } ?>
        </div>
        <?php
    }

    function render_wpo_textarea($field)
    {
        $attributes = array();
        $attributes_keys = array('placeholder', 'class', 'name', 'style', 'id');
        $field['name'] = isset($field['name']) ? $field['name'] : (isset($field['id']) ? $field['id'] : '');

        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        $field['custom_attributes']['cols'] = !empty($field['custom_attributes']['cols']) ? $field['custom_attributes']['cols'] : 50;
        $field['custom_attributes']['rows'] = !empty($field['custom_attributes']['rows']) ? $field['custom_attributes']['rows'] : 5;

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                if( $value != '' )
                    $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }
        ?>

        <textarea <?php echo implode(' ', $attributes); ?>><?php echo isset($field['value']) ? $field['value'] : ''; ?></textarea>
        <?php
    }

    function render_wpo_hidden($field)
    {
        $attributes = array();
        $attributes_keys = array('class', 'name', 'value', 'id');
        $field['name'] = isset($field['name']) ? $field['name'] : (isset($field['id']) ? $field['id'] : '');

        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                if( $value != '' )
                    $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }

        if ( !empty( $field['display_value'] ) ) { ?>
            <span><?php echo $field['display_value'] ?></span>
        <?php } ?>

        <input type="hidden" <?php echo implode(' ', $attributes); ?> />
        <?php
    }


    function render_wpo_input($field)
    {
        $attributes = array();
        $attributes_keys = array('placeholder', 'class', 'name', 'type', 'style', 'value', 'id', 'min', 'max', 'step', 'autocomplite');
        $field['class'] = isset($field['class']) ? $field['class'] : 'short';
        $field['name'] = isset($field['name']) ? $field['name'] : (isset($field['id']) ? $field['id'] : '');
        $field['type'] = isset($field['type']) ? $field['type'] : 'text';

        foreach ($attributes_keys as $key) {
            if (isset($field[$key])) {
                $attributes[] = esc_attr($key) . '="' . esc_attr($field[$key]) . '"';
            }
        }

        if (!empty($field['custom_attributes']) && is_array($field['custom_attributes'])) {
            foreach ($field['custom_attributes'] as $attribute => $value) {
                if( $value != '' )
                    $attributes[] = esc_attr($attribute) . '="' . esc_attr($value) . '"';
            }
        }
        ?>

        <input <?php echo implode(' ', $attributes); ?> />
        <?php
    }


    function render_wpo_value( $field ) {
        $value = !empty( $field['value'] ) ? $field['value'] : '';
        ?>

        <span><?php echo $value ?></span>

        <?php
    }

    function render_wpo_assign_link( $field ) {
        $field['style'] = 'line-height:24px;';
        $custom_data = !empty( $field['custom_data'] ) ? true : false;
        echo WO()->assign()->build_assign_link( $field, $custom_data,
            isset( $field['custom_attributes']['data-wpo-valid'] ) ? $field['custom_attributes']['data-wpo-valid'] : '' );
    }

    function render_wpo_wp_editor( $field ) {
        $value = !empty( $field['value'] ) ? $field['value'] : '';
        wp_editor( $value, $field['id'], array( 'textarea_name' => $field['name'], 'media_buttons' => false, 'wpautop' => false ) );
    }

    function submit_button( $text = '' ) {
        if( $text == '' ) $text = __( 'Save', WP_OFFICE_TEXT_DOMAIN );
        return WO()->get_button( $text, array( 'class'=>'wpo_button_submit wpo_save_form' . $this->unique ), array( 'ajax' => true, 'primary' => true ), false );
    }

    function js_before_build_ajax_data() {
        return '';
    }

    function js_before_submit_ajax() {
        return '';
    }

    function js_on_save_error() {
        return '';
    }

    function js_on_save_success() {
        return '';
    }

    function display_fields( $form_id ) {
        $attributes = array(
            'autocomplete' => !empty($this->options['autocomplete']) ? $this->options['autocomplete'] : 'off',
            'action' => isset($this->options['action']) ? $this->options['action'] : '',
            'method' => !empty($this->options['method']) ? $this->options['method'] : 'POST',
            'id' => $form_id,
            'class' => 'wpo_admin_form ' . (isset($this->options['class']) ? $this->options['class'] : ''),
            'name' => isset($this->options['name']) ? $this->options['name'] : '',
            'style' => isset($this->options['style']) ? $this->options['style'] : ''
        );
        $string = '';
        foreach ($attributes as $key => $val) {
            $string .= " $key = \"$val\"";
        }
        ?>
        <form <?php echo $string; ?>>
            <?php $this->before_form();
            $this->render_form_inner( $form_id );
            $this->after_form(); ?>
        </form>
        <?php
    }

    function display() {
        wp_register_script(
            'wpo-validation',
            WO()->plugin_url . 'assets/js/plugins/jquery.wpo_validation.js',
            array(),
            WP_OFFICE_VER,
            true
        );

        wp_localize_script('wpo-validation', 'wpo_validation',
            apply_filters( 'wpoffice_validation_localize', array(
                'error' => WO()->validation()->error_messages
            ) )
        );
        wp_print_scripts( array(
            'wpo-validation'
        ));

        $form_id = !empty( $this->options['id'] ) ? $this->options['id'] : uniqid('wpo_form_');

        $this->display_fields( $form_id );
        ?>
        <script type="text/javascript">
            var p_obj = '.wpo_pulllayer';
            var submit_button = "<?php echo str_replace("\r", "", str_replace("\n", "", addslashes($this->submit_button()))); ?>";
            if( jQuery('.wpo_pulllayer_opened').length ) {
                //backend
                var p_obj = '.wpo_pulllayer_opened:last';
                jQuery( p_obj + ' .wpo_pulllayer_title:first > h2').append( submit_button );
            } else {
                //frontend
                jQuery( p_obj + ' .wpo_pulllayer_title > h2').append( submit_button );
            }

            var list_table_uniqueid;
            if ( typeof jQuery(p_obj).data('options') !== 'undefined' ) {
                if ( typeof jQuery(p_obj).data('options').custom_data !== 'undefined' ) {
                    list_table_uniqueid = jQuery(p_obj).data('options').custom_data.list_table_uniqueid;
                }
            }

            <?php if ( $this->include_save_handler ) { ?>
            jQuery('body').off('click', p_obj + ' .wpo_save_form<?php echo $this->unique ?>').on('click', p_obj + ' .wpo_save_form<?php echo $this->unique ?>', function() {
                if( jQuery(this).data('loading') == '1' ) return false;
                var validation = jQuery('#<?php echo $form_id; ?>').wpo_validation('validate_submit');
                if( !validation ) return false;

                <?php $this->js_before_build_ajax_data(); ?>

                var fields_data = jQuery.base64Encode( jQuery(this).closest( ".wpo_pulllayer" ).find('.wpo_admin_form').serialize() ).split('+').join('-');

                jQuery( '.wpo_form_error' ).removeClass( 'wpo_form_error' );
                jQuery( '.wpo_form_error_notice' ).remove();

                jQuery(this).attr('data-loading', '1');
                var obj = jQuery(this);

                <?php $this->js_before_submit_ajax(); ?>

                jQuery.ajax({
                    type: "POST",
                    url: '<?php echo WO()->get_ajax_route( get_class( $this ), 'ajax_save_form' ); ?>',
//                                data : 'wpo_form_data=' + fields_data + hash,
                    data : 'wpo_form_data=' + fields_data,
                    dataType: "json",
                    success: function( data ) {
                        if (data.status) {
                            if ( data.message ) {
                                jQuery(this).wpo_notice({
                                    message: data.message,
                                    type: 'update'
                                });
                            }

                            if ( data.close ) {
                                jQuery( p_obj ).pulllayer('close');
                            } else if (data.refresh) {
                                jQuery( p_obj ).pulllayer('refresh');
                            }

                            <?php $this->js_on_save_success(); ?>

                        } else {

                            if( data.errors ) {

                                for( $key in data.errors ) {
                                    $fobj = jQuery( '#wpo_form_' + $key );

                                    if ( $fobj.length ) {
                                        $fobj.addClass( 'wpo_form_error' );
                                        $fobj.after( '<span class="wpo_form_error_notice">' + data.errors[$key] + '</span>' );
                                    }
                                }

                            } else if( data.validation_message ) {
                                var error_message, $field;
                                for( name in data.validation_message ) {
                                    $field = obj.closest(".wpo_pulllayer").find('.wpo_admin_form *[name="' + name + '"]');
                                    error_message = Object.keys( data.validation_message[ name ] )[0];

                                    if( typeof $field.data('wpo-valid') == 'undefined' ) {
                                        $field = $field.closest('[data-wpo-valid]');
                                    }

                                    jQuery(this).closest(".wpo_pulllayer").find('.wpo_admin_form')
                                        .wpo_validation( 'show_validation_message', $field.get(0), error_message );
                                }
                            } else {

                                jQuery(this).wpo_notice({
                                    message: data.message,
                                    type: 'error'
                                });

                            }

                            <?php $this->js_on_save_error(); ?>
                        }
                        obj.removeAttr('data-loading');
                    }
                });
            });
            <?php } ?>
        </script>
        <?php
    }

    function render_single_title( $field ) {
        ?>
        <tr>
            <th scope="row" colspan="2">
                <h3>
                    <?php echo isset($field['label']) ? $field['label'] : ''; ?>
                    <?php if( !empty( $field['description'] ) ) { ?>
                        <br />
                        <span class="description" style="font-weight: normal"><?php echo $field['description'] ?></span>
                    <?php } ?>
                </h3>
            </th>
        </tr>
        <?php
    }


    function render_separator() { ?>
        <tr>
            <td colspan="2">
                <hr />
            </td>
        </tr>
        <?php
    }

    function render_single_row( $key, $field ) {
        $field['id'] = isset( $field['id'] ) ? $field['id'] : uniqid('wpo_field_');

        if( !empty( $this->validation_array[$key] ) && is_array( $this->validation_array[$key] ) ) {
            $field['custom_attributes']['data-wpo-valid'] = implode(' ', $this->validation_array[$key]);
        }

        ?>

        <tr <?php if ( !empty( $field['tag'] ) && 'hidden' == $field['tag'] ) {?>style="display:none;"<?php } ?>>
            <th scope="row" class="wpo_admin_form_th">

                <label for="<?php echo $field['id']; ?>" <?php if ( empty( $field['label'] ) ) { ?>style="display: none;"<?php } ?>>
                    <span class="wpo_admin_form_field_label">
                        <?php echo isset($field['label']) ? $field['label'] : ''; ?>

                        <?php if( isset( $field['validation']['required'] ) && $field['validation']['required'] ) { ?>
                            <span class="wpo_admin_form_required_field">*</span>
                        <?php } ?>
                    </span>

                    <?php if( !empty( $field['helptip'] ) ) {
                    echo '<span class="wpo_admin_form_helptip_container">' . WO()->render_helptip( $field['helptip'], false ) . '</span>' ;
                    } ?>

                </label>
            </th>
            <td>
                <?php
                if ( !empty( $field['tag'] ) ) {
                    $field_content = apply_filters( 'wpoffice_render_single_field_' . $field['tag'], '', $field );
                    if ( !empty( $field_content ) ) {
                        echo $field_content;
                    } else {
                        if ( method_exists( $this, 'render_wpo_' . $field['tag'] ) ) {
                            call_user_func( array( &$this, 'render_wpo_' . $field['tag'] ), $field );
                        }
                    }
                } ?>
                <?php if ( !empty( $field['description'] ) ) { ?>
                    <br />
                    <span class="description"><?php echo $field['description']; ?></span>
                <?php } ?>
            </td>
        </tr>
        <?php
    }

    function render_form_inner( $form_id ) {
        $validation_flag = false;

        //prepare validation array
        $this->get_validation_fields();

        ?>
        <div class="wpo_admin_form_inner">
            <?php if ( !empty( $this->options['description'] ) ) { ?>
                <div class="wpo_admin_form_description"><?php echo $this->options['description'] ?></div>
            <?php } ?>
            <table>
                <?php foreach ($this->fields as $key => $field) {
                    if ( isset( $field['type'] ) && 'title' == $field['type'] ) {
                        $this->render_single_title( $field );
                    } elseif ( isset( $field['type'] ) && 'separator' == $field['type'] ) {
                        $this->render_separator();
                    } else {
                        if ( isset( $this->input_data ) ) {
                            if ( is_array( $this->input_data ) && isset( $this->input_data[ $key ] ) ) {
                                $field['value'] = $this->input_data[ $key ];
                            } elseif ( is_object( $this->input_data ) && isset( $this->input_data->$key ) ) {
                                $field['value'] = $this->input_data->$key;
                            }
                        } elseif ( !isset( $field['value'] ) ) {
                            $field['value'] = '';
                        }
                        $this->render_single_row( $key, $field );
                    }

                    if( !empty( $field['validation'] )  ) {
                        $validation_flag = true;
                    }
                } ?>
            </table>
        </div>
        <?php if( $validation_flag ) { ?>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('#<?php echo $form_id; ?>').wpo_validation();
                });
            </script>
        <?php }
    }

    function after_form() {
        return '';
    }

    function before_form() {
        return '';
    }

    function get_form_fields() {
        return $this->fields;
    }


    /**
     * Get prepare array of fields for validation
     *
     * @return void
     */
    function get_validation_fields() {
        $fields = $this->fields;

        $this->validation_array = array();
        foreach ( $fields as $name => $value ) {
            if ( isset( $value['validation'] ) && is_array( $value['validation'] ) ) {
                $this->validation_array[ $value['name'] ] = $value['validation'];
            }
        }

    }

    function add_fields($array) {
        if (is_array($array)) {
            $this->fields = array_merge( $this->fields, $array );
        }
        return $this;
    }
}
