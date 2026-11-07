<?php
/**
 * Theme setup.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus and image sizes.
 */
function devco_setup() {
	load_theme_textdomain( 'devco', DEVCO_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'post-formats',
		array( 'aside', 'gallery', 'quote', 'image', 'video' )
	);

	// WooCommerce.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	// Elementor.
	add_theme_support( 'elementor' );
	add_theme_support( 'elementor-pro' );
	add_theme_support( 'elementor-default-kits' );

	register_nav_menus(
		array(
			'primary'    => 'منوی اصلی',
			'categories' => 'منوی دسته‌بندی‌ها',
			'footer'     => 'منوی فوتر',
			'topbar'     => 'منوی نوار بالا',
			'mobile'     => 'منوی موبایل',
		)
	);

	add_image_size( 'devco-card', 600, 750, true );
	add_image_size( 'devco-card-square', 600, 600, true );
	add_image_size( 'devco-banner', 1600, 700, true );
	add_image_size( 'devco-thumb', 160, 160, true );

	$content_width = (int) devco_opt( 'layout.site_width', 1280 );

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = $content_width;
	}
}
add_action( 'after_setup_theme', 'devco_setup' );

/**
 * Register widget areas.
 */
function devco_widgets_init() {
	$areas = array(
		'sidebar-shop'   => 'نوار کناری فروشگاه',
		'sidebar-blog'   => 'نوار کناری بلاگ',
		'footer-1'       => 'فوتر — ستون ۱',
		'footer-2'       => 'فوتر — ستون ۲',
		'footer-3'       => 'فوتر — ستون ۳',
		'footer-4'       => 'فوتر — ستون ۴',
		'header-slot-1'  => 'هدر — جایگاه ۱',
		'header-slot-2'  => 'هدر — جایگاه ۲',
		'header-slot-3'  => 'هدر — جایگاه ۳',
	);

	foreach ( $areas as $id => $name ) {
		register_sidebar(
			array(
				'id'            => $id,
				'name'          => $name,
				'description'   => 'این ناحیه در سازنده هدر/فوتر قالب قابل استفاده است.',
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4 class="widget-title">',
				'after_title'   => '</h4>',
			)
		);
	}
}
add_action( 'widgets_init', 'devco_widgets_init' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function devco_body_classes( $classes ) {
	$classes[] = 'devco';
	$classes[] = 'devco-layout-' . sanitize_html_class( devco_opt( 'layout.container', 'boxed' ) );

	if ( devco_opt_bool( 'header.sticky', true ) ) {
		$classes[] = 'devco-header-sticky';
	}

	if ( devco_opt_bool( 'header.transparent', false ) ) {
		$classes[] = 'devco-header-transparent';
	}

	$classes[] = 'devco-mobile-nav-' . sanitize_html_class( devco_opt( 'mobile.style', 'glass' ) );

	if ( devco_opt_bool( 'mobile.bottom_bar', true ) ) {
		$classes[] = 'devco-has-bottom-bar';
	}

	if ( is_singular() && ! is_front_page() ) {
		$classes[] = 'devco-singular';
	}

	if ( devco_is_woo() ) {
		if ( is_product() ) {
			$classes[] = 'devco-single-product';
			$classes[] = 'devco-sp-' . sanitize_html_class( devco_opt( 'product.layout', 'split' ) );
		}

		if ( is_shop() || is_product_taxonomy() ) {
			$classes[] = 'devco-shop';
			$classes[] = 'devco-shop-cols-' . (int) devco_opt( 'shop.columns', 4 );
		}
	}

	return $classes;
}
add_filter( 'body_class', 'devco_body_classes' );

/**
 * Add a pingback url header for singular pages.
 */
function devco_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'devco_pingback_header' );

/**
 * Excerpt length.
 *
 * @param int $length Length.
 * @return int
 */
function devco_excerpt_length( $length ) {
	return is_admin() ? $length : 24;
}
add_filter( 'excerpt_length', 'devco_excerpt_length', 999 );

/**
 * Excerpt more.
 *
 * @param string $more More string.
 * @return string
 */
function devco_excerpt_more( $more ) {
	return is_admin() ? $more : '…';
}
add_filter( 'excerpt_more', 'devco_excerpt_more' );

/**
 * Remove the default WooCommerce container wrappers — the theme provides its own.
 */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

/**
 * WooCommerce sidebar override.
 *
 * @param string $sidebar Sidebar.
 * @return string
 */
function devco_woo_sidebar( $sidebar ) {
	if ( is_product() ) {
		return '';
	}

	if ( ! devco_opt_bool( 'shop.sidebar', true ) ) {
		return '';
	}

	return 'sidebar-shop';
}
add_filter( 'woocommerce_get_sidebar', 'devco_woo_sidebar' );

/**
 * Theme admin notice when required plugins are missing.
 */
function devco_requirements_notice() {
	if ( ! current_user_can( 'install_plugins' ) || devco_opt_bool( 'wizard.done', false ) ) {
		return;
	}

	$missing = array();

	if ( ! devco_is_woo() ) {
		$missing[] = 'ووکامرس';
	}

	if ( ! devco_is_elementor() ) {
		$missing[] = 'المنتور';
	}

	if ( ! $missing ) {
		return;
	}

	$url = admin_url( 'admin.php?page=devco-wizard' );

	printf(
		'<div class="notice notice-warning"><p><strong>قالب دیوکو:</strong> برای فعال‌سازی کامل امکانات، افزونه‌های %1$s را نصب کنید. <a href="%2$s">اجرای ویزارد نصب</a></p></div>',
		esc_html( implode( '، ', $missing ) ),
		esc_url( $url )
	);
}
add_action( 'admin_notices', 'devco_requirements_notice' );
