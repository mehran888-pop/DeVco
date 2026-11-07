<?php
/**
 * Template tags and header/footer UI components.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Site logo.
 *
 * @param array $args Arguments.
 */
function devco_site_logo( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'variant' => 'desktop',
			'class'   => '',
		)
	);

	$logo_id = 0;

	if ( 'mobile' === $args['variant'] ) {
		$logo_id = (int) devco_opt( 'mobile.header_logo', 0 );
	}

	$url = home_url( '/' );

	echo '<a class="devco-logo devco-logo--' . esc_attr( $args['variant'] ) . ' ' . esc_attr( $args['class'] ) . '" href="' . esc_url( $url ) . '" rel="home" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '">';

	if ( $logo_id ) {
		echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'devco-logo__img', 'alt' => get_bloginfo( 'name' ) ) );
	} elseif ( has_custom_logo() ) {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		echo wp_get_attachment_image( $custom_logo_id, 'medium', false, array( 'class' => 'devco-logo__img', 'alt' => get_bloginfo( 'name' ) ) );
	} else {
		echo '<span class="devco-logo__mark" aria-hidden="true">' . devco_icon( 'sparkle', array( 'size' => 22 ) ) . '</span>';
		echo '<span class="devco-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
	}

	echo '</a>';
}

/**
 * Render a navigation menu with a safe fallback.
 *
 * @param string $location Theme location.
 * @param array  $args     Extra args.
 */
function devco_nav_menu( $location, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'theme_location' => $location,
			'container'      => false,
			'menu_class'     => 'devco-menu',
			'fallback_cb'    => 'devco_menu_fallback',
			'depth'          => 3,
		)
	);

	wp_nav_menu( $args );
}

/**
 * Fallback menu when no menu is assigned.
 */
function devco_menu_fallback() {
	echo '<ul class="devco-menu devco-menu--fallback">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">صفحه اصلی</a></li>';

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">فروشگاه</a></li>';
	}

	wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 1,
			'number'   => 5,
		)
	);

	echo '</ul>';
}

/**
 * Header component: main menu.
 *
 * @param array $args Args.
 */
function devco_component_menu( $args = array() ) {
	$layout = devco_opt( 'header.menu_layout', 'classic' );
	$mode   = devco_opt( 'header.menu_mode', 'mega' );
	$cols   = (int) devco_opt( 'header.mega_columns', 4 );

	printf(
		'<nav class="devco-nav devco-nav--%1$s devco-nav--mode-%2$s" data-menu-mode="%2$s" data-mega-cols="%3$d" aria-label="منوی اصلی">',
		esc_attr( $layout ),
		esc_attr( $mode ),
		$cols
	);

	devco_nav_menu( 'primary', array( 'menu_class' => 'devco-menu devco-menu--main' ) );

	echo '</nav>';
}

/**
 * Header component: categories menu.
 *
 * @param array $args Args.
 */
