<?php
/**
 * DeVco theme bootstrap.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

define( 'DEVCO_VERSION', '1.1.0' );
define( 'DEVCO_DIR', get_template_directory() );
define( 'DEVCO_URI', get_template_directory_uri() );
define( 'DEVCO_OPTION', 'devco_options' );

/**
 * Core helpers, icons and option access.
 */
require_once DEVCO_DIR . '/inc/helpers.php';

/**
 * Theme setup (supports, menus, image sizes).
 */
require_once DEVCO_DIR . '/inc/setup.php';

/**
 * Options framework (custom theme panel).
 */
require_once DEVCO_DIR . '/inc/options/class-devco-options.php';
require_once DEVCO_DIR . '/inc/options/schema.php';

/**
 * Assets and dynamic CSS.
 */
require_once DEVCO_DIR . '/inc/scripts.php';

/**
 * Header / footer builder, mobile header and bottom navigation.
 */
require_once DEVCO_DIR . '/inc/class-devco-builder.php';
require_once DEVCO_DIR . '/inc/template-tags.php';

/**
 * Creative front page sections (hero, demo products, editorial blocks).
 */
require_once DEVCO_DIR . '/inc/home.php';

/**
 * Global floating UI (cart, contact, socials, login modal) and fitting room.
 */
require_once DEVCO_DIR . '/inc/fitting.php';
require_once DEVCO_DIR . '/inc/floating.php';

/**
 * SMS gateways + unified auth (OTP / password).
 */
require_once DEVCO_DIR . '/inc/sms/class-devco-sms.php';
require_once DEVCO_DIR . '/inc/auth.php';

/**
 * Dashboards, order tracking and admin web app.
 */
require_once DEVCO_DIR . '/inc/dashboard.php';
require_once DEVCO_DIR . '/inc/admin-app.php';

/**
 * Tailor-made orders: form builder, 3D body map, invoices.
 */
require_once DEVCO_DIR . '/inc/tailoring.php';

/**
 * Setup wizard + plugin auto installer + demo importer.
 */
require_once DEVCO_DIR . '/inc/wizard.php';
require_once DEVCO_DIR . '/inc/demo-import.php';
require_once DEVCO_DIR . '/inc/plugin-installer.php';

/**
 * WooCommerce integration.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require_once DEVCO_DIR . '/inc/woo/class-devco-woo.php';
}

/**
 * Elementor integration and custom widgets.
 */
if ( did_action( 'elementor/loaded' ) ) {
	require_once DEVCO_DIR . '/inc/elementor/class-devco-elementor.php';
}

/**
 * Load Elementor integration once Elementor is ready (it may load after the theme).
 */
add_action(
	'elementor/loaded',
	function () {
		if ( ! class_exists( 'DeVco_Elementor' ) ) {
			require_once DEVCO_DIR . '/inc/elementor/class-devco-elementor.php';
			DeVco_Elementor::instance();
		}
	}
);

/**
 * Load WooCommerce integration late enough for Woo classes to exist.
 */
add_action(
	'woocommerce_loaded',
	function () {
		if ( ! class_exists( 'DeVco_Woo' ) ) {
			require_once DEVCO_DIR . '/inc/woo/class-devco-woo.php';
			DeVco_Woo::instance();
		}
	},
	5
);

/**
 * Kick everything off.
 */
DeVco_Options::init();
DeVco_Wizard::instance();
DeVco_Auth::instance();
DeVco_Dashboard::instance();
DeVco_Tailoring::instance();
DeVco_Admin_App::instance();
DeVco_Floating::instance();
DeVco_Builder::instance();
