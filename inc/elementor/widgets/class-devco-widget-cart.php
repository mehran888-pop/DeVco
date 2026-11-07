<?php
/**
 * Widget: mini cart button.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Cart
 */
class DeVco_Widget_Cart extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-cart';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | سبد خرید (هدر)';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-cart';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'cart', 'سبد', 'basket', 'mini cart' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'style',
			array(
				'label'   => 'استایل',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'icon'    => 'آیکون + شمارنده',
					'labeled' => 'آیکون + عنوان و مبلغ',
					'button'  => 'دکمه توپر',
					'circle'  => 'دایره‌ای',
				),
				'default' => 'labeled',
			)
		);

		$this->add_control(
			'show_total',
			array(
				'label'        => 'نمایش مبلغ سبد',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'style' => array( 'labeled', 'button' ) ),
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => 'نمایش تعداد',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'size',
			array(
				'label'      => 'اندازه آیکون',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 16, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 21 ),
				'selectors'  => array( '{{WRAPPER}} .devco-cart-btn svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_btn', 'استایل' );

		$this->add_control(
			'color',
			array(
				'label'     => 'رنگ متن و آیکون',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-cart-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-cart-btn svg' => 'stroke: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'bg',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-cart-btn' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_bg',
			array(
				'label'     => 'رنگ شمارنده',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-cart-btn__count' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => 'رنگ متن شمارنده',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-cart-btn__count' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( ! devco_is_woo() ) {
			echo '<div class="devco-widget devco-widget--cart"><p class="devco-hint">ووکامرس فعال نیست.</p></div>';
			return;
		}

		$s     = $this->get_settings_for_display();
		$style = isset( $s['style'] ) ? $s['style'] : 'labeled';
		$count = devco_cart_count();

		printf(
			'<div class="devco-widget devco-widget--cart"><button type="button" class="devco-cart-btn devco-cart-btn--%1$s js-devco-cart-open" aria-label="سبد خرید" data-count="%2$d"><span class="devco-cart-btn__icon">%3$s%4$s</span>',
			esc_attr( $style ),
			(int) $count,
			devco_icon( 'bag', array( 'size' => 21 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'yes' === ( isset( $s['show_count'] ) ? $s['show_count'] : 'yes' ) ? '<span class="devco-cart-btn__count" data-cart-count>' . esc_html( devco_num( $count ) ) . '</span>' : ''
		);

		if ( in_array( $style, array( 'labeled', 'button' ), true ) ) {
			echo '<span class="devco-cart-btn__meta"><small>سبد خرید</small>';
			if ( 'yes' === ( isset( $s['show_total'] ) ? $s['show_total'] : 'yes' ) ) {
				echo '<strong data-cart-total>' . wp_kses_post( devco_cart_subtotal() ) . '</strong>';
			}
			echo '</span>';
		}

		echo '</button></div>';
	}
}
