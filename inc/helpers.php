<?php
/**
 * Helper functions and the inline icon library.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a theme option using dot notation.
 *
 * @param string $key     Option key, e.g. "shop.columns".
 * @param mixed  $default Fallback value.
 * @return mixed
 */
function devco_opt( $key, $default = null ) {
	static $options = null;

	if ( null === $options ) {
		$stored  = get_option( DEVCO_OPTION, array() );
		$options = is_array( $stored ) ? $stored : array();
	}

	if ( array_key_exists( $key, $options ) ) {
		return $options[ $key ];
	}

	$value = $options;
	foreach ( explode( '.', $key ) as $segment ) {
		if ( is_array( $value ) && array_key_exists( $segment, $value ) ) {
			$value = $value[ $segment ];
		} else {
			$fallback = class_exists( 'DeVco_Options' ) ? DeVco_Options::default_for( $key ) : null;
			return null !== $fallback ? $fallback : $default;
		}
	}

	if ( null === $value ) {
		$fallback = class_exists( 'DeVco_Options' ) ? DeVco_Options::default_for( $key ) : null;
		return null !== $fallback ? $fallback : $default;
	}

	return $value;
}

/**
 * Boolean option shortcut.
 *
 * @param string $key     Option key.
 * @param bool   $default Default.
 * @return bool
 */
function devco_opt_bool( $key, $default = false ) {
	$value = devco_opt( $key, $default );

	if ( is_string( $value ) ) {
		return in_array( strtolower( $value ), array( '1', 'yes', 'on', 'true' ), true );
	}

	return (bool) $value;
}

/**
 * Update a single theme option.
 *
 * @param string $key   Key.
 * @param mixed  $value Value.
 */
function devco_set_opt( $key, $value ) {
	$options          = get_option( DEVCO_OPTION, array() );
	$options          = is_array( $options ) ? $options : array();
	$options[ $key ]  = $value;
	update_option( DEVCO_OPTION, $options );
}

/**
 * All theme options.
 *
 * @return array
 */
function devco_options() {
	$stored = get_option( DEVCO_OPTION, array() );
	return is_array( $stored ) ? $stored : array();
}

/**
 * Is WooCommerce active?
 *
 * @return bool
 */
function devco_is_woo() {
	return class_exists( 'WooCommerce' );
}

/**
 * Is Elementor active?
 *
 * @return bool
 */
function devco_is_elementor() {
	return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
}

/**
 * Build an HTML attribute string from an array.
 *
 * @param array $atts Attributes.
 * @return string
 */
function devco_attr( $atts ) {
	$out = array();

	foreach ( (array) $atts as $key => $value ) {
		if ( null === $value || false === $value || '' === $value ) {
			continue;
		}

		if ( true === $value ) {
			$out[] = esc_attr( $key );
			continue;
		}

		if ( is_array( $value ) ) {
			$value = implode( ' ', array_filter( array_map( 'sanitize_html_class', $value ) ) );
		}

		$out[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	return $out ? ' ' . implode( ' ', $out ) : '';
}

/**
 * Inline SVG icons.
 *
 * @param string $name  Icon name.
 * @param array  $args  size, class, stroke.
 * @return string
 */
function devco_icon( $name, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'size'   => 20,
			'class'  => '',
			'stroke' => 1.7,
			'fill'   => false,
		)
	);

	$icons = devco_icon_paths();

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$icon = $icons[ $name ];

	$classes = trim( 'devco-icon devco-icon--' . sanitize_html_class( $name ) . ' ' . $args['class'] );

	$svg = sprintf(
		'<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="%s" stroke="%s" stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">',
		esc_attr( $classes ),
		(int) $args['size'],
		(int) $args['size'],
		$args['fill'] ? 'currentColor' : 'none',
		$args['fill'] ? 'none' : 'currentColor',
		esc_attr( $args['stroke'] )
	);

	$svg .= $icon;
	$svg .= '</svg>';

	return $svg;
}

/**
 * Echo an inline icon.
 *
 * @param string $name Icon name.
 * @param array  $args Arguments.
 */
function devco_icon_e( $name, $args = array() ) {
	echo devco_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG.
}

