<?php
/**
 * WooCommerce integration.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Woo
 */
class DeVco_Woo {

	/**
	 * Instance.
	 *
	 * @var DeVco_Woo|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Woo
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
		// Layout wrappers.
		add_action( 'woocommerce_before_main_content', array( $this, 'shop_wrapper_start' ), 5 );
		add_action( 'woocommerce_after_main_content', array( $this, 'shop_wrapper_end' ), 50 );

		// Catalog.
		add_filter( 'loop_shop_columns', array( $this, 'loop_columns' ) );
		add_filter( 'loop_shop_per_page', array( $this, 'products_per_page' ), 20 );
		add_filter( 'woocommerce_output_related_products_args', array( $this, 'related_args' ) );
		add_filter( 'woocommerce_product_thumbnails_columns', array( $this, 'thumb_columns' ) );

		// Single product.
		add_filter( 'body_class', array( $this, 'single_body_class' ) );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'single_gallery_open' ), 1 );
		add_action( 'woocommerce_before_single_product_summary', array( $this, 'single_gallery_close' ), 25 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'single_badges' ), 4 );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'buy_now_button' ), 15 );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'extra_product_actions' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'trust_badges' ), 45 );
		add_action( 'wp_footer', array( $this, 'sticky_add_to_cart' ), 30 );

		// Cart & checkout.
		add_action( 'woocommerce_before_cart', array( $this, 'cart_header' ), 5 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'coupon_html' ), 10, 3 );
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'checkout_trust_note' ) );
		add_action( 'woocommerce_before_checkout_form', array( $this, 'checkout_steps' ), 8 );

		// AJAX.
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragments' ) );
		add_action( 'wp_ajax_devco_quick_view', array( $this, 'ajax_quick_view' ) );
		add_action( 'wp_ajax_nopriv_devco_quick_view', array( $this, 'ajax_quick_view' ) );
		add_action( 'wp_ajax_devco_live_search', array( $this, 'ajax_live_search' ) );
		add_action( 'wp_ajax_nopriv_devco_live_search', array( $this, 'ajax_live_search' ) );
		add_action( 'wp_ajax_devco_mini_cart', array( $this, 'ajax_mini_cart' ) );
		add_action( 'wp_ajax_nopriv_devco_mini_cart', array( $this, 'ajax_mini_cart' ) );
		add_action( 'wp_ajax_devco_buy_now', array( $this, 'ajax_buy_now' ) );
		add_action( 'wp_ajax_nopriv_devco_buy_now', array( $this, 'ajax_buy_now' ) );
		add_action( 'wp_ajax_devco_newsletter', array( $this, 'ajax_newsletter' ) );
		add_action( 'wp_ajax_nopriv_devco_newsletter', array( $this, 'ajax_newsletter' ) );

		// Checkout fields order.
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );

		// Breadcrumb removal (theme renders its own).
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'shop_toolbar' ), 15 );

		// Sale badge markup.
		add_filter( 'woocommerce_sale_flash', array( $this, 'sale_flash' ), 10, 3 );
	}

	/**
	 * Column count.
	 *
	 * @return int
	 */
	public function loop_columns() {
		return max( 2, min( 5, (int) devco_opt( 'shop.columns', 4 ) ) );
	}

	/**
	 * Products per page.
	 *
	 * @return int
	 */
	public function products_per_page() {
		return max( 4, (int) devco_opt( 'shop.per_page', 16 ) );
	}

	/**
	 * Related products args.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	public function related_args( $args ) {
		$count              = (int) devco_opt( 'product.related_count', 4 );
		$args['posts_per_page'] = $count;
		$args['columns']        = min( 4, max( 2, $count ) );

		return $args;
	}

	/**
	 * Thumbnail columns.
	 *
	 * @return int
	 */
	public function thumb_columns() {
		return 4;
	}

