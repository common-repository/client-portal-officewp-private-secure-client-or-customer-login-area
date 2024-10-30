<?php
namespace wpo\core;
// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Dashboard {

    public $widgets = array();

    /**
     * PHP 5 constructor
     **/
    function __construct() {
        $roles = WO()->get_settings( 'roles' );
        $start_order = array();
        if ( !empty( $roles ) ) {
            foreach ( $roles as $role_key=>$role ) {

                //default colors for default first install roles
                $color = 'violet-dark';
                if ( 'wpoffice_client' == $role_key ) {
                    $color = 'violet-dark';
                } elseif ( 'wpoffice_manager' == $role_key ) {
                    $color = 'aqua-dark';
                }

                $this->widgets["{$role_key}_dashboard_widget"] = array(
                    'collapsed'     => false,
                    'color'         => $color,
                    'role'          => $role_key,
                    'action'        => 'members_dashboard_widget',
                );

                $start_order[] = "{$role_key}_dashboard_widget";
            }
        }

        $this->widgets['office_pages_dashboard_widget'] = array(
            'collapsed'     => false,
            'color'         => 'sands-light',
        );

        $this->widgets = apply_filters( 'wpoffice_dashboard_widgets_list', $this->widgets );

        //default widgets sorting HARDCODE resolve only
        $widgets_order = get_user_meta( get_current_user_id(), 'wpo_widgets_order', true );
        if ( empty( $widgets_order ) ) {
            $start_order = array_merge( $start_order, array(
                'files_dashboard_widget',
                'messages_dashboard_widget',
                'circles_dashboard_widget',
                'office_pages_dashboard_widget',
            ) );
            update_user_meta( get_current_user_id(), 'wpo_widgets_order', $start_order );
        }
    }


    function collapse_widget() {
        if( isset( $_POST['collapse'] ) && !empty( $_POST['widget'] ) ) {
            $widget = get_user_meta( get_current_user_id(), 'wpo_' . $_POST['widget'], true );

            $widget['collapsed'] = ( isset( $_POST['collapse'] ) && 'true' == $_POST['collapse'] ) ? true : false;

            update_user_meta( get_current_user_id(), 'wpo_' . $_POST['widget'], $widget );

            die('ok');
        }
    }


    function update_widgets_color() {
        if( !empty( $_POST['color'] ) && !empty( $_POST['widget'] ) ) {
            $widget = get_user_meta( get_current_user_id(), 'wpo_' . $_POST['widget'], true );

            $widget['color'] = $_POST['color'];

            update_user_meta( get_current_user_id(), 'wpo_' . $_POST['widget'], $widget );

            die('ok');
        }
    }


    function update_widgets_order() {
        if( isset( $_POST['order'] ) && !empty( $_POST['order'] ) ) {
            $_POST['order'] = explode( ',', $_POST['order'] );

            update_user_meta( get_current_user_id(), 'wpo_widgets_order', $_POST['order'] );

            die('ok');
        }
    }


    function office_pages_dashboard_widget() {
        global $wpdb;

        $office_pages = $wpdb->get_results(
            "SELECT p.ID AS id,
                    p.post_title AS title,
                    p.post_status AS status,
                    p.post_date AS date
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'office_page' AND p.post_status='publish'
            ORDER BY p.post_date DESC
            LIMIT 0, 10",
        ARRAY_A );

        $widget_options = get_user_meta( get_current_user_id(), 'wpo_' . $_POST['widget'], true );
        $widget_options['color'] = ( isset( $widget_options['color'] ) && !empty( $widget_options['color'] ) ) ? $widget_options['color'] : 'sands-light';

        ob_start(); ?>

        <!--  Office Pages Widget  -->
        <div class="tiles <?php echo $widget_options['color'] ?>">
            <div class="tile_body">
                <div class="widget_header">
                    <div class="widget_control"><?php echo $this->widget_controls( $_POST['widget'] ) ?></div>
                    <div class="tile_title"><?php _e( 'Last 10 Office Pages', WP_OFFICE_TEXT_DOMAIN ) ?></div>
                </div>
                <div class="widget_content scrollbox">
                    <?php if( !empty( $office_pages ) ) {
                        foreach( $office_pages as $page ) { ?>
                            <div class="scroll_item wpo_office_page">
                                <div class="left_wrapper">
                                    <div class="item_header"><?php echo $page['title'] ?></div>
                                    <div class="action_links">
                                        <a href="<?php echo get_permalink( $page['id'] ) ?>" target="_blank"><?php _e( 'View', WP_OFFICE_TEXT_DOMAIN ) ?></a> |
                                        <a href="post.php?post=<?php echo $page['id'] ?>&action=edit" target="blank_" title="<?php esc_attr( __( 'Edit this item', WP_OFFICE_TEXT_DOMAIN ) ) ?>"><?php _e( 'Edit', WP_OFFICE_TEXT_DOMAIN ) ?></a>
                                    </div>
                                </div>
                                <div class="right_wrapper"><?php echo WO()->date( strtotime( $page['date'] ) ) ?></div>
                                <div class="clearfix"></div>
                            </div>
                        <?php }
                    } else { ?>
                        <div class="empty_content"><?php _e( 'You don\'t have Office Pages', WP_OFFICE_TEXT_DOMAIN ) ?></div>
                    <?php } ?>
                </div>
                <div class="widget_footer">
                    <div class="widget_control"><?php echo $this->widget_controls( $_POST['widget'], 'bottom' ) ?></div>
                </div>
            </div>
        </div>

        <?php $widget = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }
        return $widget;
    }


    function members_dashboard_widget() {
        global $wpdb;

        $current_role = $_POST['role'];
        $roles = WO()->get_settings( 'roles' );

        $include = '';
        if ( !current_user_can( 'administrator' ) && WO()->current_member_main_manage_cap( $current_role ) == 'assigned' ) {
            $assigned_users = WO()->get_assign_data_by_object( 'user', get_current_user_id(), 'member' );
            $include = " AND u.ID IN('" . implode( "','", $assigned_users ) . "')";
        }

        $excluded_members = WO()->members()->get_excluded_members( false, $current_role );
        $exclude = '';
        if ( count( $excluded_members ) ) {
            $exclude .= ' AND u.ID NOT IN( "' . implode( '","', $excluded_members ) . '" )';
        }

        $active_members = $wpdb->get_var(
            "SELECT COUNT( DISTINCT u.ID )
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities'
                AND um.meta_value LIKE '%:\"{$current_role}\";%'
                $include
                $exclude"
        );


        $archived_members = 0;
        $in_archive_members = WO()->members()->get_excluded_members( 'archived', $current_role );
        if ( count( $in_archive_members ) ) {
            $include2 = ' AND u.ID IN( "' . implode( '","', $in_archive_members ) . '" )';

            $archived_members = $wpdb->get_var(
                "SELECT COUNT( DISTINCT u.ID )
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                      um.meta_value LIKE '%:\"{$current_role}\";%'
                      $include
                      $include2"
            );
        }

        $start_current_month = strtotime( date( "Y-m", time() ) );
        $start_previous_month = strtotime( date( "Y-m", $start_current_month - 1 ) );

        $previous_month_registrations = $wpdb->get_var(
            "SELECT COUNT(u.ID)
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                  um.meta_value LIKE '%:\"{$current_role}\";%' AND
                  u.user_registered < '" . date( "Y-m-d H:i:s", $start_current_month ) . "' AND
                  u.user_registered > '" . date( "Y-m-d H:i:s", $start_previous_month ) . "'"
        );

        $current_month_registrations = $wpdb->get_var(
            "SELECT COUNT(u.ID)
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND
                  um.meta_value LIKE '%:\"{$current_role}\";%' AND
                  user_registered >= '" . date( "Y-m-d H:i:s", $start_current_month ) . "'"
        );

        $difference = 0;
        if( $previous_month_registrations != 0 ) {
            $difference = round( ( $current_month_registrations * 100 ) / $previous_month_registrations - 100, 2 );
        }

        $widget_options = get_user_meta( get_current_user_id(), "wpo_{$current_role}_dashboard_widget", true );
        $widget_options['color'] = ( isset( $widget_options['color'] ) && !empty( $widget_options['color'] ) ) ? $widget_options['color'] : 'sands-light';

        $total_members = $wpdb->get_var(
            "SELECT COUNT( DISTINCT u.ID )
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND 
                  um.meta_value LIKE '%:\"{$current_role}\";%'"
        );;

        ob_start(); ?>

        <!--  Client Widget  -->
        <div class="tiles <?php echo $widget_options['color'] ?>">
            <div class="tile_body">
                <div class="widget_header">
                    <div class="widget_control"><?php echo $this->widget_controls( $_POST['widget'] ) ?></div>
                    <div class="tile_title"><?php echo $roles[$current_role]['title'] . ' (' . $total_members . ')' ?></div>
                </div>
                <div class="widget_content statistic">
                    <div class="member_widget_item">
                        <div class="item_count"><?php WO()->numeric_circle_spinner( 80, $active_members, $total_members, 1, 3 ); ?></div>
                        <div class="item_title"><?php _e( 'Active', WP_OFFICE_TEXT_DOMAIN ) ?></div>
                    </div>
                    <?php echo apply_filters( 'wpoffice_members_dashboard_content', '', $total_members, $current_role ); ?>
                    <div class="member_widget_item">
                        <div class="item_count"><?php WO()->numeric_circle_spinner( 80, $archived_members, $total_members, 1, 3 ); ?></div>
                        <div class="item_title"><?php _e( 'In Archive', WP_OFFICE_TEXT_DOMAIN ) ?></div>
                    </div>
                    <div class="description">
                        <?php if( $previous_month_registrations == 0 ) { ?>
                            <span class="higher">-&nbsp;</span>
                        <?php } else {
                            if( $current_month_registrations > 0 ) { ?>
                                <span class="<?php echo $difference >= 0 ? 'higher' : 'less' ?>"><?php echo ( $difference >= 0 ? '+' . $difference : $difference ) . '%' ?>&nbsp;</span>
                            <?php }
                        } ?>
                        <span>
                            <?php echo $current_month_registrations ?><?php _e( ' registrations in this month', WP_OFFICE_TEXT_DOMAIN ) ?>
                        </span>
                    </div>
                </div>
                <div class="widget_footer">
                    <?php echo $this->widget_controls( $_POST['widget'], 'bottom' ) ?>
                </div>
            </div>
        </div>

        <?php $widget = ob_get_contents();
        if( ob_get_length() ) {
            ob_end_clean();
        }
        return $widget;
    }


    function widget_controls( $widget_id, $position = 'top' ) {

        $widget_options = get_user_meta( get_current_user_id(), 'wpo_' . $widget_id, true );

        if ( 'top' == $position ) {
            $color = ( isset( $widget_options['color'] ) && !empty( $widget_options['color'] ) ) ? $widget_options['color'] : 'white';
            ob_start(); ?>

            <div class="widget_custom_palette">
                <a href="javascript:void(0);" class="control_button widget_colorize" data-value="blue" title="<?php _e( 'Select Widget\'s Color', WP_OFFICE_TEXT_DOMAIN ) ?>"></a>
                <div class="colorize_palette">
                    <table>
                        <tr>
                            <td><div style="background: #f5f6d4;" class="widget_color <?php echo( 'sands-light' == $color ) ? 'selected' : '' ?>" data-value="sands-light"></div></td>
                            <td><div style="background: #f6e8b1;" class="widget_color <?php echo( 'sands-middle' == $color ) ? 'selected' : '' ?>" data-value="sands-middle"></div></td>
                            <td><div style="background: #a7a37e;" class="widget_color <?php echo( 'sands-dark' == $color ) ? 'selected' : '' ?>" data-value="sands-dark"></div></td>
                        </tr>
                        <tr>
                            <td><div style="background: #faeeff;" class="widget_color <?php echo( 'violet-light' == $color ) ? 'selected' : '' ?>" data-value="violet-light"></div></td>
                            <td><div style="background: #f1baf3;" class="widget_color <?php echo( 'violet-middle' == $color ) ? 'selected' : '' ?>" data-value="violet-middle"></div></td>
                            <td><div style="background: #5d4970;" class="widget_color <?php echo( 'violet-dark' == $color ) ? 'selected' : '' ?>" data-value="violet-dark"></div></td>
                        </tr>
                        <tr>
                            <td><div style="background: #beeb9f;" class="widget_color <?php echo( 'aqua-light' == $color ) ? 'selected' : '' ?>" data-value="aqua-light"></div></td>
                            <td><div style="background: #79bd8f;" class="widget_color <?php echo( 'aqua-middle' == $color ) ? 'selected' : '' ?>" data-value="aqua-middle"></div></td>
                            <td><div style="background: #00a388;" class="widget_color <?php echo( 'aqua-dark' == $color ) ? 'selected' : '' ?>" data-value="aqua-dark"></div></td>
                        </tr>
                    </table>
                </div>
            </div>
            <a href="javascript:void(0);" class="control_button widget_reload" title="<?php _e( 'Reload This Widget', WP_OFFICE_TEXT_DOMAIN ) ?>"><?php echo WO()->get_ajax_loader( 18 ) ?></a>
            <a href="javascript:void(0);" class="control_button widget_toggle" title="<?php _e( 'Toogle Collapsing This Widget', WP_OFFICE_TEXT_DOMAIN ) ?>">
                <span class="imageicon"></span>
            </a>

            <?php $html = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }
        } else {
            $view_all_link = '';
            switch( $widget_id ) {
                case 'members_dashboard_widget':
                    $view_all_link = add_query_arg( array( 'page'=>'wp-office-members' ), get_admin_url() . 'admin.php' ) . '#filters_tab[1]=' . $_POST['role'];
                    break;
                case 'office_pages_dashboard_widget':
                    $view_all_link = add_query_arg( array( 'page'=>'wp-office-contents' ), get_admin_url() . 'admin.php' );
                    break;
                default:
                    $view_all_link = apply_filters( "wpoffice_{$widget_id}_all_link", $view_all_link );
                    break;
            }

            ob_start(); ?>

            <div class="widget_all">
                <a href="<?php echo $view_all_link ?>" target="blank_" class="control_button" title="<?php _e( 'View All', WP_OFFICE_TEXT_DOMAIN ) ?>">
                    &#133;
                </a>
            </div>
            <div class="widget_reload_wrapper"></div>
            <?php $html = ob_get_contents();
            if( ob_get_length() ) {
                ob_end_clean();
            }
        }

        return $html;
    }


    function load_widget() {
        if ( !empty( $_POST['widget'] ) ) {
            $widget = $_POST['widget'];
            if ( method_exists( $this, $widget ) ) {
                echo $this->$widget();
            } else {
                echo apply_filters( 'wpoffice_' . $_POST['widget'] . '_content', '' );
            }
            exit;
        }

        return '';
    }

    //end class
}