/**
 * Icon path definitions (24x24 grid).
 *
 * @return array
 */
function devco_icon_paths() {
	static $icons = null;

	if ( null !== $icons ) {
		return $icons;
	}

	$icons = array(
		'cart'        => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3.5h2.2l2.6 11h11.4l2.2-8.2H6.2"/>',
		'bag'         => '<path d="M5.5 8h13l-1 12.2a1.5 1.5 0 0 1-1.5 1.3H8a1.5 1.5 0 0 1-1.5-1.3Z"/><path d="M8.8 8V6.2a3.2 3.2 0 0 1 6.4 0V8"/>',
		'user'        => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20.5c.6-3.7 3.9-5.8 7.5-5.8s6.9 2.1 7.5 5.8"/>',
		'search'      => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
		'menu'        => '<path d="M3.5 6.5h17M3.5 12h17M3.5 17.5h17"/>',
		'close'       => '<path d="m6 6 12 12M18 6 6 18"/>',
		'chevron-down'  => '<path d="m6 9.5 6 6 6-6"/>',
		'chevron-up'    => '<path d="m6 14.5 6-6 6 6"/>',
		'chevron-left'  => '<path d="M14.5 6 8.5 12l6 6"/>',
		'chevron-right' => '<path d="m9.5 6 6 6-6 6"/>',
		'arrow-left'  => '<path d="M20 12H4.5M10 5.5 4 12l6 6.5"/>',
		'arrow-right' => '<path d="M4 12h15.5M14 5.5 20 12l-6 6.5"/>',
		'arrow-up'    => '<path d="M12 20V4.5M5.5 10.5 12 4l6.5 6.5"/>',
		'home'        => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1Z"/>',
		'grid'        => '<rect x="3.5" y="3.5" width="7" height="7" rx="2"/><rect x="13.5" y="3.5" width="7" height="7" rx="2"/><rect x="3.5" y="13.5" width="7" height="7" rx="2"/><rect x="13.5" y="13.5" width="7" height="7" rx="2"/>',
		'categories'  => '<path d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 15.5h6v4H4zM14 15.5h6v4h-6z"/>',
		'heart'       => '<path d="M12 20.2s-8-4.8-8-10a4.6 4.6 0 0 1 8-3.1 4.6 4.6 0 0 1 8 3.1c0 5.2-8 10-8 10Z"/>',
		'phone'       => '<path d="M6.2 3.5h3l1.6 4-2 1.4a11 11 0 0 0 5.4 5.4l1.4-2 4 1.6v3a2 2 0 0 1-2.2 2C11 18.2 5.8 13 4.2 5.7a2 2 0 0 1 2-2.2Z"/>',
		'mail'        => '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="m3.8 7 8.2 6 8.2-6"/>',
		'pin'         => '<path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11Z"/><circle cx="12" cy="10" r="2.6"/>',
		'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3.2 2"/>',
		'check'       => '<path d="m4.5 12.5 5 5 10-11"/>',
		'check-badge' => '<path d="m12 3.2 2.5 1.9 3.1-.2.9 3 2.4 2-1.2 2.9.5 3-3 1-1.6 2.7-3-1-3 1L9.5 17l-3-1 .5-3L5.8 10l2.4-2 .9-3 3.1.2Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
		'star'        => '<path d="m12 3.5 2.7 5.6 6.1.8-4.5 4.3 1.1 6.1-5.4-3-5.4 3 1.1-6.1L3.2 9.9l6.1-.8Z"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'trash'       => '<path d="M4.5 7h15M9 7V4.8h6V7M6.5 7l1 12.5a1.5 1.5 0 0 0 1.5 1.3h6a1.5 1.5 0 0 0 1.5-1.3L17.5 7"/>',
		'truck'       => '<path d="M2.5 6.5h10v10h-10zM12.5 10h4.2l3.3 3.2v3.3h-7.5"/><circle cx="6.5" cy="18" r="1.6"/><circle cx="16.8" cy="18" r="1.6"/>',
		'shield'      => '<path d="M12 3.5 19 6v6c0 4.4-3 7.3-7 8.6-4-1.3-7-4.2-7-8.6V6Z"/><path d="m9 12 2.2 2.2L15 10"/>',
		'refresh'     => '<path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 4v4.5h-4.5"/>',
		'credit-card' => '<rect x="2.5" y="5.5" width="19" height="13" rx="2.5"/><path d="M2.5 10h19M6 15h4"/>',
		'ruler'       => '<path d="m14.8 3.2 6 6a1 1 0 0 1 0 1.4l-8.9 8.9a1 1 0 0 1-1.4 0l-6-6a1 1 0 0 1 0-1.4l8.9-8.9a1 1 0 0 1 1.4 0Z"/><path d="m8 9 2 2M11 6l2 2M5 12l2 2"/>',
		'camera'      => '<path d="M3.5 8.5h3l1.4-2.3h8.2l1.4 2.3h3v10a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5Z"/><circle cx="12" cy="13.5" r="3.4"/>',
		'upload'      => '<path d="M12 16V4.5M7.5 9 12 4.5 16.5 9"/><path d="M4 15v4a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-4"/>',
		'drag'        => '<circle cx="9" cy="6" r="1.3"/><circle cx="15" cy="6" r="1.3"/><circle cx="9" cy="12" r="1.3"/><circle cx="15" cy="12" r="1.3"/><circle cx="9" cy="18" r="1.3"/><circle cx="15" cy="18" r="1.3"/>',
		'eye'         => '<path d="M2.5 12S6 6 12 6s9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.8"/>',
		'lock'        => '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
		'sms'         => '<path d="M20.5 12.5c0 4-3.8 7-8.5 7a10 10 0 0 1-2.6-.3L4.5 21l1.2-3.4A6.6 6.6 0 0 1 3.5 12.5c0-4 3.8-7 8.5-7s8.5 3 8.5 7Z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>',
		'dashboard'   => '<rect x="3.5" y="3.5" width="7.5" height="9" rx="2"/><rect x="13.5" y="3.5" width="7" height="5.5" rx="2"/><rect x="13.5" y="11.5" width="7" height="9" rx="2"/><rect x="3.5" y="15" width="7.5" height="5.5" rx="2"/>',
		'box'         => '<path d="m12 3.2 8 4v9.6l-8 4-8-4V7.2Z"/><path d="m4.2 7.4 7.8 4 7.8-4M12 11.4v9.4"/>',
		'invoice'     => '<path d="M6 3.5h9L19 7.5v13H6Z"/><path d="M14.5 3.5v4.5H19M9 12h6M9 15.5h6"/>',
		'logout'      => '<path d="M14.5 8V5.5a1.5 1.5 0 0 0-1.5-1.5H6a1.5 1.5 0 0 0-1.5 1.5v13A1.5 1.5 0 0 0 6 20h7a1.5 1.5 0 0 0 1.5-1.5V16"/><path d="M10 12h10.5M17 8.5l3.5 3.5L17 15.5"/>',
		'filter'      => '<path d="M3.5 5.5h17l-6.6 7.6v6.4l-3.8-2v-4.4Z"/>',
		'scissors'    => '<circle cx="6.5" cy="6.5" r="2.6"/><circle cx="6.5" cy="17.5" r="2.6"/><path d="M8.6 8.5 20 19M20 5 8.6 15.5"/>',
		'sparkle'     => '<path d="m12 3 1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4Z"/><path d="M18.5 16.5 19 18l1.5.5-1.5.6-.5 1.4-.6-1.4-1.4-.6 1.4-.5Z"/>',
		'tag'         => '<path d="M12.5 3.5H20v7.5l-8.6 8.6a1.5 1.5 0 0 1-2.1 0l-5.4-5.4a1.5 1.5 0 0 1 0-2.1Z"/><circle cx="16.6" cy="7.4" r="1.2"/>',
		'percent'     => '<path d="M5.5 18.5 18.5 5.5"/><circle cx="7.5" cy="7.5" r="2.4"/><circle cx="16.5" cy="16.5" r="2.4"/>',
		'gift'        => '<rect x="3.5" y="8.5" width="17" height="4" rx="1"/><path d="M5 12.5h14v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19.5Z"/><path d="M12 8.5V21M12 8.5C10.5 5 5.5 4.5 5.5 7.2c0 1.6 2.6 1.3 6.5 1.3ZM12 8.5C13.5 5 18.5 4.5 18.5 7.2c0 1.6-2.6 1.3-6.5 1.3Z"/>',
		'headset'     => '<path d="M4.5 15v-3a7.5 7.5 0 0 1 15 0v3"/><rect x="2.8" y="13.5" width="3.6" height="6" rx="1.6"/><rect x="17.6" y="13.5" width="3.6" height="6" rx="1.6"/><path d="M19.4 19.5v.4a2.4 2.4 0 0 1-2.4 2.4H13"/>',
		'download'    => '<path d="M12 3.5V15M7.5 10.5 12 15l4.5-4.5"/><path d="M4 16v3a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 20 19v-3"/>',
		'edit'        => '<path d="M15.5 4.5 19.5 8.5 8.5 19.5H4.5v-4Z"/><path d="m13.5 6.5 4 4"/>',
		'external'    => '<path d="M13.5 4.5H19.5V10.5"/><path d="M19.5 4.5 11 13"/><path d="M17.5 14v5a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5V8A1.5 1.5 0 0 1 5 6.5h5"/>',
		'copy'        => '<rect x="8.5" y="8.5" width="12" height="12" rx="2.5"/><path d="M15.5 5.5A2 2 0 0 0 13.5 3.5h-8a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2"/>',
		'info'        => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8h.01"/>',
		'warning'     => '<path d="M12 4 21 19.5H3Z"/><path d="M12 10v4M12 17h.01"/>',
		'play'        => '<path d="M8 5.5 18 12 8 18.5Z"/>',
		'calendar'    => '<rect x="3.5" y="5.5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3.5V7M16 3.5V7"/>',
		'chart'       => '<path d="M4 20V9M10 20V4M16 20v-7M22 20H2"/>',
		'wallet'      => '<rect x="3" y="6" width="18" height="13" rx="2.5"/><path d="M3 10h18M16.5 14.5h1.5"/>',
		'3d'          => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9Z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
		'webcam'      => '<circle cx="12" cy="10" r="4"/><rect x="4" y="4.5" width="16" height="12" rx="3"/><path d="M8.5 20.5 12 16.5l3.5 4"/>',
		'tshirt'      => '<path d="M8.5 4 5 5.5 3 10l3.5 1.6V20h11v-8.4L21 10l-2-4.5L15.5 4a3.5 3.5 0 0 1-7 0Z"/>',
		'instagram'   => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="3.8"/><path d="M17 7h.01"/>',
		'telegram'    => '<path d="m21 4.5-3 15-5.5-4-3 2.5.5-4.5L19 5.5 7.5 13 3 11.5Z"/>',
		'whatsapp'    => '<path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.5L3.5 20.5l1.4-4.2A8.5 8.5 0 1 1 20.5 11.7Z"/><path d="M9 8.5c-.5.6-.7 1.6.2 3.1 1 1.6 2.2 2.6 3.6 3.1 1.2.4 2 .1 2.5-.5l-1.4-1.2-1.2.6c-.7-.3-1.5-1.1-1.9-1.9l.7-1.1-1.2-1.5Z"/>',
		'facebook'    => '<path d="M14.5 21v-7.5h2.8l.5-3.2h-3.3V8.2c0-1 .5-1.7 1.8-1.7h1.6V3.6c-.6-.1-1.6-.2-2.6-.2-2.7 0-4.3 1.5-4.3 4.4v2.5H8v3.2h3v7.5Z"/>',
		'twitter'     => '<path d="M4 4.5h3.5l5 6.7 5.8-6.7h2L13.8 12l7 9h-3.6l-5.4-7.1-6.1 7.1H3.6L11 12.2Z"/>',
		'youtube'     => '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="m10 9.5 5 2.5-5 2.5Z"/>',
		'linkedin'    => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="M8 10.5V17M8 7.5h.01M12 17v-3.8c0-1.4.8-2.2 2-2.2s2 .8 2 2.2V17"/>',
		'support'     => '<circle cx="12" cy="12" r="8.5"/><path d="M9.5 9.2a2.6 2.6 0 1 1 3.7 2.4c-.7.3-1.2 1-1.2 1.9M12 17h.01"/>',
	);

	return $icons;
}