	/**
	 * Wrap the shop content.
	 */
	public function shop_wrapper_start() {
		$sidebar = devco_opt_bool( 'shop.sidebar', true ) && ! is_product();
		$pos     = devco_opt( 'shop.sidebar_position', 'right' );

		echo '<div class="' . esc_attr( devco_container_class() ) . '">';

		if ( is_shop() || is_product_taxonomy() ) {
			$banner = devco_opt( 'shop.banner_id', '' );

			echo '<div class="devco-shop-head">';
			if ( $banner ) {
				echo '<div class="devco-shop-head__banner"><img src="' . esc_url( $banner ) . '" alt="' . esc_attr( devco_opt( 'shop.title', '' ) ) . '" /></div>';
			}
			echo '<h1 class="devco-shop-head__title">' . esc_html( is_shop() ? devco_opt( 'shop.title', 'همه محصولات' ) : single_term_title( '', false ) ) . '</h1>';
			if ( devco_opt( 'shop.subtitle', '' ) ) {
				echo '<p class="devco-shop-head__subtitle">' . esc_html( devco_opt( 'shop.subtitle', '' ) ) . '</p>';
			}
			echo '</div>';
		}

		if ( ! is_product() ) {
			printf(
				'<div class="devco-shop-layout devco-shop-layout--sidebar-%1$s %2$s">',
				esc_attr( $pos ),
				$sidebar ? 'has-sidebar' : 'no-sidebar'
			);
		}

		echo '<main class="devco-shop-main" id="main">';
	}

