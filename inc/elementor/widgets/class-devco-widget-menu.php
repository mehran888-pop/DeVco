<?php
/**
 * Widget: primary navigation menu (header).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Menu
 */
class DeVco_Widget_Menu extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-menu';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | منوی اصلی';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-nav-menu';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'menu', 'منو', 'navigation', 'هدر' ) );
	}

	/**
	 * Scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'devco-main' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$menus = array( '0' => '— منوی اصلی قالب —' );

		foreach ( wp_get_nav_menus() as $menu ) {
			$menus[ $menu->term_id ] = $menu->name;
		}

		$this->add_control(
			'menu_id',
			array(
				'label'   => 'انتخاب منو',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $menus,
				'default' => '0',
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'classic'   => 'کلاسیک (لیست افقی)',
					'pill'      => 'قرصی با پس‌زمینه',
					'underline' => 'خط زیر آیتم فعال',
					'center'    => 'وسط‌چین با فاصله',
					'vertical'  => 'عمودی',
				),
				'default' => 'classic',
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => 'حالت زیرمنو',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'dropdown' => 'کرکره‌ای ساده',
					'mega'     => 'مگا منو (چند ستونه)',
					'flyout'   => 'کشویی از کنار',
				),
				'default' => 'mega',
			)
		);

		$this->add_control(
			'mega_columns',
			array(
				'label'     => 'ستون‌های مگا منو',
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array( '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵' ),
				'default'   => '4',
				'condition' => array( 'mode' => 'mega' ),
			)
		);

		$this->add_control(
			'show_arrow',
			array(
				'label'        => 'فلش آیتم‌های دارای زیرمنو',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'accessibility_label',
			array(
				'label'       => 'برچسب دسترسی‌پذیری',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'منوی اصلی',
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		/* ------------------------------ Items style ------------------------------ */
		$this->start_style_section( 'style_items', 'آیتم‌های منو' );

		$this->reg_typography( 'item', '{{WRAPPER}} .devco-menu > li > a', 'تایپوگرافی آیتم‌ها' );

		$this->start_controls_tabs( 'item_states' );

		$this->start_controls_tab( 'item_normal', array( 'label' => 'عادی' ) );

		$this->add_control(
			'item_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li > a' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_bg',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li > a' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'item_hover', array( 'label' => 'هاور' ) );

		$this->add_control(
			'item_color_hover',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li:hover > a, {{WRAPPER}} .devco-menu > li > a:focus' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_bg_hover',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li:hover > a' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'item_active', array( 'label' => 'فعال' ) );

		$this->add_control(
			'item_color_active',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li.current-menu-item > a, {{WRAPPER}} .devco-menu > li.current-menu-parent > a' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_indicator',
			array(
				'label'     => 'رنگ نشانگر',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu > li > a::after' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'item_gap',
			array(
				'label'      => 'فاصله بین آیتم‌ها',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors'  => array( '{{WRAPPER}} .devco-menu' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'item_padding',
			array(
				'label'      => 'فاصله داخلی آیتم',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-menu > li > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'item_radius',
			array(
				'label'      => 'گردی آیتم‌ها',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-menu > li > a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		/* ---------------------------- Dropdown style ---------------------------- */
		$this->start_style_section( 'style_dropdown', 'زیرمنو' );

		$this->add_control(
			'dropdown_bg',
			array(
				'label'     => 'پس‌زمینه زیرمنو',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-menu .sub-menu' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .devco-nav--mode-flyout .devco-menu' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'dropdown_border',
				'label'    => 'حاشیه زیرمنو',
				'selector' => '{{WRAPPER}} .devco-menu .sub-menu',
			)
		);

		$this->reg_typography( 'dropdown_item', '{{WRAPPER}} .devco-menu .sub-menu a', 'تایپوگرافی زیرمنو' );

		$this->add_control(
			'dropdown_color',
			array(
				'label'     => 'رنگ متن زیرمنو',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu .sub-menu a' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dropdown_color_hover',
			array(
				'label'     => 'رنگ متن هاور',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-menu .sub-menu li:hover > a' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'dropdown_width',
			array(
				'label'      => 'حداقل عرض زیرمنو',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 140, 'max' => 900 ) ),
				'selectors'  => array( '{{WRAPPER}} .devco-menu .sub-menu' => 'min-width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$menu_id  = isset( $settings['menu_id'] ) ? (int) $settings['menu_id'] : 0;

		$classes = array(
			'devco-widget',
			'devco-widget--menu',
			'devco-nav',
			'devco-nav--' . ( isset( $settings['layout'] ) ? $settings['layout'] : 'classic' ),
			'devco-nav--mode-' . ( isset( $settings['mode'] ) ? $settings['mode'] : 'mega' ),
		);

		if ( 'yes' !== ( isset( $settings['show_arrow'] ) ? $settings['show_arrow'] : 'yes' ) ) {
			$classes[] = 'devco-nav--no-arrow';
		}

		printf(
			'<nav class="%1$s" data-menu-mode="%2$s" data-mega-cols="%3$s" aria-label="%4$s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( isset( $settings['mode'] ) ? $settings['mode'] : 'mega' ),
			esc_attr( isset( $settings['mega_columns'] ) ? $settings['mega_columns'] : '4' ),
			esc_attr( isset( $settings['accessibility_label'] ) ? $settings['accessibility_label'] : 'منوی اصلی' )
		);

		$args = array(
			'container'   => false,
			'menu_class'  => 'devco-menu devco-menu--main',
			'fallback_cb' => 'devco_menu_fallback',
			'depth'       => 3,
		);

		if ( $menu_id ) {
			$args['menu'] = $menu_id;
		} else {
			$args['theme_location'] = 'primary';
		}

		wp_nav_menu( $args );

		echo '</nav>';
	}
}