/**
 * Social networks used across the theme.
 *
 * @return array
 */
function devco_social_networks() {
	return array(
		'instagram' => array( 'label' => 'اینستاگرام', 'icon' => 'instagram' ),
		'telegram'  => array( 'label' => 'تلگرام', 'icon' => 'telegram' ),
		'whatsapp'  => array( 'label' => 'واتساپ', 'icon' => 'whatsapp' ),
		'facebook'  => array( 'label' => 'فیسبوک', 'icon' => 'facebook' ),
		'twitter'   => array( 'label' => 'ایکس (توییتر)', 'icon' => 'twitter' ),
		'youtube'   => array( 'label' => 'یوتیوب', 'icon' => 'youtube' ),
		'linkedin'  => array( 'label' => 'لینکدین', 'icon' => 'linkedin' ),
	);

}

/**
 * Active social links from the options panel.
 *
 * @return array
 */
function devco_social_links() {
	$links = array();

	foreach ( devco_social_networks() as $key => $data ) {
		$url = devco_opt( 'social.' . $key, '' );
		if ( ! empty( $url ) ) {
			$links[ $key ] = array(
				'url'   => $url,
				'label' => $data['label'],
				'icon'  => $data['icon'],
			);
		}
	}

	$email = devco_opt( 'social.email', '' );

	if ( $email ) {
		$links['email'] = array(
			'url'   => 'mailto:' . $email,
			'label' => 'ایمیل',
			'icon'  => 'mail',
		);
	}

	return $links;
}