function devco_component_category_menu( $args = array() ) {
	$layout = devco_opt( 'header.cat_layout', 'dropdown' );
	$icon   = devco_opt( 'header.cat_icon', 'categories' );
	$label  = isset( $args['label'] ) ? $args['label'] : 'دسته‌بندی محصولات';

	printf(
		'<div class="devco-catmenu devco-catmenu--%1$s" data-catmenu="%1$s">',
		esc_attr( $layout )
	);

	printf(
		'<button type="button" class="devco-catmenu__toggle" aria-expanded="false"><span class="devco-catmenu__icon">%1$s</span><span class="devco-catmenu__label">%2$s</span>%3$s</button>',
		devco_icon( $icon, array( 'size' => 18 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $label ),
		devco_icon( 'chevron-down', array( 'size' => 16 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	echo '<div class="devco-catmenu__panel">';

	$assigned = has_nav_menu( 'categories' );

	if ( $assigned ) {
		devco_nav_menu( 'categories', array( 'menu_class' => 'devco-catmenu__list' ) );
	} elseif ( devco_is_woo() ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 14,
				'parent'     => 0,
			)
		);

		if ( ! is_wp_error( $terms ) && $terms ) {
			echo '<ul class="devco-catmenu__list devco-catmenu__list--auto">';
			foreach ( $terms as $term ) {
				$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				printf(
					'<li class="devco-catmenu__item"><a href="%1$s" class="devco-catmenu__link">%2$s<span class="devco-catmenu__name">%3$s</span><span class="devco-catmenu__count">%4$s</span></a></li>',
					esc_url( get_term_link( $term ) ),
					$thumb_id ? wp_get_attachment_image( $thumb_id, 'devco-thumb', false, array( 'class' => 'devco-catmenu__thumb', 'loading' => 'lazy' ) ) : '<span class="devco-catmenu__thumb devco-catmenu__thumb--ph">' . devco_icon( 'bag', array( 'size' => 16 ) ) . '</span>',
					esc_html( $term->name ),
					esc_html( devco_num( $term->count ) )
				);
			}
			echo '</ul>';
		}
	} else {
		devco_menu_fallback();
	}

	echo '</div></div>';
}

/**
 * Header component: search.
 *
 * @param array $args Args.
 */
function devco_component_search( $args = array() ) {
	$style = isset( $args['style'] ) ? $args['style'] : devco_opt( 'header.search_style', 'inline' );

	printf( '<div class="devco-search devco-search--%s" data-search-style="%s">', esc_attr( $style ), esc_attr( $style ) );

	if ( 'icon' !== $style && 'popup' !== $style ) {
		devco_product_search_form();
	} else {
		printf(
			'<button type="button" class="devco-icon-btn devco-search__toggle" aria-label="جستجو" aria-expanded="false">%s</button>',
			devco_icon( 'search', array( 'size' => 20 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '<div class="devco-search__panel">';
		devco_product_search_form();
		echo '</div>';
	}

	echo '</div>';
}

/**
 * Product search form.
 */
function devco_product_search_form() {
	$id = 'devco-search-' . wp_rand( 1000, 9999 );

	echo '<form role="search" method="get" class="devco-search__form" action="' . esc_url( home_url( '/' ) ) . '">';
	echo '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">جستجو در محصولات</label>';
	echo '<span class="devco-search__icon">' . devco_icon( 'search', array( 'size' => 18 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<input type="search" id="' . esc_attr( $id ) . '" class="devco-search__input" name="s" value="' . esc_attr( get_search_query() ) . '" placeholder="جستجو در محصولات…" autocomplete="off" />';

	if ( devco_is_woo() ) {
		echo '<input type="hidden" name="post_type" value="product" />';
	}

	echo '<button type="submit" class="devco-search__submit">جستجو</button>';
	echo '<div class="devco-search__results" aria-live="polite"></div>';
	echo '</form>';
}

/**
 * Header component: account / login button.
 */
function devco_component_account() {
	echo '<div class="devco-account">';

	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$url  = devco_dashboard_url();

		printf(
			'<button type="button" class="devco-account__toggle devco-icon-btn" aria-expanded="false" aria-label="%1$s"><span class="devco-account__avatar">%2$s</span><span class="devco-account__name">%3$s</span>%4$s</button>',
			esc_attr( 'حساب کاربری' ),
			get_avatar( $user->ID, 28, '', '', array( 'class' => 'devco-avatar' ) ),
			esc_html( devco_trim( $user->display_name, 2 ) ),
			devco_icon( 'chevron-down', array( 'size' => 14 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		echo '<div class="devco-account__dropdown">';
		echo '<div class="devco-account__head">' . get_avatar( $user->ID, 44, '', '', array( 'class' => 'devco-avatar' ) ) . '<div><strong>' . esc_html( $user->display_name ) . '</strong><span>' . esc_html( $user->user_email ) . '</span></div></div>';
		echo '<ul class="devco-account__menu">';

		$items = array(
			'overview'  => array( 'dashboard', 'نمای کلی' ),
			'orders'    => array( 'box', 'سفارش‌های من' ),
			'tailoring' => array( 'scissors', 'سفارش‌های دوخت' ),
			'invoices'  => array( 'invoice', 'فاکتورها' ),
			'account'   => array( 'user', 'اطلاعات حساب' ),
		);

		foreach ( $items as $slug => $item ) {
			printf(
				'<li><a href="%1$s">%2$s<span>%3$s</span></a></li>',
				esc_url( devco_dashboard_url( $slug ) ),
				devco_icon( $item[0], array( 'size' => 17 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $item[1] )
			);
		}

		printf(
			'<li><a class="devco-account__logout" href="%1$s">%2$s<span>خروج</span></a></li>',
			esc_url( wp_logout_url( home_url( '/' ) ) ),
			devco_icon( 'logout', array( 'size' => 17 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		echo '</ul></div>';
	} else {
		$style = devco_opt( 'auth.button_style', 'ghost' );
		$text  = devco_opt( 'auth.button_text', 'ورود | ثبت‌نام' );

		printf(
			'<button type="button" class="devco-login-btn devco-login-btn--%1$s js-devco-auth-open" data-auth-view="%2$s"><span class="devco-login-btn__icon">%3$s</span><span class="devco-login-btn__text">%4$s</span></button>',
			esc_attr( $style ),
			esc_attr( devco_opt( 'auth.default_method', 'otp' ) ),
			devco_icon( 'user', array( 'size' => 19 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $text )
		);
	}

	echo '</div>';
}

/**
 * Header component: cart button + drawer.
 */
function devco_component_cart() {
	if ( ! devco_is_woo() ) {
		return;
	}

	$count = devco_cart_count();

	printf(
		'<button type="button" class="devco-cart-btn js-devco-cart-open" aria-label="سبد خرید" data-count="%1$d"><span class="devco-cart-btn__icon">%2$s<span class="devco-cart-btn__count" data-cart-count>%3$s</span></span><span class="devco-cart-btn__meta"><small>سبد خرید</small><strong data-cart-total>%4$s</strong></span></button>',
		(int) $count,
		devco_icon( 'bag', array( 'size' => 21 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( devco_num( $count ) ),
		wp_kses_post( devco_cart_subtotal() )
	);
}

/**
 * Header component: wishlist.
 */
function devco_component_wishlist() {
	printf(
		'<a class="devco-icon-btn devco-wishlist-link" href="%1$s" aria-label="علاقه‌مندی‌ها">%2$s<span class="devco-wishlist-count" data-wishlist-count>0</span></a>',
		esc_url( devco_dashboard_url( 'wishlist' ) ),
		devco_icon( 'heart', array( 'size' => 20 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}

/**
 * Header component: social icons.
 *
 * @param array $args Args.
 */
function devco_social_icons( $args = array() ) {
	$links = devco_social_links();

	if ( ! $links ) {
		return;
	}

	$args = wp_parse_args( $args, array( 'class' => '', 'size' => 18 ) );

	echo '<div class="devco-socials ' . esc_attr( $args['class'] ) . '">';

	foreach ( $links as $key => $link ) {
		printf(
			'<a class="devco-social devco-social--%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s" title="%3$s">%4$s</a>',
			esc_attr( $key ),
			esc_url( $link['url'] ),
			esc_attr( $link['label'] ),
			devco_icon( $link['icon'], array( 'size' => $args['size'] ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	echo '</div>';
}

/**
 * Render one header slot component.
 *
 * @param string $component Component key.
 * @param array  $args      Args.
 */
function devco_render_component( $component, $args = array() ) {
	switch ( $component ) {
		case 'logo':
			devco_site_logo( array( 'class' => 'devco-header__logo' ) );
			break;

		case 'menu':
			devco_component_menu( $args );
			break;

		case 'category_menu':
			devco_component_category_menu( $args );
			break;

		case 'search':
			devco_component_search( $args );
			break;

		case 'account':
			devco_component_account();
			break;

		case 'cart':
			devco_component_cart();
			break;

		case 'wishlist':
			devco_component_wishlist();
			break;

		case 'socials':
			devco_social_icons( array( 'class' => 'devco-socials--header' ) );
			break;

		case 'phone':
			$phone = devco_opt( 'general.phone', '' );
			if ( $phone ) {
				printf(
					'<a class="devco-header__phone" href="tel:%1$s">%2$s<span><small>تماس با ما</small><strong>%3$s</strong></span></a>',
					esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ),
					devco_icon( 'phone', array( 'size' => 18 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $phone )
				);
			}
			break;

		case 'text':
			$text = devco_opt( 'header.topbar_text', '' );
			if ( $text ) {
				echo '<p class="devco-header__text">' . devco_icon( 'sparkle', array( 'size' => 15 ) ) . '<span>' . esc_html( $text ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			break;

		case 'topbar_menu':
			devco_nav_menu( 'topbar', array( 'menu_class' => 'devco-menu devco-menu--topbar' ) );
			break;

		case 'divider':
			echo '<span class="devco-header__divider" aria-hidden="true"></span>';
			break;

		case 'widget_1':
		case 'widget_2':
		case 'widget_3':
			$slot = str_replace( 'widget_', '', $component );
			if ( is_active_sidebar( 'header-slot-' . $slot ) ) {
				echo '<div class="devco-header__widget">';
				dynamic_sidebar( 'header-slot-' . $slot );
				echo '</div>';
			}
			break;
	}
}

/**
 * Breadcrumbs.
 */
function devco_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	echo '<nav class="devco-breadcrumb" aria-label="مسیر صفحه"><ol>';

	printf( '<li><a href="%1$s">%2$s خانه</a></li>', esc_url( home_url( '/' ) ), devco_icon( 'home', array( 'size' => 14 ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		$shop_id = wc_get_page_id( 'shop' );

		if ( is_shop() ) {
			echo '<li aria-current="page">' . esc_html( get_the_title( $shop_id ) ) . '</li>';
		} elseif ( is_product_taxonomy() ) {
			printf( '<li><a href="%1$s">فروشگاه</a></li>', esc_url( get_permalink( $shop_id ) ) );
			echo '<li aria-current="page">' . esc_html( single_term_title( '', false ) ) . '</li>';
		} elseif ( is_product() ) {
			printf( '<li><a href="%1$s">فروشگاه</a></li>', esc_url( get_permalink( $shop_id ) ) );
			$terms = get_the_terms( get_the_ID(), 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$term = array_shift( $terms );
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
			}
			echo '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
		} else {
			echo '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
		}
	} elseif ( is_singular() ) {
		$post_type = get_post_type();

		if ( 'post' === $post_type ) {
			$cats = get_the_category();
			if ( $cats ) {
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( get_category_link( $cats[0] ) ), esc_html( $cats[0]->name ) );
			}
		}

		echo '<li aria-current="page">' . esc_html( get_the_title() ) . '</li>';
	} elseif ( is_archive() ) {
		echo '<li aria-current="page">' . esc_html( get_the_archive_title() ) . '</li>';
	} elseif ( is_search() ) {
		echo '<li aria-current="page">نتایج جستجو برای: ' . esc_html( get_search_query() ) . '</li>';
	} elseif ( is_404() ) {
		echo '<li aria-current="page">صفحه پیدا نشد</li>';
	}

	echo '</ol></nav>';
}

/**
 * Page header block.
 *
 * @param array $args Args.
 */
function devco_page_header( $args = array() ) {
	$style = devco_opt( 'layout.page_header', 'minimal' );

	if ( 'hidden' === $style ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'title'    => '',
			'subtitle' => '',
			'class'    => '',
		)
	);

	if ( ! $args['title'] ) {
		$args['title'] = get_the_archive_title() ? wp_strip_all_tags( get_the_archive_title() ) : get_the_title();
	}

	if ( is_404() ) {
		$args['title'] = 'صفحه پیدا نشد';
	}

	$image = 'image' === $style ? devco_opt( 'layout.page_header_image', '' ) : '';

	printf(
		'<header class="devco-page-header devco-page-header--%1$s %2$s">',
		esc_attr( $style ),
		esc_attr( $args['class'] )
	);

	if ( $image ) {
		echo '<div class="devco-page-header__bg" style="background-image:url(' . esc_url( $image ) . ')"></div>';
	}

	echo '<div class="' . esc_attr( devco_container_class() ) . '">';
	devco_breadcrumbs();
	echo '<h1 class="devco-page-header__title">' . esc_html( $args['title'] ) . '</h1>';

	if ( $args['subtitle'] ) {
		echo '<p class="devco-page-header__subtitle">' . esc_html( $args['subtitle'] ) . '</p>';
	}

	echo '</div></header>';
}

/**
 * Post meta row.
 */
function devco_post_meta() {
	echo '<div class="devco-post-meta">';

	printf( '<span class="devco-post-meta__item">%1$s %2$s</span>', devco_icon( 'calendar', array( 'size' => 14 ) ), esc_html( get_the_date() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	$author = get_the_author();

	if ( $author ) {
		printf( '<span class="devco-post-meta__item">%1$s %2$s</span>', devco_icon( 'user', array( 'size' => 14 ) ), esc_html( $author ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	$cats = get_the_category_list( '، ' );

	if ( $cats ) {
		echo '<span class="devco-post-meta__item">' . devco_icon( 'tag', array( 'size' => 14 ) ) . wp_kses_post( $cats ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/**
 * Numeric pagination.
 *
 * @param WP_Query|null $query Query.
 */
function devco_pagination( $query = null ) {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => devco_icon( 'chevron-right', array( 'size' => 16 ) ) . '<span class="screen-reader-text">قبلی</span>',
			'next_text' => devco_icon( 'chevron-left', array( 'size' => 16 ) ) . '<span class="screen-reader-text">بعدی</span>',
			'class'     => 'devco-pagination',
		)
	);
}

/**
 * Mobile header markup.
 */
function devco_mobile_header() {
	$style = devco_opt( 'mobile.header_style', 'glass' );
	$class = array( 'devco-mobile-header', 'devco-mobile-header--' . $style );

	if ( devco_opt_bool( 'mobile.sticky_header', true ) ) {
		$class[] = 'is-sticky';
	}

	if ( devco_opt_bool( 'mobile.hide_icons_on_scroll', true ) ) {
		$class[] = 'is-hide-on-scroll';
	}
	?>
	<header class="<?php echo esc_attr( implode( ' ', $class ) ); ?>" data-mobile-header>
		<div class="devco-mobile-header__inner">
			<button type="button" class="devco-mobile-header__btn js-devco-drawer-open" aria-label="منو" aria-expanded="false">
				<?php devco_icon_e( 'menu', array( 'size' => 22 ) ); ?>
			</button>

			<div class="devco-mobile-header__logo">
				<?php devco_site_logo( array( 'variant' => 'mobile' ) ); ?>
			</div>

			<div class="devco-mobile-header__actions">
				<?php if ( devco_opt_bool( 'mobile.search', true ) ) : ?>
					<button type="button" class="devco-mobile-header__btn js-devco-mobile-search" aria-label="جستجو">
						<?php devco_icon_e( 'search', array( 'size' => 21 ) ); ?>
					</button>
				<?php endif; ?>

				<?php if ( devco_is_woo() ) : ?>
					<a class="devco-mobile-header__btn devco-mobile-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="سبد خرید">
						<?php devco_icon_e( 'cart', array( 'size' => 21 ) ); ?>
						<span class="devco-mobile-header__count" data-cart-count><?php echo esc_html( devco_num( devco_cart_count() ) ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="devco-mobile-search" data-mobile-search>
			<?php devco_product_search_form(); ?>
		</div>
	</header>
	<?php
}

/**
 * Bottom navigation bar markup.
 */
function devco_bottom_nav() {
	if ( ! devco_opt_bool( 'mobile.bottom_bar', true ) ) {
		return;
	}

	$items = devco_opt( 'mobile.bottom_bar_items', array() );

	if ( ! is_array( $items ) || ! $items ) {
		$items = array(
			array( 'icon' => 'home', 'label' => 'خانه', 'action' => 'home' ),
			array( 'icon' => 'categories', 'label' => 'دسته‌ها', 'action' => 'categories' ),
			array( 'icon' => 'bag', 'label' => 'فروشگاه', 'action' => 'shop' ),
			array( 'icon' => 'cart', 'label' => 'سبد', 'action' => 'cart' ),
			array( 'icon' => 'user', 'label' => 'حساب من', 'action' => 'account' ),
		);
	}

	$style       = devco_opt( 'mobile.style', 'glass' );
	$center      = devco_opt_bool( 'mobile.center_item', true );
	$middle      = (int) floor( count( $items ) / 2 );
	$cart_count  = devco_cart_count();

	echo '<nav class="devco-bottom-nav devco-bottom-nav--' . esc_attr( $style ) . '" aria-label="ناوبری موبایل" data-bottom-nav>';

	foreach ( $items as $index => $item ) {
		$action = isset( $item['action'] ) ? $item['action'] : 'home';
		$icon   = isset( $item['icon'] ) ? $item['icon'] : 'home';
		$label  = isset( $item['label'] ) ? $item['label'] : '';
		$url    = '#';
		$extra  = '';
		$is_active = false;

		switch ( $action ) {
			case 'home':
				$url       = home_url( '/' );
				$is_active = is_front_page();
				break;
			case 'shop':
				$url       = devco_is_woo() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
				$is_active = devco_is_woo() && is_shop();
				break;
			case 'categories':
				$url  = devco_is_woo() ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
				$extra = ' js-devco-drawer-categories';
				break;
			case 'cart':
				$url       = devco_is_woo() ? wc_get_cart_url() : '#';
				$is_active = devco_is_woo() && is_cart();
				$extra     = ' js-devco-cart-open';
				break;
			case 'account':
				$url       = devco_dashboard_url();
				$is_active = is_user_logged_in() && is_page() && (int) get_queried_object_id() === (int) devco_opt( 'dashboard.page_id', 0 );
				break;
			case 'orders':
				$url = devco_dashboard_url( 'orders' );
				break;
			case 'tailoring':
				$url = devco_dashboard_url( 'tailoring' );
				break;
			case 'wishlist':
				$url = devco_dashboard_url( 'wishlist' );
				break;
			case 'search':
				$extra = ' js-devco-mobile-search';
				break;
			case 'link':
				$url = isset( $item['link'] ) ? $item['link'] : '#';
				break;
		}

		$classes = array( 'devco-bottom-nav__item' );

		if ( $center && $index === $middle && count( $items ) % 2 ) {
			$classes[] = 'is-center';
		}

		if ( $is_active ) {
			$classes[] = 'is-active';
		}

		printf(
			'<a class="%1$s%2$s" href="%3$s" data-action="%4$s">%5$s%6$s<span class="devco-bottom-nav__label">%7$s</span>%8$s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $extra ),
			esc_url( $url ),
			esc_attr( $action ),
			'<span class="devco-bottom-nav__icon">',
			devco_icon( $icon, array( 'size' => 22 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $label ),
			'</span>' . ( 'cart' === $action && $cart_count ? '<span class="devco-bottom-nav__badge" data-cart-count>' . esc_html( devco_num( $cart_count ) ) . '</span>' : '' )
		);
	}

	echo '</nav>';
}

/**
 * Dashboard URL with an optional tab.
 *
 * @param string $tab Tab slug.
 * @return string
 */
function devco_dashboard_tab_url( $tab ) {
	return devco_dashboard_url( $tab );
}
