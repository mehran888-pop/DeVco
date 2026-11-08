<?php
/**
 * Front-end assets and dynamic CSS.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register and enqueue front-end assets.
 */
function devco_enqueue_assets() {
	$ver = DEVCO_VERSION;

	// Base stylesheet.
	wp_enqueue_style( 'devco-main', devco_asset( 'css/main.css' ), array(), $ver );
	wp_enqueue_style( 'devco-auth', devco_asset( 'css/auth.css' ), array( 'devco-main' ), $ver );
	wp_enqueue_style( 'devco-floating', devco_asset( 'css/floating.css' ), array( 'devco-main' ), $ver );
	wp_enqueue_style( 'devco-widgets', devco_asset( 'css/widgets.css' ), array( 'devco-main' ), $ver );

	if ( devco_opt_bool( 'fitting.enabled', true ) ) {
		wp_enqueue_style( 'devco-fitting', devco_asset( 'css/fitting.css' ), array( 'devco-main' ), $ver );
	}

	if ( is_page_template( array( 'templates/dashboard.php', 'templates/tailoring.php', 'templates/track.php' ) ) ) {
		wp_enqueue_style( 'devco-dashboard', devco_asset( 'css/dashboard.css' ), array( 'devco-main' ), $ver );
		wp_enqueue_style( 'devco-tailoring', devco_asset( 'css/tailoring.css' ), array( 'devco-main' ), $ver );
	}

	if ( devco_is_woo() ) {
		wp_enqueue_style( 'devco-woo', devco_asset( 'css/woo.css' ), array( 'devco-main' ), $ver );
	}

	// Dynamic CSS variables + option driven styles.
	$dynamic = devco_dynamic_css();

	if ( $dynamic ) {
		wp_add_inline_style( 'devco-main', $dynamic );
	}

	// Core script.
	wp_register_script( 'devco-main', devco_asset( 'js/main.js' ), array( 'jquery' ), $ver, true );
	wp_register_script( 'devco-floating', devco_asset( 'js/floating.js' ), array( 'devco-main' ), $ver, true );
	wp_register_script( 'devco-fitting', devco_asset( 'js/fitting.js' ), array( 'devco-main' ), $ver, true );
	wp_register_script( 'devco-auth', devco_asset( 'js/auth.js' ), array( 'devco-main' ), $ver, true );
	wp_register_script( 'devco-dashboard', devco_asset( 'js/dashboard.js' ), array( 'devco-main' ), $ver, true );
	wp_register_script( 'devco-tailoring', devco_asset( 'js/tailoring.js' ), array( 'devco-main' ), $ver, true );

	// Localize before enqueue so the data is printed with the main script.
	wp_localize_script( 'devco-main', 'DeVcoData', devco_js_data() );

	wp_enqueue_script( 'devco-main' );
	wp_enqueue_script( 'devco-auth' );
	wp_enqueue_script( 'devco-floating' );

	if ( devco_opt_bool( 'fitting.enabled', true ) ) {
		wp_enqueue_script( 'devco-fitting' );
	}

	if ( is_page_template( array( 'templates/dashboard.php', 'templates/tailoring.php', 'templates/track.php' ) ) ) {
		wp_enqueue_script( 'devco-dashboard' );
		wp_enqueue_script( 'devco-tailoring' );
	}

	if ( devco_opt_bool( 'auth.otp', true ) || devco_opt_bool( 'auth.password', true ) ) {
		wp_enqueue_script( 'devco-auth' );
	}

	if ( devco_is_woo() ) {
		wp_localize_script(
			'devco-main',
			'DeVcoWoo',
			array(
				'ajaxUrl'   => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( '%%endpoint%%' ) : '',
				'cartUrl'   => wc_get_cart_url(),
				'isCart'    => is_cart(),
				'isCheckout' => is_checkout(),
				'cartCount' => devco_cart_count(),
			)
		);
	}

	if ( devco_opt_bool( 'colors.dark_mode', true ) ) {
		wp_add_inline_script( 'devco-main', 'document.documentElement.classList.toggle("devco-dark", localStorage.getItem("devco_theme")==="dark");' );
	}

	// Comment reply.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'devco_enqueue_assets' );

/**
 * Google Fonts / font files for the chosen families.
 */
function devco_font_styles() {
	$families = array();

	foreach ( array( 'fonts.body', 'fonts.heading' ) as $key ) {
		$families[] = devco_opt( $key, 'vazirmatn' );
	}

	if ( in_array( 'vazirmatn', $families, true ) && devco_opt_bool( 'fonts.cdn_vazirmatn', false ) ) {
		wp_enqueue_style(
			'devco-vazirmatn',
			'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@33.003/Vazirmatn-font-face.css',
			array(),
			null
		);
	}
}
add_action( 'wp_enqueue_scripts', 'devco_font_styles', 5 );

/**
 * Build the option-driven CSS.
 *
 * @return string
 */
function devco_dynamic_css() {
	$primary   = devco_color( devco_opt( 'colors.primary' ), '#111827' );
	$accent    = devco_color( devco_opt( 'colors.accent' ), '#c8a96a' );
	$accent_dk = devco_color( devco_opt( 'colors.accent_dark' ), '#a8894c' );
	$text      = devco_color( devco_opt( 'colors.text' ), '#1f2937' );
	$muted     = devco_color( devco_opt( 'colors.muted' ), '#6b7280' );
	$border    = devco_color( devco_opt( 'colors.border' ), '#e8e6e1' );
	$surface   = devco_color( devco_opt( 'colors.surface' ), '#ffffff' );
	$bg        = devco_color( devco_opt( 'colors.background' ), '#faf9f7' );
	$header_bg = devco_color( devco_opt( 'colors.header_bg' ), '#ffffff' );
	$footer_bg = devco_color( devco_opt( 'colors.footer_bg' ), '#111827' );
	$success   = devco_color( devco_opt( 'colors.success' ), '#10b981' );
	$danger    = devco_color( devco_opt( 'colors.danger' ), '#e11d48' );
	$radius    = (int) devco_opt( 'layout.radius', 16 );
	$width     = (int) devco_opt( 'layout.site_width', 1280 );
	$sidebar   = (int) devco_opt( 'layout.sidebar_width', 280 );
	$font_size = (float) devco_opt( 'fonts.body_size', 15 );
	$h_weight  = (int) devco_opt( 'fonts.heading_weight', 700 );
	$font_body = devco_font_stack( devco_opt( 'fonts.body', 'vazirmatn' ) );
	$font_head = devco_font_stack( devco_opt( 'fonts.heading', 'vazirmatn' ) );
	$h_height  = (int) devco_opt( 'header.height', 82 );
	$m_height  = (int) devco_opt( 'mobile.header_height', 62 );
	$fit_accent = devco_color( devco_opt( 'fitting.accent' ), $accent );
	$fit_bg     = devco_color( devco_opt( 'fitting.box_bg' ), '#0f172a' );
	$fab_color  = devco_color( devco_opt( 'floating.actions_color' ), $accent );

	$css = ':root{';

	$vars = array(
		'--devco-primary'      => $primary,
		'--devco-accent'       => $accent,
		'--devco-accent-dark'  => $accent_dk,
		'--devco-text'         => $text,
		'--devco-muted'        => $muted,
		'--devco-border'       => $border,
		'--devco-surface'      => $surface,
		'--devco-bg'           => $bg,
		'--devco-header-bg'    => $header_bg,
		'--devco-footer-bg'    => $footer_bg,
		'--devco-success'      => $success,
		'--devco-danger'       => $danger,
		'--devco-radius'       => $radius . 'px',
		'--devco-radius-sm'    => max( 4, $radius / 2 ) . 'px',
		'--devco-width'        => $width . 'px',
		'--devco-sidebar'      => $sidebar . 'px',
		'--devco-font'         => $font_body,
		'--devco-font-heading' => $font_head,
		'--devco-fs'           => $font_size . 'px',
		'--devco-h-weight'     => (string) $h_weight,
		'--devco-header-h'     => $h_height . 'px',
		'--devco-mobile-h'     => $m_height . 'px',
		'--devco-fit-accent'   => $fit_accent,
		'--devco-fit-bg'       => $fit_bg,
		'--devco-fab'          => $fab_color,
	);

	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}

	$css .= '}';

	// Font smoothing + base typography.
	$css .= 'html{font-size:' . $font_size . 'px;}';
	$css .= 'body{font-family:var(--devco-font);color:var(--devco-text);background:var(--devco-bg);}';

	// Button style variants.
	$button_style = devco_opt( 'colors.button_style', 'solid' );

	if ( 'outline' === $button_style ) {
		$css .= '.devco-btn--primary,.woocommerce a.button,.woocommerce button.button,.woocommerce input.button{background:transparent;color:var(--devco-primary);border:1.5px solid var(--devco-primary);}';
	} elseif ( 'soft' === $button_style ) {
		$css .= '.devco-btn--primary,.woocommerce a.button,.woocommerce button.button,.woocommerce input.button{background:color-mix(in srgb, var(--devco-primary) 10%, transparent);color:var(--devco-primary);}';
	} elseif ( 'pill' === $button_style ) {
		$css .= '.devco-btn,.woocommerce a.button,.woocommerce button.button,.woocommerce input.button{border-radius:999px;}';
	}

	// Card style variants.
	$card_style = devco_opt( 'colors.card_style', 'shadow' );

	$card_css = array(
		'flat'     => 'box-shadow:none;border:1px solid var(--devco-border);',
		'shadow'   => 'box-shadow:0 10px 30px -18px rgba(17,24,39,.35);border:1px solid transparent;',
		'glass'    => 'background:color-mix(in srgb, var(--devco-surface) 72%, transparent);backdrop-filter:blur(14px);border:1px solid color-mix(in srgb, var(--devco-border) 60%, transparent);',
		'bordered' => 'border:1.5px solid var(--devco-border);box-shadow:none;',
	);

	$css .= '.devco-card,li.product .devco-product-card__inner,.devco-panel{' . ( isset( $card_css[ $card_style ] ) ? $card_css[ $card_style ] : '' ) . '}';

	// Custom font face.
	$custom_url = devco_opt( 'fonts.custom_url', '' );
	$custom_fam = devco_opt( 'fonts.custom_family', '' );

	if ( $custom_url && $custom_fam ) {
		$css .= '@font-face{font-family:"' . esc_attr( $custom_fam ) . '";src:url("' . esc_url_raw( $custom_url ) . '") format("woff2");font-display:swap;}';
	}

	// Floating cart position.
	$cart_pos = devco_opt( 'floating.cart_position', 'bottom-left' );
	$css     .= '.devco-floating-cart{--devco-fc-side:' . ( 'bottom-right' === $cart_pos ? 'right' : 'left' ) . ';}';

	// Header transparency helper.
	if ( devco_opt_bool( 'header.shadow', true ) ) {
		$css .= '.devco-header{box-shadow:0 8px 30px -24px rgba(15,23,42,.5);}';
	}

	// Bottom bar offset for content.
	if ( devco_opt_bool( 'mobile.bottom_bar', true ) ) {
		$css .= '@media (max-width:991px){body.devco-has-bottom-bar{padding-bottom:96px;}}';
	}

	// Custom CSS from options.
	$custom_css = devco_opt( 'advanced.css', '' );

	if ( $custom_css ) {
		$css .= $custom_css;
	}

	return apply_filters( 'devco_dynamic_css', $css );
}

