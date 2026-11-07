<?php
/**
 * Automatic plugin installation for required plugins.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Plugin_Installer
 */
class DeVco_Plugin_Installer {

	/**
	 * Required plugins.
	 *
	 * @return array
	 */
	public static function plugins() {
		return array(
			'woocommerce' => array(
				'name'     => 'WooCommerce',
				'slug'     => 'woocommerce',
				'file'     => 'woocommerce/woocommerce.php',
				'required' => true,
				'desc'     => 'فروشگاه، سبد خرید، پرداخت و مدیریت محصولات.',
			),
			'elementor'   => array(
				'name'     => 'Elementor',
				'slug'     => 'elementor',
				'file'     => 'elementor/elementor.php',
				'required' => true,
				'desc'     => 'صفحه‌ساز و المان‌های اختصاصی دیوکو.',
			),
		);
	}

	/**
	 * Status of a plugin.
	 *
	 * @param string $slug Slug.
	 * @return string not-installed|installed|active
	 */
	public static function status( $slug ) {
		$plugins = self::plugins();

		if ( ! isset( $plugins[ $slug ] ) ) {
			return 'unknown';
		}

		$plugin = $plugins[ $slug ];

		if ( is_plugin_active( $plugin['file'] ) ) {
			return 'active';
		}

		$installed = get_plugins();

		return isset( $installed[ $plugin['file'] ] ) ? 'installed' : 'not-installed';
	}

	/**
	 * Install and activate a plugin from WordPress.org.
	 *
	 * @param string $slug Slug.
	 * @return true|WP_Error
	 */
	public static function install( $slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'devco_cap', 'دسترسی نصب افزونه را ندارید.' );
		}

		$plugins = self::plugins();

		if ( ! isset( $plugins[ $slug ] ) ) {
			return new WP_Error( 'devco_slug', 'افزونه ناشناخته است.' );
		}

		$plugin = $plugins[ $slug ];
		$status = self::status( $slug );

		if ( 'active' === $status ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( 'not-installed' === $status ) {
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin['slug'],
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			$skin     = new Automatic_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( is_wp_error( $skin->result ) ) {
				return $skin->result;
			}
		}

		$activate = activate_plugin( $plugin['file'] );

		if ( is_wp_error( $activate ) ) {
			return $activate;
		}

		return true;
	}
}