/**
 * Container classes for the configured site width.
 *
 * @return string
 */
function devco_container_class() {
	$mode = devco_opt( 'layout.container', 'boxed' );

	$classes = array( 'devco-container' );

	if ( 'full' === $mode ) {
		$classes[] = 'devco-container--fluid';
	}

	return implode( ' ', $classes );
}

/**
 * Open the site container.
 */
function devco_container_start() {
	echo '<div class="' . esc_attr( devco_container_class() ) . '">';
}

/**
 * Close the site container.
 */
function devco_container_end() {
	echo '</div>';
}

/**
 * Persian/Arabic digits helper.
 *
 * @param string|int $number Number.
 * @return string
 */
function devco_num( $number ) {
	if ( ! devco_opt_bool( 'layout.persian_digits', true ) ) {
		return (string) $number;
	}

	$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );

	return str_replace( $en, $fa, (string) $number );
}

/**
 * Trim text by words.
 *
 * @param string $text  Text.
 * @param int    $limit Words.
 * @return string
 */
function devco_trim( $text, $limit = 18 ) {
	$text = wp_strip_all_tags( (string) $text );
	$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

	if ( ! $words || count( $words ) <= $limit ) {
		return $text;
	}

	return implode( ' ', array_slice( $words, 0, $limit ) ) . '…';
}

