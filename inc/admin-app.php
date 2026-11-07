<?php
/**
 * DeVco admin web app (dashboard, products, tailoring, form builder).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Admin_App
 */
class DeVco_Admin_App {

	/**
	 * Instance.
	 *
	 * @var DeVco_Admin_App|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Admin_App
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
		add_action( 'admin_menu', array( $this, 'register_pages' ) );

		foreach ( array( 'app_product_save', 'app_product_get', 'app_product_delete', 'app_forms_save', 'app_forms_get', 'app_sms_resend' ) as $action ) {
			add_action( 'wp_ajax_devco_' . $action, array( $this, 'ajax_' . $action ) );
		}
	}

	/**
	 * Register the app pages.
	 */
	public function register_pages() {
		$cap = devco_is_woo() ? 'manage_woocommerce' : 'manage_options';

		add_submenu_page(
			'devco-settings',
			'پنل مدیریت دیوکو',
			'پنل مدیریت (وب‌اپ)',
			$cap,
			'devco-app',
			array( $this, 'render' )
		);

		add_submenu_page(
			'devco-settings',
			'فرم‌ساز سفارش دوخت',
			'فرم‌ساز دوخت',
			'manage_options',
			'devco-forms',
			array( $this, 'render_forms_page' )
		);
	}

	/**
	 * Current view.
	 *
	 * @return string
	 */
	protected function view() {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$allowed = array( 'dashboard', 'products', 'product-new', 'product-edit', 'tailoring', 'invoices', 'sms', 'settings' );

		return in_array( $view, $allowed, true ) ? $view : 'dashboard';
	}

