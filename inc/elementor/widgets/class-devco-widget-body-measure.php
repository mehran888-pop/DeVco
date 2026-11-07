<?php
/**
 * Widget: body measurement / tailoring form.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Body_Measure
 */
class DeVco_Widget_Body_Measure extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-body-measure';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | فرم اندازه‌گیری (دوخت)';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-person';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'اندازه', 'دوخت', 'measure', 'tailor', '3d', 'مانکن' ) );
	}

	/**
	 * Styles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-tailoring' );
	}

	/**
	 * Scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'devco-tailoring' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'mode',
			array(
				'label'   => 'حالت',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'full'    => 'فرم کامل سفارش دوخت',
					'bodymap' => 'فقط مانکن و اندازه‌ها',
				),
				'default' => 'full',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => 'عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'اندازه‌های خود را ثبت کنید',
			)
		);

		$this->add_control(
			'model',
			array(
				'label'   => 'مدل مانکن',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'female'  => 'زنانه',
					'male'    => 'مردانه',
					'neutral' => 'خنثی',
				),
				'default' => 'female',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل مانکن' );

		$this->add_control(
			'skin',
			array(
				'label'     => 'رنگ بدنه مانکن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-bodymap' => '--bm-skin: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'accent',
			array(
				'label'     => 'رنگ نقاط اندازه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-bodymap' => '--bm-accent: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'bg',
			array(
				'label'     => 'پس‌زمینه صحنه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-bodymap__stage' => 'background: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$mode = isset( $s['mode'] ) ? $s['mode'] : 'full';

		echo '<div class="devco-widget devco-widget--bodymeasure">';

		if ( 'bodymap' === $mode ) {
			if ( ! empty( $s['title'] ) ) {
				echo '<h3 class="devco-widget__title">' . esc_html( $s['title'] ) . '</h3>';
			}

			DeVco_Tailoring::body_map( array( 'model' => isset( $s['model'] ) ? $s['model'] : 'female' ) );
		} else {
			DeVco_Tailoring::render_form(
				array(
					'title' => isset( $s['title'] ) ? $s['title'] : devco_opt( 'tailoring.title', 'سفارش دوخت' ),
				)
			);
		}

		echo '</div>';
	}
}
