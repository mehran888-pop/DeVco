<?php
/**
 * Widget: features / icon boxes row.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Features
 */
class DeVco_Widget_Features extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-features';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | مزیت‌ها و خدمات';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-icon-box';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'feature', 'icon box', 'مزیت', 'خدمات' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'icon',
			array(
				'label'   => 'آیکون',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'truck'      => 'ارسال',
					'shield'     => 'ضمانت',
					'refresh'    => 'مرجوعی',
					'credit-card' => 'پرداخت',
					'headset'    => 'پشتیبانی',
					'scissors'   => 'دوخت',
					'ruler'      => 'اندازه',
					'sparkle'    => 'ویژه',
					'gift'       => 'هدیه',
					'check-badge' => 'تایید',
					'phone'      => 'تماس',
					'camera'     => 'اتاق پرو',
				),
				'default' => 'truck',
			)
		);

		$repeater->add_control(
			'title',
			array(
				'label'   => 'عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'ارسال سریع',
			)
		);

		$repeater->add_control(
			'text',
			array(
				'label' => 'توضیح',
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$repeater->add_control(
			'link',
			array(
				'label' => 'لینک',
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'آیتم‌ها',
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'icon' => 'truck', 'title' => 'ارسال سریع', 'text' => 'تهران ۲۴ ساعته، شهرستان ۲ روز کاری' ),
					array( 'icon' => 'refresh', 'title' => '۷ روز ضمانت بازگشت', 'text' => 'بدون قید و شرط' ),
					array( 'icon' => 'shield', 'title' => 'ضمانت اصالت کالا', 'text' => 'تولید اختصاصی دیوکو' ),
					array( 'icon' => 'headset', 'title' => 'پشتیبانی واقعی', 'text' => 'همه روزه ۹ تا ۲۱' ),
				),
				'title_field' => '{{{ title }}}',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => 'تعداد ستون',
				'type'           => \Elementor\Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '2',
				'options'        => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵' ),
				'selectors'      => array( '{{WRAPPER}} .devco-features' => '--devco-cols: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل' );

		$this->add_control(
			'card_bg',
			array(
				'label'     => 'پس‌زمینه کارت',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-feature' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'icon_bg',
			array(
				'label'     => 'پس‌زمینه آیکون',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-feature__icon' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'icon_color',
			array(
				'label'     => 'رنگ آیکون',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-feature__icon svg' => 'stroke: {{VALUE}};' ),
			)
		);

		$this->reg_typography( 'title_typo', '{{WRAPPER}} .devco-feature__title' );

		$this->add_control(
			'title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-feature__title' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => 'رنگ توضیح',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-feature__text' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی کارت',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-feature' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'row'    => 'آیکون کنار متن',
					'column' => 'آیکون بالا، متن پایین',
					'minimal' => 'مینیمال بدون پس‌زمینه',
				),
				'default' => 'row',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = isset( $s['items'] ) ? $s['items'] : array();

		if ( ! $items ) {
			return;
		}

		printf( '<div class="devco-widget devco-features devco-features--%s">', esc_attr( isset( $s['layout'] ) ? $s['layout'] : 'row' ) );

		foreach ( $items as $item ) {
			$tag   = ! empty( $item['link']['url'] ) ? 'a' : 'div';
			$attrs = ! empty( $item['link']['url'] ) ? ' href="' . esc_url( $item['link']['url'] ) . '"' : '';

			printf( '<%1$s class="devco-feature"%2$s>', esc_attr( $tag ), $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="devco-feature__icon">' . devco_icon( isset( $item['icon'] ) ? $item['icon'] : 'sparkle', array( 'size' => 22 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="devco-feature__body">';
			echo '<span class="devco-feature__title">' . esc_html( $item['title'] ) . '</span>';
			if ( ! empty( $item['text'] ) ) {
				echo '<span class="devco-feature__text">' . esc_html( $item['text'] ) . '</span>';
			}
			echo '</span>';
			printf( '</%s>', esc_attr( $tag ) );
		}

		echo '</div>';
	}
}
