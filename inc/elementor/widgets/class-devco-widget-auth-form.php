<?php
/**
 * Widget: inline unified auth form.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Auth_Form
 */
class DeVco_Widget_Auth_Form extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-auth-form';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | فرم ورود و ثبت‌نام';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'login', 'register', 'otp', 'پیامک', 'ورود' ) );
	}

	/**
	 * Styles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-auth' );
	}

	/**
	 * Scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'devco-auth' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'style',
			array(
				'label'   => 'استایل فرم',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'elegant' => 'لوکس (طلایی)',
					'modern'  => 'مدرن',
					'glass'   => 'شیشه‌ای',
					'dark'    => 'تیره',
					'neon'    => 'نئون',
				),
				'default' => 'elegant',
			)
		);

		$this->add_control(
			'default_view',
			array(
				'label'   => 'تب پیش‌فرض',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array( 'otp' => 'پیامکی', 'password' => 'رمز عبور' ),
				'default' => 'otp',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => 'عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'ورود / ثبت‌نام',
			)
		);

		$this->add_control(
			'show_modal_button',
			array(
				'label'        => 'افزودن دکمه باز کردن پنجره ورود',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_form', 'استایل فرم' );

		$this->add_control(
			'accent',
			array(
				'label'     => 'رنگ تاکیدی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-auth' => '--devco-auth-accent: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-auth',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'border',
				'label'    => 'حاشیه',
				'selector' => '{{WRAPPER}} .devco-auth',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'shadow',
				'label'    => 'سایه',
				'selector' => '{{WRAPPER}} .devco-auth',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-auth' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-auth' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'max_width',
			array(
				'label'      => 'حداکثر عرض',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 280, 'max' => 700 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 420 ),
				'selectors'  => array( '{{WRAPPER}} .devco-auth' => 'max-width: {{SIZE}}{{UNIT}};margin-inline:auto;' ),
			)
		);

		$this->reg_typography( 'title_typo', '{{WRAPPER}} .devco-auth__title' );

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			echo '<div class="devco-widget devco-widget--auth-logged">';
			echo '<div class="devco-auth-logged">';
			echo get_avatar( $user->ID, 56, '', '', array( 'class' => 'devco-avatar' ) );
			echo '<div><strong>' . esc_html( $user->display_name ) . '</strong><p>شما وارد شده‌اید.</p><a class="devco-btn devco-btn--sm devco-btn--primary" href="' . esc_url( devco_dashboard_url() ) . '">رفتن به داشبورد</a></div>';
			echo '</div></div>';
			return;
		}

		$s = $this->get_settings_for_display();

		echo '<div class="devco-widget devco-widget--auth">';

		if ( 'yes' === ( isset( $s['show_modal_button'] ) ? $s['show_modal_button'] : 'no' ) ) {
			echo '<button type="button" class="devco-btn devco-btn--primary js-devco-auth-open">' . esc_html( devco_opt( 'auth.button_text', 'ورود | ثبت‌نام' ) ) . '</button>';
		}

		echo DeVco_Auth::form_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'context' => 'inline',
				'style'   => isset( $s['style'] ) ? $s['style'] : 'elegant',
				'view'    => isset( $s['default_view'] ) ? $s['default_view'] : 'otp',
			)
		);

		echo '</div>';
	}
}