/**
 * Print head/body custom code.
 */
function devco_print_head_code() {
	$head = devco_opt( 'advanced.head', '' );

	if ( $head ) {
		echo $head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- explicit admin option.
	}
}
add_action( 'wp_head', 'devco_print_head_code', 99 );

/**
 * Footer custom code.
 */
function devco_print_body_code() {
	$body = devco_opt( 'advanced.body', '' );

	if ( $body ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- explicit admin option.
	}

	$js = devco_opt( 'advanced.js', '' );

	if ( $js ) {
		echo '<script id="devco-custom-js">' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- explicit admin option.
	}
}
add_action( 'wp_footer', 'devco_print_body_code', 99 );

/**
 * Site-wide optimizations.
 */
function devco_optimizations() {
	if ( devco_opt_bool( 'advanced.disable_emojis', true ) ) {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	if ( devco_opt_bool( 'advanced.remove_wp_version', true ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}
}
add_action( 'init', 'devco_optimizations' );

/**
 * WooCommerce asset tweaks: keep select2 on checkout, load our swatches.
 */
function devco_woo_asset_tweaks() {
	if ( ! devco_is_woo() ) {
		return;
	}

	wp_register_style( 'devco-woo-extra', false, array(), DEVCO_VERSION );
	wp_enqueue_style( 'devco-woo-extra' );

	if ( devco_opt_bool( 'product.variation_swatches', true ) ) {
		wp_enqueue_style( 'devco-swatches', devco_asset( 'css/swatches.css' ), array( 'devco-main' ), DEVCO_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'devco_woo_asset_tweaks', 20 );

/**
 * Cart item count helper.
 *
 * @return int
 */
function devco_cart_count() {
	if ( ! devco_is_woo() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}

	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * Cart subtotal formatted.
 *
 * @return string
 */
function devco_cart_subtotal() {
	if ( ! devco_is_woo() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}

	return WC()->cart->get_cart_subtotal();
}
