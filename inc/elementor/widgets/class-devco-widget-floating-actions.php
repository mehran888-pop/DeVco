<?php
/**
 * Widget: floating contact & social buttons.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Floating_Actions
 */
class DeVco_Widget_Floating_Actions extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-floating-actions';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | دکمه‌های شناور تماس';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-whatsapp';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'whatsapp', 'telegram', 'تماس', 'social', 'شناور' ) );
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
		$this->start_controls_section( 'content', array( 'label' => 'دکمه‌ها' ) );

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'expand'     => 'دکمه اصلی + بازشو',
					'vertical'   => 'ستونی',
					'horizontal' => 'افقی',
					'arc'        => 'قوسی',
				),
				'default' => 'expand',
			)
		);

		$this->add_control(
			'position',
			array(
				'label'   => 'موقعیت',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'bottom-right' => 'پایین راست',
					'bottom-left'  => 'پایین چپ',
					'custom'       => 'داخل صفحه',
				),
				'default' => 'bottom-right',
			)
		);

		$this->add_control(
			'style',
			array(
				'label'   => 'ظاهر',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'circle' => 'دایره‌ای رنگی',
					'soft'   => 'نرم با سایه',
					'glass'  => 'شیشه‌ای',
					'label'  => 'با برچسب متنی',
				),
				'default' => 'circle',
			)
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'type',
			array(
				'label'   => 'نوع',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'whatsapp'  => 'واتساپ',
					'telegram'  => 'تلگرام',
					'instagram' => 'اینستاگرام',
					'phone'     => 'تماس',
					'email'     => 'ایمیل',
					'sms'       => 'پیامک',
					'link'      => 'لینک دلخواه',
				),
				'default' => 'whatsapp',
			)
		);

		$repeater->add_control(
			'value',
			array(
				'label'       => 'مقدار (شماره / آیدی / لینک)',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'label',
			array(
				'label' => 'برچسب',
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$repeater->add_control(
			'color',
			array(
				'label' => 'رنگ اختصاصی',
				'type'  => \Elementor\Controls_Manager::COLOR,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => 'دکمه‌ها',
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'type' => 'whatsapp', 'value' => '', 'label' => 'واتساپ' ),
					array( 'type' => 'phone', 'value' => '', 'label' => 'تماس' ),
				),
				'title_field' => '{{{ label }}}',
			)
		);

		$this->add_control(
			'toggle_icon',
			array(
				'label'     => 'آیکون دکمه اصلی',
				'type'      => \Elementor\Controls_Manager::SELECT,
				'options'   => array(
					'headset' => 'پشتیبانی',
					'sms'     => 'گفتگو',
					'plus'    => 'پلاس',
					'sparkle' => 'جرقه',
				),
				'default'   => 'headset',
				'condition' => array( 'layout' => 'expand' ),
			)
		);

		$this->add_control(
			'hide_mobile',
			array(
				'label'        => 'مخفی در موبایل',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_box', 'استایل' );

		$this->add_control(
			'accent',
			array(
				'label'     => 'رنگ اصلی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fab' => '--devco-fab: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_bg',
			array(
				'label'     => 'پس‌زمینه دکمه‌ها',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fab__item' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_color',
			array(
				'label'     => 'رنگ آیکون‌ها',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-fab__item' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-fab__item svg' => 'stroke: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'toggle_bg',
			array(
				'label'     => 'پس‌زمینه دکمه اصلی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fab__toggle' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'toggle_color',
			array(
				'label'     => 'رنگ آیکون دکمه اصلی',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-fab__toggle svg' => 'stroke: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_size',
			array(
				'label'      => 'اندازه دکمه‌ها',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 34, 'max' => 76 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 50 ),
				'selectors'  => array( '{{WRAPPER}} .devco-fab' => '--devco-fab-size: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$layout = isset( $s['layout'] ) ? $s['layout'] : 'expand';
		$pos    = isset( $s['position'] ) ? $s['position'] : 'bottom-right';

		$classes = array( 'devco-widget', 'devco-fab', 'devco-fab--' . $layout, 'devco-fab--' . ( isset( $s['style'] ) ? $s['style'] : 'circle' ) );

		if ( 'custom' === $pos ) {
			$classes[] = 'devco-fab--inline';
		} else {
			$classes[] = 'devco-fab--' . $pos;
		}

		if ( 'yes' === ( isset( $s['hide_mobile'] ) ? $s['hide_mobile'] : 'no' ) ) {
			$classes[] = 'hide-mobile';
		}

		$items = isset( $s['items'] ) ? $s['items'] : array();

		if ( ! $items ) {
			return;
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" data-fab><div class="devco-fab__list">';

		foreach ( array_reverse( $items ) as $item ) {
			$type  = $item['type'];
			$value = trim( (string) $item['value'] );
			$url   = '';
			$icon  = 'link';

			switch ( $type ) {
				case 'whatsapp':
					$icon = 'whatsapp';
					$url  = 'https://wa.me/' . rawurlencode( preg_replace( '/[^0-9]/', '', $value ) );
					break;
				case 'telegram':
					$icon = 'telegram';
					$url  = 'https://t.me/' . rawurlencode( ltrim( $value, '@' ) );
					break;
				case 'instagram':
					$icon = 'instagram';
					$url  = $value ? $value : '';
					break;
				case 'phone':
					$icon = 'phone';
					$url  = 'tel:' . preg_replace( '/[^0-9+]/', '', $value );
					break;
				case 'email':
					$icon = 'mail';
					$url  = 'mailto:' . $value;
					break;
				case 'sms':
					$icon = 'sms';
					$url  = 'sms:' . preg_replace( '/[^0-9+]/', '', $value );
					break;
				default:
					$url = $value;
			}

			if ( ! $url ) {
				continue;
			}

			printf(
				'<a class="devco-fab__item is-%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s"%4$s><span class="devco-fab__ico">%5$s</span><span class="devco-fab__label">%6$s</span></a>',
				esc_attr( $type ),
				esc_url( $url ),
				esc_attr( $item['label'] ),
				! empty( $item['color'] ) ? ' style="background:' . esc_attr( $item['color'] ) . '"' : '',
				devco_icon( $icon, array( 'size' => 20 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $item['label'] )
			);
		}

		echo '</div>';

		if ( 'expand' === $layout ) {
			printf(
				'<button type="button" class="devco-fab__toggle" data-fab-toggle aria-expanded="false" aria-label="راه‌های ارتباطی">%s</button>',
				devco_icon( isset( $s['toggle_icon'] ) ? $s['toggle_icon'] : 'headset', array( 'size' => 23 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</div>';
	}
}
