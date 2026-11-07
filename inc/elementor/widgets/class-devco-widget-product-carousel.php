<?php
/**
 * Widget: product carousel.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Product_Carousel
 */
class DeVco_Widget_Product_Carousel extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-product-carousel';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | اسلایدر محصولات';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-carousel';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'carousel', 'slider', 'اسلایدر', 'محصولات' ) );
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

		$this->add_control(
			'source',
			array(
				'label'   => 'منبع',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'recent'   => 'جدیدترین',
					'featured' => 'ویژه',
					'sale'     => 'تخفیف‌دار',
					'best'     => 'پرفروش‌ترین',
					'related'  => 'مشابه محصول جاری',
				),
				'default' => 'featured',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => 'تعداد',
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 2,
				'max'     => 30,
			)
		);

		$this->add_responsive_control(
			'slides',
			array(
				'label'          => 'تعداد اسلاید نمایش‌داده‌شده',
				'type'           => \Elementor\Controls_Manager::SELECT,
				'default'        => '4',
				'tablet_default' => '2',
				'mobile_default' => '2',
				'options'        => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵' ),
				'selectors'      => array( '{{WRAPPER}} .devco-carousel' => '--devco-slides: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'header_title',
			array(
				'label'       => 'عنوان',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'پیشنهادهای ویژه دیوکو',
				'label_block' => true,
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => 'پخش خودکار',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => 'سرعت پخش (میلی‌ثانیه)',
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 4000,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'        => 'فلش‌های ناوبری',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'dots',
			array(
				'label'        => 'نقطه‌های ناوبری',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل اسلایدر' );

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => 'پس‌زمینه فلش‌ها',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-carousel__nav' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'arrow_color',
			array(
				'label'     => 'رنگ فلش‌ها',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-carousel__nav svg' => 'stroke: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => 'رنگ نقطه فعال',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-carousel__dot.is-active' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'فاصله اسلایدها',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .devco-carousel__track' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( ! devco_is_woo() ) {
			echo '<div class="devco-widget"><p class="devco-hint">ووکامرس را فعال کنید.</p></div>';
			return;
		}

		$s   = $this->get_settings_for_display();
		$ids = array();

		if ( 'related' === $s['source'] && is_product() ) {
			$ids = wc_get_related_products( get_the_ID(), (int) $s['limit'] );
		}

		$args = array(
			'limit'   => isset( $s['limit'] ) ? (int) $s['limit'] : 10,
			'status'  => 'publish',
			'orderby' => 'date',
		);

		if ( $ids ) {
			$args['include'] = $ids;
			$args['orderby'] = 'post__in';
		} elseif ( 'featured' === $s['source'] ) {
			$args['featured'] = true;
		} elseif ( 'best' === $s['source'] ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		} elseif ( 'sale' === $s['source'] ) {
			$sale_ids        = wc_get_product_ids_on_sale();
			$args['include'] = $sale_ids ? $sale_ids : array( 0 );
		}

		$products = wc_get_products( $args );

		if ( ! $products ) {
			echo '<p class="devco-hint">محصولی یافت نشد.</p>';
			return;
		}

		$autoplay = 'yes' === ( isset( $s['autoplay'] ) ? $s['autoplay'] : 'yes' );

		printf(
			'<div class="devco-widget devco-widget--carousel"><div class="devco-carousel" data-carousel data-autoplay="%1$s" data-speed="%2$d">',
			$autoplay ? (int) ( isset( $s['autoplay_speed'] ) ? $s['autoplay_speed'] : 4000 ) : '0',
			(int) ( isset( $s['autoplay_speed'] ) ? $s['autoplay_speed'] : 4000 )
		);

		if ( ! empty( $s['header_title'] ) ) {
			echo '<div class="devco-widget__head"><h2 class="devco-widget__title">' . esc_html( $s['header_title'] ) . '</h2>';
			if ( 'yes' === ( isset( $s['arrows'] ) ? $s['arrows'] : 'yes' ) ) {
				echo '<div class="devco-carousel__arrows"><button type="button" class="devco-carousel__nav" data-dir="prev" aria-label="قبلی">' . devco_icon( 'chevron-right', array( 'size' => 18 ) ) . '</button><button type="button" class="devco-carousel__nav" data-dir="next" aria-label="بعدی">' . devco_icon( 'chevron-left', array( 'size' => 18 ) ) . '</button></div>';
			}
			echo '</div>';
		}

		echo '<div class="devco-carousel__viewport"><div class="devco-carousel__track">';

		foreach ( $products as $product ) {
			echo '<div class="devco-carousel__slide">';
			devco_product_card( $product );
			echo '</div>';
		}

		echo '</div></div>';

		if ( 'yes' === ( isset( $s['dots'] ) ? $s['dots'] : 'yes' ) ) {
			echo '<div class="devco-carousel__dots" data-dots></div>';
		}

		echo '</div></div>';
	}
}
