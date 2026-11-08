<?php
/**
 * Floating UI: cart, contact actions, drawers and global modals.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Floating
 */
class DeVco_Floating {

	/**
	 * Instance.
	 *
	 * @var DeVco_Floating|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Floating
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'wp_footer', array( $this, 'render_all' ), 20 );
	}

	/**
	 * Print all floating UI.
	 */
	public function render_all() {
		$this->mobile_drawer();
		$this->cart_drawer();
		$this->floating_cart();
		$this->floating_actions();
		$this->fitting_auto();
		$this->modals();
	}

	/**
	 * Off-canvas mobile menu.
	 */
	public function mobile_drawer() {
		$style = devco_opt( 'mobile.drawer_style', 'slide' );
		?>
		<div class="devco-drawer devco-drawer--<?php echo esc_attr( $style ); ?>" data-drawer aria-hidden="true">
			<div class="devco-drawer__backdrop" data-drawer-close></div>
			<aside class="devco-drawer__panel" role="dialog" aria-modal="true" aria-label="منوی موبایل">
				<header class="devco-drawer__head">
					<?php devco_site_logo( array( 'variant' => 'mobile' ) ); ?>
					<button type="button" class="devco-drawer__close" data-drawer-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>
				</header>

				<div class="devco-drawer__search"><?php devco_product_search_form(); ?></div>

				<nav class="devco-drawer__nav" aria-label="منوی موبایل">
					<?php
					if ( has_nav_menu( 'mobile' ) ) {
						devco_nav_menu( 'mobile', array( 'menu_class' => 'devco-menu devco-menu--drawer' ) );
					} else {
						devco_nav_menu( 'primary', array( 'menu_class' => 'devco-menu devco-menu--drawer' ) );
					}
					?>
				</nav>

				<div class="devco-drawer__section">
					<h4>دسته‌بندی محصولات</h4>
					<?php devco_component_category_menu( array( 'label' => 'همه دسته‌ها' ) ); ?>
				</div>

				<div class="devco-drawer__actions">
					<?php if ( is_user_logged_in() ) : ?>
						<a class="devco-btn devco-btn--primary devco-btn--block" href="<?php echo esc_url( devco_dashboard_url() ); ?>"><?php devco_icon_e( 'dashboard', array( 'size' => 17 ) ); ?> داشبورد من</a>
					<?php else : ?>
						<button type="button" class="devco-btn devco-btn--primary devco-btn--block js-devco-auth-open"><?php devco_icon_e( 'user', array( 'size' => 17 ) ); ?> <?php echo esc_html( devco_opt( 'auth.button_text', 'ورود | ثبت‌نام' ) ); ?></button>
					<?php endif; ?>
					<?php if ( devco_opt( 'general.phone', '' ) ) : ?>
						<a class="devco-btn devco-btn--ghost devco-btn--block" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', devco_opt( 'general.phone', '' ) ) ); ?>"><?php devco_icon_e( 'phone', array( 'size' => 17 ) ); ?> <?php echo esc_html( devco_opt( 'general.phone', '' ) ); ?></a>
					<?php endif; ?>
				</div>

				<footer class="devco-drawer__footer">
					<?php devco_social_icons( array( 'class' => 'devco-socials--drawer' ) ); ?>
				</footer>
			</aside>
		</div>
		<?php
	}

	/**
	 * Cart drawer.
	 */
	public function cart_drawer() {
		if ( ! devco_is_woo() ) {
			return;
		}
		?>
		<div class="devco-cart-drawer" data-cart-drawer aria-hidden="true">
			<div class="devco-cart-drawer__backdrop" data-cart-close></div>
			<aside class="devco-cart-drawer__panel" role="dialog" aria-modal="true" aria-label="سبد خرید">
				<header class="devco-cart-drawer__head">
					<h3><?php devco_icon_e( 'bag', array( 'size' => 19 ) ); ?> سبد خرید شما</h3>
					<button type="button" class="devco-cart-drawer__close" data-cart-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>
				</header>
				<div class="devco-cart-drawer__body">
					<?php devco_mini_cart_content(); ?>
				</div>
			</aside>
		</div>
		<?php
	}

	/**
	 * Floating cart widget markup.
	 */
	public function floating_cart() {
		if ( ! devco_opt_bool( 'floating.cart', true ) || ! devco_is_woo() ) {
			return;
		}

		$count  = devco_cart_count();
		$style  = devco_opt( 'floating.cart_style', 'card' );
		$hide   = devco_opt_bool( 'floating.cart_hide_empty', false );
		$pos    = devco_opt( 'floating.cart_position', 'bottom-left' );

		$classes = array(
			'devco-floating-cart',
			'devco-floating-cart--' . $style,
			'devco-floating-cart--' . $pos,
		);

		if ( $hide && ! $count ) {
			$classes[] = 'is-hidden';
		}

		if ( ! $count ) {
			$classes[] = 'is-empty';
		}

		if ( devco_opt_bool( 'floating.cart_hide_mobile', true ) ) {
			$classes[] = 'hide-mobile';
		}

		do_action( 'devco_before_floating_cart' );

		printf(
			'<div class="%1$s" data-floating-cart data-style="%2$s"><button type="button" class="devco-fc js-devco-cart-open" aria-label="سبد خرید"><span class="devco-fc__inner">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $style )
		);

		do_action( 'devco_floating_cart_inner' );

		echo '</span></button></div>';

		do_action( 'devco_after_floating_cart' );
	}

