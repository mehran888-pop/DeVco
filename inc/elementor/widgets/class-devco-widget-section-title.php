<?php
/**
 * Widget: section title.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Section_Title
 */
class DeVco_Widget_Section_Title extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-section-title';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | عنوان بخش';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-heading';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'title', 'heading', 'عنوان' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'eyebrow',
			array(
				'label'   => 'متن بالای عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'کالکشن جدید',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'استایل خودت را بساز',
				'label_block' => true,
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label' => 'زیرعنوان',
				'type'  => \Elementor\Controls_Manager::TEXTAREA,
				'rows'  => 2,
			)
		);

		$this->add_control(
			'link',
			array(
				'label' => 'لینک',
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => 'استایل',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'line'    => 'با خط تزئینی',
					'center'  => 'وسط‌چین',
					'outline' => 'عنوان خطی بزرگ',
					'split'   => 'عنوان و دکمه در دو طرف',
				),
				'default' => 'line',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_text', 'استایل' );

		$this->reg_typography( 'title', '{{WRAPPER}} .devco-section-title__title', 'عنوان' );

		$this->add_control(
			'title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-section-title__title' => 'color: {{VALUE}};' ),
			)
		);

		$this->reg_typography( 'eyebrow_typo', '{{WRAPPER}} .devco-section-title__eyebrow' );

		$this->add_control(
			'eyebrow_color',
			array(
				'label'     => 'رنگ متن بالای عنوان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-section-title__eyebrow' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'line_color',
			array(
				'label'     => 'رنگ خط تزئینی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-section-title__line' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'align',
			array(
				'label'     => 'چینش',
				'type'      => \Elementor\Controls_Manager::CHOOSE,
				'options'   => array(
					'right'  => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ),
					'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ),
					'left'   => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ),
				),
				'selectors' => array( '{{WRAPPER}} .devco-section-title' => 'text-align: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		printf( '<div class="devco-widget devco-section-title devco-section-title--%s">', esc_attr( isset( $s['style'] ) ? $s['style'] : 'line' ) );

		echo '<div class="devco-section-title__text">';

		if ( ! empty( $s['eyebrow'] ) ) {
			echo '<span class="devco-section-title__eyebrow"><span class="devco-section-title__line"></span>' . esc_html( $s['eyebrow'] ) . '</span>';
		}

		if ( ! empty( $s['title'] ) ) {
			echo '<h2 class="devco-section-title__title">' . esc_html( $s['title'] ) . '</h2>';
		}

		if ( ! empty( $s['subtitle'] ) ) {
			echo '<p class="devco-section-title__subtitle">' . esc_html( $s['subtitle'] ) . '</p>';
		}

		echo '</div>';

		if ( ! empty( $s['link']['url'] ) ) {
			printf(
				'<a class="devco-section-title__link" href="%1$s">مشاهده همه %2$s</a>',
				esc_url( $s['link']['url'] ),
				devco_icon( 'arrow-left', array( 'size' => 16 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</div>';
	}
}
