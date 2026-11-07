<?php
/**
 * Widget: account / login button.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Account
 */
class DeVco_Widget_Account extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-account';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | حساب کاربری';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-user-circle-o';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'account', 'login', 'ورود', 'حساب' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'show_dropdown',
			array(
				'label'        => 'منوی کشویی حساب',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_name',
			array(
				'label'        => 'نمایش نام کاربر',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'icon_size',
			array(
				'label'      => 'اندازه آیکون',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 14, 'max' => 40 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 21 ),
				'selectors'  => array( '{{WRAPPER}} .devco-icon-btn svg, {{WRAPPER}} .devco-login-btn__icon svg' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_btn', 'دکمه' );

		$this->reg_typography( 'btn', '{{WRAPPER}} .devco-login-btn, {{WRAPPER}} .devco-account__name', 'تایپوگرافی' );

		$this->add_control(
			'btn_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-login-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-login-btn__icon svg' => 'stroke: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-login-btn' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'btn_border_color',
			array(
				'label'     => 'رنگ حاشیه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-login-btn' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-login-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-login-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$user = wp_get_current_user();

		echo '<div class="devco-widget devco-widget--account devco-account">';

		if ( is_user_logged_in() ) {
			printf(
				'<a class="devco-login-btn devco-login-btn--logged" href="%1$s"><span class="devco-login-btn__icon">%2$s</span>%3$s</a>',
				esc_url( devco_dashboard_url() ),
				get_avatar( $user->ID, 30, '', '', array( 'class' => 'devco-avatar' ) ),
				'yes' === ( isset( $s['show_name'] ) ? $s['show_name'] : 'yes' ) ? '<span class="devco-login-btn__text">' . esc_html( $user->display_name ) . '</span>' : ''
			);
		} else {
			printf(
				'<button type="button" class="devco-login-btn js-devco-auth-open" data-auth-view="%1$s"><span class="devco-login-btn__icon">%2$s</span><span class="devco-login-btn__text">%3$s</span></button>',
				esc_attr( devco_opt( 'auth.default_method', 'otp' ) ),
				devco_icon( 'user', array( 'size' => 19 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( devco_opt( 'auth.button_text', 'ورود | ثبت‌نام' ) )
			);
		}

		echo '</div>';
	}
}
