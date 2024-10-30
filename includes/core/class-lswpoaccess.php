<?php
namespace wpo\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class lswpoaccess extends \Office_Licensor {

        /**
         * @var null
         */
        protected static $_instance = null;

        public static function instance( $_api_url, $_plugin_file, $_api_data = array() ) {
            if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $_api_url, $_plugin_file, $_api_data );
            }

            return self::$_instance;
        }

        public function __construct( $_api_url, $_plugin_file, $_api_data = array() ) {

            $this->hide_menu_after_activate = true;

            parent::__construct( $_api_url, $_plugin_file, $_api_data );

            do_action( 'lget_activation_init_' . $this->prefix );
        }


        function successful_activation_redirect() {
            return admin_url( 'admin.php?page=wp-office' );
        }


        /**
         * Render HTML for License options page
         *
         * @param array $args data for render form.
         *
         * @return void
         */
        function render_license_form( $args = array() ) {

            ?>

            <style type="text/css">

                #wpo_activation_block {
                    background: linear-gradient(45deg, rgba( 255, 255, 255, 0.2 ), rgba( 255, 255, 255, 0.4 ) );
                    /*border: 2px solid rgba(221,235,255, 0.5 );*/
                    /*border: 2px solid rgba(188,215,255, 0.5 );*/
                    border: 2px solid rgba(207,226,255, 0.5 );
                    padding: 15px 10px 0px 10px;
                    box-shadow: 0 0 4px rgba(0,0,0,0.3);
                    border-radius:0px;
                    width: 450px;
                    margin: 50px auto;
                    /*background-color: #d1e4ff;*/
                    background-color: #B5D2FF;
                    position: relative;
                    overflow: hidden;
                }

                #wpo_activation_block .wpo_clouds {
                    background: url( '<?php echo WO()->plugin_url ?>assets/images/header_clouds.png' ) no-repeat transparent;
                    width: 450px;
                    height: 60px;
                    position: absolute;
                    right: 20px;
                    top: -7px;
                }

                .error {
                    display: none;
                }

                .error.license_error {
                    display: block;
                }

                .updated {
                    display: none;
                }

                #wpo_activation_block .settings-error {
                    display: block;
                    background-color: rgba(255, 255, 255, 0.3);
                    margin-top: 20px;
                }

                #wpo_activation_block .settings-error strong {
                    font-weight: normal;
                }

                #wpo_activation_block span.dashicons-yes,
                #wpo_activation_block span.dashicons-no {
                    display: none;
                }

                #wpo_activation_block .submit {
                    text-align: center;
                }

                /*#wpo_activation_block h2 {*/
                        /*display: none;*/
                /*}*/

                #wpo_activation_block h2.lget_atcivation_title {
                    display: block;
                    text-align: center;
                    font-size: 20px;
                    margin-bottom: 10px;
                    font-weight: normal;
                }

                #wpo_activation_block .form-table input {
                    font-size: 13px;
                    line-height: 18px;
                    text-align: center;
                    width: 100%;
                }


            </style>



            <div class='wrap'>
                <div id="wpo_activation_block">

                    <?php do_action( 'lget_activation_logo_' . $this->prefix ) ?>

                    <div class="wpo_clouds"></div>

                    <h2 class="lget_atcivation_title"><?php _e( 'License Activation' ) ?></h2>

                    <?php if ( ! empty( $args['message'] ) ) { ?>
                        <div class="error license_error">
                            <p><?php echo $args['message'] ?></p>
                        </div>
                    <?php }

                    if ( ! empty( $args['license'] ) && ! empty( $args['status'] ) && $args['status'] == 'valid' ) { ?>
                        <div class="updated">
                            <p><?php _e( 'License is Active' ) ?></p>
                        </div>
                    <?php } ?>

                    <form method="post" action="">
                        <input type="hidden" name="page" value="<?php echo $this->menu_slug ?>">
                        <?php wp_nonce_field( "{$this->prefix}_license_activation", md5( "{$this->prefix}_license_activation" . $this->menu_slug . get_current_user() ) ); ?>

                        <table class="form-table">
                            <tbody>
                            <tr valign="top">
                                <td>
                                    <input id="license_key" placeholder="<?php _e( 'License Key' ); ?>" name="<?php echo $this->prefix ?>_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $args['license'] ); ?>" />
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <?php submit_button( __( 'Activate License' ) ); ?>

                    </form>
                </div>
            </div>

            <?php
        }

        /**
         * Register submenu specific to this product.
         */
        function add_license_menu() {
            add_menu_page( WO()->plugin['title'], WO()->plugin['title'], 'manage_options', 'wp-office-act', array( $this, 'license_page'), WO()->plugin['icon_url'], '2,000000000006' );
        }

}