<?php
/**
 * Base class for DeVco Elementor widgets.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Base
 */
abstract class DeVco_Widget_Base extends \Elementor\Widget_Base {

	/**
	 * Category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'devco' );
	}

	/**
	 * Common style deps.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-widgets' );
	}

	/**
	 * Elementor keywords helper.
	 *
	 * @param array $extra Extra keywords.
	 * @return array
	 */
	protected function keywords( $extra = array() ) {
		return array_merge( array( 'devco', 'دیوکو', 'فروشگاه', 'shop', 'store' ), $extra );
	}

	/**
	 * Register a typography + color control pair.
	 *
	 * @param string $prefix   Control prefix.
	 * @param string $selector CSS selector.
	 * @param string $label    Label.
	 */
	protected function reg_typography( $prefix, $selector, $label = 'تایپوگرافی' ) {
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => $prefix . '_typography',
				'label'    => $label,
				'selector' => $selector,
			)
		);

		$this->add_control(
			$prefix . '_color',
			array(
				'label'     => 'رنگ',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( $selector => 'color: {{VALUE}};' ),
			)
		);
	}

	/**
	 * Register a box style group (background, border, radius, shadow, padding).
	 *
	 * @param string $prefix   Prefix.
	 * @param string $selector Selector.
	 */
	protected function reg_box_style( $prefix, $selector ) {
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => $prefix . '_bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $selector,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => $prefix . '_border',
				'label'    => 'حاشیه',
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			$prefix . '_radius',
			array(
				'label'      => 'گردی گوشه‌ها',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => $prefix . '_shadow',
				'label'    => 'سایه',
				'selector' => $selector,
			)
		);

		$this->add_responsive_control(
			$prefix . '_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
	}

	/**
	 * Start a style section.
	 *
	 * @param string $name  Section name.
	 * @param string $label Label.
	 * @param string $tab   Tab.
	 */
	protected function start_style_section( $name, $label, $tab = \Elementor\Controls_Manager::TAB_STYLE ) {
		$this->start_controls_section( $name, array( 'label' => $label, 'tab' => $tab ) );
	}

	/**
	 * A simple "advanced" alignment control.
	 *
	 * @param string $prefix   Prefix.
	 * @param string $selector Selector.
	 * @param string $label    Label.
	 */
	protected function reg_align( $prefix, $selector, $label = 'چینش' ) {
		$this->add_responsive_control(
			$prefix . '_align',
			array(
				'label'     => $label,
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
				),
				'selectors' => array( $selector => 'text-align: {{VALUE}};' ),
			)
		);
	}

	/**
	 * Render helper: fetch a product by id.
	 *
	 * @param int $product_id Product ID.
	 * @return WC_Product|false
	 */
	protected function get_product( $product_id ) {
		if ( ! devco_is_woo() ) {
			return false;
		}

		$product = wc_get_product( (int) $product_id );

		return $product ? $product : false;
	}
}
