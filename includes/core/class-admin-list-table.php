<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Admin_List_Table
 */
class Admin_List_Table {

    /**
     * The current list of items
     *
     * @var array
     * @access public
     */
    public $items;

    /**
     * Various information about the current table
     *
     * @var array
     * @access protected
     */
    protected $_args;

    /**
     * Various information needed for displaying the pagination
     *
     * @var array
     * @access protected
     */
    protected $_pagination_args = array();

    /**
     * Cached bulk actions
     *
     * @var array
     * @access private
     */
    private $_actions;


    /**
     * Stores the value returned by ->get_column_info()
     *
     * @var array
     * @access protected
     */
    protected $_column_headers;


    /**
     * Stores the value items ID column name
     *
     * @var string
     * @access protected
     */
    protected $_ID_column;

    /**
     * Stores the value of sortable column
     *
     * @var array
     * @access protected
     */
    protected $sortable_columns = array();

    /**
     * Stores the value of filters line attributes
     *
     * @var array
     * @access protected
     */
    protected $filter_line_args = array();


    /**
     * Stores the value of filters block attributes
     *
     * @var array
     * @access protected
     */
    protected $filters_block = array();

    /**
     * Stores the value of default sorting field
     *
     * @var string
     * @access protected
     */
    protected $default_sorting_field = array();

    /**
     * Stores the value returned by ->get_columns()
     *
     * @var array
     * @access protected
     */
    protected $columns;
    protected $columns_data;

    /**
     * Stores the value returned by ->get_bulk_actions()
     *
     * @var array
     * @access protected
     */
    protected $bulk_actions = array();

    public $uniqid = '';
    public $print_scripts;

    protected $attributes = array();

    protected $compat_fields = array( '_args', '_pagination_args', '_actions', '_pagination', '_ID_column' );

    protected $compat_methods = array( 'set_pagination_args', 'get_views', 'get_bulk_actions', 'bulk_actions',
        'row_actions', 'get_items_per_page', 'pagination',
        'get_sortable_columns', 'get_column_info', 'get_table_classes', 'display_tablenav', 'extra_tablenav',
        'single_row_columns' );


    /**
     * Constructor.
     *
     * The child class should call this constructor from its own constructor to override
     * the default $args.
     *
     * @access public
     *
     * @param array|string $args {
     *     Array or string of arguments.
     *
     *     @type string $plural   Plural value used for labels and the objects being listed.
     *                            This affects things such as CSS class-names and nonces used
     *                            in the list table, e.g. 'posts'. Default empty.
     *     @type string $singular Singular label for an object being listed, e.g. 'post'.
     *                            Default empty
     * }
     */
    public function __construct( $args = array() ) {
        $args = wp_parse_args( $args, array(
            'plural'            => 'items',
            'singular'          => 'item',
            'no_items_message'  => '',
        ) );

        $this->_args = $args;

        if( empty( WO()->wpo_flags['list_table_count'] ) ) {
            WO()->wpo_flags['list_table_count'] = 1;
        } else {
            WO()->wpo_flags['list_table_count']++;
        }

        $this->uniqid = WO()->wpo_flags['list_table_count'];
        $this->print_scripts = true;
    }


    /**
     * Make private/protected methods readable for backwards compatibility.
     *
     * @access public
     *
     * @param callable $name      Method to call.
     * @param array    $arguments Arguments to pass when calling.
     * @return mixed|bool Return value of the callback, false otherwise.
     */
    public function __call( $name, $arguments ) {
        if ( in_array( $name, $this->compat_methods ) ) {
            return call_user_func_array( array( $this, $name ), $arguments );
        }
        return false;
    }


    public function parse_filter( $active_filters, $default_format ) {

    }


    public function parse_active_filters() {
        $filters = array();

        if ( count( $this->filters_block ) ) {
            foreach ( $this->filters_block as $filter_by_key => $filter_by_value ) {
                if( !empty( $_REQUEST[$filter_by_key] ) ) {
                    if( count( explode( ',', $_REQUEST[$filter_by_key] ) ) > 1 ) {
                        foreach( explode( ',', $_REQUEST[$filter_by_key] ) as $filter_item ) {
                            $filters[] = array(
                                'filter_by' => array(
                                    'title' => $filter_by_value,
                                    'value' => $filter_by_key
                                ),
                                'filter_value' => $filter_item
                            );
                        }
                    } else {
                        $filters[] = array(
                            'filter_by' => array(
                                'title' => $this->filters_block[$filter_by_key],
                                'value' => $filter_by_key
                            ),
                            'filter_value' => $_REQUEST[$filter_by_key]
                        );
                    }
                }
            }
        }

        return $filters;
    }