	/**
	 * Inner content of the floating cart button (also used as an AJAX fragment).
	 */
	public static function floating_cart_inner() {
		$count = devco_cart_count();
		$total = devco_cart_subtotal();
		$style = devco_opt( 'floating.cart_style', 'card' );

		echo '<span class="devco-fc__icon">' . devco_icon( 'bag', array( 'size' => 22 ) ) . '<span class="devco-fc__count" data-cart-count>' . esc_html( devco_num( $count ) ) . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( in_array( $style, array( 'card', 'dock' ), true ) ) {
			echo '<span class="devco-fc__meta"><small>' . ( $count ? esc_html( devco_num( $count ) ) . ' کالا در سبد' : 'سبد خرید' ) . '</small><strong data-cart-total>' . wp_kses_post( $total ? $total : devco_price( 0 ) ) . '</strong>';

			echo self::shipping_progress(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</span>';
		}

		if ( 'dock' === $style && devco_opt_bool( 'floating.cart_items_preview', true ) && devco_is_woo() && WC()->cart && ! WC()->cart->is_empty() ) {
			echo '<span class="devco-fc__preview">';

			$shown = 0;
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( $shown >= 3 ) {
					break;
				}
				$product = $item['data'];
				if ( $product instanceof WC_Product ) {
					echo $product->get_image( 'devco-thumb' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$shown++;
				}
			}

			echo '</span>';
		}

		echo '<span class="devco-fc__arrow" aria-hidden="true">' . devco_icon( 'chevron-left', array( 'size' => 16 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Free shipping progress bar for the floating cart.
	 *
	 * @return void
	 */
	public static function shipping_progress() {
		$threshold = (float) devco_opt( 'floating.cart_threshold', 0 );

		if ( $threshold <= 0 || ! devco_is_woo() || ! WC()->cart ) {
			return;
		}

		$subtotal = (float) WC()->cart->get_subtotal();
		$percent  = min( 100, max( 0, ( $subtotal / $threshold ) * 100 ) );
		$remain   = max( 0, $threshold - $subtotal );

		echo '<span class="devco-fc__ship">';
		printf(
			'<span class="devco-fc__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%1$d"><i style="width:%1$d%%"></i></span>',
			(int) round( $percent )
		);

		if ( $remain > 0 ) {
			printf(
				'<span class="devco-fc__ship-note">%s</span>',
				wp_kses_post( sprintf( '%s تا ارسال رایگان', devco_price( $remain ) ) )
			);
		} else {
			printf(
				'<span class="devco-fc__ship-note is-free">%s</span>',
				esc_html( 'ارسال رایگان فعال شد 🎉' )
			);
		}

		echo '</span>';
	}

	/**
	 * Floating contact & social buttons.
	 */
	public function floating_actions() {
		if ( ! devco_opt_bool( 'floating.actions', true ) ) {
			return;
		}

		$layout   = devco_opt( 'floating.actions_layout', 'expand' );
		$style    = devco_opt( 'floating.actions_style', 'circle' );
		$position = devco_opt( 'floating.actions_position', 'bottom-right' );
		$phone    = devco_opt( 'floating.phone', devco_opt( 'general.phone', '' ) );
		$wa       = devco_opt( 'floating.whatsapp', '' );
		$tg       = devco_opt( 'floating.telegram', '' );
		$ig       = devco_opt( 'floating.instagram', '' );

		$actions = array();

		if ( $wa ) {
			$actions[] = array(
				'icon'  => 'whatsapp',
				'label' => 'واتساپ',
				'url'   => 'https://wa.me/' . rawurlencode( $wa ),
				'class' => 'is-whatsapp',
			);
		}

		if ( $tg ) {
			$actions[] = array(
				'icon'  => 'telegram',
				'label' => 'تلگرام',
				'url'   => 'https://t.me/' . rawurlencode( $tg ),
				'class' => 'is-telegram',
			);
		}

		if ( $ig ) {
			$actions[] = array(
				'icon'  => 'instagram',
				'label' => 'اینستاگرام',
				'url'   => $ig,
				'class' => 'is-instagram',
			);
		}

		if ( $phone && devco_opt_bool( 'floating.quick_call', true ) ) {
			$actions[] = array(
				'icon'  => 'phone',
				'label' => 'تماس',
				'url'   => 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ),
				'class' => 'is-phone',
			);
		}

		if ( ! $actions ) {
			return;
		}

		$actions = array_reverse( $actions );

		$classes = array(
			'devco-fab',
			'devco-fab--' . $layout,
			'devco-fab--' . $style,
			'devco-fab--' . $position,
		);

		if ( ! devco_opt_bool( 'floating.actions_mobile', true ) ) {
			$classes[] = 'hide-mobile';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-fab>
			<div class="devco-fab__list">
				<?php foreach ( $actions as $action ) : ?>
					<a class="devco-fab__item <?php echo esc_attr( $action['class'] ); ?>"
						href="<?php echo esc_url( $action['url'] ); ?>"
						target="_blank" rel="noopener nofollow"
						aria-label="<?php echo esc_attr( $action['label'] ); ?>">
						<span class="devco-fab__ico"><?php devco_icon_e( $action['icon'], array( 'size' => 20 ) ); ?></span>
						<span class="devco-fab__label"><?php echo esc_html( $action['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if ( 'expand' === $layout ) : ?>
				<button type="button" class="devco-fab__toggle" data-fab-toggle aria-label="راه‌های ارتباطی" aria-expanded="false">
					<?php devco_icon_e( 'headset', array( 'size' => 23 ) ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Auto-render the fitting room on configured pages.
	 */
	public function fitting_auto() {
		if ( ! devco_opt_bool( 'fitting.enabled', true ) ) {
			return;
		}

		$position = devco_opt( 'fitting.position', 'right' );

		if ( 'none' === $position ) {
			return;
		}

		$show_on = devco_opt( 'fitting.show_on', 'shop' );
		$show    = false;

		if ( 'all' === $show_on ) {
			$show = true;
		} elseif ( 'shop' === $show_on && devco_is_woo() && ( is_shop() || is_product_taxonomy() || is_product() ) ) {
			$show = true;
		} elseif ( 'product' === $show_on && devco_is_woo() && is_product() ) {
			$show = true;
		}

		if ( ! $show ) {
			return;
		}

		devco_fitting_trigger( array( 'float' => true ) );
		devco_fitting_room( array( 'side' => $position, 'product_id' => is_product() ? get_the_ID() : 0 ) );
	}

	/**
	 * Global modals: quick view, size chart, filters.
	 */
	public function modals() {
		?>
		<div class="devco-modal" data-quick-view aria-hidden="true">
			<div class="devco-modal__backdrop" data-modal-close></div>
			<div class="devco-modal__dialog devco-modal__dialog--wide" role="dialog" aria-modal="true" aria-label="نمایش سریع محصول">
				<button type="button" class="devco-modal__close" data-modal-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>
				<div class="devco-modal__body" data-quick-view-content>
					<div class="devco-modal__loading"><span class="devco-spinner"></span> در حال بارگذاری…</div>
				</div>
			</div>
		</div>

		<div class="devco-modal" data-size-chart aria-hidden="true">
			<div class="devco-modal__backdrop" data-modal-close></div>
			<div class="devco-modal__dialog" role="dialog" aria-modal="true" aria-label="راهنمای اندازه">
				<button type="button" class="devco-modal__close" data-modal-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>
				<div class="devco-modal__body">
					<h3 class="devco-modal__title"><?php devco_icon_e( 'ruler', array( 'size' => 20 ) ); ?> راهنمای اندازه</h3>
					<p class="devco-modal__hint">اندازه‌ها تقریبی و بر حسب سانتی‌متر است. برای اندازه دقیق‌تر از اتاق پرو یا سفارش دوخت استفاده کنید.</p>
					<table class="devco-size-table">
						<thead>
							<tr><th>سایز</th><th>دور سینه</th><th>دور کمر</th><th>دور باسن</th><th>قد آستین</th></tr>
						</thead>
						<tbody>
							<tr><td>S</td><td>۸۶–۹۰</td><td>۶۸–۷۲</td><td>۹۰–۹۴</td><td>۵۸</td></tr>
							<tr><td>M</td><td>۹۰–۹۶</td><td>۷۲–۷۸</td><td>۹۴–۱۰۰</td><td>۵۹</td></tr>
							<tr><td>L</td><td>۹۶–۱۰۲</td><td>۷۸–۸۶</td><td>۱۰۰–۱۰۶</td><td>۶۰</td></tr>
							<tr><td>XL</td><td>۱۰۲–۱۱۰</td><td>۸۶–۹۴</td><td>۱۰۶–۱۱۴</td><td>۶۱</td></tr>
							<tr><td>XXL</td><td>۱۱۰–۱۱۸</td><td>۹۴–۱۰۲</td><td>۱۱۴–۱۲۲</td><td>۶۲</td></tr>
						</tbody>
					</table>
					<div class="devco-modal__actions">
						<button type="button" class="devco-btn devco-btn--primary js-devco-fitting-open"><?php devco_icon_e( 'tshirt', array( 'size' => 17 ) ); ?> امتحان در اتاق پرو</button>
						<a class="devco-btn devco-btn--ghost" href="<?php echo esc_url( devco_opt( 'tailoring.page_id', 0 ) ? get_permalink( (int) devco_opt( 'tailoring.page_id', 0 ) ) : home_url( '/' ) ); ?>">سفارش دوخت اختصاصی</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

/**
 * Hook the floating cart inner markup to its action.
 */
add_action( 'devco_floating_cart_inner', array( 'DeVco_Floating', 'floating_cart_inner' ) );
