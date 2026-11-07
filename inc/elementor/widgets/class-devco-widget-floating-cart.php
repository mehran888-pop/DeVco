<?php
/**
 * Widget: floating cart (can be placed anywhere).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Floating_Cart
 */
class DeVco_Widget_Floating_Cart extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-floating-cart';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | سبد خرید شناور';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-cart-medium';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'cart', 'سبد', 'floating', 'شناور' ) );
	}

	/**
	 * Styles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-floating' );
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
					'bubble' => 'حبابی با شمارنده',
					'card'   => 'کارت با مبلغ',
					'dock'   => 'داک با پیش‌نمایش محصولات',
					'circle' => 'دایره‌ای',
				),
				'default' => 'dock',
			)
		);

		$this->add_control(
			'position',
			array(
				'label'   => 'موقعیت',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'bottom-left'  => 'پایین چپ',
					'bottom-right' => 'پایین راست',
					'bottom-center' => 'پایین وسط',
					'custom'       => 'داخل صفحه (بدون شناوری)',
				),
				'default' => 'bottom-left',
			)
		);

		$this->add_control(
			'offset_y',
			array(
				'label'      => 'فاصله از پایین',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 200 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .devco-floating-cart' => 'bottom: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'position!' => 'custom' ),
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => 'مخفی وقتی خالی است',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'label_text',
			array(
				'label'   => 'متن',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'سبد خرید',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_box', 'استایل' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-fc',
			)
		);

		$this->add_control(
			'color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-fc' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-fc svg' => 'stroke: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'badge_bg',
			array(
				'label'     => 'رنگ شمارنده',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fc__count' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'label'    => 'سایه',
				'selector' => '{{WRAPPER}} .devco-fc',
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-fc' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( ! devco_is_woo() ) {
			echo '<p class="devco-hint">ووکامرس را فعال کنید.</p>';
			return;
		}

		$s      = $this->get_settings_for_display();
		$style  = isset( $s['style'] ) ? $s['style'] : 'dock';
		$pos    = isset( $s['position'] ) ? $s['position'] : 'bottom-left';
		$count  = devco_cart_count();
		$class  = array( 'devco-floating-cart', 'devco-floating-cart--' . $style );

		if ( 'custom' === $pos ) {
			$class[] = 'devco-floating-cart--inline';
		} else {
			$class[] = 'devco-floating-cart--' . $pos;
		}

		if ( 'yes' === ( isset( $s['hide_empty'] ) ? $s['hide_empty'] : 'no' ) && ! $count ) {
			$class[] = 'is-hidden';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $class ) ); ?>" data-floating-cart data-style="<?php echo esc_attr( $style ); ?>">
			<button type="button" class="devco-fc js-devco-cart-open" aria-label="<?php echo esc_attr( isset( $s['label_text'] ) ? $s['label_text'] : 'سبد خرید' ); ?>">
				<span class="devco-fc__inner">
					<span class="devco-fc__icon">
						<?php devco_icon_e( 'bag', array( 'size' => 22 ) ); ?>
						<span class="devco-fc__count" data-cart-count><?php echo esc_html( devco_num( $count ) ); ?></span>
					</span>
					<?php if ( in_array( $style, array( 'card', 'dock' ), true ) ) : ?>
						<span class="devco-fc__meta">
							<small><?php echo esc_html( $count ? devco_num( $count ) . ' کالا' : ( isset( $s['label_text'] ) ? $s['label_text'] : 'سبد خرید' ) ); ?></small>
							<strong data-cart-total><?php echo wp_kses_post( devco_cart_subtotal() ); ?></strong>
						</span>
					<?php endif; ?>

					<?php if ( 'dock' === $style && ! WC()->cart->is_empty() ) : ?>
						<span class="devco-fc__preview">
							<?php
							$shown = 0;
							foreach ( WC()->cart->get_cart() as $item ) {
								if ( $shown >= 3 ) {
									break;
								}
								$product = $item['data'];
								if ( $product instanceof WC_Product ) {
									echo $product->get_image( 'devco-thumb' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								$shown++;
							}
							?>
						</span>
					<?php endif; ?>
				</span>
			</button>
		</div>
		<?php
	}
}