	/**
	 * Render the app shell.
	 */
	public function render() {
		if ( ! current_user_can( devco_is_woo() ? 'manage_woocommerce' : 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}

		$view = $this->view();

		$nav = array(
			'dashboard'   => array( 'label' => 'داشبورد', 'icon' => 'dashboard' ),
			'products'    => array( 'label' => 'محصولات', 'icon' => 'box' ),
			'product-new' => array( 'label' => 'افزودن محصول', 'icon' => 'plus' ),
			'tailoring'   => array( 'label' => 'سفارش دوخت', 'icon' => 'scissors' ),
			'invoices'    => array( 'label' => 'فاکتورها', 'icon' => 'invoice' ),
			'sms'         => array( 'label' => 'گزارش پیامک', 'icon' => 'sms' ),
			'settings'    => array( 'label' => 'تنظیمات قالب', 'icon' => 'sparkle' ),
		);
		?>
		<div class="wrap devco-app" dir="rtl">
			<div class="devco-app__layout">
				<aside class="devco-app__side">
					<div class="devco-app__brand">
						<span class="devco-app__logo">D</span>
						<div>
							<strong>پنل دیوکو</strong>
							<small>وب‌اپ مدیریت فروشگاه</small>
						</div>
					</div>

					<nav class="devco-app__nav">
						<?php foreach ( $nav as $key => $item ) : ?>
							<?php
							$url = 'settings' === $key ? admin_url( 'admin.php?page=devco-settings' ) : admin_url( 'admin.php?page=devco-app&view=' . $key );
							$is_active = $key === $view || ( 'product-edit' === $view && 'products' === $key );
							?>
							<a class="devco-app__nav-item <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
								<?php devco_icon_e( $item['icon'], array( 'size' => 18 ) ); ?>
								<span><?php echo esc_html( $item['label'] ); ?></span>
							</a>
						<?php endforeach; ?>
						<a class="devco-app__nav-item" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-forms' ) ); ?>">
							<?php devco_icon_e( 'edit', array( 'size' => 18 ) ); ?>
							<span>فرم‌ساز دوخت</span>
						</a>
						<a class="devco-app__nav-item" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
							<?php devco_icon_e( 'external', array( 'size' => 18 ) ); ?>
							<span>مشاهده سایت</span>
						</a>
					</nav>

					<div class="devco-app__side-foot">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-wizard' ) ); ?>">اجرای دوباره ویزارد</a>
						<small>نسخه <?php echo esc_html( DEVCO_VERSION ); ?></small>
					</div>
				</aside>

				<main class="devco-app__main">
					<?php
					switch ( $view ) {
						case 'products':
							$this->view_products();
							break;
						case 'product-new':
							$this->view_product_editor( 0 );
							break;
						case 'product-edit':
							$this->view_product_editor( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							break;
						case 'tailoring':
							$this->view_tailoring();
							break;
						case 'invoices':
							$this->view_invoices();
							break;
						case 'sms':
							$this->view_sms();
							break;
						default:
							$this->view_dashboard();
					}
					?>
				</main>
			</div>
		</div>
		<?php
	}

	/**
	 * Dashboard view.
	 */
	protected function view_dashboard() {
		$orders_today = 0;
		$revenue      = 0;
		$products     = 0;
		$requests     = get_posts(
			array(
				'post_type'      => 'devco_trequest',
				'posts_per_page' => 5,
				'post_status'    => 'publish',
			)
		);
		$pending_requests = count(
			get_posts(
				array(
					'post_type'      => 'devco_trequest',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_devco_status',
							'value' => array( 'pending', 'review' ),
						),
					),
				)
			)
		);

		if ( devco_is_woo() ) {
			$todays = wc_get_orders(
				array(
					'limit'        => -1,
					'date_created' => '>' . gmdate( 'Y-m-d', current_time( 'timestamp' ) ),
					'status'       => array( 'processing', 'completed', 'on-hold' ),
					'return'       => 'ids',
				)
			);

			$orders_today = is_array( $todays ) ? count( $todays ) : 0;

			$totals = wc_get_orders(
				array(
					'limit'   => 50,
					'status'  => array( 'processing', 'completed' ),
					'orderby' => 'date',
					'order'   => 'DESC',
				)
			);

			foreach ( $totals as $order ) {
				$revenue += (float) $order->get_total();
			}

			$counts   = wp_count_posts( 'product' );
			$products = isset( $counts->publish ) ? (int) $counts->publish : 0;
		}
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">خلاصه امروز — <?php echo esc_html( date_i18n( 'j F Y' ) ); ?></span>
				<h1 class="devco-app__title">داشبورد مدیریت</h1>
			</div>
			<div class="devco-app__head-actions">
				<a class="devco-btn devco-btn--sm devco-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=product-new' ) ); ?>"><?php devco_icon_e( 'plus', array( 'size' => 16 ) ); ?> محصول جدید</a>
				<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=tailoring' ) ); ?>"><?php devco_icon_e( 'scissors', array( 'size' => 16 ) ); ?> سفارشات دوخت</a>
			</div>
		</header>

		<div class="devco-app__stats">
			<div class="devco-app__stat">
				<span class="devco-app__stat-icon is-blue"><?php devco_icon_e( 'chart', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( $orders_today ) ); ?></strong><span>سفارش امروز</span></div>
			</div>
			<div class="devco-app__stat">
				<span class="devco-app__stat-icon is-green"><?php devco_icon_e( 'wallet', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo wp_kses_post( devco_is_woo() ? wc_price( $revenue ) : devco_price( $revenue ) ); ?></strong><span>درآمد ۵۰ سفارش اخیر</span></div>
			</div>
			<div class="devco-app__stat">
				<span class="devco-app__stat-icon is-gold"><?php devco_icon_e( 'scissors', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( $pending_requests ) ); ?></strong><span>درخواست دوخت در انتظار</span></div>
			</div>
			<div class="devco-app__stat">
				<span class="devco-app__stat-icon is-purple"><?php devco_icon_e( 'box', array( 'size' => 22 ) ); ?></span>
				<div><strong><?php echo esc_html( devco_num( $products ) ); ?></strong><span>محصول منتشرشده</span></div>
			</div>
		</div>

		<div class="devco-app__grid">
			<section class="devco-app__card">
				<header class="devco-app__card-head">
					<h2>آخرین درخواست‌های دوخت</h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=tailoring' ) ); ?>">همه</a>
				</header>

				<?php if ( $requests ) : ?>
					<ul class="devco-app__list">
						<?php foreach ( $requests as $request ) : ?>
							<?php $status = get_post_meta( $request->ID, '_devco_status', true ); ?>
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=tailoring&id=' . $request->ID ) ); ?>">
									<span class="devco-app__list-title"><?php echo esc_html( get_the_title( $request ) ); ?></span>
									<span class="devco-app__badge devco-app__badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( DeVco_Tailoring::status_label( $status ) ); ?></span>
									<small><?php echo esc_html( get_the_date( '', $request ) ); ?></small>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="devco-app__empty">درخواستی ثبت نشده است.</p>
				<?php endif; ?>
			</section>

			<section class="devco-app__card">
				<header class="devco-app__card-head">
					<h2>دسترسی سریع</h2>
				</header>
				<div class="devco-app__quick">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=product-new' ) ); ?>"><?php devco_icon_e( 'plus', array( 'size' => 20 ) ); ?><span>محصول جدید</span></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-forms' ) ); ?>"><?php devco_icon_e( 'drag', array( 'size' => 20 ) ); ?><span>فرم‌ساز کشیدنی</span></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=mobile' ) ); ?>"><?php devco_icon_e( 'phone', array( 'size' => 20 ) ); ?><span>ناوبری موبایل</span></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=sms' ) ); ?>"><?php devco_icon_e( 'sms', array( 'size' => 20 ) ); ?><span>تنظیم پیامک</span></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=header' ) ); ?>"><?php devco_icon_e( 'menu', array( 'size' => 20 ) ); ?><span>سازنده هدر</span></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=fitting' ) ); ?>"><?php devco_icon_e( 'tshirt', array( 'size' => 20 ) ); ?><span>اتاق پرو</span></a>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Products list view.
	 */
	protected function view_products() {
		if ( ! devco_is_woo() ) {
			echo '<p class="devco-app__empty">برای مدیریت محصولات، ووکامرس را نصب کنید.</p>';
			return;
		}

		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$products = wc_get_products(
			array(
				'limit'  => 40,
				'status' => array( 'publish', 'draft', 'pending' ),
				's'      => $search,
			)
		);
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">مدیریت کاتالوگ</span>
				<h1 class="devco-app__title">محصولات</h1>
			</div>
			<div class="devco-app__head-actions">
				<form method="get" class="devco-app__search">
					<input type="hidden" name="page" value="devco-app" />
					<input type="hidden" name="view" value="products" />
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="جستجوی محصول…" />
					<button type="submit" class="devco-btn devco-btn--sm devco-btn--ghost"><?php devco_icon_e( 'search', array( 'size' => 15 ) ); ?></button>
				</form>
				<a class="devco-btn devco-btn--sm devco-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=product-new' ) ); ?>"><?php devco_icon_e( 'plus', array( 'size' => 16 ) ); ?> افزودن</a>
			</div>
		</header>

		<div class="devco-app__card">
			<?php if ( ! $products ) : ?>
				<p class="devco-app__empty">محصولی پیدا نشد.</p>
			<?php else : ?>
				<table class="devco-app__table">
					<thead><tr><th>محصول</th><th>قیمت</th><th>موجودی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
					<tbody>
						<?php foreach ( $products as $product ) : ?>
							<tr>
								<td>
									<div class="devco-app__product">
										<?php echo $product->get_image( array( 44, 44 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<div>
											<strong><?php echo esc_html( $product->get_name() ); ?></strong>
											<small>#<?php echo esc_html( $product->get_id() ); ?></small>
										</div>
									</div>
								</td>
								<td><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
								<td><?php echo esc_html( devco_num( $product->get_stock_quantity() ? $product->get_stock_quantity() : 0 ) ); ?></td>
								<td><span class="devco-app__badge devco-app__badge--<?php echo esc_attr( $product->get_status() ); ?>"><?php echo esc_html( 'publish' === $product->get_status() ? 'منتشر شده' : 'پیش‌نویس' ); ?></span></td>
								<td class="devco-app__table-actions">
									<a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=product-edit&id=' . $product->get_id() ) ); ?>">ویرایش سریع</a>
									<a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>">ویرایش کامل</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Product wizard / editor.
	 *
	 * @param int $product_id Product.
	 */
	protected function view_product_editor( $product_id = 0 ) {
		if ( ! devco_is_woo() ) {
			echo '<p class="devco-app__empty">ووکامرس را نصب کنید.</p>';
			return;
		}

		$product = $product_id ? wc_get_product( $product_id ) : false;
		$is_new  = ! $product;

		$categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		$image_id   = $product ? $product->get_image_id() : 0;
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow"><?php echo $is_new ? 'ویزارد افزودن محصول' : 'ویرایش محصول'; ?></span>
				<h1 class="devco-app__title"><?php echo $is_new ? 'محصول جدید' : esc_html( $product->get_name() ); ?></h1>
			</div>
			<div class="devco-app__head-actions">
				<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=products' ) ); ?>">بازگشت</a>
			</div>
		</header>

		<form class="devco-app__card devco-wizard-form" data-product-form data-id="<?php echo (int) $product_id; ?>">
			<ol class="devco-wizard-steps">
				<li class="is-active" data-wstep="1"><b>۱</b> اطلاعات پایه</li>
				<li data-wstep="2"><b>۲</b> قیمت و موجودی</li>
				<li data-wstep="3"><b>۳</b> تصاویر و دسته</li>
				<li data-wstep="4"><b>۴</b> انتشار</li>
			</ol>

			<div class="devco-wizard-step" data-wpanel="1">
				<div class="devco-app__grid-2">
					<label class="devco-field"><span>نام محصول *</span><input type="text" name="name" value="<?php echo esc_attr( $product ? $product->get_name() : '' ); ?>" required /></label>
					<label class="devco-field"><span>شماره محصول (SKU)</span><input type="text" name="sku" value="<?php echo esc_attr( $product ? $product->get_sku() : '' ); ?>" /></label>
				</div>
				<label class="devco-field"><span>توضیح کوتاه</span><textarea name="short_description" rows="3"><?php echo esc_textarea( $product ? $product->get_short_description() : '' ); ?></textarea></label>
				<label class="devco-field"><span>توضیحات کامل</span><textarea name="description" rows="6"><?php echo esc_textarea( $product ? $product->get_description() : '' ); ?></textarea></label>
				<div class="devco-wizard-nav"><span></span><button type="button" class="devco-btn devco-btn--primary" data-wnext="2">بعدی <?php devco_icon_e( 'arrow-left', array( 'size' => 15 ) ); ?></button></div>
			</div>

			<div class="devco-wizard-step" data-wpanel="2" hidden>
				<div class="devco-app__grid-3">
					<label class="devco-field"><span>قیمت عادی</span><input type="number" name="regular_price" min="0" step="1000" value="<?php echo esc_attr( $product ? $product->get_regular_price() : '' ); ?>" /></label>
					<label class="devco-field"><span>قیمت فروش ویژه</span><input type="number" name="sale_price" min="0" step="1000" value="<?php echo esc_attr( $product ? $product->get_sale_price() : '' ); ?>" /></label>
					<label class="devco-field"><span>موجودی</span><input type="number" name="stock" min="0" value="<?php echo esc_attr( $product ? $product->get_stock_quantity() : '' ); ?>" /></label>
				</div>
				<label class="devco-field devco-field--inline"><input type="checkbox" name="manage_stock" value="1" <?php checked( $product ? $product->managing_stock() : true ); ?> /><span>مدیریت موجودی</span></label>
				<div class="devco-wizard-nav">
					<button type="button" class="devco-btn devco-btn--ghost" data-wprev="1">قبلی</button>
					<button type="button" class="devco-btn devco-btn--primary" data-wnext="3">بعدی <?php devco_icon_e( 'arrow-left', array( 'size' => 15 ) ); ?></button>
				</div>
			</div>

			<div class="devco-wizard-step" data-wpanel="3" hidden>
				<div class="devco-app__grid-2">
					<div class="devco-field">
						<span>تصویر شاخص</span>
						<div class="devco-app__media" data-media>
							<div class="devco-app__media-preview"><?php echo $image_id ? wp_get_attachment_image( $image_id, 'thumbnail' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<input type="hidden" name="image_id" value="<?php echo (int) $image_id; ?>" />
							<button type="button" class="devco-btn devco-btn--sm devco-btn--ghost js-devco-media">انتخاب تصویر</button>
						</div>
					</div>
					<div class="devco-field">
						<span>دسته‌بندی‌ها</span>
						<div class="devco-app__cats">
							<?php if ( ! is_wp_error( $categories ) ) : ?>
								<?php foreach ( $categories as $category ) : ?>
									<label class="devco-check"><input type="checkbox" name="categories[]" value="<?php echo (int) $category->term_id; ?>" <?php checked( $product ? has_term( $category->term_id, 'product_cat', $product_id ) : false ); ?> /><span><?php echo esc_html( $category->name ); ?></span></label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="devco-wizard-nav">
					<button type="button" class="devco-btn devco-btn--ghost" data-wprev="2">قبلی</button>
					<button type="button" class="devco-btn devco-btn--primary" data-wnext="4">بعدی <?php devco_icon_e( 'arrow-left', array( 'size' => 15 ) ); ?></button>
				</div>
			</div>

			<div class="devco-wizard-step" data-wpanel="4" hidden>
				<div class="devco-app__grid-2">
					<label class="devco-field"><span>وضعیت</span>
						<select name="status">
							<option value="publish" <?php selected( $product ? $product->get_status() : 'publish', 'publish' ); ?>>منتشر شده</option>
							<option value="draft" <?php selected( $product ? $product->get_status() : '', 'draft' ); ?>>پیش‌نویس</option>
						</select>
					</label>
					<label class="devco-field"><span>برچسب‌ها (با ویرگول)</span><input type="text" name="tags" value="<?php echo esc_attr( $product ? implode( ',', wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) ) ) : '' ); ?>" /></label>
				</div>

				<div class="devco-wizard-summary" data-wsummary></div>

				<div class="devco-wizard-nav">
					<button type="button" class="devco-btn devco-btn--ghost" data-wprev="3">قبلی</button>
					<button type="submit" class="devco-btn devco-btn--primary devco-btn--lg">
						<?php devco_icon_e( 'check', array( 'size' => 18 ) ); ?>
						<span><?php echo $is_new ? 'ایجاد محصول' : 'ذخیره تغییرات'; ?></span>
						<span class="devco-spinner" hidden></span>
					</button>
				</div>
				<p class="devco-app__message" data-product-message></p>
			</div>
		</form>
		<?php
	}

	/**
	 * Tailoring requests view.
	 */
	protected function view_tailoring() {
		$request_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $request_id ) {
			$this->view_tailoring_detail( $request_id );
			return;
		}

		$requests = get_posts(
			array(
				'post_type'      => 'devco_trequest',
				'posts_per_page' => 50,
				'post_status'    => 'publish',
			)
		);
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">سامانه دوخت</span>
				<h1 class="devco-app__title">درخواست‌های دوخت</h1>
			</div>
			<div class="devco-app__head-actions">
				<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-forms' ) ); ?>"><?php devco_icon_e( 'edit', array( 'size' => 16 ) ); ?> فرم‌ساز</a>
			</div>
		</header>

		<div class="devco-app__card">
			<?php if ( ! $requests ) : ?>
				<p class="devco-app__empty">هنوز درخواستی ثبت نشده است.</p>
			<?php else : ?>
				<table class="devco-app__table">
					<thead><tr><th>درخواست</th><th>مشتری</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
					<tbody>
						<?php foreach ( $requests as $request ) : ?>
							<?php
							$status = get_post_meta( $request->ID, '_devco_status', true );
							$name   = get_post_meta( $request->ID, '_devco_name', true );
							$mobile = get_post_meta( $request->ID, '_devco_mobile', true );
							?>
							<tr>
								<td><strong>#<?php echo esc_html( devco_num( $request->ID ) ); ?></strong><br /><small><?php echo esc_html( devco_trim( get_the_title( $request ), 7 ) ); ?></small></td>
								<td><?php echo esc_html( $name ? $name : '—' ); ?><br /><small><?php echo esc_html( $mobile ); ?></small></td>
								<td><span class="devco-app__badge devco-app__badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( DeVco_Tailoring::status_label( $status ) ); ?></span></td>
								<td><?php echo esc_html( get_the_date( '', $request ) ); ?></td>
								<td><a class="devco-btn devco-btn--xs devco-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=tailoring&id=' . $request->ID ) ); ?>">بررسی و قیمت</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Single tailoring request.
	 *
	 * @param int $request_id Request.
	 */
	protected function view_tailoring_detail( $request_id ) {
		$request = get_post( $request_id );

		if ( ! $request || 'devco_trequest' !== $request->post_type ) {
			echo '<p class="devco-app__empty">درخواست پیدا نشد.</p>';
			return;
		}

		$status   = get_post_meta( $request_id, '_devco_status', true );
		$values   = get_post_meta( $request_id, '_devco_values', true );
		$measures = get_post_meta( $request_id, '_devco_measurements', true );
		$files    = get_post_meta( $request_id, '_devco_files', true );
		$timeline = get_post_meta( $request_id, '_devco_timeline', true );
		$invoice  = (int) get_post_meta( $request_id, '_devco_invoice_id', true );
		$mobile   = get_post_meta( $request_id, '_devco_mobile', true );
		$name     = get_post_meta( $request_id, '_devco_name', true );
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">درخواست #<?php echo esc_html( devco_num( $request_id ) ); ?></span>
				<h1 class="devco-app__title"><?php echo esc_html( $name ? $name : get_the_title( $request ) ); ?></h1>
				<p class="devco-app__sub"><?php echo esc_html( $mobile ); ?> — <?php echo esc_html( get_the_date( '', $request ) ); ?></p>
			</div>
			<div class="devco-app__head-actions">
				<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app&view=tailoring' ) ); ?>">بازگشت</a>
			</div>
		</header>

		<div class="devco-app__grid-2">
			<section class="devco-app__card">
				<header class="devco-app__card-head"><h2>مشخصات و اندازه‌ها</h2></header>

				<?php if ( is_array( $values ) && $values ) : ?>
					<ul class="devco-app__kv">
						<?php foreach ( $values as $key => $value ) : ?>
							<li><span><?php echo esc_html( $key ); ?></span><b><?php echo esc_html( is_array( $value ) ? implode( '، ', $value ) : $value ); ?></b></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( is_array( $measures ) && $measures ) : ?>
					<h3 class="devco-app__subtitle">اندازه‌ها (سانتی‌متر)</h3>
					<div class="devco-app__measures">
						<?php foreach ( $measures as $key => $value ) : ?>
							<span><?php echo esc_html( $key ); ?>: <b><?php echo esc_html( devco_num( $value ) ); ?></b></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( is_array( $files ) && $files ) : ?>
					<h3 class="devco-app__subtitle">طرح‌های مشتری</h3>
					<div class="devco-app__files">
						<?php foreach ( $files as $file_id ) : ?>
							<a href="<?php echo esc_url( wp_get_attachment_url( $file_id ) ); ?>" target="_blank" rel="noopener"><?php echo wp_get_attachment_image( $file_id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<div class="devco-app__stack">
				<section class="devco-app__card">
					<header class="devco-app__card-head"><h2>قیمت‌گذاری و صدور فاکتور</h2></header>

					<?php if ( $invoice ) : ?>
						<p class="devco-app__note"><?php devco_icon_e( 'check-badge', array( 'size' => 16 ) ); ?> فاکتور #<?php echo esc_html( devco_num( $invoice ) ); ?> صادر شده است. مبلغ: <b><?php echo esc_html( devco_price( (float) get_post_meta( $invoice, '_devco_total', true ) ) ); ?></b></p>
					<?php endif; ?>

					<form class="devco-app__form" data-price-form data-request="<?php echo (int) $request_id; ?>">
						<label class="devco-field"><span>شرح فاکتور</span><input type="text" name="title" placeholder="هزینه دوخت مانتو سفارشی" value="<?php echo esc_attr( $invoice ? get_the_title( $invoice ) : '' ); ?>" /></label>
						<div class="devco-app__grid-2">
							<label class="devco-field"><span>مبلغ (تومان)</span><input type="number" name="amount" min="0" step="1000" required /></label>
							<label class="devco-field"><span>تغییر وضعیت به</span>
								<select name="status">
									<option value="priced">قیمت‌گذاری شده</option>
									<option value="review">در حال بازبینی</option>
								</select>
							</label>
						</div>
						<label class="devco-field"><span>توضیح برای مشتری</span><textarea name="note" rows="2"></textarea></label>
						<button type="submit" class="devco-btn devco-btn--primary"><?php devco_icon_e( 'invoice', array( 'size' => 16 ) ); ?> صدور فاکتور و ارسال پیامک</button>
						<p class="devco-app__message" data-price-message></p>
					</form>
				</section>

				<section class="devco-app__card">
					<header class="devco-app__card-head"><h2>وضعیت و مراحل</h2></header>

					<form class="devco-app__form" data-status-form data-request="<?php echo (int) $request_id; ?>">
						<div class="devco-app__grid-2">
							<label class="devco-field"><span>وضعیت جدید</span>
								<select name="status">
									<?php foreach ( DeVco_Tailoring::statuses() as $key => $data ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $data['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="devco-field"><span>یادداشت</span><input type="text" name="note" /></label>
						</div>
						<button type="submit" class="devco-btn devco-btn--ghost">به‌روزرسانی و اطلاع‌رسانی</button>
						<p class="devco-app__message" data-status-message></p>
					</form>

					<?php if ( is_array( $timeline ) && $timeline ) : ?>
						<ol class="devco-app__timeline">
							<?php foreach ( array_reverse( $timeline ) as $entry ) : ?>
								<li><b><?php echo esc_html( DeVco_Tailoring::status_label( $entry['status'] ) ); ?></b><small><?php echo esc_html( $entry['time'] ); ?></small><?php echo ! empty( $entry['note'] ) ? '<em>' . esc_html( $entry['note'] ) . '</em>' : ''; ?></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</section>
			</div>
		</div>
		<?php
	}

	/**
	 * Invoices view.
	 */
	protected function view_invoices() {
		$invoices = get_posts(
			array(
				'post_type'      => 'devco_invoice',
				'posts_per_page' => 50,
				'post_status'    => 'publish',
			)
		);
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">مالی</span>
				<h1 class="devco-app__title">فاکتورها</h1>
			</div>
		</header>

		<div class="devco-app__card">
			<?php if ( ! $invoices ) : ?>
				<p class="devco-app__empty">فاکتوری صادر نشده است.</p>
			<?php else : ?>
				<table class="devco-app__table">
					<thead><tr><th>فاکتور</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
					<tbody>
						<?php foreach ( $invoices as $invoice ) : ?>
							<?php
							$status = get_post_meta( $invoice->ID, '_devco_status', true );
							$total  = (float) get_post_meta( $invoice->ID, '_devco_total', true );
							$user   = (int) get_post_meta( $invoice->ID, '_devco_user_id', true );
							$u      = $user ? get_userdata( $user ) : false;
							?>
							<tr>
								<td><strong>#<?php echo esc_html( devco_num( $invoice->ID ) ); ?></strong><br /><small><?php echo esc_html( $u ? $u->display_name : '—' ); ?></small></td>
								<td><?php echo esc_html( devco_price( $total ) ); ?></td>
								<td><span class="devco-app__badge devco-app__badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( DeVco_Tailoring::invoice_status_label( $status ) ); ?></span></td>
								<td><?php echo esc_html( get_the_date( '', $invoice ) ); ?></td>
								<td><a class="devco-btn devco-btn--xs devco-btn--ghost" href="<?php echo esc_url( admin_url( 'post.php?post=' . $invoice->ID . '&action=edit' ) ); ?>">ویرایش</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * SMS log view.
	 */
	protected function view_sms() {
		$logs = DeVco_SMS::logs();
		?>
		<header class="devco-app__head">
			<div>
				<span class="devco-app__eyebrow">ارتباطات</span>
				<h1 class="devco-app__title">گزارش پیامک‌ها</h1>
			</div>
			<div class="devco-app__head-actions">
				<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=sms' ) ); ?>">تنظیمات پیامک</a>
			</div>
		</header>

		<div class="devco-app__card">
			<?php if ( ! $logs ) : ?>
				<p class="devco-app__empty">گزارشی موجود نیست.</p>
			<?php else : ?>
				<table class="devco-app__table">
					<thead><tr><th>زمان</th><th>شماره</th><th>رویداد</th><th>متن</th><th>وضعیت</th></tr></thead>
					<tbody>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( $log['time'] ); ?></td>
								<td><?php echo esc_html( $log['mobile'] ); ?></td>
								<td><?php echo esc_html( $log['event'] ); ?></td>
								<td class="devco-app__cell-wrap"><?php echo esc_html( devco_trim( $log['message'], 14 ) ); ?></td>
								<td><?php echo ! empty( $log['ok'] ) ? '<span class="devco-app__badge devco-app__badge--publish">ارسال شد</span>' : '<span class="devco-app__badge devco-app__badge--cancelled">' . esc_html( $log['error'] ) . '</span>'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/* --------------------------------------------------------------------- */
	/* Form builder                                                           */
	/* --------------------------------------------------------------------- */

	/**
	 * Drag & drop form builder page.
	 */
	public function render_forms_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}

		$form_id = isset( $_GET['form'] ) ? (int) $_GET['form'] : DeVco_Tailoring::active_form_id(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$forms   = get_posts( array( 'post_type' => 'devco_tform', 'posts_per_page' => 50, 'post_status' => 'publish' ) );
		$fields  = DeVco_Tailoring::form_fields( $form_id );

		$palette = array(
			'text'     => array( 'label' => 'متن کوتاه', 'icon' => 'edit' ),
			'textarea' => array( 'label' => 'متن بلند', 'icon' => 'grid' ),
			'number'   => array( 'label' => 'عدد', 'icon' => 'percent' ),
			'select'   => array( 'label' => 'لیست کشویی', 'icon' => 'filter' ),
			'radio'    => array( 'label' => 'چند گزینه‌ای', 'icon' => 'check-badge' ),
			'checkbox' => array( 'label' => 'چک‌باکس', 'icon' => 'check' ),
			'date'     => array( 'label' => 'تاریخ', 'icon' => 'calendar' ),
			'color'    => array( 'label' => 'رنگ', 'icon' => 'sparkle' ),
			'upload'   => array( 'label' => 'آپلود فایل', 'icon' => 'upload' ),
			'bodymap'  => array( 'label' => 'مانکن سه‌بعدی', 'icon' => '3d' ),
			'heading'  => array( 'label' => 'تیتر بخش', 'icon' => 'tag' ),
		);
		?>
		<div class="wrap devco-app devco-builder-page" dir="rtl">
			<header class="devco-app__head">
				<div>
					<span class="devco-app__eyebrow">فرم‌ساز کشیدنی دوخت</span>
					<h1 class="devco-app__title">ساخت فرم اندازه‌گیری</h1>
					<p class="devco-app__sub">فیلدها را از سمت راست بکشید و در بوم رها کنید. ترتیب را می‌توانید جابه‌جا کنید.</p>
				</div>
				<div class="devco-app__head-actions">
					<select class="devco-app__select" id="devco-form-select">
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo esc_url( admin_url( 'admin.php?page=devco-forms&form=' . $form->ID ) ); ?>" <?php selected( $form_id, $form->ID ); ?>><?php echo esc_html( $form->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="devco-btn devco-btn--sm devco-btn--ghost" data-form-new>فرم جدید</button>
					<button type="button" class="devco-btn devco-btn--sm devco-btn--primary" data-form-save data-id="<?php echo (int) $form_id; ?>">ذخیره فرم</button>
				</div>
			</header>

			<p class="devco-app__message" data-builder-message></p>

			<div class="devco-builder" data-builder data-fields="<?php echo esc_attr( wp_json_encode( $fields ) ); ?>">
				<aside class="devco-builder__palette">
					<h2>ابزارها</h2>
					<div class="devco-builder__palette-list">
						<?php foreach ( $palette as $type => $item ) : ?>
							<button type="button" class="devco-builder__tool" draggable="true" data-field-type="<?php echo esc_attr( $type ); ?>">
								<?php devco_icon_e( $item['icon'], array( 'size' => 17 ) ); ?>
								<span><?php echo esc_html( $item['label'] ); ?></span>
								<?php devco_icon_e( 'drag', array( 'size' => 14 ) ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<div class="devco-builder__help">
						<strong>راهنما</strong>
						<p>برای حذف، روی آیکون سطل کلیک کنید. برای تنظیمات، روی فیلد در بوم کلیک کنید تا پنل سمت چپ باز شود.</p>
					</div>
				</aside>

				<div class="devco-builder__canvas-wrap">
					<div class="devco-builder__canvas" data-builder-canvas>
						<p class="devco-builder__placeholder">فیلدها را اینجا رها کنید…</p>
					</div>
				</div>

				<aside class="devco-builder__settings" data-builder-settings>
					<h2>تنظیمات فیلد</h2>
					<p class="devco-builder__settings-empty">یک فیلد را انتخاب کنید.</p>
				</aside>
			</div>

			<div class="devco-builder__preview">
				<h2>پیش‌نمایش</h2>
				<button type="button" class="devco-btn devco-btn--sm devco-btn--ghost" data-builder-preview>به‌روزرسانی پیش‌نمایش</button>
				<div class="devco-builder__preview-frame" data-builder-preview-frame></div>
			</div>
		</div>
		<?php
	}

	/* --------------------------------------------------------------------- */
	/* AJAX                                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * AJAX: save product.
	 */
	public function ajax_app_product_save() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( devco_is_woo() ? 'manage_woocommerce' : 'manage_options' ) || ! devco_is_woo() ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$product_id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$fields     = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$product = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'محصول پیدا نشد.' ) );
		}

		$product->set_name( sanitize_text_field( $fields['name'] ) );
		$product->set_status( in_array( $fields['status'], array( 'publish', 'draft' ), true ) ? $fields['status'] : 'draft' );
		$product->set_short_description( wp_kses_post( $fields['short_description'] ) );
		$product->set_description( wp_kses_post( $fields['description'] ) );

		if ( ! empty( $fields['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $fields['sku'] ) );
		}

		$product->set_regular_price( wc_format_decimal( $fields['regular_price'] ) );
		$product->set_sale_price( wc_format_decimal( $fields['sale_price'] ) );

		if ( ! empty( $fields['manage_stock'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $fields['stock'] );
		} else {
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
		}

		if ( ! empty( $fields['image_id'] ) ) {
			$product->set_image_id( (int) $fields['image_id'] );
		}

		$id = $product->save();

		if ( ! empty( $fields['categories'] ) && is_array( $fields['categories'] ) ) {
			wp_set_object_terms( $id, array_map( 'intval', $fields['categories'] ), 'product_cat' );
		}

		if ( ! empty( $fields['tags'] ) ) {
			wp_set_object_terms( $id, array_map( 'trim', explode( ',', sanitize_text_field( $fields['tags'] ) ) ), 'product_tag' );
		}

		wp_send_json_success(
			array(
				'message' => 'محصول ذخیره شد.',
				'id'      => $id,
				'url'     => get_edit_post_link( $id, 'raw' ),
			)
		);
	}

	/**
	 * AJAX: get product data.
	 */
	public function ajax_app_product_get() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$product = wc_get_product( isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 );

		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'محصول پیدا نشد.' ) );
		}

		wp_send_json_success(
			array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'price' => $product->get_price(),
			)
		);
	}

	/**
	 * AJAX: delete product.
	 */
	public function ajax_app_product_delete() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'delete_products' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;

		if ( $id ) {
			wp_trash_post( $id );
		}

		wp_send_json_success( array( 'message' => 'محصول به زباله‌دان منتقل شد.' ) );
	}

	/**
	 * AJAX: save form builder fields.
	 */
	public function ajax_app_forms_save() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$fields  = isset( $_POST['fields'] ) ? json_decode( wp_unslash( $_POST['fields'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $fields ) ) {
			wp_send_json_error( array( 'message' => 'ساختار فرم نامعتبر است.' ) );
		}

		$clean = array();

		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? sanitize_key( $field['type'] ) : 'text';

			$clean[] = array(
				'id'          => isset( $field['id'] ) ? sanitize_key( $field['id'] ) : 'f_' . wp_rand( 100, 9999 ),
				'type'        => $type,
				'label'       => isset( $field['label'] ) ? sanitize_text_field( $field['label'] ) : 'فیلد',
				'required'    => empty( $field['required'] ) ? 0 : 1,
				'placeholder' => isset( $field['placeholder'] ) ? sanitize_text_field( $field['placeholder'] ) : '',
				'hint'        => isset( $field['hint'] ) ? sanitize_text_field( $field['hint'] ) : '',
				'options'     => isset( $field['options'] ) ? sanitize_text_field( $field['options'] ) : '',
				'width'       => isset( $field['width'] ) && 'half' === $field['width'] ? 'half' : 'full',
			);
		}

		if ( $form_id ) {
			$post = get_post( $form_id );

			if ( $post && 'devco_tform' === $post->post_type ) {
				wp_update_post(
					array(
						'ID'         => $form_id,
						'post_title' => $title ? $title : $post->post_title,
					)
				);
			}
		} else {
			$form_id = wp_insert_post(
				array(
					'post_type'   => 'devco_tform',
					'post_status' => 'publish',
					'post_title'  => $title ? $title : 'فرم دوخت جدید',
				)
			);
		}

		if ( is_wp_error( $form_id ) ) {
			wp_send_json_error( array( 'message' => $form_id->get_error_message() ) );
		}

		update_post_meta( $form_id, '_devco_form_fields', $clean );

		wp_send_json_success(
			array(
				'message' => 'فرم ذخیره شد.',
				'form_id' => $form_id,
			)
		);
	}

	/**
	 * AJAX: get form fields.
	 */
	public function ajax_app_forms_get() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;

		wp_send_json_success(
			array(
				'fields' => DeVco_Tailoring::form_fields( $form_id ),
			)
		);
	}

	/**
	 * AJAX: resend an SMS log entry (helper for support).
	 */
	public function ajax_app_sms_resend() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$mobile  = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		$result = DeVco_SMS::send( $mobile, $message );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => 'ارسال شد.' ) );
	}
}