    /**
     * Function for building Filter Field on WP List Table
     *
     * @param array $attr parameters of filter
     * @param bool|false $ajax_filter AJAX loading filter's values
     * @return string Filters HTML
     */
    public function build_filter_field( $attr = array() ) {
        ob_start(); ?>

        <script class="wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_filters_sample" type="text/x-jsrender">
            <label>
                <?php _e( 'Filter', WP_OFFICE_TEXT_DOMAIN ) ?>:<br />
                <select class="wpo_filter_value">
                    <option value=""><?php _e( 'None', WP_OFFICE_TEXT_DOMAIN ) ?></option>
                    {{props data}}
                        <option value="{{>prop.id}}">{{>prop.title}}</option>
                    {{/props}}
                </select>
            </label>
        </script>

        <script class="wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_active_filters_sample" type="text/x-jsrender">
            {{if ~root.length}}
                <div class="wpo_active_filter_wrapper" data-filter_by="{{>filter_by.value}}" data-filter_value="{{>filter_value}}">
                    {{>filter_by.title}}: {{>filter_value}}
                    <div class="wpo_remove_filter">&times;</div>
                </div>
            {{/if}}
        </script>
        <div class="wpo_filter_block_wrapper">
            <div class="wpo_filter_block">
                <?php if( count( array_keys( $attr ) ) ) { ?>
                    <?php WO()->get_button(
                        '<div class="dashicons dashicons-filter"></div>',
                        array(
                            'class' => 'wpo_filter',
                            'title' => __( 'Filters', WP_OFFICE_TEXT_DOMAIN )
                        ),
                        array( 'only_text' => true )
                    ); ?>
                    <div class="wpo_filter_wrapper">
                        <label><?php _e( 'Filter By', WP_OFFICE_TEXT_DOMAIN ) ?>:<br />
                            <select class="wpo_filter_by">
                                <?php foreach( $attr as $key=>$title ) { ?>
                                    <option value="<?php echo $key ?>"><?php echo $title ?></option>
                                <?php } ?>
                            </select>
                        </label>

                        <div class="wpo_ajax_content">
                            <div class="wpo_loading_overflow">
                                <?php echo WO()->get_ajax_loader(24) ?>
                            </div>
                            <div class="wpo_overflow_content">
                                <div class="wpo_filter_selectors"></div>
                                <div class="clear"></div>
                                <?php WO()->get_button( __( 'Apply Filter', WP_OFFICE_TEXT_DOMAIN ), array('class'=>'wpo_add_filter'), array( 'only_text' => true, 'primary' => true ) ) ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="wpo_active_filters_wrapper"></div>
        </div>
        <?php $field = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $field;
    }


    /**
     * Extra controls to be displayed between bulk actions and pagination
     *
     * @access protected
     *
     * @param string $which
     */
    protected function extra_tablenav( $which ) {}


    /**
     * Generate the table navigation above or below the table
     *
     * @access protected
     * @param string $which
     */
    protected function display_tablenav( $which ) {
        if ( 'top' == $which )
            wp_nonce_field( 'bulk-' . sanitize_key( $this->_args['plural'] ) );
        ?>
        <div class="wpo_tablenav wpo_tablenav_<?php echo $this->uniqid; ?> <?php echo esc_attr( $which ); ?>">
            <?php $this->extra_tablenav( $which );
            $this->pagination( $which ); ?>
        </div>
        <?php
    }


    /**
     * Get a list of CSS classes for the list table table tag.
     *
     * @access protected
     *
     * @return array List of CSS classes for the table tag.
     */
    protected function get_table_classes() {
        return array( sanitize_key( $this->_args['plural'] ) );
    }


    /**
     * Display the bulk actions dropdown.
     *
     */
    protected function bulk_actions() {
        if ( is_null( $this->_actions ) ) {
            $no_new_actions = $this->_actions = $this->get_bulk_actions();
            /**
             * Filter the list table Bulk Actions drop-down.
             *
             * The dynamic portion of the hook name, `$this->screen->id`, refers
             * to the ID of the current screen, usually a string.
             *
             * This filter can currently only be used to remove bulk actions.
             *
             * @param array $actions An array of the available bulk actions.
             */
            $this->_actions = array_intersect_key( $this->_actions, $no_new_actions );
        }

        if ( empty( $this->_actions ) )
            return '';

        ob_start(); ?>
        <ul class="wpo_bulk_action_selector wpo_noselect">
            <?php foreach ( $this->_actions as $name => $action ) {
                $class = 'edit' == $name ? ' class="hide-if-no-js"' : '';?>
                <li <?php echo $class ?> data-value="<?php echo $name ?>" data-confirm="<?php echo ( !empty( $action['confirm'] ) || isset( $action['confirm'] ) && false === $action['confirm'] ) ? $action['confirm'] : "1" ?>" data-custom="<?php echo !empty( $action['custom'] ) ? $action['custom'] : "0" ?>">
                    <?php echo $action['title'] ?>
                </li>
            <?php } ?>
        </ul>
        <?php $bulk_actions = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }

        return $bulk_actions;
    }