	/**
	 * Close the shop wrappers.
	 */
	public function shop_wrapper_end() {
		echo '</main>';

		if ( ! is_product() ) {
			$sidebar = devco_opt_bool( 'shop.sidebar', true );

			if ( $sidebar && is_active_sidebar( 'sidebar-shop' ) ) {
				echo '<aside class="devco-shop-sidebar" aria-label="فیلترها">';
				dynamic_sidebar( 'sidebar-shop' );
				echo '</aside>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Shop toolbar (result count, ordering, filter toggle).
	 */
	public function shop_toolbar() {
		if ( ! devco_opt_bool( 'shop.filter_bar', true ) ) {
			return;
		}

		$mobile = wp_is_mobile() ? 'is-mobile' : '';
		?>
		<div class="devco-shop-toolbar <?php echo esc_attr( $mobile ); ?>">
			<div class="devco-shop-toolbar__count">
				<?php devco_icon_e( 'grid', array( 'size' => 16 ) ); ?>
				<span><?php echo esc_html( devco_num( wc_get_loop_prop( 'total' ) ) ); ?> محصول</span>
			</div>

			<div class="devco-shop-toolbar__actions">
				<button type="button" class="devco-shop-toolbar__filters js-devco-filters-open">
					<?php devco_icon_e( 'filter', array( 'size' => 16 ) ); ?>
					<span>فیلترها</span>
				</button>

				<?php woocommerce_catalog_ordering(); ?>

				<div class="devco-shop-toolbar__cols" aria-hidden="true">
					<?php foreach ( array( 2, 3, 4 ) as $col ) : ?>
						<button type="button" class="devco-col-btn <?php echo (int) devco_opt( 'shop.columns', 4 ) === $col ? 'is-active' : ''; ?>" data-cols="<?php echo (int) $col; ?>" aria-label="<?php echo esc_attr( $col . ' ستون' ); ?>">
							<?php for ( $i = 0; $i < $col; $i++ ) : ?>
								<span></span>
							<?php endfor; ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Single product body classes.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public function single_body_class( $classes ) {
		if ( is_product() ) {
			$classes[] = 'devco-gallery-' . devco_opt( 'product.gallery', 'right' );
			$classes[] = 'devco-tabs-' . devco_opt( 'product.tabs_style', 'accordion' );
		}

		return $classes;
	}

	/**
	 * Gallery opening wrapper.
	 */
	public function single_gallery_open() {
		$gallery = devco_opt( 'product.gallery', 'right' );
		$thumbs  = devco_opt( 'product.thumb_position', 'side' );

		echo '<div class="devco-product-gallery devco-product-gallery--' . esc_attr( $gallery ) . ' devco-product-gallery--thumbs-' . esc_attr( $thumbs ) . '">';
	}

	/**
	 * Gallery closing wrapper.
	 */
	public function single_gallery_close() {
		echo '<div class="devco-gallery-tools">';

		if ( devco_opt_bool( 'product.fitting_link', true ) ) {
			printf(
				'<button type="button" class="devco-gallery-tool js-devco-fitting-open">%1$s<span>پرو لباس</span></button>',
				devco_icon( 'tshirt', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		if ( devco_opt_bool( 'product.size_chart', true ) ) {
			printf(
				'<button type="button" class="devco-gallery-tool js-devco-size-chart">%1$s<span>راهنمای اندازه</span></button>',
				devco_icon( 'ruler', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Badges above the title.
	 */
	public function single_badges() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		echo '<div class="devco-product-badges">';

		if ( $product->is_on_sale() && $product->get_regular_price() ) {
			$percent = round( ( ( (float) $product->get_regular_price() - (float) $product->get_price() ) / (float) $product->get_regular_price() ) * 100 );
			printf( '<span class="devco-badge devco-badge--sale">%s%% تخفیف</span>', esc_html( devco_num( $percent ) ) );
		}

		if ( $product->is_featured() ) {
			echo '<span class="devco-badge devco-badge--featured">پیشنهاد ویژه</span>';
		}

		if ( ! $product->is_in_stock() ) {
			echo '<span class="devco-badge devco-badge--out">ناموجود</span>';
		}

		$cats = wc_get_product_category_list( $product->get_id(), ', ' );

		if ( $cats ) {
			echo '<span class="devco-badge devco-badge--cat">' . wp_kses_post( wp_strip_all_tags( $cats ) ) . '</span>';
		}

		echo '</div>';
	}

	/**
	 * "Buy now" button.
	 */
	public function buy_now_button() {
		global $product;

		if ( ! devco_opt_bool( 'product.buy_now', true ) || ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$skip = devco_opt_bool( 'product.buy_now_skip_cart', true ) ? '1' : '0';

		printf(
			'<button type="button" class="devco-buy-now button alt js-devco-buy-now" data-product-id="%1$d" data-skip-cart="%2$s">%3$s خرید فوری</button>',
			(int) $product->get_id(),
			esc_attr( $skip ),
			devco_icon( 'sparkle', array( 'size' => 17 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Extra product actions (whatsapp order, fitting room, wishlist).
	 */
	public function extra_product_actions() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		echo '<div class="devco-product-actions">';

		printf(
			'<button type="button" class="devco-product-action js-devco-wishlist" data-product-id="%1$d">%2$s<span>علاقه‌مندی</span></button>',
			(int) $product->get_id(),
			devco_icon( 'heart', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		if ( devco_opt_bool( 'product.fitting_link', true ) ) {
			printf(
				'<button type="button" class="devco-product-action js-devco-fitting-open" data-product-id="%1$d">%2$s<span>اتاق پرو</span></button>',
				(int) $product->get_id(),
				devco_icon( 'camera', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		$wa = devco_opt( 'floating.whatsapp', '' );

		if ( devco_opt_bool( 'product.whatsapp_order', true ) && $wa ) {
			$text = rawurlencode( 'سلام، برای محصول «' . $product->get_name() . '» سوال دارم: ' . $product->get_permalink() );
			printf(
				'<a class="devco-product-action devco-product-action--wa" href="https://wa.me/%1$s?text=%2$s" target="_blank" rel="noopener">%3$s<span>سفارش در واتساپ</span></a>',
				esc_attr( $wa ),
				$text,
				devco_icon( 'whatsapp', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</div>';
	}

	/**
	 * Trust badges under the summary.
	 */
	public function trust_badges() {
		if ( ! devco_opt_bool( 'product.trust_badges', true ) ) {
			return;
		}

		$items = devco_opt( 'product.trust_items', array() );

		if ( ! is_array( $items ) || ! $items ) {
			return;
		}

		echo '<div class="devco-trust-badges">';

		foreach ( $items as $item ) {
			printf(
				'<div class="devco-trust"><span class="devco-trust__icon">%1$s</span><span class="devco-trust__text"><strong>%2$s</strong><small>%3$s</small></span></div>',
				devco_icon( ! empty( $item['icon'] ) ? $item['icon'] : 'shield', array( 'size' => 20 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( isset( $item['title'] ) ? $item['title'] : '' ),
				esc_html( isset( $item['text'] ) ? $item['text'] : '' )
			);
		}

		echo '</div>';
	}

	/**
	 * Sticky add-to-cart bar.
	 */
	public function sticky_add_to_cart() {
		if ( ! is_product() || ! devco_opt_bool( 'product.sticky_atc', true ) ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}
		?>
		<div class="devco-sticky-atc" data-sticky-atc>
			<div class="<?php echo esc_attr( devco_container_class() ); ?>">
				<div class="devco-sticky-atc__inner">
					<div class="devco-sticky-atc__product">
						<?php echo $product->get_image( 'devco-thumb' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<div>
							<strong><?php echo esc_html( devco_trim( $product->get_name(), 8 ) ); ?></strong>
							<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						</div>
					</div>
					<div class="devco-sticky-atc__actions">
						<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
							<button type="button" class="devco-btn devco-btn--primary js-devco-quick-add" data-product-id="<?php echo (int) $product->get_id(); ?>">
								<?php devco_icon_e( 'cart', array( 'size' => 17 ) ); ?> افزودن به سبد
							</button>
						<?php else : ?>
							<a class="devco-btn devco-btn--primary" href="#devco-product-form">انتخاب گزینه‌ها</a>
						<?php endif; ?>
						<a class="devco-btn devco-btn--ghost" href="<?php echo esc_url( wc_get_cart_url() ); ?>">مشاهده سبد</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Cart page header.
	 */
	public function cart_header() {
		if ( ! devco_opt_bool( 'cart.cross_sell', true ) ) {
			return;
		}

		echo '<div class="devco-cart-progress">';
		$steps = array(
			array( 'label' => 'سبد خرید', 'active' => true, 'icon' => 'cart' ),
			array( 'label' => 'اطلاعات و پرداخت', 'active' => false, 'icon' => 'credit-card' ),
			array( 'label' => 'تایید سفارش', 'active' => false, 'icon' => 'check-badge' ),
		);

		foreach ( $steps as $step ) {
			printf(
				'<div class="devco-cart-progress__step %1$s"><span class="devco-cart-progress__icon">%2$s</span><span>%3$s</span></div>',
				$step['active'] ? 'is-active' : '',
				devco_icon( $step['icon'], array( 'size' => 17 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $step['label'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Checkout step indicator.
	 */
	public function checkout_steps() {
		if ( ! devco_opt_bool( 'checkout.steps', true ) || is_order_received_page() ) {
			return;
		}

		echo '<div class="devco-cart-progress devco-cart-progress--checkout">';
		$steps = array(
			array( 'label' => 'سبد خرید', 'active' => false, 'done' => true, 'icon' => 'cart' ),
			array( 'label' => 'اطلاعات و پرداخت', 'active' => true, 'icon' => 'credit-card' ),
			array( 'label' => 'تایید سفارش', 'active' => false, 'icon' => 'check-badge' ),
		);

		foreach ( $steps as $step ) {
			printf(
				'<div class="devco-cart-progress__step %1$s %2$s"><span class="devco-cart-progress__icon">%3$s</span><span>%4$s</span></div>',
				$step['active'] ? 'is-active' : '',
				! empty( $step['done'] ) ? 'is-done' : '',
				devco_icon( $step['icon'], array( 'size' => 17 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $step['label'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Trust note above the checkout button.
	 */
	public function checkout_trust_note() {
		$note = devco_opt( 'checkout.trust_note', '' );

		if ( $note ) {
			echo '<p class="devco-checkout-trust">' . devco_icon( 'lock', array( 'size' => 15 ) ) . '<span>' . esc_html( $note ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Coupon markup tweak.
	 *
	 * @param string $coupon_html HTML.
	 * @param array  $coupon Coupon data.
	 * @param string $discount_amount_html Amount html.
	 * @return string
	 */
	public function coupon_html( $coupon_html, $coupon, $discount_amount_html ) {
		return $coupon_html;
	}

	/**
	 * Checkout fields ordering.
	 *
	 * @param array $fields Fields.
	 * @return array
	 */
	public function checkout_fields( $fields ) {
		foreach ( array( 'billing', 'shipping' ) as $group ) {
			if ( isset( $fields[ $group ][ $group . '_phone' ] ) ) {
				$fields[ $group ][ $group . '_phone' ]['priority'] = 25;
			}
		}

		return $fields;
	}

	/**
	 * Sale flash markup.
	 *
	 * @param string     $html HTML.
	 * @param \WP_Post   $post Post.
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function sale_flash( $html, $post = null, $product = null ) {
		$product = $product instanceof WC_Product ? $product : ( $post ? wc_get_product( $post->ID ) : null );
		$label   = 'فروش ویژه';

		if ( $product && $product->get_regular_price() && (float) $product->get_regular_price() > 0 ) {
			$percent = round( ( ( (float) $product->get_regular_price() - (float) $product->get_price() ) / (float) $product->get_regular_price() ) * 100 );
			$label   = devco_num( $percent ) . '٪ تخفیف';
		}

		return '<span class="onsale devco-onsale devco-onsale--' . esc_attr( devco_opt( 'card.badge_style', 'pill' ) ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * AJAX fragments for cart counters.
	 *
	 * @param array $fragments Fragments.
	 * @return array
	 */
	public function cart_fragments( $fragments ) {
		$count = devco_cart_count();

		$fragments['[data-cart-count]'] = '<span class="devco-cart-btn__count" data-cart-count>' . esc_html( devco_num( $count ) ) . '</span>';
		$fragments['[data-cart-total]'] = '<strong data-cart-total>' . wp_kses_post( devco_cart_subtotal() ) . '</strong>';

		ob_start();
		devco_mini_cart_content();
		$fragments['.devco-cart-drawer__body'] = ob_get_clean();

		ob_start();
		do_action( 'devco_floating_cart_inner' );
		$fragments['.devco-floating-cart .devco-fc__inner'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * Quick view AJAX.
	 */
	public function ajax_quick_view() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'محصول پیدا نشد' ) );
		}

		global $post;
		$post = get_post( $product_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		ob_start();
		?>
		<div class="devco-quickview">
			<div class="devco-quickview__media">
				<?php echo $product->get_image( 'devco-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="devco-quickview__body">
				<h2 class="devco-quickview__title"><?php echo esc_html( $product->get_name() ); ?></h2>
				<div class="devco-quickview__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<?php if ( $product->get_short_description() ) : ?>
					<div class="devco-quickview__desc"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<?php endif; ?>
				<div class="devco-quickview__cart"><?php woocommerce_template_single_add_to_cart(); ?></div>
				<a class="devco-quickview__link" href="<?php echo esc_url( $product->get_permalink() ); ?>">مشاهده صفحه کامل محصول <?php devco_icon_e( 'arrow-left', array( 'size' => 15 ) ); ?></a>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		wp_reset_postdata();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Live search AJAX.
	 */
	public function ajax_live_search() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'html' => '' ) );
		}

		$products = wc_get_products(
			array(
				'limit'   => 6,
				'status'  => 'publish',
				's'       => $term,
			)
		);

		ob_start();

		if ( $products ) {
			echo '<div class="devco-live-search">';
			foreach ( $products as $product ) {
				echo '<a class="devco-live-search__item" href="' . esc_url( $product->get_permalink() ) . '">';
				echo '<span class="devco-live-search__thumb">' . $product->get_image( 'devco-thumb' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<span class="devco-live-search__info"><strong>' . esc_html( $product->get_name() ) . '</strong><small>' . wp_kses_post( $product->get_price_html() ) . '</small></span>';
				echo '</a>';
			}
			echo '<a class="devco-live-search__all" href="' . esc_url( add_query_arg( array( 's' => $term, 'post_type' => 'product' ), home_url( '/' ) ) ) . '">مشاهده همه نتایج</a>';
			echo '</div>';
		} else {
			echo '<p class="devco-live-search__empty">محصولی پیدا نشد.</p>';
		}

		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	/**
	 * Mini cart AJAX.
	 */
	public function ajax_mini_cart() {
		check_ajax_referer( 'devco_front', 'nonce' );

		ob_start();
		devco_mini_cart_content();
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'  => $html,
				'count' => devco_cart_count(),
			)
		);
	}

	/**
	 * Buy now AJAX.
	 */
	public function ajax_buy_now() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
		$quantity   = isset( $_POST['quantity'] ) ? max( 1, (int) $_POST['quantity'] ) : 1;
		$skip       = ! empty( $_POST['skip_cart'] );

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'محصول نامعتبر است' ) );
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( ! $added ) {
			wp_send_json_error( array( 'message' => 'افزودن به سبد ناموفق بود' ) );
		}

		wp_send_json_success(
			array(
				'redirect' => $skip ? wc_get_checkout_url() : wc_get_cart_url(),
				'count'    => devco_cart_count(),
			)
		);
	}

	/**
	 * Newsletter AJAX (stored as option list).
	 */
	public function ajax_newsletter() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'ایمیل نامعتبر است' ) );
		}

		$list   = get_option( 'devco_newsletter', array() );
		$list   = is_array( $list ) ? $list : array();
		$list[] = array(
			'email' => $email,
			'date'  => current_time( 'mysql' ),
		);

		update_option( 'devco_newsletter', array_slice( $list, -5000 ) );

		wp_send_json_success( array( 'message' => 'عضویت شما ثبت شد. سپاس!' ) );
	}
}

DeVco_Woo::instance();

/**
 * Render a product card.
 *
 * @param WC_Product|int $product Product or ID.
 * @param array          $args    Card args.
 */
function devco_product_card( $product, $args = array() ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'style'     => devco_opt( 'card.style', 'overlay' ),
			'hover'     => devco_opt( 'card.hover', 'zoom' ),
			'ratio'     => devco_opt( 'card.ratio', '3-4' ),
			'quick_add' => devco_opt_bool( 'card.quick_add', true ),
			'quick_view' => devco_opt_bool( 'card.quick_view', true ),
			'show_rating' => devco_opt_bool( 'card.show_rating', true ),
			'show_category' => devco_opt_bool( 'card.show_category', true ),
			'class'     => '',
		)
	);

	$id      = $product->get_id();
	$link    = $product->get_permalink();
	$on_sale = $product->is_on_sale();
	$classes = array(
		'devco-product-card',
		'devco-product-card--' . sanitize_html_class( $args['style'] ),
		'devco-product-card--hover-' . sanitize_html_class( $args['hover'] ),
		'devco-product-card--ratio-' . sanitize_html_class( $args['ratio'] ),
	);

	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}

	$thumb_id  = $product->get_image_id();
	$gallery   = $product->get_gallery_image_ids();
	$second_id = ! empty( $gallery[0] ) ? $gallery[0] : 0;

	echo '<article class="' . esc_attr( implode( ' ', $classes ) ) . '" data-product-id="' . (int) $id . '">';
	echo '<div class="devco-product-card__inner">';

	echo '<div class="devco-product-card__media">';
	echo '<a class="devco-product-card__link" href="' . esc_url( $link ) . '" aria-label="' . esc_attr( $product->get_name() ) . '">';
	echo $thumb_id ? wp_get_attachment_image( $thumb_id, 'devco-card', false, array( 'class' => 'devco-product-card__img', 'loading' => 'lazy' ) ) : wc_placeholder_img( 'devco-card', array( 'class' => 'devco-product-card__img' ) );

	if ( $second_id ) {
		echo wp_get_attachment_image( $second_id, 'devco-card', false, array( 'class' => 'devco-product-card__img devco-product-card__img--hover', 'loading' => 'lazy' ) );
	}

	echo '</a>';

	// Badges.
	echo '<div class="devco-product-card__badges">';

	if ( $on_sale && $product->get_regular_price() && (float) $product->get_regular_price() > 0 ) {
		$percent = round( ( ( (float) $product->get_regular_price() - (float) $product->get_price() ) / (float) $product->get_regular_price() ) * 100 );
		echo '<span class="devco-badge devco-badge--sale">' . esc_html( devco_num( $percent ) ) . '٪</span>';
	}

	if ( ! $product->is_in_stock() ) {
		echo '<span class="devco-badge devco-badge--out">ناموجود</span>';
	} elseif ( $product->is_featured() ) {
		echo '<span class="devco-badge devco-badge--featured">ویژه</span>';
	}

	echo '</div>';

	// Actions.
	echo '<div class="devco-product-card__actions">';

	if ( $args['quick_add'] && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
		printf(
			'<button type="button" class="devco-card-action devco-card-action--cart js-devco-quick-add" data-product-id="%1$d" aria-label="افزودن به سبد">%2$s</button>',
			(int) $id,
			devco_icon( 'bag', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	} else {
		printf(
			'<a class="devco-card-action devco-card-action--cart" href="%1$s" aria-label="مشاهده محصول">%2$s</a>',
			esc_url( $link ),
			devco_icon( 'bag', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	if ( $args['quick_view'] ) {
		printf(
			'<button type="button" class="devco-card-action js-devco-quick-view" data-product-id="%1$d" aria-label="نمایش سریع">%2$s</button>',
			(int) $id,
			devco_icon( 'eye', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	printf(
		'<button type="button" class="devco-card-action js-devco-wishlist" data-product-id="%1$d" aria-label="علاقه‌مندی">%2$s</button>',
		(int) $id,
		devco_icon( 'heart', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	echo '</div>';
	echo '</div>';

	echo '<div class="devco-product-card__body">';

	if ( $args['show_category'] ) {
		$cats = get_the_terms( $id, 'product_cat' );
		if ( $cats && ! is_wp_error( $cats ) ) {
			echo '<span class="devco-product-card__cat">' . esc_html( $cats[0]->name ) . '</span>';
		}
	}

	echo '<h3 class="devco-product-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $product->get_name() ) . '</a></h3>';

	if ( $args['show_rating'] && $product->get_average_rating() > 0 ) {
		echo '<div class="devco-product-card__rating">' . devco_stars( (float) $product->get_average_rating() ) . '<span>' . esc_html( devco_num( $product->get_review_count() ) ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '<div class="devco-product-card__price">' . wp_kses_post( $product->get_price_html() ) . '</div>';

	if ( 'editorial' === $args['style'] ) {
		printf(
			'<button type="button" class="devco-btn devco-btn--sm devco-btn--primary js-devco-quick-add" data-product-id="%1$d" %2$s>%3$s افزودن به سبد</button>',
			(int) $id,
			$product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ? '' : 'disabled',
			devco_icon( 'cart', array( 'size' => 15 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	echo '</div></div></article>';
}

/**
 * Mini cart content for drawers.
 */
function devco_mini_cart_content() {
	echo '<div class="devco-cart-drawer__inner widget_shopping_cart_content">';

	if ( ! devco_is_woo() || ! WC()->cart || WC()->cart->is_empty() ) {
		echo '<div class="devco-cart-empty">';
		echo '<span class="devco-cart-empty__icon">' . devco_icon( 'bag', array( 'size' => 34 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . esc_html( devco_opt( 'cart.empty_text', 'سبد خرید شما خالی است.' ) ) . '</p>';
		echo '<a class="devco-btn devco-btn--primary" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html( devco_opt( 'cart.empty_button', 'مشاهده محصولات' ) ) . '</a>';
		echo '</div>';
	} else {
		echo '<ul class="devco-cart-drawer__items">';

		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['data'];

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			echo '<li class="devco-cart-item">';
			echo '<a class="devco-cart-item__thumb" href="' . esc_url( $product->get_permalink() ) . '">' . $product->get_image( 'devco-thumb' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="devco-cart-item__info">';
			echo '<a class="devco-cart-item__title" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
			echo '<span class="devco-cart-item__qty">' . esc_html( devco_num( $item['quantity'] ) ) . ' × ' . wp_kses_post( WC()->cart->get_product_subtotal( $product, $item['quantity'] ) ) . '</span>';
			echo '</div>';
			printf(
				'<a class="devco-cart-item__remove" href="%1$s" aria-label="%2$s">%3$s</a>',
				esc_url( wc_get_cart_remove_url( $key ) ),
				esc_attr( 'حذف' ),
				devco_icon( 'close', array( 'size' => 15 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			echo '</li>';
		}

		echo '</ul>';

		echo '<div class="devco-cart-drawer__footer">';
		echo '<div class="devco-cart-drawer__total"><span>جمع سبد</span><strong>' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</strong></div>';
		echo '<a class="devco-btn devco-btn--primary devco-btn--block" href="' . esc_url( wc_get_checkout_url() ) . '">تکمیل خرید و پرداخت</a>';
		echo '<a class="devco-cart-drawer__link" href="' . esc_url( wc_get_cart_url() ) . '">مشاهده سبد خرید</a>';
		echo '</div>';
	}

	echo '</div>';
}
