<?php
/**
 * Unified authentication: OTP (SMS) + username/password login & registration.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Auth
 */
class DeVco_Auth {

	/**
	 * Instance.
	 *
	 * @var DeVco_Auth|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Auth
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
		foreach ( array( 'send_otp', 'verify_otp', 'login', 'register', 'resend' ) as $action ) {
			add_action( 'wp_ajax_devco_' . $action, array( $this, 'ajax_' . $action ) );
			add_action( 'wp_ajax_nopriv_devco_' . $action, array( $this, 'ajax_' . $action ) );
		}

		add_action( 'wp_footer', array( $this, 'render_modal' ), 25 );
		add_shortcode( 'devco_auth', array( __CLASS__, 'shortcode' ) );
		add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 10, 2 );
	}

	/**
	 * Default post-login redirect.
	 *
	 * @return string
	 */
	public static function redirect_url() {
		$custom = devco_opt( 'auth.redirect', '' );

		if ( $custom ) {
			return $custom;
		}

		$tab = devco_opt( 'dashboard.page_id', 0 ) ? '' : '';

		return devco_dashboard_url( $tab );
	}

	/**
	 * Find a user by phone number.
	 *
	 * @param string $mobile Mobile.
	 * @return WP_User|false
	 */
	public static function user_by_phone( $mobile ) {
		$mobile = DeVco_SMS::normalize( $mobile );

		$users = get_users(
			array(
				'number'     => 1,
				'fields'     => 'all',
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => 'devco_phone',
						'value'   => $mobile,
						'compare' => '=',
					),
					array(
						'key'     => 'billing_phone',
						'value'   => $mobile,
						'compare' => '=',
					),
				),
			)
		);

		if ( $users ) {
			return $users[0];
		}

		// Fallback: the login name equals the phone number.
		$user = get_user_by( 'login', $mobile );

		return $user ? $user : false;
	}

	/**
	 * Create a customer account for a phone number.
	 *
	 * @param string $mobile Mobile.
	 * @param string $name   Name.
	 * @return int|WP_Error
	 */
	public static function create_user( $mobile, $name = '' ) {
		$mobile   = DeVco_SMS::normalize( $mobile );
		$username = $mobile;
		$suffix   = 0;

		while ( username_exists( $username ) ) {
			$suffix++;
			$username = $mobile . '_' . $suffix;
		}

		$parts      = preg_split( '/\s+/u', trim( (string) $name ) );
		$first_name = $parts && $parts[0] ? $parts[0] : '';
		$last_name  = count( $parts ) > 1 ? implode( ' ', array_slice( $parts, 1 ) ) : '';

		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_pass'    => wp_generate_password( 20 ),
				'user_email'   => '',
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => $name ? $name : $mobile,
				'role'         => devco_is_woo() ? 'customer' : 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, 'devco_phone', $mobile );
		update_user_meta( $user_id, 'billing_phone', $mobile );

		if ( $first_name ) {
			update_user_meta( $user_id, 'billing_first_name', $first_name );
			update_user_meta( $user_id, 'shipping_first_name', $first_name );
		}

		if ( $last_name ) {
			update_user_meta( $user_id, 'billing_last_name', $last_name );
			update_user_meta( $user_id, 'shipping_last_name', $last_name );
		}

		do_action( 'devco_new_customer', $user_id, $mobile );

		DeVco_SMS::send_event(
			$mobile,
			'welcome',
			array(
				'name' => $first_name ? $first_name : 'دوست',
				'shop' => get_bloginfo( 'name' ),
			)
		);

		return $user_id;
	}

	/**
	 * Log a user in and return the redirect URL.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $remember Remember.
	 * @return string
	 */
	protected function login_user( $user_id, $remember = true ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, $remember );
		do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );

		$redirect = self::redirect_url();

		if ( ! empty( $_REQUEST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ), $redirect );
		}

		return apply_filters( 'devco_auth_redirect', $redirect, $user_id );
	}

	/**
	 * Rate limit helper.
	 *
	 * @param string $key    Key.
	 * @param int    $limit  Allowed hits.
	 * @param int    $window Window seconds.
	 * @return bool True when allowed.
	 */
	protected function rate_limit( $key, $limit, $window ) {
		$store = get_transient( 'devco_rl_' . $key );

		if ( ! is_array( $store ) ) {
			$store = array(
				'count' => 0,
				'start' => time(),
			);
		}

		if ( time() - $store['start'] > $window ) {
			$store = array(
				'count' => 0,
				'start' => time(),
			);
		}

		$store['count']++;

		set_transient( 'devco_rl_' . $key, $store, $window );

		return $store['count'] <= $limit;
	}

	/**
	 * AJAX: send OTP.
	 */
	public function ajax_send_otp() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$mobile = isset( $_POST['mobile'] ) ? DeVco_SMS::normalize( wp_unslash( $_POST['mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! DeVco_SMS::is_valid_mobile( $mobile ) ) {
			wp_send_json_error( array( 'message' => 'شماره موبایل را درست وارد کنید (مثال: ۰۹۱۲۱۲۳۴۵۶۷).' ) );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';

		if ( ! $this->rate_limit( 'otp_' . $mobile, 1, 60 ) ) {
			wp_send_json_error( array( 'message' => 'کد قبلی ارسال شده است. لطفاً ۶۰ ثانیه صبر کنید.' ) );
		}

		if ( ! $this->rate_limit( 'otp_ip_' . $ip, 10, 3600 ) ) {
			wp_send_json_error( array( 'message' => 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کنید.' ) );
		}

		$length = max( 4, min( 6, (int) devco_opt( 'auth.otp_length', 5 ) ) );
		$code   = (string) wp_rand( (int) str_pad( '1', $length, '0', STR_PAD_RIGHT ), (int) str_pad( '9', $length, '9', STR_PAD_RIGHT ) );
		$expiry = max( 1, (int) devco_opt( 'auth.otp_expiry', 3 ) ) * MINUTE_IN_SECONDS;

		set_transient(
			'devco_otp_' . md5( $mobile ),
			array(
				'hash'     => wp_hash_password( $code ),
				'attempts' => 0,
				'mobile'   => $mobile,
				'created'  => time(),
			),
			$expiry
		);

		DeVco_SMS::log( $mobile, 'کد تایید: ' . $code, true, 'otp-local' );

		$result = DeVco_SMS::send_otp( $mobile, $code );

		if ( is_wp_error( $result ) ) {
			// In test mode (or when no gateway is configured) report the code for admins only.
			if ( devco_opt_bool( 'auth.test_mode', false ) && current_user_can( 'manage_options' ) ) {
				wp_send_json_success(
					array(
						'message' => 'حالت آزمایشی: کد شما ' . $code . ' است. (' . $result->get_error_message() . ')',
						'expiry'  => $expiry,
						'debug'   => $code,
					)
				);
			}

			wp_send_json_error(
				array(
					'message' => 'ارسال پیامک انجام نشد: ' . $result->get_error_message(),
					'retry'   => 0,
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => 'کد تایید به شماره ' . $mobile . ' ارسال شد.',
				'expiry'  => $expiry,
				'mobile'  => $mobile,
			)
		);
	}

	/**
	 * AJAX: verify OTP and login/register.
	 */
	public function ajax_verify_otp() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$mobile = isset( $_POST['mobile'] ) ? DeVco_SMS::normalize( wp_unslash( $_POST['mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$code   = isset( $_POST['code'] ) ? preg_replace( '/[^0-9]/', '', DeVco_SMS::normalize( wp_unslash( $_POST['code'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		if ( ! DeVco_SMS::is_valid_mobile( $mobile ) ) {
			wp_send_json_error( array( 'message' => 'شماره موبایل نامعتبر است.' ) );
		}

		$key   = 'devco_otp_' . md5( $mobile );
		$store = get_transient( $key );

		if ( ! is_array( $store ) ) {
			wp_send_json_error( array( 'message' => 'کد منقضی شده است. دوباره درخواست کنید.' ) );
		}

		if ( (int) $store['attempts'] >= 5 ) {
			delete_transient( $key );
			wp_send_json_error( array( 'message' => 'تعداد تلاش‌های ناموفق زیاد است. کد جدید بگیرید.' ) );
		}

		if ( ! wp_check_password( $code, $store['hash'] ) ) {
			$store['attempts']++;
			set_transient( $key, $store, max( 60, (int) devco_opt( 'auth.otp_expiry', 3 ) * MINUTE_IN_SECONDS ) );
			wp_send_json_error( array( 'message' => 'کد وارد شده صحیح نیست.', 'attempts' => (int) $store['attempts'] ) );
		}

		delete_transient( $key );

		$user = self::user_by_phone( $mobile );

		if ( ! $user ) {
			if ( ! devco_opt_bool( 'auth.register', true ) ) {
				wp_send_json_error( array( 'message' => 'کاربری با این شماره ثبت نشده است. با نام کاربری وارد شوید.' ) );
			}

			$user_id = self::create_user( $mobile, $name );

			if ( is_wp_error( $user_id ) ) {
				wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
			}

			$is_new = true;
		} else {
			$user_id = $user->ID;
			$is_new  = false;

			if ( $name && ! get_user_meta( $user_id, 'billing_first_name', true ) ) {
				wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $name,
						'first_name'   => $name,
					)
				);
			}
		}

		$redirect = $this->login_user( $user_id, true );

		wp_send_json_success(
			array(
				'message'  => $is_new ? 'حساب شما ساخته شد. خوش آمدید!' : 'خوش آمدید!',
				'redirect' => $redirect,
				'new'      => $is_new,
			)
		);
	}

	/**
	 * AJAX: resend the OTP.
	 */
	public function ajax_resend() {
		$this->ajax_send_otp();
	}

	/**
	 * AJAX: password login.
	 */
	public function ajax_login() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$login    = isset( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remember = ! empty( $_POST['remember'] );

		if ( ! $login || ! $password ) {
			wp_send_json_error( array( 'message' => 'نام کاربری و رمز عبور را وارد کنید.' ) );
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';

		if ( ! $this->rate_limit( 'login_' . md5( $login . $ip ), 6, 600 ) ) {
			wp_send_json_error( array( 'message' => 'تلاش‌های ناموفق زیاد. ۱۰ دقیقه دیگر تلاش کنید.' ) );
		}

		// Allow logging in with a mobile number.
		if ( DeVco_SMS::is_valid_mobile( $login ) ) {
			$user = self::user_by_phone( $login );
			if ( $user ) {
				$login = $user->user_login;
			}
		}

		$creds = array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => $remember,
		);

		$signon = wp_signon( $creds, is_ssl() );

		if ( is_wp_error( $signon ) ) {
			wp_send_json_error( array( 'message' => 'نام کاربری یا رمز عبور اشتباه است.' ) );
		}

		$redirect = self::redirect_url();

		if ( ! empty( $_POST['redirect_to'] ) ) {
			$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ), $redirect );
		}

		wp_send_json_success(
			array(
				'message'  => 'خوش آمدید!',
				'redirect' => $redirect,
			)
		);
	}

	/**
	 * AJAX: password registration.
	 */
	public function ajax_register() {
		check_ajax_referer( 'devco_front', 'nonce' );

		if ( ! devco_opt_bool( 'auth.register', true ) ) {
			wp_send_json_error( array( 'message' => 'ثبت‌نام غیرفعال است.' ) );
		}

		$mobile   = isset( $_POST['mobile'] ) ? DeVco_SMS::normalize( wp_unslash( $_POST['mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! DeVco_SMS::is_valid_mobile( $mobile ) ) {
			wp_send_json_error( array( 'message' => 'شماره موبایل نامعتبر است.' ) );
		}

		if ( strlen( $password ) < 6 ) {
			wp_send_json_error( array( 'message' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.' ) );
		}

		if ( self::user_by_phone( $mobile ) ) {
			wp_send_json_error( array( 'message' => 'این شماره قبلاً ثبت شده است. وارد شوید.' ) );
		}

		$user_id = self::create_user( $mobile, $name );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
		}

		wp_set_password( $password, $user_id );

		if ( $email && is_email( $email ) ) {
			wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => $email,
				)
			);
		}

		$redirect = $this->login_user( $user_id, true );

		wp_send_json_success(
			array(
				'message'  => 'حساب شما ساخته شد. خوش آمدید!',
				'redirect' => $redirect,
			)
		);
	}

	/**
	 * WooCommerce login redirect.
	 *
	 * @param string $redirect Redirect.
	 * @param mixed  $user User.
	 * @return string
	 */
	public function login_redirect( $redirect, $user = null ) {
		$custom = devco_opt( 'auth.redirect', '' );

		return $custom ? $custom : $redirect;
	}

	/**
	 * The login/register form markup.
	 *
	 * @param array $args Args.
	 * @return string
	 */
	public static function form_markup( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'view'    => devco_opt( 'auth.default_method', 'otp' ),
				'context' => 'modal',
				'style'   => devco_opt( 'auth.style', 'elegant' ),
			)
		);

		$otp_on      = devco_opt_bool( 'auth.otp', true );
		$password_on = devco_opt_bool( 'auth.password', true );
		$view        = $otp_on ? $args['view'] : 'password';
		$terms_page  = (int) devco_opt( 'auth.terms_page', 0 );

		ob_start();
		?>
		<div class="devco-auth devco-auth--<?php echo esc_attr( $args['style'] ); ?> devco-auth--<?php echo esc_attr( $args['context'] ); ?>" data-auth data-view="<?php echo esc_attr( $view ); ?>">
			<div class="devco-auth__head">
				<span class="devco-auth__badge"><?php devco_icon_e( 'lock', array( 'size' => 16 ) ); ?> ورود امن</span>
				<h2 class="devco-auth__title"><?php echo esc_html( $args['context'] === 'inline' ? 'ورود / ثبت‌نام' : 'خوش آمدید' ); ?></h2>
				<p class="devco-auth__desc"><?php echo esc_html( devco_opt( 'auth.welcome_text', '' ) ); ?></p>
			</div>

			<?php if ( $otp_on && $password_on ) : ?>
				<div class="devco-auth__tabs" role="tablist">
					<button type="button" class="devco-auth__tab <?php echo 'otp' === $view ? 'is-active' : ''; ?>" data-auth-tab="otp" role="tab"><?php devco_icon_e( 'sms', array( 'size' => 16 ) ); ?> ورود پیامکی</button>
					<button type="button" class="devco-auth__tab <?php echo 'password' === $view ? 'is-active' : ''; ?>" data-auth-tab="password" role="tab"><?php devco_icon_e( 'lock', array( 'size' => 16 ) ); ?> رمز عبور</button>
				</div>
			<?php endif; ?>

			<div class="devco-auth__panel <?php echo 'otp' === $view ? 'is-active' : ''; ?>" data-auth-panel="otp" <?php echo 'otp' === $view ? '' : 'hidden'; ?>>
				<form class="devco-auth__form" data-auth-form="otp" novalidate>
					<div class="devco-auth__field" data-step="mobile">
						<label for="devco-auth-mobile">شماره موبایل</label>
						<div class="devco-auth__input-group">
							<span class="devco-auth__prefix"><?php devco_icon_e( 'sms', array( 'size' => 17 ) ); ?> +98</span>
							<input type="tel" id="devco-auth-mobile" name="mobile" inputmode="numeric" autocomplete="tel" placeholder="۹۱۲۱۲۳۴۵۶۷" maxlength="11" required />
						</div>
					</div>

					<div class="devco-auth__field" data-step="code" hidden>
						<label for="devco-auth-code">کد تایید پیامکی</label>
						<input type="text" id="devco-auth-code" name="code" class="devco-otp-input" inputmode="numeric" autocomplete="one-time-code" maxlength="<?php echo (int) devco_opt( 'auth.otp_length', 5 ); ?>" placeholder="۰۰۰۰۰" data-otp-length="<?php echo (int) devco_opt( 'auth.otp_length', 5 ); ?>" />
						<div class="devco-auth__code-meta">
							<span class="devco-auth__timer" data-auth-timer></span>
							<button type="button" class="devco-auth__link" data-auth-resend disabled>ارسال دوباره کد</button>
						</div>
					</div>

					<div class="devco-auth__field" data-step="name" hidden>
						<label for="devco-auth-name">نام و نام خانوادگی <small>(برای مشتریان جدید)</small></label>
						<input type="text" id="devco-auth-name" name="name" autocomplete="name" placeholder="مثلاً سارا احمدی" />
					</div>

					<?php if ( $terms_page ) : ?>
						<label class="devco-auth__terms">
							<input type="checkbox" name="terms" value="1" checked />
							<span>قوانین و مقررات فروشگاه را می‌پذیرم. <a href="<?php echo esc_url( get_permalink( $terms_page ) ); ?>" target="_blank" rel="noopener">مشاهده</a></span>
						</label>
					<?php endif; ?>

					<button type="submit" class="devco-btn devco-btn--primary devco-btn--block devco-auth__submit">
						<span class="devco-auth__submit-text">دریافت کد تایید</span>
						<span class="devco-spinner" hidden></span>
					</button>

					<p class="devco-auth__message" data-auth-message role="status" aria-live="polite"></p>
				</form>
			</div>

			<div class="devco-auth__panel <?php echo 'password' === $view ? 'is-active' : ''; ?>" data-auth-panel="password" <?php echo 'password' === $view ? '' : 'hidden'; ?>>
				<form class="devco-auth__form" data-auth-form="login" novalidate>
					<div class="devco-auth__field">
						<label for="devco-auth-login">نام کاربری / ایمیل / موبایل</label>
						<input type="text" id="devco-auth-login" name="login" autocomplete="username" required />
					</div>
					<div class="devco-auth__field">
						<label for="devco-auth-password">رمز عبور</label>
						<input type="password" id="devco-auth-password" name="password" autocomplete="current-password" required />
					</div>
					<div class="devco-auth__row">
						<label class="devco-auth__remember"><input type="checkbox" name="remember" value="1" checked /> مرا به خاطر بسپار</label>
						<a class="devco-auth__link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">فراموشی رمز؟</a>
					</div>
					<button type="submit" class="devco-btn devco-btn--primary devco-btn--block devco-auth__submit">
						<span class="devco-auth__submit-text">ورود به حساب</span>
						<span class="devco-spinner" hidden></span>
					</button>
					<p class="devco-auth__message" data-auth-message role="status" aria-live="polite"></p>
				</form>

				<?php if ( devco_opt_bool( 'auth.register', true ) ) : ?>
					<div class="devco-auth__switch">
						<span>حساب ندارید؟</span>
						<button type="button" class="devco-auth__link" data-auth-switch="register">ساخت حساب جدید</button>
					</div>

					<form class="devco-auth__form devco-auth__form--register" data-auth-form="register" hidden novalidate>
						<div class="devco-auth__field">
							<label for="devco-auth-reg-name">نام و نام خانوادگی</label>
							<input type="text" id="devco-auth-reg-name" name="name" autocomplete="name" required />
						</div>
						<div class="devco-auth__field">
							<label for="devco-auth-reg-mobile">شماره موبایل</label>
							<input type="tel" id="devco-auth-reg-mobile" name="mobile" inputmode="numeric" autocomplete="tel" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required />
						</div>
						<div class="devco-auth__field">
							<label for="devco-auth-reg-pass">رمز عبور</label>
							<input type="password" id="devco-auth-reg-pass" name="password" autocomplete="new-password" minlength="6" required />
						</div>
						<button type="submit" class="devco-btn devco-btn--primary devco-btn--block devco-auth__submit">
							<span class="devco-auth__submit-text">ثبت‌نام و ورود</span>
							<span class="devco-spinner" hidden></span>
						</button>
						<p class="devco-auth__message" data-auth-message role="status" aria-live="polite"></p>
					</form>
				<?php endif; ?>
			</div>

			<?php if ( devco_opt_bool( 'auth.social_login', true ) ) : ?>
				<div class="devco-auth__social">
					<span class="devco-auth__social-title">یا ورود سریع با</span>
					<div class="devco-auth__social-list">
						<?php
						$socials = devco_social_links();
						$shown   = 0;

						foreach ( $socials as $key => $link ) :
							if ( $shown >= 3 ) {
								break;
							}
							?>
							<a class="devco-auth__social-btn devco-auth__social-btn--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
								<?php devco_icon_e( $link['icon'], array( 'size' => 18 ) ); ?>
								<span><?php echo esc_html( $link['label'] ); ?></span>
							</a>
							<?php
							$shown++;
						endforeach;
						?>
					</div>
					<p class="devco-auth__social-hint">ورود خودکار شبکه‌های اجتماعی با افزونه‌های ورود اجتماعی فعال می‌شود.</p>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the global login modal.
	 */
	public function render_modal() {
		if ( is_user_logged_in() ) {
			return;
		}
		?>
		<div class="devco-modal devco-modal--auth" data-auth-modal aria-hidden="true">
			<div class="devco-modal__backdrop" data-modal-close></div>
			<div class="devco-modal__dialog devco-auth-dialog" role="dialog" aria-modal="true" aria-label="ورود و ثبت‌نام">
				<button type="button" class="devco-modal__close" data-modal-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>

				<?php if ( 'split' === devco_opt( 'auth.layout', 'modal' ) ) : ?>
					<div class="devco-auth-dialog__media" style="<?php echo devco_opt( 'auth.image', '' ) ? 'background-image:url(' . esc_url( devco_opt( 'auth.image', '' ) ) . ')' : ''; ?>">
						<span class="devco-auth-dialog__brand"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<p>تجربه خرید اختصاصی، اندازه دقیق و دوخت سفارشی.</p>
					</div>
				<?php endif; ?>

				<div class="devco-auth-dialog__body">
					<?php echo self::form_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * [devco_auth] shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'style' => '' ), $atts );

		if ( is_user_logged_in() ) {
			return '<p class="devco-auth__logged">شما وارد شده‌اید. <a href="' . esc_url( devco_dashboard_url() ) . '">رفتن به داشبورد</a></p>';
		}

		return self::form_markup(
			array(
				'context' => 'inline',
				'style'   => $atts['style'] ? $atts['style'] : devco_opt( 'auth.style', 'elegant' ),
			)
		);
	}
}