/**
 * Nonce action name for front-end AJAX.
 *
 * @param string $action Action.
 * @return string
 */
function devco_nonce( $action ) {
	return 'devco_' . $action;
}

/**
 * Format a price with the configured currency.
 *
 * @param float $amount Amount.
 * @return string
 */
function devco_price( $amount ) {
	return devco_currency() . ' ' . devco_num( number_format_i18n( (float) $amount ) );
}

/**
 * Currency symbol/label for DeVco panels.
 *
 * @return string
 */
function devco_currency() {
	$currency = devco_opt( 'shop.currency_label', 'تومان' );
	return apply_filters( 'devco_currency_label', $currency );
}

/**
 * Render a rating stars block.
 *
 * @param float $rating Rating out of 5.
 * @return string
 */
function devco_stars( $rating ) {
	$rating = (float) $rating;
	$out    = '<span class="devco-stars" aria-label="' . esc_attr( sprintf( 'امتیاز %s از ۵', devco_num( $rating ) ) ) . '">';

	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= '<span class="devco-star' . ( $i <= round( $rating ) ? ' is-on' : '' ) . '">' . devco_icon( 'star', array( 'size' => 14, 'fill' => $i <= round( $rating ) ) ) . '</span>';
	}

	return $out . '</span>';
}

/**
 * Get an attachment image URL with fallback to a placeholder.
 *
 * @param int    $id  Attachment ID.
 * @param string $size Image size.
 * @param string $fallback Fallback URL.
 * @return string
 */
