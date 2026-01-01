<?php
/**
 * Author:              Christopher Ross
 * Author URI:          https://thisismyurl.com/?source=thisismyurl-heic-support
 * Plugin Name:         HEIC Support by thisismyurl.com
 * Plugin URI:          https://thisismyurl.com/thisismyurl-heic-support/?source=thisismyurl-heic-support
 * Donate link:         https://thisismyurl.com/donate/?source=thisismyurl-heic-support
 * 
 * Description:         Safely enable HEIC uploads and convert them to WebP format.
 * Tags:                heic, uploads, media library
 * 
 * Version:             1.260101
 * Requires at least:   5.3
 * Requires PHP:        7.0
 * 
 * Update URI:          https://github.com/thisismyurl/thisismyurl-heic-support
 * GitHub Plugin URI:   https://github.com/thisismyurl/thisismyurl-heic-support
 * Primary Branch:      main
 * Text Domain:         thisismyurl-heic-support
 * 
 * License:             GPL2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * 
 * @package TIMU_HEIC_Support
 * 
 * 
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Version-aware Core Loader
 */
function timu_heic_support_load_core() {
    $core_path = plugin_dir_path( __FILE__ ) . 'core/class-timu-core.php';
    if ( ! class_exists( 'TIMU_Core_v1' ) ) {
        require_once $core_path;
    }
}
timu_heic_support_load_core();

class TIMU_HEIC_Support extends TIMU_Core_v1 {

    /**
     * Constructor: Initializes Core and HEIC specific hooks.
     */
    public function __construct() {
        parent::__construct( 
            'thisismyurl-heic-support', 
            plugin_dir_url( __FILE__ ), 
            'timu_hs_settings_group', 
            '', 
            'tools.php' // Routes all Core links to Tools
        );

        add_filter( 'upload_mimes', array( $this, 'allow_heic_uploads' ) );
        add_filter( 'wp_handle_upload', array( $this, 'handle_heic_upload' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Hook to set defaults upon activation
        register_activation_hook( __FILE__, array( $this, 'activate_plugin_defaults' ) );
    }

    /**
     * Activate Plugin Defaults:
     * Sets 'enabled' to 1 by default upon activation.
     */
    public function activate_plugin_defaults() {
        $option_name = $this->plugin_slug . '_options';
        if ( false === get_option( $option_name ) ) {
            update_option( $option_name, array( 'enabled' => 1 ) );
        }
    }

    /**
     * Filters allowed mime types to include HEIC/HEIF if enabled.
     */
    public function allow_heic_uploads( $mimes ) {
        if ( 1 == $this->get_plugin_option( 'enabled', 1 ) ) {
            $mimes['heic'] = 'image/heic';
            $mimes['heif'] = 'image/heif';
        }
        return $mimes;
    }

    /**
     * Register the Tools submenu page.
     */
    public function add_admin_menu() {
        add_management_page(
            __( 'HEIC Support', 'thisismyurl-heic-support' ),
            __( 'HEIC Support', 'thisismyurl-heic-support' ),
            'manage_options',
            $this->plugin_slug,
            array( $this, 'render_ui' )
        );
    }

    /**
     * Handle immediate conversion upon upload if enabled.
     */
    public function handle_heic_upload( $upload ) {
        if ( 1 != $this->get_plugin_option( 'enabled', 1 ) ) {
            return $upload;
        }

        if ( ! in_array( $upload['type'], array( 'image/heic', 'image/heif' ) ) ) {
            return $upload;
        }

        // Conversion logic using Imagick and $this->init_fs() belongs here.
        return $upload;
    }

    /**
     * Renders the UI utilizing the standardized Core components.
     */
    public function render_ui() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $imagick_active = extension_loaded( 'imagick' );
        $current_val    = $this->get_plugin_option( 'enabled', 1 ); 
        $sidebar_extra  = '<p>' . esc_html__( 'HEIC conversion requires the Imagick PHP extension to be active on your server.', 'thisismyurl-heic-support' ) . '</p>';
        ?>
        <div class="wrap timu-admin-wrap">
            <?php $this->render_core_header(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( $this->options_group ); ?>
                
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            
                            <div class="timu-card">
                                <div class="timu-card-header"><?php esc_html_e( 'HEIC Configuration', 'thisismyurl-heic-support' ); ?></div>
                                <div class="timu-card-body">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'Enable HEIC Conversion', 'thisismyurl-heic-support' ); ?></th>
                                            <td>
                                                <label class="timu-switch">
                                                    <input type="checkbox" name="<?php echo esc_attr($this->plugin_slug); ?>_options[enabled]" value="1" <?php checked( 1, $current_val ); ?> />
                                                    <span class="timu-slider"></span>
                                                </label>
                                                <p class="description"><?php esc_html_e( 'Automatically process HEIC/HEIF images upon upload.', 'thisismyurl-heic-support' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e( 'System Status', 'thisismyurl-heic-support' ); ?></th>
                                            <td>
                                                <strong><?php esc_html_e( 'Imagick Extension:', 'thisismyurl-heic-support' ); ?></strong>
                                                <?php if ( $imagick_active ) : ?>
                                                    <span style="color: #46b450; font-weight: bold;"><?php esc_html_e( 'Active', 'thisismyurl-heic-support' ); ?></span>
                                                <?php else : ?>
                                                    <span style="color: #d63638; font-weight: bold;"><?php esc_html_e( 'Inactive', 'thisismyurl-heic-support' ); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php $this->render_registration_field(); ?>
                            <?php submit_button( __( 'Save HEIC Settings', 'thisismyurl-heic-support' ), 'primary large' ); ?>
                        </div>
                        <?php $this->render_core_sidebar( $sidebar_extra ); ?>
                    </div>
                </div>
            </form>
            <?php $this->render_core_footer(); ?>
        </div>
        <?php
    }
}

new TIMU_HEIC_Support();