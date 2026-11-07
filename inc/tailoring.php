<?php
/**
 * Tailor-made orders: forms, measurements, requests, invoices.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Tailoring
 */
class DeVco_Tailoring {

	/**
	 * Instance.
	 *
	 * @var DeVco_Tailoring|null
	 */
	protected static $instance = null;

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Tailoring
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
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'template_redirect', array( $this, 'maybe_pay_invoice' ) );

		foreach ( array( 'tailoring_submit', 'tailoring_upload', 'tailoring_price', 'tailoring_status', 'invoice_pay_link' ) as $action ) {
			add_action( 'wp_ajax_devco_' . $action, array( $this, 'ajax_' . $action ) );
			add_action( 'wp_ajax_nopriv_devco_' . $action, array( $this, 'ajax_' . $action ) );
		}

		add_action( 'woocommerce_order_status_processing', array( $this, 'order_paid' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'order_paid' ), 10, 1 );
		add_action( 'woocommerce_payment_complete', array( $this, 'order_paid' ), 10, 1 );
	}

	/**
	 * Status list.
	 *
	 * @return array
	 */
	public static function statuses() {
		return array(
			'pending'   => array( 'label' => 'در انتظار بررسی', 'icon' => 'clock', 'color' => '#f59e0b' ),
			'review'    => array( 'label' => 'در حال بازبینی', 'icon' => 'eye', 'color' => '#3b82f6' ),
			'priced'    => array( 'label' => 'قیمت‌گذاری شده', 'icon' => 'tag', 'color' => '#8b5cf6' ),
			'invoiced'  => array( 'label' => 'فاکتور صادر شده', 'icon' => 'invoice', 'color' => '#0ea5e9' ),
			'paid'      => array( 'label' => 'پرداخت شده', 'icon' => 'wallet', 'color' => '#10b981' ),
			'in_progress' => array( 'label' => 'در حال دوخت', 'icon' => 'scissors', 'color' => '#6366f1' ),
			'ready'     => array( 'label' => 'آماده تحویل', 'icon' => 'check-badge', 'color' => '#14b8a6' ),
			'delivered' => array( 'label' => 'تحویل شده', 'icon' => 'check', 'color' => '#22c55e' ),
			'cancelled' => array( 'label' => 'لغو شده', 'icon' => 'close', 'color' => '#ef4444' ),
		);
	}

	/**
	 * Label for a status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function status_label( $status ) {
		$all = self::statuses();
		return isset( $all[ $status ] ) ? $all[ $status ]['label'] : $status;
	}

	/**
	 * Register post types.
	 */
	public function register_post_types() {
		register_post_type(
			'devco_tform',
			array(
				'labels'       => array(
					'name'          => 'فرم‌های دوخت',
					'singular_name' => 'فرم دوخت',
					'add_new_item'  => 'ساخت فرم دوخت',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title', 'editor' ),
				'capability_type' => 'post',
			)
		);

		register_post_type(
			'devco_trequest',
			array(
				'labels'          => array(
					'name'          => 'درخواست‌های دوخت',
					'singular_name' => 'درخواست دوخت',
					'menu_name'     => 'سفارش دوخت',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'supports'        => array( 'title', 'editor', 'author' ),
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'devco_invoice',
			array(
				'labels'          => array(
					'name'          => 'فاکتورها',
					'singular_name' => 'فاکتور',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'    => true,
			)
		);
	}

	/* --------------------------------------------------------------------- */
	/* Forms                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * The active form ID.
	 *
	 * @return int
	 */
	public static function active_form_id() {
		$forms = get_posts(
			array(
				'post_type'      => 'devco_tform',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		return $forms ? (int) $forms[0] : 0;
	}

	/**
	 * Form fields.
	 *
	 * @param int $form_id Form ID.
	 * @return array
	 */
	public static function form_fields( $form_id = 0 ) {
		$form_id = $form_id ? $form_id : self::active_form_id();

		if ( ! $form_id ) {
			return self::default_form_fields();
		}

		$fields = get_post_meta( $form_id, '_devco_form_fields', true );

		if ( ! is_array( $fields ) || ! $fields ) {
			return self::default_form_fields();
		}

		return $fields;
	}

	/**
	 * Default drag & drop builder fields.
	 *
	 * @return array
	 */
	public static function default_form_fields() {
		return array(
			array(
				'id'       => 'f_garment',
				'type'     => 'select',
				'label'    => 'نوع لباس',
				'required' => 1,
				'options'  => 'پیراهن,شومیز,کت و شلوار,مانتو,دامن,شلوار,لباس مجلسی',
				'width'    => 'half',
			),
			array(
				'id'       => 'f_fabric',
				'type'     => 'select',
				'label'    => 'جنس پارچه',
				'required' => 1,
				'options'  => 'کرپ,حریر,ساتن,کتان,مخمل,ابریشم',
				'width'    => 'half',
			),
			array(
				'id'       => 'f_bodymap',
				'type'     => 'bodymap',
				'label'    => 'اندازه‌های بدن',
				'required' => 1,
				'width'    => 'full',
				'hint'     => 'روی مانکن بچرخانید و اندازه‌ها را وارد کنید.',
			),
			array(
				'id'       => 'f_color',
				'type'     => 'color',
				'label'    => 'رنگ دلخواه',
				'required' => 0,
				'width'    => 'half',
			),
			array(
				'id'       => 'f_date',
				'type'     => 'date',
				'label'    => 'تاریخ تحویل مورد نظر',
				'required' => 0,
				'width'    => 'half',
			),
			array(
				'id'       => 'f_notes',
				'type'     => 'textarea',
				'label'    => 'توضیحات و جزئیات دوخت',
				'required' => 0,
				'width'    => 'full',
			),
			array(
				'id'       => 'f_files',
				'type'     => 'upload',
				'label'    => 'آپلود طرح و الگو',
				'required' => 0,
				'width'    => 'full',
				'hint'     => 'فرمت‌های مجاز: JPG، PNG، PDF، ZIP',
			),
		);
	}

	/**
	 * Create the default form post (used by the wizard).
	 *
	 * @return int
	 */
	public static function create_default_form() {
		$existing = self::active_form_id();

		if ( $existing ) {
			return $existing;
		}

		$form_id = wp_insert_post(
			array(
				'post_type'   => 'devco_tform',
				'post_status' => 'publish',
				'post_title'  => 'فرم سفارش دوخت پیش‌فرض',
			)
		);

		if ( is_wp_error( $form_id ) ) {
			return 0;
		}

		update_post_meta( $form_id, '_devco_form_fields', self::default_form_fields() );

		return (int) $form_id;
	}

	/* --------------------------------------------------------------------- */
	/* Front-end form                                                         */
	/* --------------------------------------------------------------------- */

	/**
	 * Render the 3D body map.
	 *
	 * @param array $args Args.
	 */
	public static function body_map( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'model'        => devco_opt( 'tailoring.body_model', 'female' ),
				'measurements' => devco_measurements(),
				'values'       => array(),
			)
		);

		$measurements = $args['measurements'];

		?>
		<div class="devco-bodymap devco-bodymap--<?php echo esc_attr( $args['model'] ); ?>" data-bodymap data-values='<?php echo esc_attr( wp_json_encode( $args['values'] ) ); ?>'>
			<div class="devco-bodymap__stage">
				<div class="devco-bodymap__glow" aria-hidden="true"></div>
				<div class="devco-bodymap__figure" data-figure style="--bm-rot:0deg">
					<div class="devco-bodymap__body">
						<span class="bm-part bm-part--head"></span>
						<span class="bm-part bm-part--neck"></span>
						<span class="bm-part bm-part--torso"></span>
						<span class="bm-part bm-part--waist"></span>
						<span class="bm-part bm-part--hips"></span>
						<span class="bm-part bm-part--arm bm-part--arm-l"></span>
						<span class="bm-part bm-part--arm bm-part--arm-r"></span>
						<span class="bm-part bm-part--leg bm-part--leg-l"></span>
						<span class="bm-part bm-part--leg bm-part--leg-r"></span>
						<span class="bm-part bm-part--foot bm-part--foot-l"></span>
						<span class="bm-part bm-part--foot bm-part--foot-r"></span>
					</div>

					<?php foreach ( $measurements as $measure ) : ?>
						<?php if ( empty( $measure['enabled'] ) ) : continue; endif; ?>
						<button type="button"
							class="bm-hotspot bm-hotspot--<?php echo esc_attr( $measure['key'] ); ?>"
							data-measure="<?php echo esc_attr( $measure['key'] ); ?>"
							aria-label="<?php echo esc_attr( $measure['label'] ); ?>">
							<span class="bm-hotspot__dot"></span>
							<span class="bm-hotspot__line"></span>
							<span class="bm-hotspot__label">
								<strong><?php echo esc_html( $measure['label'] ); ?></strong>
								<small data-bm-value><?php echo esc_html( '—' ); ?></small>
								<span class="bm-hotspot__check"><?php devco_icon_e( 'check', array( 'size' => 12 ) ); ?></span>
							</span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="devco-bodymap__hint">
					<span><?php devco_icon_e( 'drag', array( 'size' => 15 ) ); ?> برای چرخش مانکن بکشید</span>
				</div>

				<div class="devco-bodymap__controls">
					<button type="button" class="bm-ctrl" data-rotate="-30" aria-label="چرخش به راست"><?php devco_icon_e( 'chevron-right', array( 'size' => 16 ) ); ?></button>
					<button type="button" class="bm-ctrl bm-ctrl--auto" data-rotate-auto aria-label="چرخش خودکار"><?php devco_icon_e( 'refresh', array( 'size' => 16 ) ); ?></button>
					<button type="button" class="bm-ctrl" data-rotate="30" aria-label="چرخش به چپ"><?php devco_icon_e( 'chevron-left', array( 'size' => 16 ) ); ?></button>
				</div>
			</div>

			<div class="devco-bodymap__panel">
				<div class="devco-bodymap__panel-head">
					<strong>اندازه‌های شما</strong>
					<span class="devco-bodymap__progress"><i data-bm-progress></i></span>
					<small><b data-bm-count>0</b> از <b><?php echo esc_html( devco_num( count( array_filter( wp_list_pluck( $measurements, 'enabled' ) ) ) ) ); ?></b></small>
				</div>

				<div class="devco-bodymap__fields">
					<?php foreach ( $measurements as $measure ) : ?>
						<?php if ( empty( $measure['enabled'] ) ) : continue; endif; ?>
						<label class="bm-field" data-bm-field="<?php echo esc_attr( $measure['key'] ); ?>">
							<span class="bm-field__label"><?php echo esc_html( $measure['label'] ); ?><?php if ( ! empty( $measure['hint'] ) ) : ?><small><?php echo esc_html( $measure['hint'] ); ?></small><?php endif; ?></span>
							<span class="bm-field__input">
								<input type="number" step="0.5" min="0" name="measure_<?php echo esc_attr( $measure['key'] ); ?>" data-bm-input="<?php echo esc_attr( $measure['key'] ); ?>" placeholder="0" value="<?php echo esc_attr( isset( $args['values'][ $measure['key'] ] ) ? $args['values'][ $measure['key'] ] : '' ); ?>" />
								<em><?php echo esc_html( isset( $measure['unit'] ) && $measure['unit'] ? $measure['unit'] : 'سانتی‌متر' ); ?></em>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a form field from the builder schema.
	 *
	 * @param array $field Field.
	 * @param array $values Values.
	 * @param int   $form_id Form ID.
	 */
	public static function render_field( $field, $values = array(), $form_id = 0 ) {
		$id       = isset( $field['id'] ) ? $field['id'] : 'field_' . wp_rand( 10, 999 );
		$type     = isset( $field['type'] ) ? $field['type'] : 'text';
		$label    = isset( $field['label'] ) ? $field['label'] : '';
		$required = ! empty( $field['required'] );
		$width    = isset( $field['width'] ) ? $field['width'] : 'full';
		$value    = isset( $values[ $id ] ) ? $values[ $id ] : '';
		$req_attr = $required ? 'required' : '';

		printf(
			'<div class="devco-tform__field devco-tform__field--%1$s devco-tform__field--type-%2$s" data-field-id="%3$s">',
			esc_attr( $width ),
			esc_attr( $type ),
			esc_attr( $id )
		);

		if ( 'bodymap' === $type ) {
			if ( devco_opt_bool( 'tailoring.body_3d', true ) ) {
				echo '<div class="devco-tform__label"><strong>' . esc_html( $label ) . '</strong>';
				if ( ! empty( $field['hint'] ) ) {
					echo '<small>' . esc_html( $field['hint'] ) . '</small>';
				}
				echo '</div>';
				self::body_map( array( 'values' => is_array( $value ) ? $value : array() ) );
			} else {
				self::render_simple_measure_fields( $label, is_array( $value ) ? $value : array() );
			}

			echo '</div>';
			return;
		}

		if ( 'upload' === $type ) {
			$max = (int) devco_opt( 'tailoring.max_upload', 8 );

			printf(
				'<div class="devco-tform__label"><strong>%1$s</strong>%2$s</div>
				<div class="devco-upload" data-upload>
					<input type="file" id="devco-%3$s" name="%3$s[]" class="devco-upload__input" multiple accept="image/*,.pdf,.zip" data-max="%4$d" />
					<label class="devco-upload__drop" for="devco-%3$s">
						<span class="devco-upload__icon">%5$s</span>
						<span class="devco-upload__title">طرح خود را اینجا رها کنید یا کلیک کنید</span>
						<span class="devco-upload__hint">JPG، PNG، PDF، ZIP — حداکثر %4$d مگابایت برای هر فایل</span>
					</label>
					<div class="devco-upload__list" data-upload-list></div>
				</div>',
				esc_html( $label ),
				$required ? ' <span class="devco-req">*</span>' : '',
				esc_attr( $id ),
				$max,
				devco_icon( 'upload', array( 'size' => 26 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);

			echo '</div>';
			return;
		}

		printf( '<label class="devco-tform__label" for="devco-%1$s"><strong>%2$s</strong>%3$s', esc_attr( $id ), esc_html( $label ), $required ? ' <span class="devco-req">*</span>' : '' );

		if ( ! empty( $field['hint'] ) ) {
			echo '<small>' . esc_html( $field['hint'] ) . '</small>';
		}

		echo '</label>';

		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea class="devco-input" id="devco-%1$s" name="%1$s" rows="4" %2$s placeholder="%3$s">%4$s</textarea>',
					esc_attr( $id ),
					esc_attr( $req_attr ),
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
					esc_textarea( is_scalar( $value ) ? $value : '' )
				);
				break;

			case 'select':
				$options = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $field['options'] ) ? $field['options'] : '' ) ) ) );
				printf( '<select class="devco-select" id="devco-%1$s" name="%1$s" %2$s>', esc_attr( $id ), esc_attr( $req_attr ) );
				echo '<option value="">— انتخاب کنید —</option>';
				foreach ( $options as $option ) {
					printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ) );
				}
				echo '</select>';
				break;

			case 'radio':
				$options = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $field['options'] ) ? $field['options'] : '' ) ) ) );
				echo '<div class="devco-tform__choices">';
				foreach ( $options as $option ) {
					printf(
						'<label class="devco-choice"><input type="radio" name="%1$s" value="%2$s" %3$s /><span>%2$s</span></label>',
						esc_attr( $id ),
						esc_attr( $option ),
						checked( (string) $value, (string) $option, false )
					);
				}
				echo '</div>';
				break;

			case 'checkbox':
				$options = array_filter( array_map( 'trim', explode( ',', (string) ( isset( $field['options'] ) ? $field['options'] : '' ) ) ) );
				echo '<div class="devco-tform__choices">';
				foreach ( $options as $option ) {
					printf(
						'<label class="devco-choice"><input type="checkbox" name="%1$s[]" value="%2$s" /><span>%2$s</span></label>',
						esc_attr( $id ),
						esc_attr( $option )
					);
				}
				echo '</div>';
				break;

			case 'color':
				printf(
					'<input type="color" class="devco-color-input" id="devco-%1$s" name="%1$s" value="%2$s" />',
					esc_attr( $id ),
					esc_attr( $value ? $value : '#c8a96a' )
				);
				break;

			case 'number':
			case 'range':
				printf(
					'<input type="%1$s" class="devco-input" id="devco-%2$s" name="%2$s" value="%3$s" %4$s placeholder="%5$s" />',
					'range' === $type ? 'range' : 'number',
					esc_attr( $id ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					esc_attr( $req_attr ),
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
				);
				break;

			case 'heading':
				echo '<h3 class="devco-tform__section-title">' . esc_html( $label ) . '</h3>';
				break;

			case 'date':
				printf( '<input type="date" class="devco-input" id="devco-%1$s" name="%1$s" value="%2$s" %3$s />', esc_attr( $id ), esc_attr( $value ), esc_attr( $req_attr ) );
				break;

			default:
				printf(
					'<input type="%1$s" class="devco-input" id="devco-%2$s" name="%2$s" value="%3$s" %4$s placeholder="%5$s" />',
					in_array( $type, array( 'email', 'tel', 'url' ), true ) ? esc_attr( $type ) : 'text',
					esc_attr( $id ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					esc_attr( $req_attr ),
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
				);
		}

		echo '</div>';
	}

	/**
	 * Simple (non-3D) measurement fields.
	 *
	 * @param string $label Label.
	 * @param array  $values Values.
	 */
	public static function render_simple_measure_fields( $label, $values = array() ) {
		echo '<div class="devco-tform__label"><strong>' . esc_html( $label ) . '</strong></div>';
		echo '<div class="devco-simple-measures">';

		foreach ( devco_measurements() as $measure ) {
			if ( empty( $measure['enabled'] ) ) {
				continue;
			}

			printf(
				'<label class="devco-simple-measure"><span>%1$s</span><input type="number" step="0.5" name="measure_%2$s" value="%3$s" placeholder="0" /><em>%4$s</em></label>',
				esc_html( $measure['label'] ),
				esc_attr( $measure['key'] ),
				esc_attr( isset( $values[ $measure['key'] ] ) ? $values[ $measure['key'] ] : '' ),
				esc_html( $measure['unit'] )
			);
		}

		echo '</div>';
	}

	/**
	 * Render the complete tailoring form.
	 *
	 * @param array $args Args.
	 */
	public static function render_form( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'form_id' => self::active_form_id(),
				'compact' => false,
				'title'   => devco_opt( 'tailoring.title', 'سفارش دوخت اختصاصی' ),
			)
		);

		$fields      = self::form_fields( $args['form_id'] );
		$measurements = devco_measurements();
		$user        = wp_get_current_user();
		$logged       = is_user_logged_in();
		$guest_ok     = devco_opt_bool( 'tailoring.guest', false );

		if ( ! $logged && ! $guest_ok ) {
			echo '<div class="devco-panel devco-panel--auth-required">';
			echo '<h3>برای ثبت سفارش دوخت وارد شوید</h3>';
			echo '<p>با شماره موبایل خود در چند ثانیه وارد شوید تا سفارش‌ها و اندازه‌هایتان ذخیره شود.</p>';
			echo '<button type="button" class="devco-btn devco-btn--primary js-devco-auth-open">ورود / ثبت‌نام سریع</button>';
			echo '</div>';
			return;
		}
		?>
		<div class="devco-tailoring" data-tailoring-form data-form-id="<?php echo (int) $args['form_id']; ?>">
			<div class="devco-tailoring__head">
				<span class="devco-tailoring__eyebrow"><?php devco_icon_e( 'scissors', array( 'size' => 16 ) ); ?> دوخت سفارشی</span>
				<h2 class="devco-tailoring__title"><?php echo esc_html( $args['title'] ); ?></h2>
				<p class="devco-tailoring__intro"><?php echo esc_html( devco_opt( 'tailoring.intro', '' ) ); ?></p>

				<ol class="devco-tailoring__steps" data-steps>
					<li class="is-active" data-step="1"><b>۱</b> انتخاب لباس</li>
					<li data-step="2"><b>۲</b> اندازه‌گیری</li>
					<li data-step="3"><b>۳</b> طرح و تایید</li>
				</ol>
			</div>

			<form class="devco-tform js-devco-tailoring" novalidate data-nonce="<?php echo esc_attr( wp_create_nonce( 'devco_front' ) ); ?>">
				<input type="hidden" name="form_id" value="<?php echo (int) $args['form_id']; ?>" />

				<div class="devco-tform__step" data-step-panel="1">
					<div class="devco-tform__grid">
						<?php
						foreach ( $fields as $field ) {
							if ( 'bodymap' === $field['type'] || 'upload' === $field['type'] ) {
								continue;
							}
							self::render_field( $field );
						}
						?>
					</div>
					<div class="devco-tform__nav">
						<span></span>
						<button type="button" class="devco-btn devco-btn--primary" data-step-next="2">مرحله بعد: اندازه‌گیری <?php devco_icon_e( 'arrow-left', array( 'size' => 16 ) ); ?></button>
					</div>
				</div>

				<div class="devco-tform__step" data-step-panel="2" hidden>
					<?php
					if ( devco_opt_bool( 'tailoring.body_3d', true ) ) {
						echo '<div class="devco-tform__label"><strong>اندازه‌های بدن</strong><small>روی مانکن سه‌بعدی بچرخانید یا از لیست سمت راست وارد کنید.</small></div>';
						self::body_map( array( 'measurements' => $measurements ) );
					} else {
						self::render_simple_measure_fields( 'اندازه‌های بدن', array() );
					}
					?>
					<div class="devco-tform__nav">
						<button type="button" class="devco-btn devco-btn--ghost" data-step-prev="1"><?php devco_icon_e( 'chevron-right', array( 'size' => 15 ) ); ?> مرحله قبل</button>
						<button type="button" class="devco-btn devco-btn--primary" data-step-next="3">مرحله بعد: طرح و تایید <?php devco_icon_e( 'arrow-left', array( 'size' => 16 ) ); ?></button>
					</div>
				</div>

				<div class="devco-tform__step" data-step-panel="3" hidden>
					<div class="devco-tform__grid">
						<?php
						foreach ( $fields as $field ) {
							if ( 'upload' !== $field['type'] ) {
								continue;
							}
							self::render_field( $field );
						}
						?>

						<?php if ( ! $logged ) : ?>
							<div class="devco-tform__field devco-tform__field--half">
								<label class="devco-tform__label" for="devco-guest-name"><strong>نام شما</strong></label>
								<input type="text" id="devco-guest-name" name="guest_name" class="devco-input" required />
							</div>
							<div class="devco-tform__field devco-tform__field--half">
								<label class="devco-tform__label" for="devco-guest-mobile"><strong>شماره موبایل</strong></label>
								<input type="tel" id="devco-guest-mobile" name="guest_mobile" class="devco-input" required />
							</div>
						<?php endif; ?>

						<div class="devco-tform__field devco-tform__field--full">
							<label class="devco-tform__terms">
								<input type="checkbox" name="terms" value="1" required />
								<span><?php echo esc_html( devco_opt( 'tailoring.terms', '' ) ); ?></span>
							</label>
						</div>
					</div>

					<div class="devco-tform__summary" data-form-summary></div>

					<div class="devco-tform__nav">
						<button type="button" class="devco-btn devco-btn--ghost" data-step-prev="2"><?php devco_icon_e( 'chevron-right', array( 'size' => 15 ) ); ?> مرحله قبل</button>
						<button type="submit" class="devco-btn devco-btn--primary devco-btn--lg">
							<?php devco_icon_e( 'check', array( 'size' => 18 ) ); ?>
							<span>ثبت درخواست و ارسال برای مدیر</span>
							<span class="devco-spinner" hidden></span>
						</button>
					</div>

					<p class="devco-tform__message" data-tform-message role="status" aria-live="polite"></p>
				</div>
			</form>
		</div>
		<?php
	}

	/* --------------------------------------------------------------------- */
	/* Requests                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * AJAX: submit a tailoring request.
	 */
	public function ajax_tailoring_submit() {
		check_ajax_referer( 'devco_front', 'nonce' );

		$logged   = is_user_logged_in();
		$guest_ok = devco_opt_bool( 'tailoring.guest', false );

		if ( ! $logged && ! $guest_ok ) {
			wp_send_json_error( array( 'message' => 'برای ثبت درخواست وارد شوید.' ) );
		}

		$form_id  = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : self::active_form_id();
		$raw      = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$measures = isset( $_POST['measurements'] ) ? wp_unslash( $_POST['measurements'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$uploads  = isset( $_POST['uploads'] ) ? (array) wp_unslash( $_POST['uploads'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$notes    = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$name     = isset( $_POST['guest_name'] ) ? sanitize_text_field( wp_unslash( $_POST['guest_name'] ) ) : '';
		$mobile   = isset( $_POST['guest_mobile'] ) ? DeVco_SMS::normalize( wp_unslash( $_POST['guest_mobile'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$values = array();

		foreach ( $raw as $key => $value ) {
			$values[ sanitize_key( $key ) ] = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
		}

		$measure_values = array();

		foreach ( (array) $measures as $key => $value ) {
			$measure_values[ sanitize_key( $key ) ] = (float) str_replace( array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ), (string) $value );
		}

		$user_id = get_current_user_id();

		if ( $user_id ) {
			$user   = get_userdata( $user_id );
			$name   = $name ? $name : $user->display_name;
			$mobile = get_user_meta( $user_id, 'devco_phone', true );
			$mobile = $mobile ? $mobile : get_user_meta( $user_id, 'billing_phone', true );
		} else {
			$mobile = DeVco_SMS::normalize( $mobile );

			if ( ! DeVco_SMS::is_valid_mobile( $mobile ) ) {
				wp_send_json_error( array( 'message' => 'شماره موبایل نامعتبر است.' ) );
			}

			$existing = DeVco_Auth::user_by_phone( $mobile );

			if ( $existing ) {
				$user_id = $existing->ID;
			} else {
				$created = DeVco_Auth::create_user( $mobile, $name );

				if ( ! is_wp_error( $created ) ) {
					$user_id = $created;
				}
			}
		}

		$title = sprintf( 'درخواست دوخت #%s — %s', wp_rand( 1000, 9999 ), $name ? $name : 'مشتری' );

		$request_id = wp_insert_post(
			array(
				'post_type'   => 'devco_trequest',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_content' => $notes,
				'post_author' => $user_id ? $user_id : 0,
			)
		);

		if ( is_wp_error( $request_id ) ) {
			wp_send_json_error( array( 'message' => 'ثبت درخواست ناموفق بود.' ) );
		}

		$attachments = array();

		foreach ( (array) $uploads as $attachment_id ) {
			$attachment_id = (int) $attachment_id;

			if ( $attachment_id && get_post( $attachment_id ) ) {
				$attachments[] = $attachment_id;
				wp_update_post(
					array(
						'ID'          => $attachment_id,
						'post_parent' => $request_id,
					)
				);
			}
		}

		update_post_meta( $request_id, '_devco_status', 'pending' );
		update_post_meta( $request_id, '_devco_form_id', $form_id );
		update_post_meta( $request_id, '_devco_values', $values );
		update_post_meta( $request_id, '_devco_measurements', $measure_values );
		update_post_meta( $request_id, '_devco_files', $attachments );
		update_post_meta( $request_id, '_devco_name', $name );
		update_post_meta( $request_id, '_devco_mobile', $mobile );
		update_post_meta( $request_id, '_devco_user_id', $user_id );
		update_post_meta( $request_id, '_devco_timeline', array( array( 'status' => 'pending', 'time' => current_time( 'mysql' ), 'note' => 'ثبت درخواست توسط مشتری' ) ) );

		// Keep the latest measurements on the customer profile.
		if ( $user_id ) {
			update_user_meta( $user_id, 'devco_measurements', $measure_values );
			update_user_meta( $user_id, 'devco_last_request', $request_id );
		}

		$short_id = $request_id;

		DeVco_SMS::send_event(
			$mobile,
			'tailoring',
			array(
				'name'       => $name ? $name : 'مشتری',
				'request_id' => $short_id,
				'status'     => self::status_label( 'pending' ),
			)
		);

		if ( devco_opt_bool( 'tailoring.notify_admin', true ) ) {
			DeVco_SMS::notify_admin( 'درخواست دوخت جدید #' . $short_id . ' از ' . ( $name ? $name : $mobile ) . ' — بررسی کنید: ' . admin_url( 'admin.php?page=devco-app&view=tailoring&id=' . $request_id ) );
		}

		do_action( 'devco_tailoring_request_created', $request_id, $measure_values );

		wp_send_json_success(
			array(
				'message'  => 'درخواست شما با موفقیت ثبت شد. پس از بررسی و قیمت‌گذاری، فاکتور برای شما ارسال می‌شود.',
				'request'  => $request_id,
				'redirect' => devco_dashboard_url( 'tailoring' ),
			)
		);
	}

	/**
	 * AJAX: upload a design file.
	 */
	public function ajax_tailoring_upload() {
		check_ajax_referer( 'devco_front', 'nonce' );

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => 'فایلی انتخاب نشده است.' ) );
		}

		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$max_mb = (int) devco_opt( 'tailoring.max_upload', 8 );
		$file   = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( $file['size'] > $max_mb * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => 'حجم فایل بیشتر از حد مجاز است.' ) );
		}

		$allowed = array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'zip' );
		$ext     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => 'فرمت فایل مجاز نیست.' ) );
		}

		$attachment_id = media_handle_upload(
			'file',
			0,
			array(),
			array(
				'test_form' => false,
				'mimes'     => array(
					'pdf' => 'application/pdf',
					'zip' => 'application/zip',
				),
			)
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'id'    => $attachment_id,
				'url'   => wp_get_attachment_url( $attachment_id ),
				'name'  => basename( get_attached_file( $attachment_id ) ),
				'thumb' => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
			)
		);
	}

	/**
	 * AJAX: admin prices a request and creates the invoice.
	 */
	public function ajax_tailoring_price() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$amount     = isset( $_POST['amount'] ) ? (float) preg_replace( '/[^0-9.]/', '', wp_unslash( $_POST['amount'] ) ) : 0;
		$note       = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
		$status     = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'priced';

		if ( ! $request_id || ! $amount ) {
			wp_send_json_error( array( 'message' => 'درخواست یا مبلغ نامعتبر است.' ) );
		}

		$user_id = (int) get_post_meta( $request_id, '_devco_user_id', true );
		$mobile  = get_post_meta( $request_id, '_devco_mobile', true );
		$name    = get_post_meta( $request_id, '_devco_name', true );

		$invoice_id = self::create_invoice(
			array(
				'request_id' => $request_id,
				'user_id'    => $user_id,
				'title'      => $title ? $title : 'هزینه دوخت سفارشی',
				'amount'     => $amount,
				'note'       => $note,
			)
		);

		update_post_meta( $request_id, '_devco_status', 'invoiced' );
		update_post_meta( $request_id, '_devco_invoice_id', $invoice_id );
		self::add_timeline( $request_id, 'invoiced', 'فاکتور #' . $invoice_id . ' صادر شد.' );

		$link = devco_dashboard_url( 'invoices' );

		DeVco_SMS::send_event(
			$mobile,
			'invoice',
			array(
				'name'       => $name ? $name : 'مشتری',
				'invoice_id' => $invoice_id,
				'total'      => devco_price( $amount ),
				'link'       => $link,
				'request_id' => $request_id,
			)
		);

		if ( 'review' === $status ) {
			update_post_meta( $request_id, '_devco_status', 'review' );
		}

		wp_send_json_success(
			array(
				'message'    => 'فاکتور صادر شد و پیامک برای مشتری ارسال گردید.',
				'invoice_id' => $invoice_id,
			)
		);
	}

	/**
	 * AJAX: change request status.
	 */
	public function ajax_tailoring_status() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$request_id = isset( $_POST['request_id'] ) ? (int) $_POST['request_id'] : 0;
		$status     = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$note       = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		if ( ! $request_id || ! isset( self::statuses()[ $status ] ) ) {
			wp_send_json_error( array( 'message' => 'داده نامعتبر است.' ) );
		}

		update_post_meta( $request_id, '_devco_status', $status );
		self::add_timeline( $request_id, $status, $note );

		$mobile = get_post_meta( $request_id, '_devco_mobile', true );
		$name   = get_post_meta( $request_id, '_devco_name', true );

		DeVco_SMS::send_event(
			$mobile,
			'tailoring_status',
			array(
				'name'       => $name ? $name : 'مشتری',
				'request_id' => $request_id,
				'status'     => self::status_label( $status ),
				'note'       => $note,
			)
		);

		wp_send_json_success( array( 'message' => 'وضعیت به‌روزرسانی شد.' ) );
	}

	/**
	 * Append a timeline entry.
	 *
	 * @param int    $request_id Request.
	 * @param string $status     Status.
	 * @param string $note       Note.
	 */
	public static function add_timeline( $request_id, $status, $note = '' ) {
		$timeline   = get_post_meta( $request_id, '_devco_timeline', true );
		$timeline   = is_array( $timeline ) ? $timeline : array();
		$timeline[] = array(
			'status' => $status,
			'time'   => current_time( 'mysql' ),
			'note'   => $note,
			'by'     => get_current_user_id(),
		);

		update_post_meta( $request_id, '_devco_timeline', $timeline );
	}

	/* --------------------------------------------------------------------- */
	/* Invoices                                                               */
	/* --------------------------------------------------------------------- */

	/**
	 * Create an invoice post.
	 *
	 * @param array $args Args.
	 * @return int
	 */
	public static function create_invoice( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'request_id' => 0,
				'user_id'    => 0,
				'title'      => 'فاکتور دوخت',
				'amount'     => 0,
				'note'       => '',
				'items'      => array(),
			)
		);

		$items = $args['items'];

		if ( ! $items ) {
			$items = array(
				array(
					'title' => $args['title'],
					'qty'   => 1,
					'price' => (float) $args['amount'],
				),
			);
		}

		$total = 0;

		foreach ( $items as $item ) {
			$total += (float) $item['price'] * (int) $item['qty'];
		}

		$invoice_id = wp_insert_post(
			array(
				'post_type'   => 'devco_invoice',
				'post_status' => 'publish',
				'post_title'  => 'فاکتور #' . wp_rand( 10000, 99999 ) . ' — ' . $args['title'],
				'post_author' => $args['user_id'],
			)
		);

		if ( is_wp_error( $invoice_id ) ) {
			return 0;
		}

		update_post_meta( $invoice_id, '_devco_items', $items );
		update_post_meta( $invoice_id, '_devco_total', $total );
		update_post_meta( $invoice_id, '_devco_status', 'unpaid' );
		update_post_meta( $invoice_id, '_devco_user_id', $args['user_id'] );
		update_post_meta( $invoice_id, '_devco_request_id', $args['request_id'] );
		update_post_meta( $invoice_id, '_devco_note', $args['note'] );
		update_post_meta( $invoice_id, '_devco_created', current_time( 'mysql' ) );

		return (int) $invoice_id;
	}

	/**
	 * Invoice status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public static function invoice_status_label( $status ) {
		$map = array(
			'unpaid'    => 'در انتظار پرداخت',
			'paid'      => 'پرداخت شده',
			'cancelled' => 'لغو شده',
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}

	/**
	 * Payment URL for an invoice.
	 *
	 * @param int $invoice_id Invoice.
	 * @return string
	 */
	public static function invoice_pay_url( $invoice_id ) {
		return add_query_arg( 'devco_pay_invoice', (int) $invoice_id, devco_dashboard_url( 'invoices' ) );
	}

	/**
	 * Create a WooCommerce order for an invoice and redirect to the gateway.
	 *
	 * @param int $invoice_id Invoice.
	 * @return string|WP_Error Checkout URL.
	 */
	public static function create_order_for_invoice( $invoice_id ) {
		if ( ! devco_is_woo() ) {
			return new WP_Error( 'devco_no_woo', 'ووکامرس فعال نیست.' );
		}

		$invoice_id = (int) $invoice_id;
		$user_id    = (int) get_post_meta( $invoice_id, '_devco_user_id', true );
		$items      = get_post_meta( $invoice_id, '_devco_items', true );
		$status     = get_post_meta( $invoice_id, '_devco_status', true );
		$order_id   = (int) get_post_meta( $invoice_id, '_devco_order_id', true );

		if ( 'paid' === $status && $order_id ) {
			return wc_get_checkout_url();
		}

		if ( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order && ! $order->is_paid() ) {
				return $order->get_checkout_payment_url( true );
			}
		}

		if ( ! is_array( $items ) || ! $items ) {
			return new WP_Error( 'devco_empty_invoice', 'فاکتور خالی است.' );
		}

		$order = wc_create_order( array( 'customer_id' => $user_id ) );

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		foreach ( $items as $item ) {
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( $item['title'] );
			$fee->set_quantity( (int) $item['qty'] );
			$fee->set_amount( (float) $item['price'] );
			$fee->set_total( (float) $item['price'] * (int) $item['qty'] );
			$order->add_item( $fee );
		}

		$order->set_created_via( 'devco-invoice' );
		$order->update_meta_data( '_devco_invoice_id', $invoice_id );
		$order->calculate_totals();
		$order->update_status( 'pending', 'ایجاد شده از فاکتور سفارش دوخت #' . $invoice_id );
		$order->save();

		update_post_meta( $invoice_id, '_devco_order_id', $order->get_id() );

		return $order->get_checkout_payment_url( true );
	}

	/**
	 * Handle the invoice payment link.
	 */
	public function maybe_pay_invoice() {
		if ( empty( $_GET['devco_pay_invoice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$invoice_id = (int) $_GET['devco_pay_invoice']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id    = (int) get_post_meta( $invoice_id, '_devco_user_id', true );

		if ( ! is_user_logged_in() || ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_woocommerce' ) ) ) {
			wp_safe_redirect( devco_dashboard_url( 'invoices' ) );
			exit;
		}

		if ( 'paid' === get_post_meta( $invoice_id, '_devco_status', true ) ) {
			wc_add_notice( 'این فاکتور قبلاً پرداخت شده است.', 'notice' );
			wp_safe_redirect( devco_dashboard_url( 'invoices' ) );
			exit;
		}

		$url = self::create_order_for_invoice( $invoice_id );

		if ( is_wp_error( $url ) ) {
			wc_add_notice( $url->get_error_message(), 'error' );
			wp_safe_redirect( devco_dashboard_url( 'invoices' ) );
			exit;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Mark invoices paid when their WooCommerce order is paid.
	 */
	public function order_paid( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$invoice_id = (int) $order->get_meta( '_devco_invoice_id' );

		if ( ! $invoice_id || 'paid' === get_post_meta( $invoice_id, '_devco_status', true ) ) {
			return;
		}

		update_post_meta( $invoice_id, '_devco_status', 'paid' );
		update_post_meta( $invoice_id, '_devco_paid_at', current_time( 'mysql' ) );

		$request_id = (int) get_post_meta( $invoice_id, '_devco_request_id', true );

		if ( $request_id ) {
			update_post_meta( $request_id, '_devco_status', 'paid' );
			self::add_timeline( $request_id, 'paid', 'پرداخت فاکتور #' . $invoice_id . ' انجام شد.' );

			$mobile = get_post_meta( $request_id, '_devco_mobile', true );

			DeVco_SMS::send_event(
				$mobile,
				'tailoring_status',
				array(
					'name'       => get_post_meta( $request_id, '_devco_name', true ),
					'request_id' => $request_id,
					'status'     => self::status_label( 'paid' ),
				)
			);
		}
	}
}

/**
 * Enabled measurements from theme options.
 *
 * @return array
 */
function devco_measurements() {
	$list = devco_opt( 'tailoring.measurements', array() );

	if ( ! is_array( $list ) || ! $list ) {
		$list = devco_default_measurements();
	}

	return array_values(
		array_filter(
			$list,
			function ( $item ) {
				return is_array( $item ) && ! empty( $item['label'] );
			}
		)
	);
}

/**
 * Render the tailoring form (template helper).
 *
 * @param array $args Args.
 */
function devco_tailoring_form( $args = array() ) {
	DeVco_Tailoring::render_form( $args );
}
