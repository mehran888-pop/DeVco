<?php
/**
 * Widget: virtual fitting room.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Fitting_Room
 */
class DeVco_Widget_Fitting_Room extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-fitting-room';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | اتاق پرو (پرو لباس)';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-rollover';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'fitting', 'پرو', 'اتاق پرو', 'webcam', 'لباس', 'virtual try on' ) );
	}

	/**
	 * Scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'devco-fitting' );
	}

	/**
	 * Styles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-widgets', 'devco-fitting' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'mode',
			array(
				'label'   => 'حالت نمایش',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'embed'   => 'پنل توکار داخل صفحه',
					'drawer'  => 'کشویی از کنار صفحه',
					'floating' => 'دکمه شناور + کشویی',
				),
				'default' => 'embed',
			)
		);

		$this->add_control(
			'side',
			array(
				'label'     => 'موقعیت کشویی',
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array( 'right' => 'سمت راست', 'left' => 'سمت چپ' ),
				'default'   => 'right',
				'condition' => array( 'mode' => array( 'drawer', 'floating' ) ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => 'عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'اتاق پرو مجازی دیوکو',
			)
		);

		$this->add_control(
			'trigger_label',
			array(
				'label'     => 'متن دکمه بازکننده',
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'پرو لباس',
				'condition' => array( 'mode' => array( 'drawer', 'floating' ) ),
			)
		);

		$this->add_control(
			'opacity',
			array(
				'label'      => 'شفافیت پیش‌فرض لباس',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array( '%' => array( 'min' => 30, 'max' => 100 ) ),
				'default'    => array( 'unit' => '%', 'size' => 88 ),
			)
		);

		$this->add_control(
			'webcam',
			array(
				'label'        => 'پشتیبانی از دوربین',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'upload',
			array(
				'label'        => 'پشتیبانی از آپلود عکس',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'mannequin',
			array(
				'label' => 'تصویر مانکن (اختیاری)',
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);

		$this->add_control(
			'clothes',
			array(
				'label'       => 'لباس‌های اختصاصی این المان',
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => array(
					array(
						'name'  => 'image',
						'label' => 'تصویر لباس (PNG شفاف)',
						'type'  => \Elementor\Controls_Manager::MEDIA,
					),
					array(
						'name'    => 'title',
						'label'   => 'نام لباس',
						'type'    => \Elementor\Controls_Manager::TEXT,
						'default' => 'لباس جدید',
					),
					array(
						'name'  => 'product',
						'label' => 'شناسه محصول',
						'type'  => \Elementor\Controls_Manager::TEXT,
					),
				),
				'title_field' => '{{{ title }}}',
				'description' => 'اگر خالی بماند، از تنظیمات قالب استفاده می‌شود.',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل اتاق پرو' );

		$this->add_control(
			'accent',
			array(
				'label'     => 'رنگ تاکیدی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fitting' => '--devco-fit-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'panel_bg',
			array(
				'label'     => 'پس‌زمینه پنل',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fitting' => '--devco-fit-bg: {{VALUE}};' ),
			)
		);

		$this->reg_typography( 'title_typo', '{{WRAPPER}} .devco-fitting__title' );

		$this->add_responsive_control(
			'panel_width',
			array(
				'label'      => 'عرض پنل',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 300, 'max' => 720 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 440 ),
				'selectors'  => array(
					'{{WRAPPER}} .devco-fitting--embed .devco-fitting__panel' => 'width: {{SIZE}}{{UNIT}};max-width:100%;',
					'{{WRAPPER}} .devco-fitting .devco-fitting__panel' => '--devco-fit-w: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$mode = isset( $s['mode'] ) ? $s['mode'] : 'embed';

		$items = array();

		if ( ! empty( $s['clothes'] ) && is_array( $s['clothes'] ) ) {
			foreach ( $s['clothes'] as $item ) {
				if ( empty( $item['image']['url'] ) ) {
					continue;
				}
				$items[] = array(
					'image'   => $item['image']['url'],
					'title'   => isset( $item['title'] ) ? $item['title'] : '',
					'product' => isset( $item['product'] ) ? $item['product'] : '',
				);
			}
		}

		$args = array(
			'items'   => $items,
			'side'    => isset( $s['side'] ) ? $s['side'] : devco_opt( 'fitting.position', 'right' ),
			'opacity' => isset( $s['opacity']['size'] ) ? ( (float) $s['opacity']['size'] / 100 ) : 0.88,
			'id'      => 'devco-fitting-' . $this->get_id(),
		);

		// Inherit global fitting options for this widget when needed.
		add_filter( 'devco_fitting_force_webcam', '__return_true' );

		echo '<div class="devco-widget devco-widget--fitting devco-fitting--' . esc_attr( $mode ) . '">';

		if ( 'embed' === $mode ) {
			echo '<div class="devco-fitting__embed-head"><h3 class="devco-fitting__title">' . esc_html( isset( $s['title'] ) ? $s['title'] : '' ) . '</h3><p>لباس مورد نظر را انتخاب کنید و روی تصویر خودتان ببینید.</p></div>';
			devco_fitting_room( $args );
		} else {
			// Drawer/floating mode: button + panel appended to body via JS.
			printf(
				'<button type="button" class="devco-fit-trigger js-devco-fitting-open" data-fit-target="%1$s">%2$s<span>%3$s</span></button>',
				esc_attr( $args['id'] ),
				devco_icon( 'tshirt', array( 'size' => 20 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( isset( $s['trigger_label'] ) ? $s['trigger_label'] : 'پرو لباس' )
			);

			// Hidden panel moved to body by JS so it is not constrained by the column.
			echo '<div class="devco-fitting__portal" data-fit-portal="' . esc_attr( $args['id'] ) . '">';
			devco_fitting_room( $args );
			echo '</div>';
		}

		echo '</div>';
	}
}
