<?php
/**
 * Widget: product categories menu.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'DeVco_Widget_Base' ) ) {
	return;
}

/**
 * Class DeVco_Widget_Category_Menu
 */
class DeVco_Widget_Category_Menu extends DeVco_Widget_Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'devco-category-menu';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return 'دیوکو | منوی دسته‌بندی';
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
		return $this->keywords( array( 'category', 'دسته‌بندی', 'mega menu', 'مگا منو' ) );
	}

	/**
	 * Controls.
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا' ) );

		$this->add_control(
			'layout',
			array(
				'label'   => 'چیدمان',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'dropdown'   => 'دکمه کشویی',
					'vertical'   => 'ستون عمودی',
					'horizontal' => 'نوار افقی',
				),
				'default' => 'dropdown',
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => 'عنوان دکمه',
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'دسته‌بندی محصولات',
			)
		);

		$this->add_control(
			'icon',
			array(
				'label'   => 'آیکون',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->icon_options(),
				'default' => 'categories',
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => 'منبع',
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'auto'     => 'دسته‌های محصولات ووکامرس',
					'wp_menu'  => 'منوی دسته‌بندی قالب',
					'products' => 'لیست محصولات پرطرفدار',
				),
				'default' => 'auto',
			)
		);

		$this->add_control(
			'limit',
			array(
				'label'   => 'تعداد آیتم',
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 10,
				'min'     => 2,
				'max'     => 40,
			)
		);

		$this->add_control(
			'show_thumb',
			array(
				'label'        => 'نمایش تصویر دسته',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_count',
			array(
				'label'        => 'نمایش تعداد محصولات',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'subcats',
			array(
				'label'        => 'نمایش زیردسته‌ها',
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_button', 'دکمه اصلی' );

		$this->reg_typography( 'btn', '{{WRAPPER}} .devco-catmenu__toggle', 'تایپوگرافی دکمه' );

		$this->add_control(
			'btn_color',
			array(
				'label'     => 'رنگ متن دکمه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-catmenu__toggle' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'     => 'پس‌زمینه دکمه',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-catmenu__toggle' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => 'گردی دکمه',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .devco-catmenu__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => 'فاصله داخلی دکمه',
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .devco-catmenu__toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_style_section( 'style_panel', 'پنل دسته‌ها' );

		$this->add_control(
			'panel_bg',
			array(
				'label'     => 'پس‌زمینه پنل',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-catmenu__panel' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'panel_shadow',
				'label'    => 'سایه پنل',
				'selector' => '{{WRAPPER}} .devco-catmenu__panel',
			)
		);

		$this->reg_typography( 'item', '{{WRAPPER}} .devco-catmenu__link', 'تایپوگرافی آیتم‌ها' );

		$this->add_control(
			'item_color',
			array(
				'label'     => 'رنگ آیتم',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-catmenu__link' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'item_color_hover',
			array(
				'label'     => 'رنگ آیتم در هاور',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .devco-catmenu__link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .devco-catmenu__link:hover .devco-catmenu__name' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'item_hover_bg',
			array(
				'label'     => 'پس‌زمینه هاور',
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .devco-catmenu__link:hover' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_responsive_control(
			'panel_width',
			array(
				'label'      => 'عرض پنل',
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 200, 'max' => 520 ) ),
				'selectors'  => array( '{{WRAPPER}} .devco-catmenu__panel' => 'width: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Icon choices.
	 *
	 * @return array
	 */
	protected function icon_options() {
		return array(
			'categories' => 'دسته‌بندی',
			'grid'       => 'شبکه',
			'bag'        => 'کیف خرید',
			'menu'       => 'همبرگری',
			'filter'     => 'فیلتر',
			'sparkle'    => 'جرقه',
		);
	}

	/**
	 * Render.
	 */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$layout = isset( $s['layout'] ) ? $s['layout'] : 'dropdown';
		$limit  = isset( $s['limit'] ) ? (int) $s['limit'] : 10;

		printf(
			'<div class="devco-widget devco-widget--catmenu devco-catmenu devco-catmenu--%1$s" data-catmenu="%1$s">',
			esc_attr( $layout )
		);

		printf(
			'<button type="button" class="devco-catmenu__toggle" aria-expanded="false"><span class="devco-catmenu__icon">%1$s</span><span class="devco-catmenu__label">%2$s</span>%3$s</button>',
			devco_icon( isset( $s['icon'] ) ? $s['icon'] : 'categories', array( 'size' => 18 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( isset( $s['label'] ) ? $s['label'] : 'دسته‌بندی محصولات' ),
			devco_icon( 'chevron-down', array( 'size' => 15 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		echo '<div class="devco-catmenu__panel">';

		$source = isset( $s['source'] ) ? $s['source'] : 'auto';

		if ( 'wp_menu' === $source ) {
			wp_nav_menu(
				array(
					'theme_location' => 'categories',
					'container'      => false,
					'menu_class'     => 'devco-catmenu__list',
					'fallback_cb'    => 'devco_menu_fallback',
				)
			);
		} elseif ( 'products' === $source && devco_is_woo() ) {
			$products = wc_get_products(
				array(
					'limit'   => $limit,
					'status'  => 'publish',
					'orderby' => 'popularity',
				)
			);

			echo '<ul class="devco-catmenu__list">';
			foreach ( $products as $product ) {
				printf(
					'<li class="devco-catmenu__item"><a class="devco-catmenu__link" href="%1$s">%2$s<span class="devco-catmenu__name">%3$s</span></a></li>',
					esc_url( $product->get_permalink() ),
					get_the_post_thumbnail( $product->get_id(), 'devco-thumb', array( 'class' => 'devco-catmenu__thumb' ) ),
					esc_html( $product->get_name() )
				);
			}
			echo '</ul>';
		} elseif ( devco_is_woo() ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'number'     => $limit,
					'parent'     => 0,
					'orderby'    => 'count',
					'order'      => 'DESC',
				)
			);

			if ( ! is_wp_error( $terms ) && $terms ) {
				echo '<ul class="devco-catmenu__list devco-catmenu__list--auto">';
				foreach ( $terms as $term ) {
					$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
					$children = 'yes' === ( isset( $s['subcats'] ) ? $s['subcats'] : 'yes' ) ? get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'parent'     => $term->term_id,
							'hide_empty' => false,
							'number'     => 5,
						)
					) : array();

					echo '<li class="devco-catmenu__item' . ( ! is_wp_error( $children ) && $children ? ' has-children' : '' ) . '">';
					echo '<a class="devco-catmenu__link" href="' . esc_url( get_term_link( $term ) ) . '">';

					if ( 'yes' === ( isset( $s['show_thumb'] ) ? $s['show_thumb'] : 'yes' ) ) {
						echo $thumb_id
							? wp_get_attachment_image( $thumb_id, 'devco-thumb', false, array( 'class' => 'devco-catmenu__thumb', 'loading' => 'lazy' ) )
							: '<span class="devco-catmenu__thumb devco-catmenu__thumb--ph">' . devco_icon( 'bag', array( 'size' => 16 ) ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					echo '<span class="devco-catmenu__name">' . esc_html( $term->name ) . '</span>';

					if ( 'yes' === ( isset( $s['show_count'] ) ? $s['show_count'] : 'yes' ) ) {
						echo '<span class="devco-catmenu__count">' . esc_html( devco_num( $term->count ) ) . '</span>';
					}

					echo '</a>';

					if ( ! is_wp_error( $children ) && $children ) {
						echo '<ul class="devco-catmenu__sub">';
						foreach ( $children as $child ) {
							printf(
								'<li><a href="%1$s">%2$s</a></li>',
								esc_url( get_term_link( $child ) ),
								esc_html( $child->name )
							);
						}
						echo '</ul>';
					}

					echo '</li>';
				}
				echo '</ul>';
			}
		} else {
			echo '<p class="devco-catmenu__empty">برای نمایش دسته‌ها، ووکامرس را فعال کنید.</p>';
		}

		echo '</div></div>';
	}
}