    /**
     * Display the table
     *
     * @access public
     */
    public function display() {
        if ( $this->print_scripts ) {
            wp_print_scripts( array(
                'wpo-list_table-js-render',
                'wpo-list_table-js'
            ) );
            wp_print_styles( array(
                'wpo-list_table-style'
            ) );
        } ?>

        <div class="wpo_list_table_wrapper" data-id="<?php echo $this->uniqid; ?>" data-ajax="<?php echo defined( 'DOING_AJAX' ) ? '1' : '0' ?>">

            <?php $this->filters_line();
            $this->search_box( __( 'Search', WP_OFFICE_TEXT_DOMAIN ) );
            $this->display_tablenav( 'top' ); ?>

            <div class="wpo_list_table">
                <div class="wpo_list_table_content_wrapper">
                    <div class="wpo_list_table_header"></div>
                    <div class="wpo_list_table_body">
                        <div class="wpo_list_table_rows_wrapper"></div>
                        <div class="wpo_list_table_ajax_loader_wrapper">
                            <div class="wpo_list_table_ajax_loader">
                                <?php echo WO()->get_ajax_loader( 77 ) ?>
                            </div>
                        </div>
                    </div>
                    <div class="wpo_list_table_footer"></div>
                </div>
                <div class="wpo_list_table_ajax_loader_wrapper">
                    <div class="wpo_list_table_ajax_loader">
                        <?php echo WO()->get_ajax_loader( 77 ) ?>
                    </div>
                </div>
            </div>

            <?php $this->display_tablenav( 'bottom' ); ?>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                jQuery('.wpo_list_table_wrapper[data-id="<?php echo $this->uniqid; ?>"]').listTable({
                    'texts': {
                        'apply'             : '<?php _e( 'Apply', WP_OFFICE_TEXT_DOMAIN ) ?>',
                        'default_confirm'   : '<?php _e( 'Are You sure', WP_OFFICE_TEXT_DOMAIN ) ?>',
                        'no_items_selected' : '<?php _e( 'No Items Selected', WP_OFFICE_TEXT_DOMAIN ) ?>'
                    },
                    'ajax_url'              : '<?php echo WO()->get_ajax_route( get_class( $this ), 'list_table_data' ) ?>',
                    'filter_ajax_url'       : '<?php echo WO()->get_ajax_route( get_class( $this ), 'get_filter' ) ?>',
                    'bulk_ajax_url'         : '<?php echo WO()->get_ajax_route( get_class( $this ), 'bulk_action' ) ?>',
                    'page'                  : '<?php echo sanitize_key( $this->_args['plural'] ) ?>',
                    'sample'                : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_sample',
                    'headers_sample'        : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_headers_sample',
                    'pagination_sample'     : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_pagination_sample',
                    'filters_line_sample'   : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_filters_line_sample',
                    'filters_sample'        : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_filters_sample',
                    'active_filters_sample' : 'wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_active_filters_sample',
                    'no_items'              : '<div class="no-items"><?php echo $this->no_items() ?></div>',
                    'additional_params'     : <?php echo json_encode( $this->attributes ) ?>
                });
            });
        </script>
    <?php }

    /**
     * Print filters line sample
     */
    public function filters_line() {
        $filters_args = $this->get_filters_line();
        if ( !empty( $filters_args ) ) {
            if( isset( $filters_args['current'] ) ) unset( $filters_args['current'] );
            ob_start();
            echo $this->before_filters_line(); ?>
            <script class="wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_filters_line_sample" type="text/x-jsrender">
                <?php $i = 0; $current = '';
                foreach( $filters_args as $role_key => $value ) {
                    $current = ( !empty( $value['current'] )  ) ? $role_key : $current; ?>

                    <li class="<?php echo $role_key ?>">
                        <a class="wpo_list_table_filter_line_item {{if current=='<?php echo $role_key ?>'}}wpo_current{{/if}}" href="{{if <?php echo $role_key ?>.href}}{{><?php echo $role_key ?>.href}}{{else}}javascript:void(0);{{/if}}" data-is_href="{{if <?php echo $role_key ?>.href}}true{{else}}false{{/if}}" data-tab="<?php echo $role_key ?>">
                            <?php echo $value['title'] ?>
                            <span class="wpo_filter_line_count">({{><?php echo $role_key ?>.count}})</span>
                        </a>
                        <?php if( $i < count( $filters_args ) - 1 ) { ?> | <?php } ?>
                    </li>
                    <?php $i++;
                } ?>
            </script>
            <ul class="wpo_list_table_filters_line">
                <?php $i = 0;
                foreach( $filters_args as $role_key => $value ) { ?>
                    <li class="<?php echo $role_key ?>">
                        <a class="wpo_list_table_filter_line_item <?php echo ( ( empty( $current ) && $i == 0 ) || $current == $role_key ) ? 'wpo_current' : '' ?>" href="<?php echo ( !empty( $value['href'] ) ) ? $value['href'] : 'javascript:void(0);' ?>" data-is_href="<?php echo ( !empty( $value['href'] ) ) ? 'true' : 'false' ?>" data-tab="<?php echo $role_key ?>">
                            <?php echo $value['title'] ?>
                            <span class="wpo_filter_line_count">(<?php echo $value['count'] ?>)</span>
                        </a>
                        <?php if( $i < count( $filters_args ) - 1 ) { ?> | <?php } ?>
                    </li>
                    <?php $i++;
                } ?>
            </ul>
            <?php $sample = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }
            echo $sample;
        } else {
            echo $this->before_filters_line();
        }
    }


    function before_filters_line() {
        return '';
    }


    /**
     * Generates the columns for a single row of the table
     *
     * @access protected
     *
     * @param object $item The current item
     */
    protected function single_row_columns() {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        $count_columns = count( $columns );
        foreach ( $columns as $column_name => $column_display_name ) {
            $classes = "column-$column_name wpo_column";

            if ( in_array( $column_name, $hidden ) ) {
                $classes .= ' hidden';
            }

            if( $primary === $column_name ) {
                $classes .= ' wpo_primary';
            }

            // Comments column uses HTML in the display name with screen reader text.
            // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
            $data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

            $attributes = "class='$classes' $data";

            $text_align = '';
            if ( !empty( $this->columns_data[$column_name]['text-align'] ) ) {
                $text_align = "text-align:{$this->columns_data[$column_name]['text-align']};";
            }

            if( $primary === $column_name ) {
                if ( $count_columns > 1 ) {
                    $width = 'width: calc( ' . ( 100/( $count_columns - 1 ) ) . '% - 43px );';

                    if ( !empty( $this->columns_data[$column_name]['width'] ) ) {
                        $width = "width: {$this->columns_data[$column_name]['width']};";
                    }
                } else {
                    $width = "width: 100%;";
                }
                $style = 'style="' . $width . $text_align . '"';
            } else {
                if ( $count_columns > 1 ) {
                    $width = 'width:' . ( 100/( $count_columns - 1 ) ) . '%;';
                    if ( !empty( $this->columns_data[$column_name]['width'] ) ) {
                        $width = "width: {$this->columns_data[$column_name]['width']};";
                    }
                } else {
                    $width = "width: 100%;";
                }
                $style = 'style="' . $width . $text_align . '"';
            }

            if ( 'cb' == $column_name ) {
                echo '<div class="wpo_check-column wpo_column column-cb">';
                echo $this->column_cb();
                echo '</div>';
            } elseif ( method_exists( $this, '_column_' . $column_name ) ) {
                echo call_user_func(
                    array( $this, '_column_' . $column_name ),
                    $classes,
                    $data,
                    $primary
                );
            } else {
                echo "<div $attributes $style>";
                if( $primary === $column_name ) {
                    echo '<div class="wpo_primary_content">';
                } else {
                    echo '<div class="wpo_column_name">' . wp_strip_all_tags( $column_display_name ) . '</div>';
                    echo '<div class="wpo_column_value">';
                }
                if ( method_exists( $this, 'column_' . $column_name ) ) {
                    echo call_user_func( array( $this, 'column_' . $column_name ) );
                } else {
                    echo "{{:" . $column_name . "}}";
                }
                if( $primary === $column_name ) {
                    echo "</div>";
                    echo '<div class="wpo-toggle-row"></div>';
                } else {
                    echo "</div>";
                }
                echo "</div>";
            }
        }
    }

    /**
     * Print column headers samples, accounting for hidden and sortable columns.
     */
    public function headers_sample() {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if ( ! empty( $columns['cb'] ) ) {
            static $cb_counter = 1;

            $custom_style = WO()->get_settings( 'custom_style' );

            $columns['cb'] = '<div class="wpo_bulk_actions_wrapper">';
            if ( is_admin() || ( empty( $custom_style['disable_plugin_css'] ) || 'yes' != $custom_style['disable_plugin_css'] ) ) {
                $columns['cb'] .= WO()->get_button( '<input id="cb-select-all-' . $cb_counter . '" class="wpo_cb-select-all" type="checkbox" />', array(
                    'class' => 'wpo_bulk_actions',
                    'title' => __( 'Select All', WP_OFFICE_TEXT_DOMAIN )
                ), array( 'only_text' => true ), false );
            } else {
                $columns['cb'] .= '<button class="wpo_bulk_actions">
                <input id="cb-select-all-' . $cb_counter . '" class="wpo_cb-select-all" type="checkbox" />
                </button>';
            }
            $columns['cb'] .= '<div class="wpo_bulk_actions_dropdown">' . $this->bulk_actions() . '</div>
            </div>';

            $cb_counter++;
        }

        $sample = '<script class="wpo_list_table_' . sanitize_key( $this->_args['plural'] ) . '_headers_sample" type="text/x-jsrender">';
        $count_columns = count( $columns );
        foreach ( $columns as $column_key=>$column_display_name ) {
            $class = array( 'wpo_list_table_column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) ) {
                $class[] = 'hidden';
            }

            if ( 'cb' == $column_key )
                $class[] = 'wpo_check-column';

            if ( isset( $sortable[$column_key] ) ) {
                list( $orderby, $order ) = $sortable[$column_key];

                if ( !empty( $order ) ) {
                    $class[] = 'wpo_sorted';
                    $class[] = $order;
                } else {
                    $class[] = 'wpo_sortable';
                    $class[] = $order ? 'asc' : 'desc';
                }

                $column_display_name = '<a href="javascript:void(0);">
                    <span class="wpo_header_column_name">' . $column_display_name . '</span>
                    <span class="wpo_sort-icon"></span>
                 </a>';
            }

            $id = "{{if header}}id='$column_key'{{/if}}";

            $style = '';
            if ( $column_key != 'cb' ) {
                $text_align = '';
                if ( !empty( $this->columns_data[$column_key]['text-align'] ) ) {
                    $text_align = "text-align:{$this->columns_data[$column_key]['text-align']};";
                }

                if ( $primary === $column_key ) {
                    if ( $count_columns > 1 ) {
                        $width = 'width: calc( ' . ( 100/( $count_columns - 1 ) ) . '% - 43px );';
                        if ( !empty( $this->columns_data[$column_key]['width'] ) ) {
                            $width = "width: {$this->columns_data[$column_key]['width']};";
                        }
                    } else {
                        $width = "width: 100%;";
                    }
                    $style = 'style="' . $width . $text_align . '"';
                    $class[] = 'wpo_primary';
                } else {
                    if ( $count_columns > 1 ) {
                        $width = 'width:' . ( 100/( $count_columns - 1 ) ) . '%;';
                        if ( !empty( $this->columns_data[$column_key]['width'] ) ) {
                            $width = "width: {$this->columns_data[$column_key]['width']};";
                        }
                    } else {
                        $width = "width: 100%;";
                    }
                    $style = 'style="' . $width . $text_align . '"';
                }
            }

            if ( !empty( $class ) )
                $class = "class='" . join( ' ', $class ) . "'";

            $data = 'data-column="' . $column_key . '"';

            $sample .= "<div $id $class $data $style>$column_display_name</div>";
        }

        return $sample . '</script>';
    }


    /**
     * Print table row sample.
     *
     * @return string
     */
    function sample() {
        ob_start(); ?>
        <script class="wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_sample" type="text/x-jsrender">
            <div class="wpo_single_row {{>single_row_class}}">
                <?php $this->single_row_columns() ?>
            </div>
        </script>
        <?php $sample = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }
        return $sample;
    }

    /**
     * Get a list of all, hidden and sortable columns, with filter applied
     *
     * @access protected
     *
     * @return array
     */
    protected function get_column_info() {
        // $_column_headers is already set / cached
        if ( isset( $this->_column_headers ) && is_array( $this->_column_headers ) ) {
            // Back-compat for list tables that have been manually setting $_column_headers for horse reasons.
            // In 4.3, we added a fourth argument for primary column.
            $column_headers = array( array(), array(), array(), $this->get_primary_column_name() );
            foreach ( $this->_column_headers as $key => $value ) {
                $column_headers[ $key ] = $value;
            }

            return $column_headers;
        }

        $columns = get_column_headers( $this->screen );
        $hidden = get_hidden_columns( $this->screen );

        $sortable_columns = $this->get_sortable_columns();

        $sortable = array();
        foreach ( $sortable_columns as $id => $data ) {
            if ( empty( $data ) )
                continue;

            $data = (array) $data;
            if ( !isset( $data[1] ) )
                $data[1] = false;

            $sortable[$id] = $data;
        }

        $primary = $this->get_primary_column_name();

        $this->_column_headers = array( $columns, $hidden, $sortable, $primary );

        return $this->_column_headers;
    }

    /**
     * Display the pagination.
     *
     * @access protected
     *
     * @param string $which
     */
    protected function pagination( $which ) {
        ?>
        <script class="wpo_list_table_<?php echo sanitize_key( $this->_args['plural'] ) ?>_pagination_sample" type="text/x-jsrender">
            <div class="wpo_pagination_wrapper {{if pages_count == 1 }}one-page{{/if}} {{if pages_count < 1 }}no-pages{{/if}}">
                <span class="wpo_pagination_num">{{>count}}&nbsp;{{if count == 1 }}<?php _e( 'item', WP_OFFICE_TEXT_DOMAIN ) ?>{{else}}<?php _e( 'items', WP_OFFICE_TEXT_DOMAIN ) ?>{{/if}}</span>
                {{if pages_count > 1 }}
                    <div class="wpo_pagination">
                        {{if current_page < 3 }}
                            <?php WO()->get_button( '&laquo;', array( 'class' => 'wpo_first-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&laquo;', array( 'class' => 'wpo_first-page', 'data-page' => '1' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        {{if current_page < 2 }}
                            <?php WO()->get_button( '&lsaquo;', array( 'class' => 'wpo_prev-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&lsaquo;', array( 'class' => 'wpo_prev-page', 'data-page' => '{{>current_page*1 - 1}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        <span class="wpo_pagination_pages">
                            <?php if ( 'bottom' == $which ) { ?>
                                {{>current_page}}
                            <?php } else { ?>
                                <input class="wpo_pagination_current_page" type="text" name="wpo_pagination_current_page" value="{{>current_page}}" data-max_page="{{>pages_count}}" size="{{>pages_count.toString().length}}" />
                            <?php } ?>
                            &nbsp;<?php _e( 'of', WP_OFFICE_TEXT_DOMAIN ) ?>&nbsp;
                            <span class="wpo_pagination_total_pages">{{>pages_count}}</span>
                        </span>

                        {{if current_page == pages_count }}
                            <?php WO()->get_button( '&rsaquo;', array( 'class' => 'wpo_next-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&rsaquo;', array( 'class' => 'wpo_next-page', 'data-page' => '{{>current_page*1 + 1}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}

                        {{if current_page >= pages_count - 1 }}
                            <?php WO()->get_button( '&raquo;', array( 'class' => 'wpo_last-page' ), array( 'only_text' => true, 'disabled' => true ) ) ?>
                        {{else}}
                            <?php WO()->get_button( '&raquo;', array( 'class' => 'wpo_last-page', 'data-page' => '{{>pages_count}}' ), array( 'only_text' => true ) ) ?>
                        {{/if}}
                    </div>
                {{/if}}
            </div>
        </script>
        <?php
    }


    /**
     * Get number of items to display on a single page
     *
     * @access protected
     *
     * @param string $option
     * @param int    $default
     * @return int
     */
    protected function get_items_per_page( $option, $default = 20 ) {
        $per_page = (int) get_user_option( $option );
        if ( empty( $per_page ) || $per_page < 1 )
            $per_page = $default;

        if( (int)$per_page > 100 ) {
            $per_page = 20;
        }

        /**
         * Filter the number of items to be displayed on each page of the list table.
         *
         * The dynamic hook name, $option, refers to the `per_page` option depending
         * on the type of list table in use. Possible values include: 'edit_comments_per_page',
         * 'sites_network_per_page', 'site_themes_network_per_page', 'themes_network_per_page',
         * 'users_network_per_page', 'edit_post_per_page', 'edit_page_per_page',
         * 'edit_{$post_type}_per_page', etc.
         *
         * @since 2.9.0
         *
         * @param int $per_page Number of items to be displayed. Default 20.
         */
        return (int) apply_filters( $option, $per_page );
    }


    /**
     * Get the current page number
     *
     * @access public
     *
     * @return int
     */
    public function get_pagenum() {
        $pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
            $pagenum = $this->_pagination_args['total_pages'];

        return max( 1, $pagenum );
    }


    /**
     * Generate row actions div
     *
     * @access protected
     *
     * @param array $actions The list of actions
     * @param bool $always_visible Whether the actions should be always visible
     * @return string
     */
    protected function row_actions( $actions, $always_visible = false ) {
        $action_count = count( $actions );
        $i = 0;

        if ( !$action_count )
            return '<div class="wpo_list_table_row_actions">&nbsp;</div>';

        $out = '<div class="' . ( $always_visible ? 'wpo_list_table_row_actions visible' : 'wpo_list_table_row_actions' ) . '">';
        foreach ( $actions as $action => $link ) {
            ++$i;
            ( $i == $action_count ) ? $sep = '' : $sep = '<span class="wpo_action_separator"> | </span>';
            $out .= "<span class='wpo_$action'>$link$sep</span>";
        }
        $out .= '&nbsp;</div>';
        return $out;
    }


    /**
     * Display the search box.
     *
     * @access public
     *
     * @param string $text The search button text
     */
    public function search_box( $text ) {
        $custom_style = WO()->get_settings( 'custom_style' ); ?>
        <div class="wpo_search_box wpo_search_box_<?php echo $this->uniqid; ?>">
            <?php if ( is_admin() || ( empty( $custom_style['disable_plugin_css'] ) || 'yes' != $custom_style['disable_plugin_css'] ) ) {
                WO()->get_button(
                    '<div class="dashicons dashicons-search"></div>',
                    array(
                        'class' => 'wpo_search_submit',
                        'title' => $text
                    ),
                    array(
                        'only_text' => true
                    )
                );
            } else { ?>
                <button class="wpo_search_submit" title="<?php $text ?>"><span class="dashicons dashicons-search"></span></button>
            <?php } ?>
            <input type="search" class="wpo_search_line" value="" />
        </div>
        <?php
    }


    /**
     * Message to be displayed when there are no items
     *
     * @access public
     */
    public function no_items() {
        if( !empty( $this->_args['no_items_message'] ) ) {
            return $this->_args['no_items_message'];
        } else {
            return sprintf( __( 'No %s found.', WP_OFFICE_TEXT_DOMAIN ), $this->_args['plural'] );
        }
    }


    /**
     * Whether the table has items to display or not
     *
     * @access public
     *
     * @return bool
     */
    public function has_items() {
        return !empty( $this->items );
    }


    /**
     * Access the pagination args.
     *
     * @access public
     *
     * @param string $key Pagination argument to retrieve. Common values include 'total_items',
     *                    'total_pages', 'per_page', or 'infinite_scroll'.
     * @return int Number of items that correspond to the given pagination argument.
     */
    public function get_pagination_arg( $key ) {
        if ( 'page' == $key )
            return $this->get_pagenum();

        if ( isset( $this->_pagination_args[$key] ) )
            return $this->_pagination_args[$key];

        return false;
    }


    /**
     * An internal method that sets all the necessary pagination arguments
     *
     * @param array $args An associative array with information about the pagination
     * @access protected
     *
     * @param array|string $args
     */
    protected function set_pagination_args( $args ) {
        $args = wp_parse_args( $args, array(
            'total_items' => 0,
            'total_pages' => 0,
            'per_page' => 0,
        ) );

        if ( !$args['total_pages'] && $args['per_page'] > 0 )
            $args['total_pages'] = ceil( $args['total_items'] / $args['per_page'] );

        // Redirect if page number is invalid and headers are not already sent.
        if ( ! headers_sent() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && $args['total_pages'] > 0 && $this->get_pagenum() > $args['total_pages'] ) {
            wp_redirect( add_query_arg( 'paged', $args['total_pages'] ) );
            exit;
        }

        $this->_pagination_args = $args;
    }


    /**
     * Get an associative array ( option_name => option_title ) with the list
     * of bulk actions available on this table.
     *
     * @access protected
     *
     * @return array
     */
    protected function get_bulk_actions() {
        return $this->bulk_actions;
    }


    /**
     * Set table bulk actions
     *
     * @access public
     *
     * @param array $args
     * @return $this
     */
    public function set_bulk_actions( $args = array() ) {
        $this->bulk_actions = $args;
    }


    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @access protected
     *
     * @return array
     */
    protected function get_columns_data() {
        return $this->columns_data;
    }


    /**
     * Set table columns data
     *
     * @access public
     *
     * @param array $args
     * @return $this
     */
    public function set_columns_data( $args = array() ) {
        if( count( $this->bulk_actions ) ) {
            $args = array_merge( array( 'cb' => array( 'title' => '<input type="checkbox" />', 'width' => '43px' ) ), $args );
        }
        $this->columns_data = $args;

        $columns_args = array();
        $sortable_columns = array();
        foreach ( $args as $key=>$data ) {
            $columns_args[$key] = $data['title'];

            if ( !empty( $data['sortable'] ) ) {
                $sortable_columns[$key] = $data['sortable'];
            }
        }

        $this->set_columns( $columns_args );
        $this->set_sortable_columns( $sortable_columns );
    }


    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @access protected
     *
     * @return array
     */
    protected function get_columns() {
        return $this->columns;
    }


    /**
     * Set table columns
     *
     * @access public
     *
     * @param array $args
     * @return $this
     */
    public function set_columns( $args = array() ) {
        /*if( count( $this->bulk_actions ) ) {
            $args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
        }*/
        $this->columns = $args;
    }


    /**
     * Gets the name of the primary column.
     *
     * @access protected
     *
     * @return string The name of the primary column.
     */
    protected function get_primary_column_name() {
        $columns = $this->get_columns();
        $column = '';

        foreach ( $columns as $col=>$column_name ) {
            if ( 'cb' === $col ) {
                continue;
            }

            $column = $col;
            break;
        }

        return $column;
    }


    /**
     * Get filters line arguments
     *
     * @access protected
     *
     * @return array
     */
    protected function get_filters_line() {
        return $this->filter_line_args;
    }


    /**
     * Set filters line arguments
     *
     * @param array $args
     */
    public function set_filters_line( $args ) {
        $this->filter_line_args = $args;
    }

    /**
     * Set filters block arguments
     *
     * @param array $args
     */
    public function set_filters_block( $args ) {
        $this->filters_block = $args;
    }


    /**
     * Set default sortable column
     *
     * @param array $args
     */
    public function set_default_sortable( $args ) {
        $this->default_sorting_field = $args;
    }


    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     *
     * @access protected
     *
     * @return array
     */
    protected function get_sortable_columns() {
        return $this->sortable_columns;
    }


    /**
     * Set sortable columns
     *
     * @return string
     */
    public function get_order_string() {
        $order_by = $this->_ID_column;
        $order = 'desc';

        if ( !empty( $this->default_sorting_field ) ) {
            $order_by = array_keys( $this->default_sorting_field );
            $order_by = $order_by[0];
            $order = $this->default_sorting_field[$order_by];
        }

        if ( !empty( $this->attributes['sort_by'] ) ) {
            $order_by = $this->attributes['sort_by'];
        }

        if ( !empty( $this->attributes['sort'] ) ) {
            $order = $this->attributes['sort'];
        }

        if ( !empty( $_REQUEST['order_by'] ) && !empty( $this->sortable_columns[$_REQUEST['order_by']] ) ) {
            $order_by = $this->sortable_columns[$_REQUEST['order_by']][0];
        }

        $order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : $order;

        return "$order_by $order";
    }


    /**
     * Set sortable columns
     *
     * @param array $args
     */
    public function set_sortable_columns( $args = array() ) {
        $return_args = array();
        foreach( $args as $k=>$val ) {
            if( is_numeric( $k ) ) {
                $keys = array_keys( $this->default_sorting_field );
                $return_args[ $val ] = array( $val, ( !empty( $keys[0] ) && $val == $keys[0] ) ? $this->default_sorting_field[$keys[0]] : false );
            } else if( is_string( $k ) ) {
                $keys = array_keys( $this->default_sorting_field );
                $return_args[ $k ] = array( $val, ( !empty( $keys[0] ) && $k == $keys[0] ) ? $this->default_sorting_field[$keys[0]] : false );
            } else {
                continue;
            }
        }

        $this->sortable_columns = $return_args;
    }


    /**
     * Return value for bulk action checkbox column
     *
     * @return string
     */
    protected function column_cb() {
        return sprintf( '<input type="checkbox" name="item[]" value="{{>%s}}" />', $this->_ID_column );
    }


    /**
     * Return default value for current column
     *
     * @access protected
     *
     * @param object $item
     * @param string $column_name
     *
     * @return string
     */
    protected function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
    }


    /**
     * Prepares the list of items for displaying.
     * @uses WP_List_Table::set_pagination_args()
     *
     * @access public
     * @abstract
     */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
    }


    /**
     * Make private properties un-settable for backwards compatibility.
     *
     * @access public
     *
     * @param string $name Property to unset.
     */
    public function __unset( $name ) {
        if ( in_array( $name, $this->compat_fields ) ) {
            unset( $this->$name );
        }
    }


    /**
     * Make private properties checkable for backwards compatibility.
     *
     * @access public
     *
     * @param string $name Property to check if set.
     * @return bool Whether the property is set.
     */
    public function __isset( $name ) {
        if ( in_array( $name, $this->compat_fields ) ) {
            return isset( $this->$name );
        } else {
            return false;
        }
    }


    /**
     * Make private properties settable for backwards compatibility.
     *
     * @access public
     *
     * @param string $name  Property to check if set.
     * @param mixed  $value Property value.
     * @return mixed Newly-set property.
     */
    public function __set( $name, $value ) {
        if ( in_array( $name, $this->compat_fields ) ) {
            return $this->$name = $value;
        } else {
            return false;
        }
    }


    /**
     * Make private properties readable for backwards compatibility.
     *
     * @access public
     *
     * @param string $name Property to get.
     * @return mixed Property.
     */
    public function __get( $name ) {
        if ( in_array( $name, $this->compat_fields ) ) {
            return $this->$name;
        } else {
            return false;
        }
    }
    //end class
}