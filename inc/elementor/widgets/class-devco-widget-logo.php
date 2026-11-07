<?php
/**
 * Widget: site logo.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Logo
 */
class DeVco_Widget_Logo extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-logo';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | لوگو';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-logo';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'logo', 'لوگو', 'برند' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'source',
			array(
				'label'   => 'منبع لوگو',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'site'   => 'لوگوی سایت (تنظیمات وردپرس)',
					'custom' => 'لوگوی اختصاصی این المان',
					'text'   => 'متن سایت',
				),
				'default' => 'site',
			)
		);

		$this->add_control(
			'image',
			array(
				'label'     => 'تصویر لوگو',
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'condition' => array( 'source' => 'custom' ),
			)
		);

		$this->add_control(
			'image_width',
			array(
				'label'      => 'عرض لوگو',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 30, 'max' => 400 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 130 ),
				'selectors'  => array(
					'{{WRAPPER}} .devco-logo__img' => 'width: {{SIZE}}{{UNIT}};max-width:none;',
				),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => 'چینش',
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => 'شروع', 'icon' => 'eicon-text-align-right' ),
					'center'     => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'flex-end'   => array( 'title' => 'پایان', 'icon' => 'eicon-text-align-left' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .devco-logo' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_text', 'استایل متن' );

		$this->reg_typography( 'logo_text', '{{WRAPPER}} .devco-logo__text', 'تایپوگرافی نام سایت' );

		$this->add_control(
			'show_mark',
			array(
				'label'        => 'نمایش نشان گرافیکی',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'mark_color',
			array(
				'label'     => 'رنگ نشان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-logo__mark' => 'color: {{VALUE}};' ),
				'condition' => array( 'show_mark' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = isset( $settings['source'] ) ? $settings['source'] : 'site';

		echo '<div class="devco-widget devco-widget--logo">';

		if ( 'custom' === $source && ! empty( $settings['image']['url'] ) ) {
			printf(
				'<a class="devco-logo" href="%1$s" aria-label="%2$s"><img class="devco-logo__img" src="%3$s" alt="%2$s" loading="lazy" /></a>',
				esc_url( home_url( '/' ) ),
				esc_attr( get_bloginfo( 'name' ) ),
				esc_url( $settings['image']['url'] )
			);
		} elseif ( 'text' === $source || ! has_custom_logo() ) {
			echo '<a class="devco-logo" href="' . esc_url( home_url( '/' ) ) . '">';
			if ( 'yes' === ( isset( $settings['show_mark'] ) ? $settings['show_mark'] : 'yes' ) ) {
				echo '<span class="devco-logo__mark">' . devco_icon( 'sparkle', array( 'size' => 22 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '<span class="devco-logo__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span></a>';
		} else {
			$logo_id = get_theme_mod( 'custom_logo' );
			echo '<a class="devco-logo" href="' . esc_url( home_url( '/' ) ) . '">' . wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'devco-logo__img' ) ) . '</a>';
		}

		echo '</div>';
	}
}
