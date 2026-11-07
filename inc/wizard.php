<?php
/**
 * First-run setup wizard.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Wizard
 */
class DeVco_Wizard {

	/**
	 * Instance.
	 *
	 * @var DeVco_Wizard|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Wizard
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
		add_action( 'admin_menu', array( $this, 'register_page' ), 11 );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'after_switch_theme', array( $this, 'flag_activation' ) );

		foreach ( array( 'wizard_install', 'wizard_import', 'wizard_finish' ) as $action ) {
			add_action( 'wp_ajax_devco_' . $action, array( $this, 'ajax_' . $action ) );
		}
	}

	/**
	 * Flag first activation.
	 */
	public function flag_activation() {
		set_transient( 'devco_activation_redirect', 1, 60 );
	}

	/**
	 * Redirect to the wizard once after activation.
	 */
	public function maybe_redirect() {
		if ( ! get_transient( 'devco_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'devco_activation_redirect' );

		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=devco-wizard' ) );
		exit;
	}

	/**
	 * Register the wizard page.
	 */
	public function register_page() {
		add_submenu_page(
			'devco-settings',
			'ویزارد راه‌اندازی',
			'ویزارد راه‌اندازی',
			'manage_options',
			'devco-wizard',
			array( $this, 'render' )
		);
	}

	/**
	 * Whether the wizard has been completed.
	 *
	 * @return bool
	 */
	public function is_done() {
		return devco_opt_bool( 'wizard.done', false );
	}

	/**
	 * Render the wizard.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}

		$plugins  = DeVco_Plugin_Installer::plugins();
		$done     = $this->is_done();
		$tasks    = DeVco_Demo_Import::tasks();
		$shop_url = home_url( '/' );
		?>
		<div class="wrap devco-wizard" dir="rtl">
			<header class="devco-wizard__head">
				<div class="devco-wizard__brand">
					<span class="devco-wizard__logo">D</span>
					<div>
						<h1>خوش آمدید به دیوکو</h1>
						<p>در سه گام، فروشگاه حرفه‌ای خود را آماده کنید.</p>
					</div>
				</div>
				<?php if ( $done ) : ?>
					<span class="devco-wizard__done"><?php devco_icon_e( 'check-badge', array( 'size' => 18 ) ); ?> راه‌اندازی انجام شده است</span>
				<?php endif; ?>
			</header>

			<div class="devco-wizard__steps">
				<div class="devco-wizard__step is-active" data-wstep="1"><b>۱</b><span>نصب پیش‌نیازها</span></div>
				<div class="devco-wizard__step" data-wstep="2"><b>۲</b><span>ایمپورت دمو (اختیاری)</span></div>
				<div class="devco-wizard__step" data-wstep="3"><b>۳</b><span>پایان</span></div>
			</div>

			<!-- Step 1: plugins -->
			<section class="devco-wizard__panel is-active" data-wpanel="1">
				<h2>افزونه‌های ضروری</h2>
				<p class="devco-wizard__desc">دیوکو با ووکامرس و المنتور بهترین عملکرد را دارد. با یک کلیک نصب و فعال می‌شوند.</p>

				<div class="devco-wizard__plugins">
					<?php foreach ( $plugins as $slug => $plugin ) : ?>
						<?php $status = DeVco_Plugin_Installer::status( $slug ); ?>
						<article class="devco-wizard__plugin" data-plugin="<?php echo esc_attr( $slug ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
							<div class="devco-wizard__plugin-icon"><?php devco_icon_e( 'elementor' === $slug ? 'sparkle' : 'bag', array( 'size' => 24 ) ); ?></div>
							<div class="devco-wizard__plugin-info">
								<strong><?php echo esc_html( $plugin['name'] ); ?></strong>
								<span><?php echo esc_html( $plugin['desc'] ); ?></span>
							</div>
							<div class="devco-wizard__plugin-status">
								<?php if ( 'active' === $status ) : ?>
									<span class="devco-wizard__tag devco-wizard__tag--ok">فعال</span>
								<?php elseif ( 'installed' === $status ) : ?>
									<button type="button" class="devco-btn devco-btn--sm devco-btn--primary js-wizard-install">فعال‌سازی</button>
								<?php else : ?>
									<button type="button" class="devco-btn devco-btn--sm devco-btn--primary js-wizard-install">نصب و فعال‌سازی</button>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<p class="devco-wizard__message" data-wizard-message></p>

				<div class="devco-wizard__nav">
					<span></span>
					<button type="button" class="devco-btn devco-btn--primary" data-goto="2">مرحله بعد <?php devco_icon_e( 'arrow-left', array( 'size' => 16 ) ); ?></button>
				</div>
			</section>

			<!-- Step 2: demo -->
			<section class="devco-wizard__panel" data-wpanel="2" hidden>
				<h2>ساخت صفحات خلاق (اختیاری)</h2>
				<p class="devco-wizard__desc">
					با یک کلیک، صفحات صفحه اصلی، فروشگاه، محصولات، درباره ما، تماس با ما، قوانین و مقررات، داشبورد کاربری و سفارش دوخت ساخته می‌شوند.
					هیچ محتوای قبلی شما حذف نمی‌شود؛ فقط صفحات جدید اضافه می‌شوند.
				</p>

				<div class="devco-wizard__tasks">
					<?php foreach ( $tasks as $key => $label ) : ?>
						<label class="devco-wizard__task" data-task="<?php echo esc_attr( $key ); ?>">
							<span class="devco-wizard__task-check"><?php devco_icon_e( 'check', array( 'size' => 14 ) ); ?></span>
							<span class="devco-wizard__task-name"><?php echo esc_html( $label ); ?></span>
							<span class="devco-wizard__task-state" data-task-state>در انتظار</span>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="devco-wizard__demo-actions">
					<button type="button" class="devco-btn devco-btn--primary devco-btn--lg js-wizard-import"><?php devco_icon_e( 'sparkle', array( 'size' => 18 ) ); ?> اجرای ایمپورت دمو</button>
					<button type="button" class="devco-btn devco-btn--ghost" data-skip-import>رد کردن این مرحله</button>
				</div>

				<div class="devco-wizard__progress" data-demo-progress hidden><span data-demo-progress-bar></span></div>
				<p class="devco-wizard__message" data-wizard-message></p>

				<div class="devco-wizard__nav">
					<button type="button" class="devco-btn devco-btn--ghost" data-goto="1">مرحله قبل</button>
					<button type="button" class="devco-btn devco-btn--primary" data-goto="3">مرحله بعد <?php devco_icon_e( 'arrow-left', array( 'size' => 16 ) ); ?></button>
				</div>
			</section>

			<!-- Step 3: done -->
			<section class="devco-wizard__panel" data-wpanel="3" hidden>
				<div class="devco-wizard__finish">
					<span class="devco-wizard__finish-icon"><?php devco_icon_e( 'check-badge', array( 'size' => 44 ) ); ?></span>
					<h2>همه چیز آماده است!</h2>
					<p>اکنون می‌توانید فروشگاه خود را شخصی‌سازی کنید. پیشنهاد می‌کنیم ابتدا تنظیمات رنگ، هدر، ناوبری موبایل و سامانه پیامک را انجام دهید.</p>

					<div class="devco-wizard__finish-grid">
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=colors' ) ); ?>"><?php devco_icon_e( 'sparkle', array( 'size' => 22 ) ); ?><strong>رنگ و فونت</strong><span>هویت بصری برند</span></a>
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=header' ) ); ?>"><?php devco_icon_e( 'menu', array( 'size' => 22 ) ); ?><strong>هدر و منو</strong><span>سازنده هدر</span></a>
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=mobile' ) ); ?>"><?php devco_icon_e( 'phone', array( 'size' => 22 ) ); ?><strong>ناوبری موبایل</strong><span>نوار پایین</span></a>
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=sms' ) ); ?>"><?php devco_icon_e( 'sms', array( 'size' => 22 ) ); ?><strong>سامانه پیامک</strong><span>ورود پترنی</span></a>
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-settings&tab=fitting' ) ); ?>"><?php devco_icon_e( 'tshirt', array( 'size' => 22 ) ); ?><strong>اتاق پرو</strong><span>افزودن لباس‌ها</span></a>
						<a class="devco-wizard__finish-card" href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app' ) ); ?>"><?php devco_icon_e( 'dashboard', array( 'size' => 22 ) ); ?><strong>پنل مدیریت</strong><span>محصولات و سفارش دوخت</span></a>
					</div>

					<div class="devco-wizard__finish-actions">
						<button type="button" class="devco-btn devco-btn--primary devco-btn--lg js-wizard-finish">پایان راه‌اندازی</button>
						<a class="devco-btn devco-btn--ghost devco-btn--lg" href="<?php echo esc_url( $shop_url ); ?>" target="_blank" rel="noopener">مشاهده سایت</a>
					</div>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * AJAX: install a plugin.
	 */
	public function ajax_wizard_install() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$result = DeVco_Plugin_Installer::install( $slug );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => 'افزونه با موفقیت فعال شد.',
				'status'  => DeVco_Plugin_Installer::status( $slug ),
			)
		);
	}

	/**
	 * AJAX: run a demo import task.
	 */
	public function ajax_wizard_import() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$task   = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';
		$result = DeVco_Demo_Import::run( $task );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: finish the wizard.
	 */
	public function ajax_wizard_finish() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$options                = devco_options();
		$options['wizard.done'] = 1;
		update_option( DEVCO_OPTION, $options );

		wp_send_json_success(
			array(
				'message'  => 'راه‌اندازی کامل شد.',
				'redirect' => admin_url( 'admin.php?page=devco-settings' ),
			)
		);
	}
}
