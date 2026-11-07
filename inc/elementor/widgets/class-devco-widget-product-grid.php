<?php
/**
 * Widget: product grid.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Product_Grid
 */
class DeVco_Widget_Product_Grid extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-product-grid';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | شبکه محصولات';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-products';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'product', 'products', 'محصولات', 'grid', 'فروشگاه' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'source',
			array(
				'label'   => 'منبع محصولات',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'recent'    => 'جدیدترین',
					'featured'  => 'ویژه',
					'sale'      => 'تخفیف‌دار',
					'best'      => 'پرفروش‌ترین',
					'rated'     => 'بهترین امتیاز',
					'category'  => 'یک دسته‌بندی خاص',
					'manual'    => 'انتخاب دستی',
				),
				'default' => 'recent',
			)
		);

		$this->add_control(
			'category',
			array(
				'label'     => 'دسته‌بندی',
				'type'      => \Elementor\Controls_Manager::SELECT2,
				'options'   => $this->category_options(),
				'condition' => array( 'source' => 'category' ),
				'label_block' => true,
			)
		);

		$this->add_control(
			'product_ids',
			array(
				'label'       => 'شناسه محصولات',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'description' => 'شناسه‌ها را با ویرگول جدا کنید: 12,34,56',
				'condition'   => array( 'source' => 'manual' ),
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => 'تعداد',
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 8,
				'min'     => 1,
				'max'     => 40,
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
				'options'        => array(
					'1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶',
				),
				'selectors'      => array(
					'{{WRAPPER}} .devco-product-grid' => '--devco-cols: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => 'مرتب‌سازی',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'date'     => 'تاریخ',
					'price'    => 'قیمت',
					'popularity' => 'محبوبیت',
					'rating'   => 'امتیاز',
					'rand'     => 'تصادفی',
				),
				'default' => 'date',
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => 'ترتیب',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array( 'DESC' => 'نزولی', 'ASC' => 'صعودی' ),
				'default' => 'DESC',
			)
		);

		$this->add_control(
			'hide_outofstock',
			array(
				'label'        => 'مخفی کردن ناموجودها',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'header_title',
			array(
				'label'       => 'عنوان بخش',
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);

		$this->add_control(
			'header_subtitle',
			array(
				'label' => 'زیرعنوان',
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'more_link',
			array(
				'label' => 'لینک «مشاهده همه»',
				'type'  => \Elementor\Controls_Manager::URL,
			)
		);

		$this->end_controls_section();

		/* Card style */
		$this->start_style_section( 'style_card', 'کارت محصول' );

		$this->add_control(
			'card_style',
			array(
				'label'   => 'ظاهر کارت',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'clean'     => 'تمیز',
					'overlay'   => 'اکشن روی تصویر',
					'editorial' => 'ادیتوریال',
					'glass'     => 'شیشه‌ای',
				),
				'default' => 'overlay',
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => 'پس‌زمینه کارت',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-product-card__inner' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'label'    => 'سایه کارت',
				'selector' => '{{WRAPPER}} .devco-product-card__inner',
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => 'گردی کارت',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-product-card__inner, {{WRAPPER}} .devco-product-card__media img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-product-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'grid_gap',
			array(
				'label'      => 'فاصله بین کارت‌ها',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 24 ),
				'selectors'  => array( '{{WRAPPER}} .devco-product-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_text', 'متن کارت' );

		$this->reg_typography( 'title', '{{WRAPPER}} .devco-product-card__title', 'عنوان محصول' );

		$this->add_control(
			'title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-product-card__title a' => 'color: {{VALUE}};' ),
			)
		);

		$this->reg_typography( 'price', '{{WRAPPER}} .devco-product-card__price', 'قیمت' );

		$this->add_control(
			'price_color',
			array(
				'label'     => 'رنگ قیمت',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-product-card__price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'sale_color',
			array(
				'label'     => 'رنگ برچسب تخفیف',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-badge--sale' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'action_bg',
			array(
				'label'     => 'رنگ دکمه‌های اکشن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-card-action' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'action_color',
			array(
				'label'     => 'رنگ آیکون اکشن',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-card-action svg' => 'stroke: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Product category options.
	 *
	 * @return array
	 */
	protected function category_options() {
		if ( ! devco_is_woo() ) {
			return array();
		}

		$terms   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 200 ) );
		$options = array();

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Query products.
	 *
	 * @param array $s Settings.
	 * @return array
	 */
	protected function query( $s ) {
		$args = array(
			'limit'   => isset( $s['limit'] ) ? (int) $s['limit'] : 8,
			'status'  => 'publish',
			'orderby' => isset( $s['orderby'] ) ? $s['orderby'] : 'date',
			'order'   => isset( $s['order'] ) ? $s['order'] : 'DESC',
		);

		if ( 'best' === $s['source'] ) {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		if ( 'rated' === $s['source'] ) {
			$args['orderby'] = 'meta_value_num';
			$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		if ( 'rand' === $args['orderby'] ) {
			$args['orderby'] = 'rand';
		}

		switch ( $s['source'] ) {
			case 'featured':
				$args['featured'] = true;
				break;
			case 'sale':
				$args['include'] = wc_get_product_ids_on_sale();
				if ( ! $args['include'] ) {
					$args['include'] = array( 0 );
				}
				break;
			case 'category':
				if ( ! empty( $s['category'] ) ) {
					$args['category'] = array( get_term( (int) $s['category'], 'product_cat' ) ? get_term( (int) $s['category'], 'product_cat' )->slug : '' );
				}
				break;
			case 'manual':
				$ids = array_filter( array_map( 'absint', explode( ',', (string) $s['product_ids'] ) ) );
				$args['include'] = $ids ? $ids : array( 0 );
				$args['orderby'] = 'post__in';
				break;
		}

		if ( 'yes' === ( isset( $s['hide_outofstock'] ) ? $s['hide_outofstock'] : 'yes' ) ) {
			$args['stock_status'] = 'instock';
		}

		$products = wc_get_products( $args );

		return is_array( $products ) ? $products : array();
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( ! devco_is_woo() ) {
			echo '<div class="devco-widget"><p class="devco-hint">برای استفاده از این المان، ووکامرس را فعال کنید.</p></div>';
			return;
		}

		$s        = $this->get_settings_for_display();
		$products = $this->query( $s );

		echo '<div class="devco-widget devco-widget--products">';

		if ( ! empty( $s['header_title'] ) || ! empty( $s['header_subtitle'] ) ) {
			echo '<div class="devco-widget__head">';
			if ( ! empty( $s['header_title'] ) ) {
				echo '<h2 class="devco-widget__title">' . esc_html( $s['header_title'] ) . '</h2>';
			}
			if ( ! empty( $s['header_subtitle'] ) ) {
				echo '<p class="devco-widget__subtitle">' . esc_html( $s['header_subtitle'] ) . '</p>';
			}
			if ( ! empty( $s['more_link']['url'] ) ) {
				echo '<a class="devco-widget__more" href="' . esc_url( $s['more_link']['url'] ) . '">مشاهده همه ' . devco_icon( 'arrow-left', array( 'size' => 15 ) ) . '</a>';
			}
			echo '</div>';
		}

		if ( ! $products ) {
			echo '<p class="devco-hint">محصولی یافت نشد.</p></div>';
			return;
		}

		echo '<div class="devco-product-grid devco-product-grid--' . esc_attr( isset( $s['card_style'] ) ? $s['card_style'] : 'overlay' ) . '">';

		foreach ( $products as $product ) {
			devco_product_card( $product, array( 'style' => isset( $s['card_style'] ) ? $s['card_style'] : 'overlay' ) );
		}

		echo '</div></div>';
	}
}
