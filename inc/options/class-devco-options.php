<?php
/**
 * DeVco options framework.
 *
 * A lightweight, dependency-free options panel with tabs, sections and
 * rich field types (color, media, repeater, sortable, slot matrix, ...).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Options
 */
class DeVco_Options {

	/**
	 * Singleton instance.
	 *
	 * @var DeVco_Options|null
	 */
	protected static $instance = null;

	/**
	 * Cached schema.
	 *
	 * @var array|null
	 */
	protected static $schema = null;

	/**
	 * Init hooks.
	 */
	public static function init() {
		self::$instance = new self();

		add_action( 'admin_menu', array( self::$instance, 'register_page' ) );
		add_action( 'admin_post_devco_save_options', array( self::$instance, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( self::$instance, 'admin_assets' ) );
		add_action( 'wp_ajax_devco_reset_options', array( self::$instance, 'ajax_reset' ) );
	}

	/**
	 * Instance accessor.
	 *
	 * @return DeVco_Options
	 */
	public static function instance() {
		return self::$instance ? self::$instance : new self();
	}

	/**
	 * The full panel schema.
	 *
	 * @return array
	 */
	public static function schema() {
		if ( null === self::$schema ) {
			self::$schema = require DEVCO_DIR . '/inc/options/schema.php';
		}

		return self::$schema;
	}

	/**
	 * Flatten all fields (including nested groups) from the schema.
	 *
	 * @return array
	 */
	public static function fields() {
		static $fields = null;

		if ( null !== $fields ) {
			return $fields;
		}

		$fields = array();

		foreach ( self::schema() as $tab ) {
			if ( empty( $tab['sections'] ) ) {
				continue;
			}

			foreach ( $tab['sections'] as $section ) {
				if ( empty( $section['fields'] ) ) {
					continue;
				}

				foreach ( $section['fields'] as $field ) {
					if ( empty( $field['id'] ) ) {
						continue;
					}

					$fields[ $field['id'] ] = $field;

					if ( ! empty( $field['fields'] ) ) {
						foreach ( $field['fields'] as $sub ) {
							$fields[ $field['id'] . '.' . $sub['id'] ] = $sub;
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Default value for a key.
	 *
	 * @param string $key Key.
	 * @return mixed|null
	 */
	public static function default_for( $key ) {
		$fields = self::fields();

		if ( isset( $fields[ $key ] ) && array_key_exists( 'default', $fields[ $key ] ) ) {
			return $fields[ $key ]['default'];
		}

		return null;
	}

	/**
	 * All defaults as a flat array.
	 *
	 * @return array
	 */
	public static function defaults() {
		$defaults = array();

		foreach ( self::fields() as $key => $field ) {
			if ( array_key_exists( 'default', $field ) ) {
				$defaults[ $key ] = $field['default'];
			}
		}

		return $defaults;
	}

	/**
	 * Register the theme panel.
	 */
	public function register_page() {
		$cap = 'manage_options';

		add_menu_page(
			'دیوکو',
			'دیوکو',
			$cap,
			'devco-settings',
			array( $this, 'render_page' ),
			'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#a7aaad"><path d="M4 3h9a9 9 0 0 1 0 18H4Zm4 4v10h5a5 5 0 0 0 0-10Z"/></svg>' ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			58
		);

		add_submenu_page(
			'devco-settings',
			'تنظیمات قالب',
			'تنظیمات قالب',
			$cap,
			'devco-settings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue admin assets on DeVco screens.
	 *
	 * @param string $hook Current admin page.
	 */
	public function admin_assets( $hook ) {
		$screen = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$our_screens = array( 'devco-settings', 'devco-wizard', 'devco-app', 'devco-forms', 'devco-tailoring' );

		if ( ! in_array( $screen, $our_screens, true ) && false === strpos( $hook, 'devco' ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_style(
			'devco-admin',
			devco_asset( 'css/admin.css' ),
			array( 'wp-color-picker' ),
			DEVCO_VERSION
		);

		wp_enqueue_script(
			'devco-admin',
			devco_asset( 'js/admin.js' ),
			array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
			DEVCO_VERSION,
			true
		);

		if ( 'devco-wizard' === $screen ) {
			wp_enqueue_script(
				'devco-wizard',
				devco_asset( 'js/wizard.js' ),
				array( 'devco-admin' ),
				DEVCO_VERSION,
				true
			);
		}

		wp_localize_script(
			'devco-admin',
			'DeVcoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'devco_admin' ),
				'i18n'    => array(
					'select'   => 'انتخاب تصویر',
					'use'      => 'استفاده از تصویر',
					'remove'   => 'حذف',
					'addRow'   => 'افزودن',
					'confirm'  => 'آیا مطمئن هستید؟',
					'copied'   => 'کپی شد',
					'saved'    => 'ذخیره شد',
					'sending'  => 'در حال ارسال…',
					'error'    => 'خطا در ارتباط با سرور',
					'collapse' => 'بستن',
				),
			)
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$schema   = self::schema();
		$options  = devco_options();
		$active   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs     = array_keys( $schema );
		$settings = isset( $_GET['settings-saved'] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $active, $tabs, true ) ) {
			$active = $tabs[0];
		}

		?>
		<div class="wrap devco-admin-wrap" dir="rtl">
			<header class="devco-admin-header">
				<div class="devco-admin-brand">
					<span class="devco-admin-logo">دیوکو</span>
					<span class="devco-admin-sub">تنظیمات قالب فروشگاهی</span>
				</div>
				<nav class="devco-admin-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-wizard' ) ); ?>">ویزارد راه‌اندازی</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=devco-app' ) ); ?>">پنل مدیریت (وب‌اپ)</a>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">مشاهده سایت</a>
				</nav>
			</header>

			<?php if ( $settings ) : ?>
				<div class="devco-notice devco-notice--success">تنظیمات با موفقیت ذخیره شد.</div>
			<?php endif; ?>

			<div class="devco-settings">
				<aside class="devco-settings-nav">
					<?php foreach ( $schema as $tab_key => $tab ) : ?>
						<a class="devco-settings-nav-item <?php echo $active === $tab_key ? 'is-active' : ''; ?>"
							href="<?php echo esc_url( add_query_arg( array( 'page' => 'devco-settings', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>">
							<span class="devco-nav-icon"><?php devco_icon_e( isset( $tab['icon'] ) ? $tab['icon'] : 'settings' ); ?></span>
							<span class="devco-nav-label"><?php echo esc_html( $tab['title'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</aside>

				<div class="devco-settings-body">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="devco-form">
						<input type="hidden" name="action" value="devco_save_options" />
						<input type="hidden" name="tab" value="<?php echo esc_attr( $active ); ?>" />
						<?php wp_nonce_field( 'devco_save_options' ); ?>

						<h1 class="devco-page-title"><?php echo esc_html( $schema[ $active ]['title'] ); ?></h1>
						<?php if ( ! empty( $schema[ $active ]['description'] ) ) : ?>
							<p class="devco-page-desc"><?php echo esc_html( $schema[ $active ]['description'] ); ?></p>
						<?php endif; ?>

						<?php
						if ( ! empty( $schema[ $active ]['sections'] ) ) {
							foreach ( $schema[ $active ]['sections'] as $section ) {
								$this->render_section( $section, $options );
							}
						}
						?>

						<div class="devco-form-actions">
							<button type="submit" class="devco-btn devco-btn--primary">ذخیره تغییرات</button>
							<button type="button" class="devco-btn devco-btn--ghost js-devco-reset" data-tab="<?php echo esc_attr( $active ); ?>">بازگردانی این تب به پیش‌فرض</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a section.
	 *
	 * @param array $section Section schema.
	 * @param array $options Stored options.
	 */
	protected function render_section( $section, $options ) {
		$open = ! isset( $section['collapsed'] ) || ! $section['collapsed'];
		?>
		<section class="devco-section <?php echo $open ? 'is-open' : ''; ?>">
			<header class="devco-section-head">
				<h2><?php echo esc_html( isset( $section['title'] ) ? $section['title'] : '' ); ?></h2>
				<button type="button" class="devco-section-toggle" aria-label="باز و بسته کردن"><?php devco_icon_e( 'chevron-down' ); ?></button>
			</header>
			<?php if ( ! empty( $section['description'] ) ) : ?>
				<p class="devco-section-desc"><?php echo wp_kses_post( $section['description'] ); ?></p>
			<?php endif; ?>
			<div class="devco-section-body">
				<?php
				if ( ! empty( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field ) {
						$value = array_key_exists( $field['id'], $options ) ? $options[ $field['id'] ] : ( isset( $field['default'] ) ? $field['default'] : '' );
						$this->render_field( $field, $value );
					}
				}
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Render a single field.
	 *
	 * @param array $field Field schema.
	 * @param mixed $value Current value.
	 */
	public function render_field( $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$id   = $field['id'];
		$name = 'devco_options[' . $id . ']';

		if ( 'html' === $type ) {
			echo '<div class="devco-field devco-field--html">' . do_shortcode( wp_kses_post( isset( $field['html'] ) ? $field['html'] : '' ) ) . '</div>';
			return;
		}

		if ( 'heading' === $type ) {
			echo '<div class="devco-field-head"><h3>' . esc_html( $field['title'] ) . '</h3>';
			if ( ! empty( $field['description'] ) ) {
				echo '<p>' . wp_kses_post( $field['description'] ) . '</p>';
			}
			echo '</div>';
			return;
		}

		$wide = in_array( $type, array( 'repeater', 'slot_matrix', 'textarea', 'code', 'media', 'sortable', 'checklist', 'measurements' ), true );

		printf(
			'<div class="devco-field devco-field--%1$s %2$s" data-field="%3$s">',
			esc_attr( $type ),
			$wide ? 'is-wide' : '',
			esc_attr( $id )
		);

		if ( ! in_array( $type, array( 'checkbox', 'switch' ), true ) ) {
			printf(
				'<label class="devco-field-label" for="devco-%1$s">%2$s%3$s</label>',
				esc_attr( sanitize_title( $id ) ),
				esc_html( isset( $field['title'] ) ? $field['title'] : '' ),
				! empty( $field['required'] ) ? ' <span class="devco-req">*</span>' : ''
			);
		}

		echo '<div class="devco-field-control">';

		switch ( $type ) {
			case 'text':
			case 'number':
			case 'url':
			case 'email':
				printf(
					'<input type="%1$s" id="devco-%2$s" name="%3$s" value="%4$s" class="devco-input" %5$s placeholder="%6$s" />',
					esc_attr( 'number' === $type ? 'number' : ( in_array( $type, array( 'url', 'email' ), true ) ? $type : 'text' ) ),
					esc_attr( sanitize_title( $id ) ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					isset( $field['min'] ) ? 'min="' . esc_attr( $field['min'] ) . '" max="' . esc_attr( isset( $field['max'] ) ? $field['max'] : '' ) . '" step="' . esc_attr( isset( $field['step'] ) ? $field['step'] : 1 ) . '"' : '',
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
				);
				break;

			case 'textarea':
			case 'code':
				printf(
					'<textarea id="devco-%1$s" name="%2$s" rows="%3$d" class="devco-input devco-textarea %4$s" placeholder="%5$s">%6$s</textarea>',
					esc_attr( sanitize_title( $id ) ),
					esc_attr( $name ),
					'code' === $type ? 10 : 4,
					'code' === $type ? 'devco-code' : '',
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' ),
					esc_textarea( is_scalar( $value ) ? $value : '' )
				);
				break;

			case 'color':
				printf(
					'<input type="text" class="devco-color" id="devco-%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" />',
					esc_attr( sanitize_title( $id ) ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					esc_attr( isset( $field['default'] ) ? $field['default'] : '' )
				);
				break;

			case 'range':
				printf(
					'<div class="devco-range-wrap"><input type="range" min="%1$s" max="%2$s" step="%3$s" value="%4$s" class="devco-range js-devco-range" data-target="devco-%5$s" /><input type="number" id="devco-%5$s" name="%6$s" value="%4$s" class="devco-input devco-input--tiny js-devco-range-value" min="%1$s" max="%2$s" step="%3$s" /><span class="devco-unit">%7$s</span></div>',
					esc_attr( isset( $field['min'] ) ? $field['min'] : 0 ),
					esc_attr( isset( $field['max'] ) ? $field['max'] : 100 ),
					esc_attr( isset( $field['step'] ) ? $field['step'] : 1 ),
					esc_attr( is_scalar( $value ) ? $value : '' ),
					esc_attr( sanitize_title( $id ) ),
					esc_attr( $name ),
					esc_html( isset( $field['unit'] ) ? $field['unit'] : '' )
				);
				break;

			case 'select':
				printf( '<select id="devco-%1$s" name="%2$s" class="devco-select">', esc_attr( sanitize_title( $id ) ), esc_attr( $name ) );
				foreach ( (array) $field['options'] as $opt_key => $opt_label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $opt_key ),
						selected( (string) $value, (string) $opt_key, false ),
						esc_html( $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'menu':
				$menus = wp_get_nav_menus();
				printf( '<select id="devco-%1$s" name="%2$s" class="devco-select"><option value="0">— انتخاب منو —</option>', esc_attr( sanitize_title( $id ) ), esc_attr( $name ) );
				foreach ( $menus as $menu ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $menu->term_id ), selected( (string) $value, (string) $menu->term_id, false ), esc_html( $menu->name ) );
				}
				echo '</select>';
				break;

			case 'page':
				wp_dropdown_pages(
					array(
						'name'              => $name,
						'id'                => 'devco-' . sanitize_title( $id ),
						'selected'          => (int) $value,
						'show_option_none'  => '— انتخاب صفحه —',
						'option_none_value' => 0,
						'class'             => 'devco-select',
					)
				);
				break;

			case 'pages':
				$pages   = get_pages( array( 'number' => 200 ) );
				$value   = is_array( $value ) ? $value : array();
				echo '<select id="devco-' . esc_attr( sanitize_title( $id ) ) . '" name="' . esc_attr( $name ) . '[]" multiple class="devco-select devco-multi">';
				foreach ( $pages as $page ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $page->ID ), in_array( (int) $page->ID, array_map( 'intval', $value ), true ) ? 'selected' : '', esc_html( $page->post_title ) );
				}
				echo '</select>';
				break;

			case 'radio':
				echo '<div class="devco-radios">';
				foreach ( (array) $field['options'] as $opt_key => $opt_label ) {
					printf(
						'<label class="devco-radio"><input type="radio" name="%1$s" value="%2$s" %3$s /><span>%4$s</span></label>',
						esc_attr( $name ),
						esc_attr( $opt_key ),
						checked( (string) $value, (string) $opt_key, false ),
						esc_html( $opt_label )
					);
				}
				echo '</div>';
				break;

			case 'image_select':
				echo '<div class="devco-image-select">';
				foreach ( (array) $field['options'] as $opt_key => $opt ) {
					$label = is_array( $opt ) ? $opt['label'] : $opt;
					$img   = is_array( $opt ) && ! empty( $opt['image'] ) ? $opt['image'] : '';
					printf(
						'<label class="devco-image-option %1$s"><input type="radio" name="%2$s" value="%3$s" %4$s />%5$s<span>%6$s</span></label>',
						(string) $value === (string) $opt_key ? 'is-selected' : '',
						esc_attr( $name ),
						esc_attr( $opt_key ),
						checked( (string) $value, (string) $opt_key, false ),
						$img ? '<img src="' . esc_url( $img ) . '" alt="" />' : '<span class="devco-image-ph">' . devco_icon( 'grid', array( 'size' => 28 ) ) . '</span>',
						esc_html( $label )
					);
				}
				echo '</div>';
				break;

			case 'checkbox':
				printf(
					'<label class="devco-switch"><input type="checkbox" name="%1$s" value="1" %2$s /><span class="devco-switch-track"></span></label><span class="devco-field-desc">%3$s</span>',
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( isset( $field['title'] ) ? $field['title'] : '' )
				);
				break;

			case 'switch':
				printf(
					'<label class="devco-switch"><input type="checkbox" name="%1$s" value="1" %2$s /><span class="devco-switch-track"></span></label>',
					esc_attr( $name ),
					checked( (bool) $value, true, false )
				);
				break;

			case 'media':
				$url = is_scalar( $value ) ? (string) $value : ( is_array( $value ) && isset( $value['url'] ) ? $value['url'] : '' );
				printf(
					'<div class="devco-media"><div class="devco-media-preview">%1$s</div><input type="hidden" class="devco-media-input" name="%2$s" value="%3$s" /><button type="button" class="devco-btn devco-btn--sm js-devco-media">انتخاب تصویر</button><button type="button" class="devco-btn devco-btn--sm devco-btn--ghost js-devco-media-clear">حذف</button></div>',
					$url ? '<img src="' . esc_url( $url ) . '" alt="" />' : '<span class="devco-image-ph">' . devco_icon( 'upload', array( 'size' => 24 ) ) . '</span>',
					esc_attr( $name ),
					esc_url( $url )
				);
				break;

			case 'repeater':
				$this->render_repeater( $field, $value );
				break;

			case 'sortable':
				$this->render_sortable( $field, $value );
				break;

			case 'checklist':
				$value = is_array( $value ) ? $value : array();
				echo '<div class="devco-checklist">';
				foreach ( (array) $field['options'] as $opt_key => $opt_label ) {
					printf(
						'<label class="devco-check"><input type="checkbox" name="%1$s[]" value="%2$s" %3$s /><span>%4$s</span></label>',
						esc_attr( $name ),
						esc_attr( $opt_key ),
						in_array( (string) $opt_key, array_map( 'strval', $value ), true ) ? 'checked' : '',
						esc_html( $opt_label )
					);
				}
				echo '</div>';
				break;

			case 'slot_matrix':
				$this->render_slot_matrix( $field, $value );
				break;

			case 'measurements':
				$this->render_measurements( $field, $value );
				break;

			case 'icon':
				$icons = array_keys( devco_icon_paths() );
				printf( '<select id="devco-%1$s" name="%2$s" class="devco-select devco-icon-select">', esc_attr( sanitize_title( $id ) ), esc_attr( $name ) );
				printf( '<option value="">— بدون آیکون —</option>' );
				foreach ( $icons as $icon_name ) {
					printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $icon_name ), selected( (string) $value, (string) $icon_name, false ) );
				}
				echo '</select>';
				if ( $value && isset( devco_icon_paths()[ $value ] ) ) {
					echo '<span class="devco-icon-preview">' . devco_icon( $value, array( 'size' => 22 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				break;

			default:
				printf(
					'<input type="text" id="devco-%1$s" name="%2$s" value="%3$s" class="devco-input" />',
					esc_attr( sanitize_title( $id ) ),
					esc_attr( $name ),
					esc_attr( is_scalar( $value ) ? $value : '' )
				);
		}

		echo '</div>';

		if ( ! empty( $field['description'] ) && ! in_array( $type, array( 'checkbox', 'heading' ), true ) ) {
			echo '<p class="devco-field-desc">' . wp_kses_post( $field['description'] ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Repeater field.
	 *
	 * @param array $field Field schema.
	 * @param mixed $value Value.
	 */
	protected function render_repeater( $field, $value ) {
		$value  = is_array( $value ) ? array_values( $value ) : array();
		$fields = isset( $field['fields'] ) ? $field['fields'] : array();
		$name   = 'devco_options[' . $field['id'] . ']';
		$title  = isset( $field['item_title'] ) ? $field['item_title'] : 'آیتم';

		echo '<div class="devco-repeater js-devco-repeater">';
		echo '<div class="devco-repeater-items">';

		$render_item = function ( $index, $item ) use ( $fields, $name, $title ) {
			echo '<div class="devco-repeater-item"><header class="devco-repeater-head"><span class="devco-repeater-title">' . esc_html( $title ) . ' <b class="js-devco-item-index">' . esc_html( devco_num( $index + 1 ) ) . '</b></span><button type="button" class="devco-repeater-remove js-devco-remove-row" aria-label="حذف">' . devco_icon( 'trash', array( 'size' => 16 ) ) . '</button></header><div class="devco-repeater-body">';

			foreach ( $fields as $sub ) {
				$sub_id    = $sub['id'];
				$sub_name  = $name . '[' . $index . '][' . $sub_id . ']';
				$sub_value = isset( $item[ $sub_id ] ) ? $item[ $sub_id ] : ( isset( $sub['default'] ) ? $sub['default'] : '' );
				echo '<div class="devco-subfield"><label>' . esc_html( $sub['title'] ) . '</label>';

				switch ( $sub['type'] ) {
					case 'select':
						echo '<select name="' . esc_attr( $sub_name ) . '" class="devco-select">';
						foreach ( (array) $sub['options'] as $k => $label ) {
							printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $k ), selected( (string) $sub_value, (string) $k, false ), esc_html( $label ) );
						}
						echo '</select>';
						break;
					case 'icon':
						echo '<select name="' . esc_attr( $sub_name ) . '" class="devco-select">';
						echo '<option value="">—</option>';
						foreach ( array_keys( devco_icon_paths() ) as $icon_name ) {
							printf( '<option value="%1$s" %2$s>%1$s</option>', esc_attr( $icon_name ), selected( (string) $sub_value, (string) $icon_name, false ) );
						}
						echo '</select>';
						break;
					case 'color':
						echo '<input type="text" class="devco-color" name="' . esc_attr( $sub_name ) . '" value="' . esc_attr( $sub_value ) . '" />';
						break;
					case 'media':
						echo '<div class="devco-media devco-media--sm"><div class="devco-media-preview">' . ( $sub_value ? '<img src="' . esc_url( $sub_value ) . '" alt="" />' : '' ) . '</div><input type="hidden" class="devco-media-input" name="' . esc_attr( $sub_name ) . '" value="' . esc_url( $sub_value ) . '" /><button type="button" class="devco-btn devco-btn--sm js-devco-media">تصویر</button></div>';
						break;
					case 'textarea':
						echo '<textarea name="' . esc_attr( $sub_name ) . '" rows="3" class="devco-input">' . esc_textarea( $sub_value ) . '</textarea>';
						break;
					case 'checkbox':
						echo '<label class="devco-check"><input type="checkbox" name="' . esc_attr( $sub_name ) . '" value="1" ' . checked( (bool) $sub_value, true, false ) . '/><span>فعال</span></label>';
						break;
					default:
						echo '<input type="text" name="' . esc_attr( $sub_name ) . '" value="' . esc_attr( $sub_value ) . '" class="devco-input" />';
				}

				echo '</div>';
			}

			echo '</div></div>';
		};

		if ( $value ) {
			foreach ( $value as $index => $item ) {
				$render_item( $index, $item );
			}
		}

		echo '</div>';

		// JS template.
		echo '<script type="text/html" class="js-devco-repeater-tpl">';
		$render_item( 999, array() );
		echo '</script>';

		printf(
			'<button type="button" class="devco-btn devco-btn--sm js-devco-add-row" data-next="%1$d">%2$s %3$s</button>',
			count( $value ),
			devco_icon( 'plus', array( 'size' => 16 ) ),
			esc_html( isset( $field['add_label'] ) ? $field['add_label'] : 'افزودن' )
		);

		echo '</div>';
	}

	/**
	 * Sortable checklist field.
	 *
	 * @param array $field Field schema.
	 * @param mixed $value Value.
	 */
	protected function render_sortable( $field, $value ) {
		$options = (array) $field['options'];
		$value   = is_array( $value ) ? $value : array();
		$ordered = array();

		foreach ( $value as $key ) {
			if ( isset( $options[ $key ] ) ) {
				$ordered[ $key ] = $options[ $key ];
			}
		}

		foreach ( $options as $key => $label ) {
			if ( ! isset( $ordered[ $key ] ) ) {
				$ordered[ $key ] = $label;
			}
		}

		$name = 'devco_options[' . $field['id'] . ']';

		echo '<ul class="devco-sortable js-devco-sortable" data-name="' . esc_attr( $name ) . '">';
		foreach ( $ordered as $key => $label ) {
			$enabled = in_array( (string) $key, array_map( 'strval', $value ), true );
			printf(
				'<li class="devco-sortable-item" data-key="%1$s"><span class="devco-drag">%2$s</span><label><input type="checkbox" name="%3$s[]" value="%1$s" %4$s /> %5$s</label><input type="hidden" name="%3$s_order[]" value="%1$s" class="js-sort-order" /></li>',
				esc_attr( $key ),
				devco_icon( 'drag', array( 'size' => 16 ) ),
				esc_attr( $name ),
				checked( $enabled, true, false ),
				esc_html( $label )
			);
		}
		echo '</ul>';
		echo '<p class="devco-field-desc">با کشیدن و رها کردن ترتیب را تغییر دهید. فقط آیتم‌های تیک‌خورده نمایش داده می‌شوند.</p>';
	}

	/**
	 * Header/footer slot matrix.
	 *
	 * @param array $field Field schema.
	 * @param mixed $value Value.
	 */
	protected function render_slot_matrix( $field, $value ) {
		$value      = is_array( $value ) ? $value : array();
		$components = isset( $field['components'] ) ? $field['components'] : array();
		$rows       = isset( $field['rows'] ) ? $field['rows'] : array();
		$name       = 'devco_options[' . $field['id'] . ']';

		echo '<div class="devco-slot-matrix">';

		foreach ( $rows as $row_key => $row ) {
			$row_value = isset( $value[ $row_key ] ) && is_array( $value[ $row_key ] ) ? $value[ $row_key ] : array();

			echo '<div class="devco-slot-row">';
			echo '<div class="devco-slot-row-head"><strong>' . esc_html( $row ) . '</strong></div>';
			echo '<div class="devco-slot-cols">';

			$cols = array(
				'right'  => 'راست',
				'center' => 'وسط',
				'left'   => 'چپ',
			);

			foreach ( $cols as $col_key => $col_label ) {
				$col_value = isset( $row_value[ $col_key ] ) ? (array) $row_value[ $col_key ] : array();
				echo '<div class="devco-slot-col"><span class="devco-slot-title">' . esc_html( $col_label ) . '</span><div class="devco-slot-list">';
				foreach ( $components as $comp_key => $comp_label ) {
					printf(
						'<label class="devco-check devco-check--tight"><input type="checkbox" name="%1$s[%2$s][%3$s][]" value="%4$s" %5$s /><span>%6$s</span></label>',
						esc_attr( $name ),
						esc_attr( $row_key ),
						esc_attr( $col_key ),
						esc_attr( $comp_key ),
						in_array( (string) $comp_key, array_map( 'strval', $col_value ), true ) ? 'checked' : '',
						esc_html( $comp_label )
					);
				}
				echo '</div></div>';
			}

			echo '</div></div>';
		}

		echo '</div>';
		echo '<p class="devco-field-desc">ترتیب نمایش اجزا بر اساس ترتیب پیش‌فرض اجزاست. برای چیدمان دقیق‌تر از سازنده هدر پیشرفته (الگوهای المنتور) استفاده کنید.</p>';
	}

	/**
	 * Body measurement schema editor.
	 *
	 * @param array $field Field schema.
	 * @param mixed $value Value.
	 */
	protected function render_measurements( $field, $value ) {
		$defaults = devco_default_measurements();
		$value    = is_array( $value ) && $value ? $value : $defaults;
		$name     = 'devco_options[' . $field['id'] . ']';

		echo '<div class="devco-measure-table">';
		echo '<div class="devco-measure-head"><span>فعال</span><span>کلید</span><span>عنوان</span><span>واحد</span><span>توضیح</span></div>';

		foreach ( $value as $index => $item ) {
			$item = wp_parse_args(
				$item,
				array(
					'enabled' => 1,
					'key'     => '',
					'label'   => '',
					'unit'    => 'سانتی‌متر',
					'hint'    => '',
				)
			);
			echo '<div class="devco-measure-row">';
			printf( '<label class="devco-check"><input type="checkbox" name="%1$s[%2$d][enabled]" value="1" %3$s /></label>', esc_attr( $name ), (int) $index, checked( (bool) $item['enabled'], true, false ) );
			printf( '<input type="text" name="%1$s[%2$d][key]" value="%3$s" class="devco-input devco-input--sm" />', esc_attr( $name ), (int) $index, esc_attr( $item['key'] ) );
			printf( '<input type="text" name="%1$s[%2$d][label]" value="%3$s" class="devco-input" />', esc_attr( $name ), (int) $index, esc_attr( $item['label'] ) );
			printf( '<input type="text" name="%1$s[%2$d][unit]" value="%3$s" class="devco-input devco-input--sm" />', esc_attr( $name ), (int) $index, esc_attr( $item['unit'] ) );
			printf( '<input type="text" name="%1$s[%2$d][hint]" value="%3$s" class="devco-input" />', esc_attr( $name ), (int) $index, esc_attr( $item['hint'] ) );
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Handle the settings form submission.
	 */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}

		check_admin_referer( 'devco_save_options' );

		$tab    = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
		$posted = isset( $_POST['devco_options'] ) ? wp_unslash( $_POST['devco_options'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field below.
		$posted = is_array( $posted ) ? $posted : array();

		$stored = devco_options();
		$schema = self::schema();
		$fields = array();

		if ( isset( $schema[ $tab ]['sections'] ) ) {
			foreach ( $schema[ $tab ]['sections'] as $section ) {
				if ( empty( $section['fields'] ) ) {
					continue;
				}
				foreach ( $section['fields'] as $field ) {
					$fields[ $field['id'] ] = $field;
				}
			}
		}

		foreach ( $fields as $id => $field ) {
			$type = isset( $field['type'] ) ? $field['type'] : 'text';

			if ( in_array( $type, array( 'heading', 'html' ), true ) ) {
				continue;
			}

			$raw = array_key_exists( $id, $posted ) ? $posted[ $id ] : null;

			// Checkboxes are absent when unchecked.
			if ( in_array( $type, array( 'checkbox', 'switch' ), true ) ) {
				$stored[ $id ] = $raw ? 1 : 0;
				continue;
			}

			if ( null === $raw && in_array( $type, array( 'checklist', 'pages', 'sortable', 'repeater', 'slot_matrix' ), true ) ) {
				$raw = array();
			}

			$stored[ $id ] = $this->sanitize( $field, $raw );
		}

		update_option( DEVCO_OPTION, $stored );

		// Refresh Elementor CSS if kit-linked options changed.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'devco-settings',
					'tab'            => $tab,
					'settings-saved' => 1,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Sanitize a field value by type.
	 *
	 * @param array $field Field schema.
	 * @param mixed $raw   Raw value.
	 * @return mixed
	 */
	protected function sanitize( $field, $raw ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';

		switch ( $type ) {
			case 'number':
			case 'range':
				return is_numeric( $raw ) ? (float) $raw : ( isset( $field['default'] ) ? $field['default'] : 0 );

			case 'color':
				return sanitize_hex_color( (string) $raw );

			case 'url':
			case 'media':
				return esc_url_raw( (string) $raw );

			case 'email':
				return sanitize_email( (string) $raw );

			case 'textarea':
				return wp_kses_post( (string) $raw );

			case 'code':
				return wp_kses_post( (string) $raw );

			case 'select':
			case 'radio':
			case 'image_select':
				$options = isset( $field['options'] ) ? (array) $field['options'] : array();
				$value   = sanitize_text_field( (string) $raw );
				if ( ! array_key_exists( $value, $options ) && ! in_array( $value, $options, true ) ) {
					return isset( $field['default'] ) ? $field['default'] : '';
				}
				return $value;

			case 'menu':
			case 'page':
				return (int) $raw;

			case 'pages':
			case 'checklist':
			case 'sortable':
				return array_values( array_filter( array_map( 'sanitize_text_field', is_array( $raw ) ? $raw : array() ) ) );

			case 'repeater':
				$out    = array();
				$subs   = isset( $field['fields'] ) ? $field['fields'] : array();
				$raw    = is_array( $raw ) ? $raw : array();
				foreach ( $raw as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$clean = array();
					foreach ( $subs as $sub ) {
						$value           = isset( $item[ $sub['id'] ] ) ? $item[ $sub['id'] ] : '';
						$clean[ $sub['id'] ] = $this->sanitize( $sub, $value );
					}
					$out[] = $clean;
				}
				return $out;

			case 'slot_matrix':
				$out = array();
				$raw = is_array( $raw ) ? $raw : array();
				foreach ( $raw as $row_key => $cols ) {
					$out[ sanitize_key( $row_key ) ] = array();
					if ( ! is_array( $cols ) ) {
						continue;
					}
					foreach ( $cols as $col_key => $items ) {
						$out[ sanitize_key( $row_key ) ][ sanitize_key( $col_key ) ] = array_values( array_map( 'sanitize_key', (array) $items ) );
					}
				}
				return $out;

			case 'measurements':
				$out = array();
				$raw = is_array( $raw ) ? $raw : array();
				foreach ( $raw as $item ) {
					if ( ! is_array( $item ) || empty( $item['label'] ) ) {
						continue;
					}
					$out[] = array(
						'enabled' => empty( $item['enabled'] ) ? 0 : 1,
						'key'     => sanitize_key( isset( $item['key'] ) ? $item['key'] : '' ),
						'label'   => sanitize_text_field( $item['label'] ),
						'unit'    => sanitize_text_field( isset( $item['unit'] ) ? $item['unit'] : '' ),
						'hint'    => sanitize_text_field( isset( $item['hint'] ) ? $item['hint'] : '' ),
					);
				}
				return $out;

			case 'icon':
				return sanitize_key( (string) $raw );

			default:
				return sanitize_text_field( (string) $raw );
		}
	}

	/**
	 * AJAX: reset a tab to its defaults.
	 */
	public function ajax_reset() {
		check_ajax_referer( 'devco_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی غیرمجاز' ), 403 );
		}

		$tab    = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
		$schema = self::schema();
		$stored = devco_options();

		if ( ! isset( $schema[ $tab ]['sections'] ) ) {
			wp_send_json_error( array( 'message' => 'تب نامعتبر' ) );
		}

		foreach ( $schema[ $tab ]['sections'] as $section ) {
			foreach ( (array) $section['fields'] as $field ) {
				if ( array_key_exists( 'default', $field ) ) {
					$stored[ $field['id'] ] = $field['default'];
				} else {
					unset( $stored[ $field['id'] ] );
				}
			}
		}

		update_option( DEVCO_OPTION, $stored );

		wp_send_json_success( array( 'message' => 'بازگردانی شد' ) );
	}
}

/**
 * Default body measurement list (used by the options panel and the 3D body map).
 *
 * @return array
 */
function devco_default_measurements() {
	return array(
		array( 'enabled' => 1, 'key' => 'neck', 'label' => 'دور گردن', 'unit' => 'سانتی‌متر', 'hint' => 'دور کامل گردن' ),
		array( 'enabled' => 1, 'key' => 'shoulder', 'label' => 'سرشانه', 'unit' => 'سانتی‌متر', 'hint' => 'از شانه تا شانه' ),
		array( 'enabled' => 1, 'key' => 'chest', 'label' => 'دور سینه', 'unit' => 'سانتی‌متر', 'hint' => 'برجسته‌ترین نقطه سینه' ),
		array( 'enabled' => 1, 'key' => 'waist', 'label' => 'دور کمر', 'unit' => 'سانتی‌متر', 'hint' => 'باریک‌ترین نقطه کمر' ),
		array( 'enabled' => 1, 'key' => 'hip', 'label' => 'دور باسن', 'unit' => 'سانتی‌متر', 'hint' => 'برجسته‌ترین نقطه باسن' ),
		array( 'enabled' => 1, 'key' => 'sleeve', 'label' => 'دور بازو', 'unit' => 'سانتی‌متر', 'hint' => 'دور کامل بازو' ),
		array( 'enabled' => 1, 'key' => 'wrist', 'label' => 'دور مچ دست', 'unit' => 'سانتی‌متر', 'hint' => 'دور مچ' ),
		array( 'enabled' => 1, 'key' => 'arm_length', 'label' => 'قد آستین', 'unit' => 'سانتی‌متر', 'hint' => 'از شانه تا مچ' ),
		array( 'enabled' => 1, 'key' => 'shirt_length', 'label' => 'قد بلوز', 'unit' => 'سانتی‌متر', 'hint' => 'از گردن تا پایین‌تنه' ),
		array( 'enabled' => 1, 'key' => 'thigh', 'label' => 'دور ران', 'unit' => 'سانتی‌متر', 'hint' => 'دور ران' ),
		array( 'enabled' => 1, 'key' => 'knee', 'label' => 'دور زانو', 'unit' => 'سانتی‌متر', 'hint' => 'دور زانو' ),
		array( 'enabled' => 1, 'key' => 'ankle', 'label' => 'دور مچ پا', 'unit' => 'سانتی‌متر', 'hint' => 'دور مچ پا' ),
		array( 'enabled' => 1, 'key' => 'trouser_length', 'label' => 'قد شلوار', 'unit' => 'سانتی‌متر', 'hint' => 'از کمر تا مچ پا' ),
		array( 'enabled' => 0, 'key' => 'height', 'label' => 'قد', 'unit' => 'سانتی‌متر', 'hint' => 'قد کامل' ),
		array( 'enabled' => 0, 'key' => 'weight', 'label' => 'وزن', 'unit' => 'کیلوگرم', 'hint' => 'وزن' ),
	);
}
