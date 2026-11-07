<?php
/**
 * Widget: product category cards.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Product_Categories
 */
class DeVco_Widget_Product_Categories extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-product-categories';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | دسته‌بندی محصولات';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-categories';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'category', 'دسته‌بندی', 'categories' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'title',
			array(
				'label'       => 'عنوان بخش',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'خرید بر اساس دسته‌بندی',
				'label_block' => true,
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => 'تعداد',
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
				'max'     => 24,
			)
		);

		$this->add_control(
			'parents_only',
			array(
				'label'        => 'فقط دسته‌های اصلی',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hide_empty',
			array(
				'label'        => 'مخفی کردن دسته‌های خالی',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'no',
				'return_value' => 'yes',
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => 'تعداد ستون',
				'type'           => \Elementor\Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array( '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶' ),
				'selectors'      => array( '{{WRAPPER}} .devco-cat-grid' => '--devco-cols: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => 'نمایش تعداد محصول',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'tile_height',
			array(
				'label'      => 'ارتفاع کاشی',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 140, 'max' => 520 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 260 ),
				'selectors'  => array( '{{WRAPPER}} .devco-cat-tile' => 'height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل کاشی' );

		$this->add_control(
			'overlay_color',
			array(
				'label'     => 'رنگ لایه روی تصویر',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-cat-tile__overlay' => 'background: linear-gradient(180deg, transparent 30%, {{VALUE}} 100%);' ),
			)
		);

		$this->reg_typography( 'name', '{{WRAPPER}} .devco-cat-tile__name', 'نام دسته' );

		$this->add_control(
			'name_color',
			array(
				'label'     => 'رنگ نام',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-cat-tile__name' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی کاشی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-cat-tile' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'gap',
			array(
				'label'      => 'فاصله کاشی‌ها',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 50 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 20 ),
				'selectors'  => array( '{{WRAPPER}} .devco-cat-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
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

		$s = $this->get_settings_for_display();

		$args = array(
			'taxonomy'   => 'product_cat',
			'number'     => isset( $s['limit'] ) ? (int) $s['limit'] : 6,
			'hide_empty' => 'yes' === ( isset( $s['hide_empty'] ) ? $s['hide_empty'] : 'no' ),
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		if ( 'yes' === ( isset( $s['parents_only'] ) ? $s['parents_only'] : 'yes' ) ) {
			$args['parent'] = 0;
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || ! $terms ) {
			echo '<p class="devco-hint">دسته‌بندی‌ای یافت نشد.</p>';
			return;
		}

		echo '<div class="devco-widget devco-widget--cat-cards">';

		if ( ! empty( $s['title'] ) ) {
			echo '<div class="devco-widget__head"><h2 class="devco-widget__title">' . esc_html( $s['title'] ) . '</h2></div>';
		}

		echo '<div class="devco-cat-grid">';

		foreach ( $terms as $term ) {
			$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$image    = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'devco-card' ) : wc_placeholder_img_src( 'devco-card' );

			printf( '<a class="devco-cat-tile" href="%1$s">', esc_url( get_term_link( $term ) ) );
			printf( '<span class="devco-cat-tile__bg" style="background-image:url(%1$s)"></span>', esc_url( $image ) );
			echo '<span class="devco-cat-tile__overlay"></span>';
			echo '<span class="devco-cat-tile__content">';
			echo '<span class="devco-cat-tile__name">' . esc_html( $term->name ) . '</span>';

			if ( 'yes' === ( isset( $s['show_count'] ) ? $s['show_count'] : 'yes' ) ) {
				echo '<span class="devco-cat-tile__count">' . esc_html( devco_num( $term->count ) ) . ' محصول</span>';
			}

			echo '<span class="devco-cat-tile__cta">مشاهده ' . devco_icon( 'arrow-left', array( 'size' => 15 ) ) . '</span>';
			echo '</span></a>';
		}

		echo '</div></div>';
	}
}
