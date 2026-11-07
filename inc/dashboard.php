<?php
/**
 * Customer dashboard, order tracking and invoices.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Dashboard
 */
class DeVco_Dashboard {

	/**
	 * Instance.
	 *
	 * @var DeVco_Dashboard|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Dashboard
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
		add_shortcode( 'devco_dashboard', array( __CLASS__, 'render' ) );
		add_action( 'wp_ajax_devco_track_order', array( __CLASS__, 'ajax_track_order' ) );
		add_action( 'wp_ajax_nopriv_devco_track_order', array( __CLASS__, 'ajax_track_order' ) );
		add_action( 'wp_ajax_devco_save_measurements', array( __CLASS__, 'ajax_save_measurements' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'account_menu_items' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_woo_account' ) );
	}

	/**
	 * Menu definitions.
	 *
	 * @return array
	 */
	public static function menu_items() {
		return array(
			'overview'   => array( 'label' => 'نمای کلی', 'icon' => 'dashboard' ),
			'orders'     => array( 'label' => 'سفارش‌های من', 'icon' => 'box' ),
			'track'      => array( 'label' => 'پیگیری سفارش', 'icon' => 'truck' ),
			'tailoring'  => array( 'label' => 'سفارش‌های دوخت', 'icon' => 'scissors' ),
			'invoices'   => array( 'label' => 'فاکتورها و پرداخت', 'icon' => 'invoice' ),
			'measurements' => array( 'label' => 'اندازه‌های من', 'icon' => 'ruler' ),
			'addresses'  => array( 'label' => 'آدرس‌ها', 'icon' => 'pin' ),
			'wishlist'   => array( 'label' => 'علاقه‌مندی‌ها', 'icon' => 'heart' ),
			'account'    => array( 'label' => 'اطلاعات حساب', 'icon' => 'user' ),
			'support'    => array( 'label' => 'پشتیبانی', 'icon' => 'headset' ),
		);
	}

	/**
	 * Enabled + ordered menu items.
	 *
	 * @return array
	 */
	public static function active_menu() {
		$all     = self::menu_items();
		$enabled = devco_opt( 'dashboard.menu', array() );

		if ( ! is_array( $enabled ) || ! $enabled ) {
			$enabled = array( 'overview', 'orders', 'track', 'tailoring', 'invoices', 'account' );
		}

		$menu = array();

		foreach ( $enabled as $key ) {
			if ( isset( $all[ $key ] ) ) {
				$menu[ $key ] = $all[ $key ];
			}
		}

		return $menu;
	}

