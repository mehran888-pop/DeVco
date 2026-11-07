<?php
/**
 * Widget: product search.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Search
 */
class DeVco_Widget_Search extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-search';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | جستجو';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'search', 'جستجو' ) );
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
					'inline' => 'کادر همیشه باز',
					'icon'   => 'آیکون بازشونده',
					'popup'  => 'پنجره تمام‌صفحه',
				),
				'default' => 'inline',
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'   => 'متن راهنما',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'جستجو در محصولات…',
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => 'متن دکمه',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'جستجو',
			)
		);

		$this->add_control(
			'ajax',
			array(
				'label'        => 'نتایج زنده هنگام تایپ',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_field', 'کادر جستجو' );

		$this->add_control(
			'field_bg',
			array(
				'label'     => 'پس‌زمینه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-search__form' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'field_color',
			array(
				'label'     => 'رنگ متن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-search__input' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			array(
				'name'     => 'field_border',
				'label'    => 'حاشیه',
				'selector' => '{{WRAPPER}} .devco-search__form',
			)
		);

		$this->add_responsive_control(
			'field_radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-search__form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'     => 'پس‌زمینه دکمه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-search__submit' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => 'رنگ دکمه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-search__submit' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'field_width',
			array(
				'label'      => 'عرض کادر',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 180, 'max' => 900 ), '%' => array( 'min' => 20, 'max' => 100 ) ),
				'selectors'  => array( '{{WRAPPER}} .devco-search' => 'max-width: {{SIZE}}{{UNIT}};width:100%;' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$style = isset( $s['style'] ) ? $s['style'] : 'inline';
		$id    = 'devco-search-' . $this->get_id();

		printf( '<div class="devco-widget devco-widget--search devco-search devco-search--%1$s" data-search-style="%1$s" data-ajax="%2$s">', esc_attr( $style ), esc_attr( isset( $s['ajax'] ) ? $s['ajax'] : 'yes' ) );

		if ( 'inline' !== $style ) {
			printf(
				'<button type="button" class="devco-icon-btn devco-search__toggle" aria-label="جستجو" aria-expanded="false">%s</button>',
				devco_icon( 'search', array( 'size' => 20 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
			echo '<div class="devco-search__panel">';
		}

		echo '<form role="search" method="get" class="devco-search__form" action="' . esc_url( home_url( '/' ) ) . '">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">جستجو</label>';
		echo '<span class="devco-search__icon">' . devco_icon( 'search', array( 'size' => 18 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf(
			'<input type="search" id="%1$s" class="devco-search__input" name="s" value="%2$s" placeholder="%3$s" autocomplete="off" />',
			esc_attr( $id ),
			esc_attr( get_search_query() ),
			esc_attr( isset( $s['placeholder'] ) ? $s['placeholder'] : 'جستجو در محصولات…' )
		);
		if ( devco_is_woo() ) {
			echo '<input type="hidden" name="post_type" value="product" />';
		}
		printf( '<button type="submit" class="devco-search__submit">%s</button>', esc_html( isset( $s['button_text'] ) ? $s['button_text'] : 'جستجو' ) );
		echo '<div class="devco-search__results" aria-live="polite"></div>';
		echo '</form>';

		if ( 'inline' !== $style ) {
			echo '</div>';
		}

		echo '</div>';
	}
}