function devco_image_url( $id, $size = 'full', $fallback = '' ) {
	$src = $id ? wp_get_attachment_image_url( (int) $id, $size ) : '';

	if ( ! $src ) {
		$src = $fallback;
	}

	if ( ! $src ) {
		$src = DEVCO_URI . '/assets/images/placeholder.svg';
	}

	return $src;
}

/**
 * Whether the current request should show the theme header/footer.
 *
 * @param string $part header|footer.
 * @return bool
 */
function devco_show_part( $part ) {
	if ( is_singular() && get_post_meta( get_queried_object_id(), '_devco_hide_' . $part, true ) ) {
		return false;
	}

	if ( function_exists( 'elementor_theme_do_location' ) && 'header' === $part && devco_opt( 'header_source', 'builder' ) === 'elementor' ) {
		return true;
	}

	return true;
}

/**
 * Sanitize a hex color with fallback.
 *
 * @param string $color   Color.
 * @param string $default Default.
 * @return string
 */
function devco_color( $color, $default = '' ) {
	$color = sanitize_hex_color( (string) $color );
	return $color ? $color : $default;
}

/**
 * Build a CSS value for spacing fields.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function devco_css_size( $value ) {
	if ( is_numeric( $value ) ) {
		return $value . 'px';
	}

	return (string) $value;
}

/**
 * Data used by front-end scripts.
 *
 * @return array
 */
function devco_js_data() {
	return array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'restUrl'     => esc_url_raw( rest_url( 'devco/v1/' ) ),
		'nonce'       => wp_create_nonce( 'devco_front' ),
		'currency'    => devco_currency(),
		'persianNums' => devco_opt_bool( 'layout.persian_digits', true ),
		'isWoo'       => devco_is_woo(),
		'cartUrl'     => devco_is_woo() ? wc_get_cart_url() : '',
		'checkoutUrl' => devco_is_woo() ? wc_get_checkout_url() : '',
		'accountUrl'  => devco_is_woo() ? wc_get_page_permalink( 'myaccount' ) : '',
		'dashboard'   => devco_dashboard_url(),
		'loggedIn'    => is_user_logged_in(),
		'otpLength'   => (int) devco_opt( 'auth.otp_length', 5 ),
		'fit'         => array(
			'webcam'  => devco_opt_bool( 'fitting.webcam', true ),
			'opacity' => (float) devco_opt( 'fitting.cloth_opacity', 0.88 ),
		),
		'i18n'        => array(
			'added'      => 'به سبد خرید اضافه شد',
			'addToCart'  => 'افزودن به سبد خرید',
			'loading'    => 'در حال پردازش…',
			'error'      => 'خطایی رخ داد، دوباره تلاش کنید.',
			'codeSent'   => 'کد تایید ارسال شد.',
			'copy'       => 'کپی شد',
			'confirm'    => 'آیا مطمئن هستید؟',
			'emptyCart'  => 'سبد خرید شما خالی است.',
			'cameraOff'  => 'دسترسی به دوربین امکان‌پذیر نیست.',
			'uploadImg'  => 'تصویر خود را آپلود کنید',
			'sending'    => 'در حال ارسال…',
			'remove'     => 'حذف',
		),
	);
}

/**
 * Theme dashboard URL (custom page or Woo account endpoint).
 *
 * @return string
 */
function devco_dashboard_url( $tab = '' ) {
	$page_id = (int) devco_opt( 'dashboard.page_id', 0 );
	$url     = '';

	if ( $page_id ) {
		$url = get_permalink( $page_id );
	}

	if ( ! $url && devco_is_woo() ) {
		$url = wc_get_page_permalink( 'myaccount' );
	}

	if ( ! $url ) {
		$url = home_url( '/' );
	}

	if ( $tab ) {
		$url = add_query_arg( 'devco_tab', sanitize_key( $tab ), $url );
	}

	return $url;
}