	/**
	 * Current tab.
	 *
	 * @return string
	 */
	protected static function current_tab() {
		$menu = self::active_menu();
		$tab  = isset( $_GET['devco_tab'] ) ? sanitize_key( wp_unslash( $_GET['devco_tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return isset( $menu[ $tab ] ) ? $tab : ( isset( $menu['overview'] ) ? 'overview' : array_key_first( $menu ) );
	}

	/**
	 * Redirect the default WooCommerce account page to the DeVco dashboard when enabled.
	 */
	public static function maybe_redirect_woo_account() {
		if ( ! devco_is_woo() || ! devco_opt_bool( 'dashboard.enabled', true ) ) {
			return;
		}

		$page_id = (int) devco_opt( 'dashboard.page_id', 0 );

		if ( ! $page_id || is_user_logged_in() === false ) {
			return;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_page( $page_id ) && ! is_wc_endpoint_url() && empty( $_GET['devco_tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( devco_dashboard_url() );
			exit;
		}
	}

	/**
	 * Keep native Woo items in the account menu, but label them in Persian.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	public function account_menu_items( $items ) {
		return $items;
	}

	/**
	 * Render the dashboard.
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! is_user_logged_in() ) {
			ob_start();
			echo '<div class="devco-panel devco-panel--auth-required">';
			echo '<h2>برای دیدن داشبورد وارد شوید</h2>';
			echo '<p>با شماره موبایل خود در چند ثانیه وارد شوید.</p>';
			echo '<button type="button" class="devco-btn devco-btn--primary js-devco-auth-open">ورود / ثبت‌نام</button>';
			echo '</div>';
			return ob_get_clean();
		}

		$user = wp_get_current_user();
		$menu = self::active_menu();
		$tab  = self::current_tab();
		$style = devco_opt( 'dashboard.style', 'light' );
		$layout = devco_opt( 'dashboard.layout', 'sidebar' );

		ob_start();
		?>
		<div class="devco-dash devco-dash--<?php echo esc_attr( $style ); ?> devco-dash--<?php echo esc_attr( $layout ); ?>" data-dashboard>
			<aside class="devco-dash__side">
				<div class="devco-dash__profile">
					<?php echo get_avatar( $user->ID, 64, '', '', array( 'class' => 'devco-avatar' ) ); ?>
					<div class="devco-dash__profile-info">
						<strong><?php echo esc_html( $user->display_name ); ?></strong>
						<span><?php echo esc_html( $user->user_email ? $user->user_email : get_user_meta( $user->ID, 'devco_phone', true ) ); ?></span>
					</div>
				</div>

				<nav class="devco-dash__nav" aria-label="منوی داشبورد">
					<?php foreach ( $menu as $key => $item ) : ?>
						<a class="devco-dash__nav-item <?php echo $key === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( devco_dashboard_url( $key ) ); ?>">
							<?php devco_icon_e( $item['icon'], array( 'size' => 19 ) ); ?>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</a>
					<?php endforeach; ?>

					<a class="devco-dash__nav-item devco-dash__nav-item--logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
						<?php devco_icon_e( 'logout', array( 'size' => 19 ) ); ?>
						<span>خروج از حساب</span>
					</a>
				</nav>

				<?php if ( devco_opt( 'dashboard.support_phone', '' ) ) : ?>
					<div class="devco-dash__support">
						<span><?php devco_icon_e( 'headset', array( 'size' => 18 ) ); ?> پشتیبانی</span>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', devco_opt( 'dashboard.support_phone', '' ) ) ); ?>"><?php echo esc_html( devco_opt( 'dashboard.support_phone', '' ) ); ?></a>
					</div>
				<?php endif; ?>
			</aside>

			<div class="devco-dash__main">
				<header class="devco-dash__head">
					<div>
						<span class="devco-dash__eyebrow"><?php echo esc_html( devco_opt( 'dashboard.welcome', 'خوش آمدید' ) ); ?></span>
						<h1 class="devco-dash__title"><?php echo esc_html( $menu[ $tab ]['label'] ); ?></h1>
					</div>
					<div class="devco-dash__head-actions">
						<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php devco_icon_e( 'home', array( 'size' => 16 ) ); ?> فروشگاه</a>
						<a class="devco-btn devco-btn--sm devco-btn--primary" href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>"><?php devco_icon_e( 'plus', array( 'size' => 16 ) ); ?> سفارش دوخت</a>
					</div>
				</header>

				<div class="devco-dash__body">
					<?php self::render_tab( $tab, $user ); ?>
				</div>
			</div>

			<?php if ( devco_opt_bool( 'dashboard.mobile_nav', true ) ) : ?>
				<nav class="devco-dash__mobile-nav" aria-label="ناوبری داشبورد">
					<?php
					$count = 0;
					foreach ( $menu as $key => $item ) :
						if ( $count >= 4 && 'overview' !== $key ) {
							break;
						}
						if ( $count >= 5 ) {
							break;
						}
						?>
						<a class="devco-dash__mobile-item <?php echo $key === $tab ? 'is-active' : ''; ?>" href="<?php echo esc_url( devco_dashboard_url( $key ) ); ?>">
							<?php devco_icon_e( $item['icon'], array( 'size' => 20 ) ); ?>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</a>
						<?php
						$count++;
					endforeach;
					?>
				</nav>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a dashboard tab.
	 *
	 * @param string  $tab  Tab.
	 * @param WP_User $user User.
	 */
	public static function render_tab( $tab, $user ) {
		switch ( $tab ) {
			case 'orders':
				self::tab_orders( $user );
				break;
			case 'track':
				self::tab_track( $user );
				break;
			case 'tailoring':
				self::tab_tailoring( $user );
				break;
			case 'invoices':
				self::tab_invoices( $user );
				break;
			case 'measurements':
				self::tab_measurements( $user );
				break;
			case 'addresses':
				self::tab_addresses( $user );
				break;
			case 'wishlist':
				self::tab_wishlist( $user );
				break;
			case 'account':
				self::tab_account( $user );
				break;
			case 'support':
				self::tab_support( $user );
				break;
			default:
				self::tab_overview( $user );
		}
	}

	/**
	 * Customer orders.
	 *
	 * @param int $user_id User.
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function user_orders( $user_id, $limit = 20 ) {
		if ( ! devco_is_woo() ) {
			return array();
		}

		return wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => $limit,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
	}

	/**
	 * Overview tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_overview( $user ) {
		$orders   = self::user_orders( $user->ID, 5 );
		$requests = get_posts(
			array(
				'post_type'      => 'devco_trequest',
				'author'         => $user->ID,
				'posts_per_page' => 5,
				'post_status'    => 'publish',
			)
		);
		$invoices = get_posts(
			array(
				'post_type'      => 'devco_invoice',
				'posts_per_page' => 20,
				'post_status'    => 'publish',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_devco_user_id',
						'value' => $user->ID,
					),
				),
			)
		);

		$spent = 0;
		foreach ( $orders as $order ) {
			$spent += (float) $order->get_total();
		}
		?>
		<div class="devco-dash__stats">
			<div class="devco-dash__stat">
				<span class="devco-dash__stat-icon"><?php devco_icon_e( 'box', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( count( $orders ) ) ); ?></strong><span>سفارش‌ها</span></div>
			</div>
			<div class="devco-dash__stat">
				<span class="devco-dash__stat-icon"><?php devco_icon_e( 'scissors', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( count( $requests ) ) ); ?></strong><span>سفارش دوخت</span></div>
			</div>
			<div class="devco-dash__stat">
				<span class="devco-dash__stat-icon"><?php devco_icon_e( 'invoice', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( count( $invoices ) ) ); ?></strong><span>فاکتور</span></div>
			</div>
			<div class="devco-dash__stat devco-dash__stat--accent">
				<span class="devco-dash__stat-icon"><?php devco_icon_e( 'wallet', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo wp_kses_post( devco_is_woo() ? wc_price( $spent ) : devco_price( $spent ) ); ?></strong><span>مجموع خریدها</span></div>
			</div>
		</div>

		<div class="devco-dash__grid">
			<section class="devco-panel">
				<header class="devco-panel__head">
					<h2><?php devco_icon_e( 'box', array( 'size' => 18 ) ); ?> آخرین سفارش‌ها</h2>
					<a href="<?php echo esc_url( devco_dashboard_url( 'orders' ) ); ?>">همه سفارش‌ها</a>
				</header>

				<?php if ( $orders ) : ?>
					<ul class="devco-dash__orders">
						<?php foreach ( $orders as $order ) : ?>
							<li>
								<a class="devco-dash__order" href="<?php echo esc_url( devco_dashboard_url( 'track' ) . '&order=' . $order->get_id() ); ?>">
									<span class="devco-dash__order-id">#<?php echo esc_html( devco_num( $order->get_order_number() ) ); ?></span>
									<span class="devco-dash__order-meta">
										<b><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></b>
										<small><?php echo wp_kses_post( $order->get_item_count() . ' کالا' ); ?></small>
									</span>
									<span class="devco-dash__status devco-dash__status--<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
									<span class="devco-dash__order-total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="devco-panel__empty">هنوز سفارشی ثبت نکرده‌اید.</p>
				<?php endif; ?>
			</section>

			<section class="devco-panel">
				<header class="devco-panel__head">
					<h2><?php devco_icon_e( 'scissors', array( 'size' => 18 ) ); ?> سفارش‌های دوخت</h2>
					<a href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>">مشاهده همه</a>
				</header>

				<?php if ( $requests ) : ?>
					<ul class="devco-dash__orders">
						<?php foreach ( $requests as $request ) : ?>
							<?php $status = get_post_meta( $request->ID, '_devco_status', true ); ?>
							<li>
								<a class="devco-dash__order" href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) . '&request=' . $request->ID ); ?>">
									<span class="devco-dash__order-id">#<?php echo esc_html( devco_num( $request->ID ) ); ?></span>
									<span class="devco-dash__order-meta"><b><?php echo esc_html( get_the_title( $request ) ); ?></b><small><?php echo esc_html( get_the_date( '', $request ) ); ?></small></span>
									<span class="devco-dash__status"><?php echo esc_html( DeVco_Tailoring::status_label( $status ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="devco-panel__empty">درخواست دوختی ثبت نشده است.</p>
					<a class="devco-btn devco-btn--sm devco-btn--primary" href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>">ثبت سفارش دوخت</a>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Orders tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_orders( $user ) {
		$orders = self::user_orders( $user->ID, 30 );
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'box', array( 'size' => 18 ) ); ?> سفارش‌های من</h2>
				<a href="<?php echo esc_url( devco_dashboard_url( 'track' ) ); ?>">پیگیری سفارش</a>
			</header>

			<?php if ( ! $orders ) : ?>
				<p class="devco-panel__empty">هنوز سفارشی ندارید. <a href="<?php echo esc_url( devco_is_woo() ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">شروع خرید</a></p>
			<?php else : ?>
				<div class="devco-table-wrap">
					<table class="devco-table">
						<thead>
							<tr><th>سفارش</th><th>تاریخ</th><th>وضعیت</th><th>مبلغ</th><th></th></tr>
						</thead>
						<tbody>
							<?php foreach ( $orders as $order ) : ?>
								<tr>
									<td>#<?php echo esc_html( devco_num( $order->get_order_number() ) ); ?></td>
									<td><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
									<td><span class="devco-pill devco-pill--<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span></td>
									<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
									<td>
										<a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( devco_dashboard_url( 'track' ) . '&order=' . $order->get_id() ); ?>">جزئیات</a>
										<?php if ( $order->needs_payment() ) : ?>
											<a class="devco-btn devco-btn--xs devco-btn--primary" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">پرداخت</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Render the order timeline.
	 *
	 * @param WC_Order $order Order.
	 */
	protected static function order_timeline( $order ) {
		$steps   = devco_opt( 'track.statuses', array() );
		$current = $order->get_status();
		$reached = array();

		if ( $order->is_paid() ) {
			$reached[] = 'processing';
		}

		$flow = array( 'pending', 'processing', 'ready', 'shipped', 'completed' );
		$index = array_search( $current, $flow, true );
		$index = false === $index ? 0 : $index;

		foreach ( $flow as $i => $status ) {
			if ( $i <= $index ) {
				$reached[] = $status;
			}
		}

		echo '<ol class="devco-timeline">';

		foreach ( (array) $steps as $step ) {
			$status = isset( $step['status'] ) ? $step['status'] : '';
			$done   = in_array( $status, $reached, true );
			$active = $status === $current;

			printf(
				'<li class="devco-timeline__step %1$s %2$s"><span class="devco-timeline__dot">%3$s</span><span class="devco-timeline__label">%4$s</span></li>',
				$done ? 'is-done' : '',
				$active ? 'is-active' : '',
				devco_icon( ! empty( $step['icon'] ) ? $step['icon'] : 'check', array( 'size' => 15 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( isset( $step['label'] ) ? $step['label'] : $status )
			);
		}

		echo '</ol>';
	}

	/**
	 * Track tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_track( $user ) {
		$order_id = isset( $_GET['order'] ) ? (int) $_GET['order'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = $order_id && devco_is_woo() ? wc_get_order( $order_id ) : false;

		if ( $order && (int) $order->get_customer_id() !== (int) $user->ID && ! current_user_can( 'manage_woocommerce' ) ) {
			$order = false;
		}
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'truck', array( 'size' => 18 ) ); ?> پیگیری سفارش</h2>
			</header>

			<form class="devco-track-form" data-track-form>
				<div class="devco-track-form__row">
					<label>
						<span>شماره سفارش</span>
						<input type="text" name="order_id" placeholder="مثلاً 1024" value="<?php echo esc_attr( $order_id ? $order_id : '' ); ?>" required />
					</label>
					<label>
						<span>شماره موبایل یا ایمیل</span>
						<input type="text" name="contact" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required />
					</label>
					<button type="submit" class="devco-btn devco-btn--primary"><?php devco_icon_e( 'search', array( 'size' => 16 ) ); ?> پیگیری</button>
				</div>
				<p class="devco-track-form__message" data-track-message role="status"></p>
			</form>

			<?php if ( $order ) : ?>
				<div class="devco-track-result">
					<div class="devco-track-result__head">
						<div>
							<strong>سفارش #<?php echo esc_html( devco_num( $order->get_order_number() ) ); ?></strong>
							<span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
						</div>
						<span class="devco-pill devco-pill--<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
					</div>

					<?php self::order_timeline( $order ); ?>

					<ul class="devco-track-result__items">
						<?php foreach ( $order->get_items() as $item ) : ?>
							<li>
								<span><?php echo esc_html( $item->get_name() ); ?> × <?php echo esc_html( devco_num( $item->get_quantity() ) ); ?></span>
								<b><?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></b>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ( $order->get_formatted_shipping_address() ) : ?>
						<div class="devco-track-result__address">
							<strong>آدرس ارسال</strong>
							<p><?php echo wp_kses_post( $order->get_formatted_shipping_address() ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * AJAX order tracking.
	 */
	public static function ajax_track_order() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$order_id = isset( $_POST['order_id'] ) ? (int) preg_replace( '/[^0-9]/', '', wp_unslash( $_POST['order_id'] ) ) : 0;
		$contact  = isset( $_POST['contact'] ) ? sanitize_text_field( wp_unslash( $_POST['contact'] ) ) : '';

		if ( ! devco_is_woo() || ! $order_id || ! $contact ) {
			wp_send_json_error( array( 'message' => 'شماره سفارش و اطلاعات تماس را وارد کنید.' ) );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'سفارشی با این شماره پیدا نشد.' ) );
		}

		$phone = DeVco_SMS::normalize( $order->get_billing_phone() );
		$input = DeVco_SMS::normalize( $contact );
		$email = strtolower( $order->get_billing_email() );

		if ( ! ( $phone && $phone === $input ) && ! ( $email && $email === strtolower( $contact ) ) ) {
			wp_send_json_error( array( 'message' => 'اطلاعات تماس با سفارش مطابقت ندارد.' ) );
		}

		ob_start();
		?>
		<div class="devco-track-result">
			<div class="devco-track-result__head">
				<div>
					<strong>سفارش #<?php echo esc_html( devco_num( $order->get_order_number() ) ); ?></strong>
					<span><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
				</div>
				<span class="devco-pill devco-pill--<?php echo esc_attr( $order->get_status() ); ?>"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span>
			</div>
			<?php self::order_timeline( $order ); ?>
			<ul class="devco-track-result__items">
				<?php foreach ( $order->get_items() as $item ) : ?>
					<li><span><?php echo esc_html( $item->get_name() ); ?> × <?php echo esc_html( devco_num( $item->get_quantity() ) ); ?></span><b><?php echo wp_kses_post( wc_price( $item->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></b></li>
				<?php endforeach; ?>
			</ul>
			<p class="devco-track-result__total">مبلغ کل: <strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong></p>
		</div>
		<?php

		wp_send_json_success( array( 'html' => ob_get_clean() ) );
	}

	/**
	 * Tailoring tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_tailoring( $user ) {
		$request_id = isset( $_GET['request'] ) ? (int) $_GET['request'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_id    = (int) devco_opt( 'tailoring.page_id', 0 );

		if ( $request_id ) {
			$request = get_post( $request_id );

			if ( $request && ( (int) $request->post_author === (int) $user->ID || current_user_can( 'manage_woocommerce' ) ) ) {
				self::render_request_detail( $request );
				return;
			}
		}

		$requests = get_posts(
			array(
				'post_type'      => 'devco_trequest',
				'author'         => $user->ID,
				'posts_per_page' => 30,
				'post_status'    => 'publish',
			)
		);
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'scissors', array( 'size' => 18 ) ); ?> سفارش‌های دوخت</h2>
				<?php if ( $page_id ) : ?>
					<a class="devco-btn devco-btn--sm devco-btn--primary" href="<?php echo esc_url( get_permalink( $page_id ) ); ?>"><?php devco_icon_e( 'plus', array( 'size' => 15 ) ); ?> سفارش جدید</a>
				<?php endif; ?>
			</header>

			<?php if ( ! $requests ) : ?>
				<div class="devco-panel__empty">
					<p>هنوز سفارش دوختی ثبت نکرده‌اید. اندازه‌های خود را روی مانکن سه‌بعدی ثبت کنید و طرح دلخواهتان را بفرستید.</p>
					<?php if ( $page_id ) : ?>
						<a class="devco-btn devco-btn--primary" href="<?php echo esc_url( get_permalink( $page_id ) ); ?>">شروع سفارش دوخت</a>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="devco-dash__cards">
					<?php foreach ( $requests as $request ) : ?>
						<?php
						$status   = get_post_meta( $request->ID, '_devco_status', true );
						$invoice  = (int) get_post_meta( $request->ID, '_devco_invoice_id', true );
						$statuses = DeVco_Tailoring::statuses();
						$color    = isset( $statuses[ $status ]['color'] ) ? $statuses[ $status ]['color'] : '#64748b';
						?>
						<article class="devco-tcard" style="--devco-tcard-color:<?php echo esc_attr( $color ); ?>">
							<header class="devco-tcard__head">
								<span class="devco-tcard__id">#<?php echo esc_html( devco_num( $request->ID ) ); ?></span>
								<span class="devco-tcard__badge"><?php echo esc_html( DeVco_Tailoring::status_label( $status ) ); ?></span>
							</header>
							<h3 class="devco-tcard__title"><?php echo esc_html( get_the_title( $request ) ); ?></h3>
							<p class="devco-tcard__date"><?php echo esc_html( get_the_date( '', $request ) ); ?></p>
							<div class="devco-tcard__actions">
								<a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) . '&request=' . $request->ID ); ?>">جزئیات و مراحل</a>
								<?php if ( $invoice ) : ?>
									<a class="devco-btn devco-btn--xs devco-btn--primary" href="<?php echo esc_url( devco_dashboard_url( 'invoices' ) . '&invoice=' . $invoice ); ?>">فاکتور</a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Single request detail with timeline.
	 *
	 * @param WP_Post $request Request.
	 */
	protected static function render_request_detail( $request ) {
		$status    = get_post_meta( $request->ID, '_devco_status', true );
		$values    = get_post_meta( $request->ID, '_devco_values', true );
		$measures  = get_post_meta( $request->ID, '_devco_measurements', true );
		$files     = get_post_meta( $request->ID, '_devco_files', true );
		$timeline  = get_post_meta( $request->ID, '_devco_timeline', true );
		$invoice   = (int) get_post_meta( $request->ID, '_devco_invoice_id', true );
		$fields    = DeVco_Tailoring::form_fields( (int) get_post_meta( $request->ID, '_devco_form_id', true ) );
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'scissors', array( 'size' => 18 ) ); ?> <?php echo esc_html( get_the_title( $request ) ); ?></h2>
				<a href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>"><?php devco_icon_e( 'arrow-right', array( 'size' => 15 ) ); ?> بازگشت</a>
			</header>

			<div class="devco-request">
				<div class="devco-request__main">
					<div class="devco-request__status">
						<span class="devco-pill devco-pill--tailoring"><?php echo esc_html( DeVco_Tailoring::status_label( $status ) ); ?></span>
						<span><?php echo esc_html( get_the_date( '', $request ) ); ?></span>
					</div>

					<?php if ( is_array( $timeline ) && $timeline ) : ?>
						<ol class="devco-timeline devco-timeline--vertical">
							<?php foreach ( array_reverse( $timeline ) as $entry ) : ?>
								<li class="devco-timeline__step is-done">
									<span class="devco-timeline__dot"><?php devco_icon_e( 'check', array( 'size' => 13 ) ); ?></span>
									<span class="devco-timeline__label">
										<strong><?php echo esc_html( DeVco_Tailoring::status_label( $entry['status'] ) ); ?></strong>
										<small><?php echo esc_html( $entry['time'] ); ?></small>
										<?php if ( ! empty( $entry['note'] ) ) : ?>
											<em><?php echo esc_html( $entry['note'] ); ?></em>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>

					<?php if ( is_array( $values ) && $values ) : ?>
						<h3 class="devco-request__subtitle">مشخصات درخواست</h3>
						<ul class="devco-request__values">
							<?php foreach ( $values as $key => $value ) : ?>
								<li><span><?php echo esc_html( self::field_label( $key, $fields ) ); ?></span><b><?php echo esc_html( is_array( $value ) ? implode( '، ', $value ) : $value ); ?></b></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( is_array( $measures ) && $measures ) : ?>
						<h3 class="devco-request__subtitle">اندازه‌های ثبت‌شده</h3>
						<div class="devco-request__measures">
							<?php foreach ( $measures as $key => $value ) : ?>
								<span><?php echo esc_html( self::measure_label( $key ) ); ?>: <b><?php echo esc_html( devco_num( $value ) ); ?></b></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( is_array( $files ) && $files ) : ?>
						<h3 class="devco-request__subtitle">طرح‌های ارسالی</h3>
						<div class="devco-request__files">
							<?php foreach ( $files as $file_id ) : ?>
								<a href="<?php echo esc_url( wp_get_attachment_url( $file_id ) ); ?>" target="_blank" rel="noopener">
									<?php echo wp_get_attachment_image( $file_id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span>مشاهده فایل</span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

				<aside class="devco-request__side">
					<?php if ( $invoice ) : ?>
						<?php self::render_invoice_box( $invoice ); ?>
					<?php else : ?>
						<div class="devco-panel devco-panel--muted">
							<h4>در انتظار قیمت‌گذاری</h4>
							<p>پس از بررسی توسط تیم دیوکو، فاکتور در همین صفحه نمایش داده می‌شود و پیامک اطلاع‌رسانی ارسال می‌گردد.</p>
						</div>
					<?php endif; ?>

					<div class="devco-panel devco-panel--muted">
						<h4>پشتیبانی دوخت</h4>
						<p>سوالی دارید؟ با پشتیبانی تماس بگیرید.</p>
						<?php if ( devco_opt( 'dashboard.support_phone', '' ) ) : ?>
							<a class="devco-btn devco-btn--sm devco-btn--ghost" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', devco_opt( 'dashboard.support_phone', '' ) ) ); ?>"><?php devco_icon_e( 'phone', array( 'size' => 15 ) ); ?> تماس</a>
						<?php endif; ?>
					</div>
				</aside>
			</div>
		</section>
		<?php
	}

	/**
	 * Field label from the builder schema.
	 *
	 * @param string $key    Field key.
	 * @param array  $fields Fields.
	 * @return string
	 */
	protected static function field_label( $key, $fields ) {
		foreach ( (array) $fields as $field ) {
			if ( isset( $field['id'] ) && $field['id'] === $key ) {
				return $field['label'];
			}
		}

		return $key;
	}

	/**
	 * Measurement label.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	protected static function measure_label( $key ) {
		foreach ( devco_measurements() as $measure ) {
			if ( $measure['key'] === $key ) {
				return $measure['label'];
			}
		}

		return $key;
	}

	/**
	 * Invoices tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_invoices( $user ) {
		$invoice_id = isset( $_GET['invoice'] ) ? (int) $_GET['invoice'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $invoice_id ) {
			self::render_invoice_box( $invoice_id, true );
			return;
		}

		$invoices = get_posts(
			array(
				'post_type'      => 'devco_invoice',
				'posts_per_page' => 50,
				'post_status'    => 'publish',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_devco_user_id',
						'value' => $user->ID,
					),
				),
			)
		);
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'invoice', array( 'size' => 18 ) ); ?> فاکتورها و پرداخت‌ها</h2>
			</header>

			<?php if ( ! $invoices ) : ?>
				<p class="devco-panel__empty">فاکتوری برای شما صادر نشده است.</p>
			<?php else : ?>
				<div class="devco-dash__cards">
					<?php foreach ( $invoices as $invoice ) : ?>
						<?php
						$status = get_post_meta( $invoice->ID, '_devco_status', true );
						$total  = (float) get_post_meta( $invoice->ID, '_devco_total', true );
						?>
						<article class="devco-tcard devco-tcard--invoice">
							<header class="devco-tcard__head">
								<span class="devco-tcard__id">#<?php echo esc_html( devco_num( $invoice->ID ) ); ?></span>
								<span class="devco-tcard__badge devco-tcard__badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( DeVco_Tailoring::invoice_status_label( $status ) ); ?></span>
							</header>
							<h3 class="devco-tcard__title"><?php echo esc_html( get_the_title( $invoice ) ); ?></h3>
							<p class="devco-tcard__price"><?php echo esc_html( devco_price( $total ) ); ?></p>
							<div class="devco-tcard__actions">
								<a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( devco_dashboard_url( 'invoices' ) . '&invoice=' . $invoice->ID ); ?>">مشاهده فاکتور</a>
								<?php if ( 'unpaid' === $status ) : ?>
									<a class="devco-btn devco-btn--xs devco-btn--primary" href="<?php echo esc_url( DeVco_Tailoring::invoice_pay_url( $invoice->ID ) ); ?>">پرداخت آنلاین</a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Render an invoice box.
	 *
	 * @param int  $invoice_id Invoice.
	 * @param bool $extended   Show back link.
	 */
	protected static function render_invoice_box( $invoice_id, $extended = false ) {
		$invoice = get_post( $invoice_id );

		if ( ! $invoice || 'devco_invoice' !== $invoice->post_type ) {
			echo '<p class="devco-panel__empty">فاکتور پیدا نشد.</p>';
			return;
		}

		$user_id = (int) get_post_meta( $invoice_id, '_devco_user_id', true );

		if ( (int) get_current_user_id() !== $user_id && ! current_user_can( 'manage_woocommerce' ) ) {
			echo '<p class="devco-panel__empty">دسترسی ندارید.</p>';
			return;
		}

		$items  = get_post_meta( $invoice_id, '_devco_items', true );
		$total  = (float) get_post_meta( $invoice_id, '_devco_total', true );
		$status = get_post_meta( $invoice_id, '_devco_status', true );
		$note   = get_post_meta( $invoice_id, '_devco_note', true );
		?>
		<div class="devco-invoice">
			<div class="devco-invoice__head">
				<div>
					<span class="devco-invoice__label">فاکتور</span>
					<h3>#<?php echo esc_html( devco_num( $invoice_id ) ); ?></h3>
					<p><?php echo esc_html( get_the_date( '', $invoice ) ); ?></p>
				</div>
				<span class="devco-pill devco-pill--invoice devco-pill--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( DeVco_Tailoring::invoice_status_label( $status ) ); ?></span>
			</div>

			<table class="devco-table devco-invoice__table">
				<thead><tr><th>شرح</th><th>تعداد</th><th>قیمت واحد</th><th>جمع</th></tr></thead>
				<tbody>
					<?php foreach ( (array) $items as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['title'] ); ?></td>
							<td><?php echo esc_html( devco_num( $item['qty'] ) ); ?></td>
							<td><?php echo esc_html( devco_price( $item['price'] ) ); ?></td>
							<td><?php echo esc_html( devco_price( (float) $item['price'] * (int) $item['qty'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr><td colspan="3">مبلغ قابل پرداخت</td><td><strong><?php echo esc_html( devco_price( $total ) ); ?></strong></td></tr>
				</tfoot>
			</table>

			<?php if ( $note ) : ?>
				<p class="devco-invoice__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>

			<div class="devco-invoice__actions">
				<?php if ( 'unpaid' === $status ) : ?>
					<a class="devco-btn devco-btn--primary" href="<?php echo esc_url( DeVco_Tailoring::invoice_pay_url( $invoice_id ) ); ?>"><?php devco_icon_e( 'credit-card', array( 'size' => 17 ) ); ?> پرداخت آنلاین</a>
				<?php else : ?>
					<span class="devco-invoice__paid"><?php devco_icon_e( 'check-badge', array( 'size' => 18 ) ); ?> این فاکتور پرداخت شده است.</span>
				<?php endif; ?>

				<button type="button" class="devco-btn devco-btn--ghost" onclick="window.print()"><?php devco_icon_e( 'download', array( 'size' => 17 ) ); ?> چاپ / ذخیره PDF</button>

				<?php if ( $extended ) : ?>
					<a class="devco-btn devco-btn--ghost" href="<?php echo esc_url( devco_dashboard_url( 'invoices' ) ); ?>">بازگشت به فاکتورها</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Measurements tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_measurements( $user ) {
		$values = get_user_meta( $user->ID, 'devco_measurements', true );
		$values = is_array( $values ) ? $values : array();
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'ruler', array( 'size' => 18 ) ); ?> اندازه‌های من</h2>
				<span class="devco-panel__hint">این اندازه‌ها هنگام سفارش دوخت به صورت خودکار پر می‌شوند.</span>
			</header>

			<form class="devco-measure-form" data-measure-form>
				<?php DeVco_Tailoring::body_map( array( 'values' => $values ) ); ?>
				<div class="devco-measure-form__actions">
					<button type="submit" class="devco-btn devco-btn--primary"><?php devco_icon_e( 'check', array( 'size' => 17 ) ); ?> ذخیره اندازه‌ها</button>
					<p class="devco-measure-form__message" data-measure-message></p>
				</div>
			</form>
		</section>
		<?php
	}

	/**
	 * AJAX: save the measurements on the profile.
	 */
	public static function ajax_save_measurements() {
		check_ajax_referer( 'devco_front', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'ابتدا وارد شوید.' ) );
		}

		$raw = isset( $_POST['measurements'] ) ? wp_unslash( $_POST['measurements'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array();

		foreach ( (array) $raw as $key => $value ) {
			$out[ sanitize_key( $key ) ] = (float) preg_replace( '/[^0-9.]/', '', (string) $value );
		}

		update_user_meta( get_current_user_id(), 'devco_measurements', $out );

		wp_send_json_success( array( 'message' => 'اندازه‌ها ذخیره شد.' ) );
	}

	/**
	 * Addresses tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_addresses( $user ) {
		if ( ! devco_is_woo() ) {
			echo '<p class="devco-panel__empty">برای مدیریت آدرس‌ها ووکامرس را فعال کنید.</p>';
			return;
		}
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'pin', array( 'size' => 18 ) ); ?> آدرس‌های من</h2>
				<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>">ویرایش آدرس‌ها</a>
			</header>

			<div class="devco-addresses">
				<div class="devco-address">
					<h4>آدرس صورتحساب</h4>
					<p><?php echo wp_kses_post( $user->billing_address_1 ? wc_get_account_formatted_address( 'billing' ) : 'آدرسی ثبت نشده است.' ); ?></p>
				</div>
				<div class="devco-address">
					<h4>آدرس ارسال</h4>
					<p><?php echo wp_kses_post( $user->shipping_address_1 ? wc_get_account_formatted_address( 'shipping' ) : 'آدرسی ثبت نشده است.' ); ?></p>
				</div>
			</div>
		</section>
		<?php
	}

	/**
	 * Wishlist tab (local storage driven).
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_wishlist( $user ) {
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'heart', array( 'size' => 18 ) ); ?> علاقه‌مندی‌ها</h2>
			</header>
			<div data-wishlist-page class="devco-product-grid">
				<p class="devco-panel__empty" data-wishlist-empty>لیست علاقه‌مندی‌های شما خالی است. با کلیک روی آیکون قلب در کارت محصولات، آن‌ها را ذخیره کنید.</p>
			</div>
		</section>
		<?php
	}

	/**
	 * Account tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_account( $user ) {
		?>
		<section class="devco-panel">
			<header class="devco-panel__head">
				<h2><?php devco_icon_e( 'user', array( 'size' => 18 ) ); ?> اطلاعات حساب</h2>
			</header>

			<div class="devco-account-form">
				<div class="devco-account-form__row">
					<label><span>نام نمایشی</span><input type="text" value="<?php echo esc_attr( $user->display_name ); ?>" disabled /></label>
					<label><span>ایمیل</span><input type="email" value="<?php echo esc_attr( $user->user_email ); ?>" disabled /></label>
					<label><span>موبایل</span><input type="text" value="<?php echo esc_attr( get_user_meta( $user->ID, 'devco_phone', true ) ); ?>" disabled /></label>
				</div>
				<a class="devco-btn devco-btn--primary" href="<?php echo esc_url( devco_is_woo() ? wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) ) : admin_url( 'profile.php' ) ); ?>">ویرایش اطلاعات</a>
			</div>
		</section>
		<?php
	}

	/**
	 * Support tab.
	 *
	 * @param WP_User $user User.
	 */
	protected static function tab_support( $user ) {
		?>
		<section class="devco-panel">
			<header class="devco-panel__head"><h2><?php devco_icon_e( 'headset', array( 'size' => 18 ) ); ?> پشتیبانی</h2></header>

			<div class="devco-support-grid">
				<a class="devco-support-card" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', devco_opt( 'general.phone', '' ) ) ); ?>">
					<?php devco_icon_e( 'phone', array( 'size' => 24 ) ); ?>
					<strong>تماس تلفنی</strong>
					<span><?php echo esc_html( devco_opt( 'general.phone', '—' ) ); ?></span>
				</a>
				<a class="devco-support-card" href="https://wa.me/<?php echo esc_attr( devco_opt( 'floating.whatsapp', '' ) ); ?>" target="_blank" rel="noopener">
					<?php devco_icon_e( 'whatsapp', array( 'size' => 24 ) ); ?>
					<strong>واتساپ</strong>
					<span>پاسخ سریع در ساعات کاری</span>
				</a>
				<a class="devco-support-card" href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
					<?php devco_icon_e( 'mail', array( 'size' => 24 ) ); ?>
					<strong>ایمیل</strong>
					<span><?php echo esc_html( get_option( 'admin_email' ) ); ?></span>
				</a>
			</div>
		</section>
		<?php
	}
}
