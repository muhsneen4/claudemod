<?php
/**
 * Appyn Pro - child theme bootstrap.
 *
 * Loads the settings registry, the admin panel, the CSS token generator and the
 * front-end renderers. Nothing here hardcodes a design value: every colour,
 * size, font and animation comes from the options panel.
 *
 * @package Appyn_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APX_VERSION', '1.0.0' );
define( 'APX_DIR', get_stylesheet_directory() );
define( 'APX_URI', get_stylesheet_directory_uri() );
define( 'APX_OPTION', 'appyn_pro_settings' );
define( 'APX_PRESETS_OPTION', 'appyn_pro_presets' );

require_once APX_DIR . '/inc/helpers.php';
require_once APX_DIR . '/inc/settings-schema.php';
require_once APX_DIR . '/inc/class-apx-settings.php';
require_once APX_DIR . '/inc/class-apx-css.php';
require_once APX_DIR . '/inc/class-apx-frontend.php';
require_once APX_DIR . '/inc/class-apx-blocks.php';

if ( is_admin() ) {
	require_once APX_DIR . '/inc/class-apx-admin.php';
	new APX_Admin();
}

require_once APX_DIR . '/inc/class-apx-customizer.php';

new APX_Frontend();
new APX_Blocks();
new APX_Customizer();

/**
 * Load the theme text domain.
 */
function apx_load_textdomain() {
	load_child_theme_textdomain( 'appyn-pro', APX_DIR . '/languages' );
}
add_action( 'after_setup_theme', 'apx_load_textdomain' );
