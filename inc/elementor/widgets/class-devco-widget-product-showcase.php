<?php
/**
 * Widget: single product showcase (hero).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Product_Showcase
 */
class DeVco_Widget_Product_Showcase extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-product-showcase';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | ویترین محصول';
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-woocommerce';
	}

	/**
	 * Keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return $this->keywords( array( 'product', 'showcase', 'ویترین', 'محصول' ) );
	}

	/**
	 * Styles.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'devco-main', 'devco-woo', 'devco-widgets', 'devco-fitting' );
	}

	/**
	 * Scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'devco-main', 'devco-fitting' );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'product_id',
			array(
				'label'       => 'محصول',
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'options'     => $this->product_options(),
				'label_block' => true,
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'split'   => 'تصویر و متن کنار هم',
					'reverse' => 'متن راست، تصویر چپ',
					'overlay' => 'متن روی تصویر',
				),
				'default' => 'split',
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'   => 'متن بالای عنوان',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'کالکشن پاییز',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => 'نمایش قیمت',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_cart',
			array(
				'label'        => 'فرم افزودن به سبد',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_fitting',
			array(
				'label'        => 'دکمه اتاق پرو',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'notes',
			array(
				'label'       => 'نکات (هر خط یک مورد)',
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => "پارچه کرپ درجه یک\nدوخت سفارشی با اندازه شما\nامکان تعویض تا ۷ روز",
				'label_block' => true,
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style', 'استایل' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'bg',
				'label'    => 'پس‌زمینه',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .devco-showcase',
			)
		);

		$this->add_responsive_control(
			'padding',
			array(
				'label'      => 'فاصله داخلی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-showcase' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'radius',
			array(
				'label'      => 'گردی',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-showcase, {{WRAPPER}} .devco-showcase__media img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->reg_typography( 'title_typo', '{{WRAPPER}} .devco-showcase__title' );

		$this->add_control(
			'title_color',
			array(
				'label'     => 'رنگ عنوان',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-showcase__title' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'price_color',
			array(
				'label'     => 'رنگ قیمت',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-showcase__price' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'     => 'پس‌زمینه دکمه خرید',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-showcase .button' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Product options.
	 *
	 * @return array
	 */
	protected function product_options() {
		if ( ! devco_is_woo() ) {
			return array();
		}

		$products = wc_get_products( array( 'limit' => 100, 'status' => 'publish' ) );
		$options  = array();

		foreach ( $products as $product ) {
			$options[ $product->get_id() ] = $product->get_name();
		}

		return $options;
	}

	/**
	 * Render.
	 */
	protected function render() {
		if ( ! devco_is_woo() ) {
			echo '<p class="devco-hint">ووکامرس را فعال کنید.</p>';
			return;
		}

		$s          = $this->get_settings_for_display();
		$product_id = isset( $s['product_id'] ) ? (int) $s['product_id'] : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product ) {
			$recent = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) );
			$product = $recent ? $recent[0] : false;
		}

		if ( ! $product ) {
			echo '<p class="devco-hint">محصولی انتخاب نشده است.</p>';
			return;
		}

		$layout = isset( $s['layout'] ) ? $s['layout'] : 'split';

		printf( '<div class="devco-showcase devco-showcase--%s">', esc_attr( $layout ) );

		echo '<div class="devco-showcase__media">';
		echo '<a href="' . esc_url( $product->get_permalink() ) . '">';
		echo $product->get_image( 'large' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</a>';
		if ( $product->is_on_sale() ) {
			echo '<span class="devco-badge devco-badge--sale">فروش ویژه</span>';
		}
		echo '</div>';

		echo '<div class="devco-showcase__body">';

		if ( ! empty( $s['eyebrow'] ) ) {
			echo '<span class="devco-showcase__eyebrow">' . esc_html( $s['eyebrow'] ) . '</span>';
		}

		echo '<h2 class="devco-showcase__title"><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a></h2>';

		if ( $product->get_average_rating() ) {
			echo '<div class="devco-showcase__rating">' . devco_stars( (float) $product->get_average_rating() ) . '<span>' . esc_html( devco_num( $product->get_review_count() ) ) . ' دیدگاه</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( 'yes' === ( isset( $s['show_price'] ) ? $s['show_price'] : 'yes' ) ) {
			echo '<div class="devco-showcase__price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
		}

		if ( $product->get_short_description() ) {
			echo '<div class="devco-showcase__desc">' . wp_kses_post( wpautop( $product->get_short_description() ) ) . '</div>';
		}

		if ( ! empty( $s['notes'] ) ) {
			echo '<ul class="devco-showcase__notes">';
			foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $s['notes'] ) ) ) as $note ) {
				echo '<li>' . devco_icon( 'check', array( 'size' => 15 ) ) . '<span>' . esc_html( $note ) . '</span></li>';
			}
			echo '</ul>';
		}

		echo '<div class="devco-showcase__actions">';

		if ( 'yes' === ( isset( $s['show_cart'] ) ? $s['show_cart'] : 'yes' ) ) {
			if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
				printf(
					'<button type="button" class="devco-btn devco-btn--primary js-devco-quick-add" data-product-id="%1$d">%2$s افزودن به سبد</button>',
					(int) $product->get_id(),
					devco_icon( 'cart', array( 'size' => 17 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
			printf( '<a class="devco-btn devco-btn--primary" href="%s">مشاهده و خرید</a>', esc_url( $product->get_permalink() ) );
		}

		if ( 'yes' === ( isset( $s['show_fitting'] ) ? $s['show_fitting'] : 'yes' ) ) {
			printf(
				'<button type="button" class="devco-btn devco-btn--ghost js-devco-fitting-open" data-product-id="%1$d">%2$s پرو لباس</button>',
				(int) $product->get_id(),
				devco_icon( 'tshirt', array( 'size' => 17 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		echo '</div></div></div>';

		devco_fitting_room( array( 'id' => 'devco-fitting-' . $this->get_id(), 'product_id' => $product->get_id() ) );
	}
}