/**
 * Theme url for asset.
 *
 * @param string $path Relative path.
 * @return string
 */
function devco_asset( $path ) {
	return DEVCO_URI . '/assets/' . ltrim( $path, '/' );
}

/**
 * Cache-busting version string for a theme asset.
 *
 * Uses the file modification time so browsers and page caches always pick up
 * edited CSS/JS, even when DEVCO_VERSION has not changed.
 *
 * @param string $path Asset path relative to /assets.
 * @return string
 */
function devco_asset_ver( $path ) {
	$version = defined( 'DEVCO_VERSION' ) ? DEVCO_VERSION : '1.0.0';
	$file    = DEVCO_DIR . '/assets/' . ltrim( $path, '/' );

	if ( $file && file_exists( $file ) ) {
		$version .= '.' . filemtime( $file );
	}

	return $version;
}

/**
 * Admin notice helper (used by wizard / requirements).
 *
 * @param string $message Message.
 * @param string $type    Type.
 */
function devco_admin_notice( $message, $type = 'info' ) {
	printf(
		'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $type ),
		wp_kses_post( $message )
	);
}

/**
 * Get edit link for admin bar / panels.
 *
 * @param int $id Post ID.
 * @return string
 */
function devco_edit_link( $id ) {
	return esc_url( admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) );
}

/**
 * Font family stack for a configured font key.
 *
 * @param string $key Font key.
 * @return string
 */
function devco_font_stack( $key ) {
	$custom = devco_opt( 'fonts.custom_family', '' );

	$stacks = array(
		'vazirmatn' => "'DeVcoVazirmatn', 'Vazirmatn', 'Vazir', 'IRANSans', Tahoma, Arial, sans-serif",
		'iransans'  => "'IRANSansX', 'IRANSans', 'IRANSansWeb', Tahoma, Arial, sans-serif",
		'dana'      => "'Dana', 'DanaFaNum', Tahoma, Arial, sans-serif",
		'shabnam'   => "'Shabnam', 'ShabnamFaNum', Tahoma, Arial, sans-serif",
		'yekanbakh' => "'Yekan Bakh', 'YekanBakh', 'IRANYekan', Tahoma, Arial, sans-serif",
		'estedad'   => "'Estedad', 'EstedadFaNum', Tahoma, Arial, sans-serif",
		'system'    => "system-ui, -apple-system, 'Segoe UI', Tahoma, Arial, sans-serif",
		'custom'    => ( $custom ? "'" . $custom . "', " : '' ) . 'Tahoma, Arial, sans-serif',
	);

	return isset( $stacks[ $key ] ) ? $stacks[ $key ] : $stacks['vazirmatn'];
}

/**
 * System status table used in the advanced tab.
 *
 * @return string
 */
function devco_system_status_html() {
	$rows = array(
		'نسخه قالب'              => DEVCO_VERSION,
		'نسخه وردپرس'            => get_bloginfo( 'version' ),
		'نسخه PHP'               => PHP_VERSION,
		'ووکامرس'                => devco_is_woo() ? 'فعال' : 'غیرفعال',
		'المنتور'                => devco_is_elementor() ? 'فعال' : 'غیرفعال',
		'حافظه مجاز'             => WP_MEMORY_LIMIT,
		'حداکثر آپلود'           => size_format( wp_max_upload_size() ),
		'زبان سایت'              => get_locale(),
		'راست به چپ'             => is_rtl() ? 'بله' : 'خیر',
		'سرویس پیامک'            => devco_opt( 'sms.provider', '' ) ? devco_opt( 'sms.provider' ) : 'تنظیم نشده',
	);

	$html = '<div class="devco-status-grid">';

	foreach ( $rows as $label => $value ) {
		$html .= '<div class="devco-status-item"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $value ) . '</strong></div>';
	}

	$html .= '</div>';

	return $html;
}

/**
 * Whether the current page is built with Elementor.
 *
 * @param int|null $post_id Post ID.
 * @return bool
 */
function devco_is_elementor_page( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Whether the current request is a full-width / no-container layout.
 *
 * @return bool
 */
function devco_is_wide_page() {
	return (bool) get_post_meta( get_the_ID(), '_devco_wide', true );
}
