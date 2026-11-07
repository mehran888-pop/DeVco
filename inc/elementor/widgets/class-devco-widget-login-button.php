<?php
/**
 * Widget: dedicated login / register button for header & footer.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Login_Button
 */
class DeVco_Widget_Login_Button extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-login-button';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | دکمه ورود و ثبت‌نام';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-lock-user';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'login', 'register', 'ورود', 'ثبت نام', 'otp' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'preset',
			array(
				'label'   => 'پیش‌تنظیم ظاهر',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'split'   => 'دکمه دوگانه (ورود | ثبت‌نام)',
					'gradient' => 'دکمه گرادیانی درخشان',
					'glass'   => 'شیشه‌ای با آواتار',
					'avatar'  => 'آواتار با متن',
					'outline' => 'قاب‌دار مینیمال',
					'ribbon'  => 'نوار کناری برجسته',
				),
				'default' => 'split',
			)
		);

		$this->add_control(
			'login_text',
			array(
				'label'   => 'متن ورود',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'ورود',
			)
		);

		$this->add_control(
			'register_text',
			array(
				'label'     => 'متن ثبت‌نام',
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'ثبت‌نام',
				'condition' => array( 'preset' => 'split' ),
			)
		);

		$this->add_control(
			'logged_text',
			array(
				'label'   => 'متن پس از ورود',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'حساب کاربری',
			)
		);

		$this->add_control(
			'show_icon',
			array(
				'label'        => 'نمایش آیکون',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'     => 'آیکون',
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'user'      => 'کاربر',
					'lock'      => 'قفل',
					'sms'       => 'پیامک',
					'sparkle'   => 'جرقه',
					'dashboard' => 'داشبورد',
				),
				'default'   => 'user',
				'condition' => array( 'show_icon' => 'yes' ),
			)
		);

		$this->add_control(
			'method',
			array(
				'label'   => 'روش پیش‌فرض فرم',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'otp'      => 'پیامکی (OTP)',
					'password' => 'نام کاربری و رمز',
				),
				'default' => 'otp',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_btn', 'استایل دکمه' );

		$this->reg_typography( 'btn', '{{WRAPPER}} .devco-login-btn', 'تایپوگرافی' );

		$this->start_controls_tabs( 'btn_tabs' );

		$this->start_controls_tab( 'btn_normal', array( 'label' => 'عادی' ) );

		$this->add_control(
			'color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-login-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-login-btn svg' => 'stroke: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-login-btn',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'label'    => 'حاشیه',
				'selector' => '{{WRAPPER}} .devco-login-btn',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'label'    => 'سایه',
				'selector' => '{{WRAPPER}} .devco-login-btn',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'btn_hover', array( 'label' => 'هاور' ) );

		$this->add_control(
			'color_hover',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-login-btn:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg_hover',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-login-btn:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-login-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'padding',
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
		$s      = $this->get_settings_for_display();
		$preset = isset( $s['preset'] ) ? $s['preset'] : 'split';
		$icon   = isset( $s['icon'] ) ? $s['icon'] : 'user';
		$method = isset( $s['method'] ) ? $s['method'] : devco_opt( 'auth.default_method', 'otp' );

		echo '<div class="devco-widget devco-widget--login-btn">';

		if ( is_user_logged_in() ) {
			printf(
				'<a class="devco-login-btn devco-login-btn--preset-%1$s is-logged" href="%2$s">%3$s<span class="devco-login-btn__text">%4$s</span></a>',
				esc_attr( $preset ),
				esc_url( devco_dashboard_url() ),
				'yes' === ( isset( $s['show_icon'] ) ? $s['show_icon'] : 'yes' ) ? '<span class="devco-login-btn__icon">' . devco_icon( $icon, array( 'size' => 18 ) ) . '</span>' : '',
				esc_html( isset( $s['logged_text'] ) ? $s['logged_text'] : 'حساب کاربری' )
			);
		} elseif ( 'split' === $preset ) {
			printf(
				'<div class="devco-auth-split devco-login-btn--preset-split">%1$s<button type="button" class="devco-auth-split__login js-devco-auth-open" data-auth-view="%2$s" data-auth-mode="login"><span>%3$s</span></button><span class="devco-auth-split__sep" aria-hidden="true"></span><button type="button" class="devco-auth-split__register js-devco-auth-open" data-auth-view="%2$s" data-auth-mode="register"><span>%4$s</span></button></div>',
				'yes' === ( isset( $s['show_icon'] ) ? $s['show_icon'] : 'yes' ) ? '<span class="devco-auth-split__icon">' . devco_icon( $icon, array( 'size' => 18 ) ) . '</span>' : '',
				esc_attr( $method ),
				esc_html( isset( $s['login_text'] ) ? $s['login_text'] : 'ورود' ),
				esc_html( isset( $s['register_text'] ) ? $s['register_text'] : 'ثبت‌نام' )
			);
		} else {
			printf(
				'<button type="button" class="devco-login-btn devco-login-btn--preset-%1$s js-devco-auth-open" data-auth-view="%2$s">%3$s<span class="devco-login-btn__text">%4$s</span><span class="devco-login-btn__shine" aria-hidden="true"></span></button>',
				esc_attr( $preset ),
				esc_attr( $method ),
				'yes' === ( isset( $s['show_icon'] ) ? $s['show_icon'] : 'yes' ) ? '<span class="devco-login-btn__icon">' . devco_icon( $icon, array( 'size' => 18 ) ) . '</span>' : '',
				esc_html( isset( $s['login_text'] ) ? $s['login_text'] : 'ورود | ثبت‌نام' )
			);
		}

		echo '</div>';
	}
}
