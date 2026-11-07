<?php
/**
 * Widget: scrolling announcement marquee.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Marquee
 */
class DeVco_Widget_Marquee extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-marquee';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | نوار متحرک اطلاع‌رسانی';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-text';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'marquee', 'ticker', 'نوار', 'اطلاع‌رسانی' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'items',
			array(
				'label'       => 'متن‌ها (هر خط یک آیتم)',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 4,
				'default'     => "ارسال رایگان خرید بالای ۲ میلیون تومان\n۷ روز ضمانت بازگشت کالا\nاتاق پرو مجازی برای همه محصولات\nدوخت سفارشی با اندازه دقیق",
				'label_block' => true,
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => 'آیکون جداکننده',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'sparkle'  => 'جرقه',
					'star'     => 'ستاره',
					'tag'      => 'برچسب',
					'gift'     => 'هدیه',
					'percent'  => 'تخفیف',
					'none'     => 'بدون آیکون',
				),
				'default' => 'sparkle',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'      => 'سرعت (ثانیه)',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 's' ),
				'range'      => array( 's' => array( 'min' => 8, 'max' => 60 ) ),
				'default'    => array( 'unit' => 's', 'size' => 26 ),
			)
		);

		$this->add_control(
			'pause',
			array(
				'label'        => 'توقف با هاور',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-marquee',
			)
		);

		$this->reg_typography( 'typo', '{{WRAPPER}} .devco-marquee__item' );

		$this->add_control(
			'color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-marquee__item' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => 'رنگ آیکون',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-marquee__item svg' => 'stroke: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-marquee' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s        = $this->get_settings_for_display();
		$items    = preg_split( '/\r\n|\r|\n/', (string) ( isset( $s['items'] ) ? $s['items'] : '' ) );
		$items    = array_filter( array_map( 'trim', $items ) );
		$speed    = isset( $s['speed']['size'] ) ? (int) $s['speed']['size'] : 26;
		$pause    = 'yes' === ( isset( $s['pause'] ) ? $s['pause'] : 'yes' );
		$icon     = isset( $s['icon'] ) ? $s['icon'] : 'sparkle';

		if ( ! $items ) {
			return;
		}

		printf(
			'<div class="devco-widget"><div class="devco-marquee %1$s" style="--devco-marquee-speed:%2$ds"><div class="devco-marquee__track">',
			$pause ? 'devco-marquee--pause' : '',
			$speed
		);

		// Duplicate the content for a seamless loop.
		for ( $copy = 0; $copy < 2; $copy++ ) {
			echo '<div class="devco-marquee__group" ' . ( $copy ? 'aria-hidden="true"' : '' ) . '>';
			foreach ( $items as $item ) {
				echo '<span class="devco-marquee__item">';
				if ( 'none' !== $icon ) {
					echo devco_icon( $icon, array( 'size' => 15 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo esc_html( $item ) . '</span>';
			}
			echo '</div>';
		}

		echo '</div></div></div>';
	}
}
