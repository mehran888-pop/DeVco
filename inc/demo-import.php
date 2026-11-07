<?php
/**
 * Optional demo content importer used by the setup wizard.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Demo_Import
 */
class DeVco_Demo_Import {

	/**
	 * Available tasks.
	 *
	 * @return array
	 */
	public static function tasks() {
		return array(
			'pages'     => 'ساخت صفحات اصلی',
			'menus'     => 'ساخت منوها',
			'home'      => 'طراحی صفحه اصلی با المنتور',
			'templates' => 'ساخت قالب هدر و فوتر',
			'products'  => 'افزودن محصولات نمونه',
			'forms'     => 'ساخت فرم سفارش دوخت',
			'settings'  => 'اعمال تنظیمات پیشنهادی',
		);
	}

	/**
	 * Run a task.
	 *
	 * @param string $task Task key.
	 * @return array|WP_Error
	 */
	public static function run( $task ) {
		switch ( $task ) {
			case 'pages':
				return self::import_pages();
			case 'menus':
				return self::import_menus();
			case 'home':
				return self::import_home();
			case 'templates':
				return self::import_templates();
			case 'products':
				return self::import_products();
			case 'forms':
				return self::import_forms();
			case 'settings':
				return self::import_settings();
		}

		return new WP_Error( 'devco_task', 'تسک نامعتبر است.' );
	}

	/**
	 * Find or create a page.
	 *
	 * @param array $args Args: title, slug, template, content.
	 * @return int
	 */
	protected static function page( $args ) {
		$existing = get_page_by_path( $args['slug'] );

		if ( $existing ) {
			if ( ! empty( $args['template'] ) ) {
				update_post_meta( $existing->ID, '_wp_page_template', $args['template'] );
			}

			return (int) $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $args['title'],
				'post_name'    => $args['slug'],
				'post_content' => isset( $args['content'] ) ? $args['content'] : '',
			)
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		if ( ! empty( $args['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $args['template'] );
		}

		return (int) $page_id;
	}

	/**
	 * Import the core pages.
	 *
	 * @return array
	 */
	protected static function import_pages() {
		$pages = array(
			'home'      => array( 'title' => 'صفحه اصلی', 'slug' => 'home' ),
			'about'     => array( 'title' => 'درباره ما', 'slug' => 'about' ),
			'contact'   => array( 'title' => 'تماس با ما', 'slug' => 'contact' ),
			'terms'     => array( 'title' => 'قوانین و مقررات', 'slug' => 'terms' ),
			'faq'       => array( 'title' => 'سوالات متداول', 'slug' => 'faq' ),
			'dashboard' => array( 'title' => 'حساب کاربری', 'slug' => 'my-dashboard', 'template' => 'templates/dashboard.php' ),
			'tailoring' => array( 'title' => 'سفارش دوخت', 'slug' => 'tailoring', 'template' => 'templates/tailoring.php' ),
			'track'     => array( 'title' => 'پیگیری سفارش', 'slug' => 'track-order', 'template' => 'templates/track.php' ),
			'blog'      => array( 'title' => 'مجله دیوکو', 'slug' => 'blog' ),
		);

		$ids = array();

		foreach ( $pages as $key => $args ) {
			$ids[ $key ] = self::page( $args );
		}

		// Front page + blog page.
		if ( ! empty( $ids['home'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $ids['home'] );
		}

		if ( ! empty( $ids['blog'] ) ) {
			update_option( 'page_for_posts', $ids['blog'] );
		}

		// WooCommerce pages.
		if ( devco_is_woo() ) {
			$shop = get_page_by_path( 'shop' );

			if ( ! $shop ) {
				$shop_id = self::page( array( 'title' => 'فروشگاه', 'slug' => 'shop' ) );
				update_option( 'woocommerce_shop_page_id', $shop_id );
			}

			foreach ( array( 'cart' => 'سبد خرید', 'checkout' => 'تسویه حساب', 'myaccount' => 'حساب کاربری من' ) as $slug => $title ) {
				if ( ! get_page_by_path( $slug ) ) {
					$id = self::page( array( 'title' => $title, 'slug' => $slug ) );
					update_option( 'woocommerce_' . $slug . '_page_id', $id );
				}
			}
		}

		// Persist references used across the theme.
		$options                      = devco_options();
		$options['dashboard.page_id']  = $ids['dashboard'];
		$options['tailoring.page_id']  = $ids['tailoring'];
		$options['auth.terms_page']    = $ids['terms'];
		update_option( DEVCO_OPTION, $options );

		return array(
			'message' => 'صفحات ساخته شد: صفحه اصلی، فروشگاه، درباره ما، تماس، قوانین، داشبورد، سفارش دوخت و پیگیری سفارش.',
		);
	}

	/**
	 * Import menus.
	 *
	 * @return array
	 */
	protected static function import_menus() {
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		$locations = is_array( $locations ) ? $locations : array();

		$primary = wp_get_nav_menu_object( 'منوی اصلی دیوکو' );

		if ( ! $primary ) {
			$primary_id = wp_create_nav_menu( 'منوی اصلی دیوکو' );

			$items = array(
				array( 'title' => 'صفحه اصلی', 'page' => 'home' ),
				array( 'title' => 'فروشگاه', 'page' => 'shop' ),
				array( 'title' => 'سفارش دوخت', 'page' => 'tailoring' ),
				array( 'title' => 'درباره ما', 'page' => 'about' ),
				array( 'title' => 'تماس با ما', 'page' => 'contact' ),
				array( 'title' => 'قوانین', 'page' => 'terms' ),
			);

			foreach ( $items as $item ) {
				$page = get_page_by_path( $item['page'] );

				wp_update_nav_menu_item(
					$primary_id,
					0,
					array(
						'menu-item-title'     => $item['title'],
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $page ? $page->ID : 0,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
		} else {
			$primary_id = $primary->term_id;
		}

		$footer = wp_get_nav_menu_object( 'منوی فوتر دیوکو' );

		if ( ! $footer ) {
			$footer_id = wp_create_nav_menu( 'منوی فوتر دیوکو' );

			foreach ( array( 'about', 'terms', 'contact' ) as $slug ) {
				$page = get_page_by_path( $slug );
				wp_update_nav_menu_item(
					$footer_id,
					0,
					array(
						'menu-item-title'     => $page ? $page->post_title : $slug,
						'menu-item-object'    => 'page',
						'menu-item-object-id' => $page ? $page->ID : 0,
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					)
				);
			}
		} else {
			$footer_id = $footer->term_id;
		}

		$locations['primary'] = $primary_id;
		$locations['mobile']  = $primary_id;
		$locations['footer']  = $footer_id;

		set_theme_mod( 'nav_menu_locations', $locations );

		return array( 'message' => 'منوهای اصلی، موبایل و فوتر ساخته و به جایگاه‌ها متصل شدند.' );
	}

	/**
	 * Import Elementor home page content.
	 *
	 * @return array
	 */
	protected static function import_home() {
		if ( ! devco_is_elementor() ) {
			return new WP_Error( 'devco_no_elementor', 'برای ساخت صفحه اصلی، المنتور را فعال کنید.' );
		}

		$page = get_page_by_path( 'home' );

		if ( ! $page ) {
			$page_id = self::page( array( 'title' => 'صفحه اصلی', 'slug' => 'home' ) );
		} else {
			$page_id = (int) $page->ID;
		}

		$shop_url = devco_is_woo() ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
		$tailor   = get_permalink( (int) devco_opt( 'tailoring.page_id', 0 ) );

		$data = array(
			self::section(
				array( 'content_width' => 'boxed', 'padding' => array( 'unit' => 'px', 'top' => '40', 'right' => '0', 'bottom' => '40', 'left' => '0' ) ),
				array(
					self::column(
						50,
						array(
							self::widget( 'heading', array( 'title' => 'استایلی که فقط مال خودت است', 'header_size' => 'h1', 'title_color' => '#111827', 'typography_typography' => 'custom', 'typography_font_size' => array( 'unit' => 'px', 'size' => 46 ), 'typography_font_weight' => '800', 'typography_line_height' => array( 'unit' => 'em', 'size' => 1.35 ) ) ),
							self::widget( 'text-editor', array( 'editor' => '<p>کالکشن پاییز دیوکو با پارچه‌های ممتاز، دوخت اختصاصی و اتاق پرو آنلاین. لباس را روی خودت ببین، اندازه دقیق بده و سفارش بده.</p>' ) ),
							self::widget( 'button', array( 'text' => 'خرید از فروشگاه', 'link' => array( 'url' => $shop_url ), 'button_text_color' => '#ffffff', 'background_color' => '#111827', 'border_radius' => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14' ) ) ),
							self::widget( 'button', array( 'text' => 'سفارش دوخت اختصاصی', 'link' => array( 'url' => $tailor ), 'button_text_color' => '#c8a96a', 'background_color' => 'rgba(200,169,106,0.08)', 'border_border' => 'solid', 'border_width' => array( 'unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1' ), 'border_color' => '#c8a96a', 'border_radius' => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14' ) ) ),
						)
					),
					self::column(
						50,
						array(
							self::widget( 'devco-product-categories', array( 'title' => '', 'limit' => 4, 'columns' => '2', 'tile_height' => array( 'unit' => 'px', 'size' => 240 ) ) ),
						)
					),
				)
			),
			self::section(
				array( 'background_color' => '#111827', 'padding' => array( 'unit' => 'px', 'top' => '8', 'right' => '0', 'bottom' => '8', 'left' => '0' ) ),
				array(
					self::column(
						100,
						array(
							self::widget( 'devco-marquee', array( 'color' => '#f7f5f1', 'icon_color' => '#c8a96a', 'speed' => array( 'unit' => 's', 'size' => 30 ) ) ),
						)
					),
				)
			),
			self::section(
				array( 'padding' => array( 'unit' => 'px', 'top' => '60', 'right' => '0', 'bottom' => '60', 'left' => '0' ) ),
				array(
					self::column(
						100,
						array(
							self::widget( 'devco-features', array() ),
						)
					),
				)
			),
			self::section(
				array( 'padding' => array( 'unit' => 'px', 'top' => '30', 'right' => '0', 'bottom' => '60', 'left' => '0' ) ),
				array(
					self::column(
						100,
						array(
							self::widget( 'devco-section-title', array( 'eyebrow' => 'کالکشن جدید', 'title' => 'پرفروش‌ترین‌ها', 'subtitle' => 'محبوب‌ترین انتخاب‌های مشتریان دیوکو', 'link' => array( 'url' => $shop_url ) ) ),
							self::widget( 'devco-product-grid', array( 'source' => 'best', 'limit' => 8, 'columns' => '4', 'header_title' => '' ) ),
						)
					),
				)
			),
			self::section(
				array( 'background_color' => '#f3efe9', 'padding' => array( 'unit' => 'px', 'top' => '50', 'right' => '0', 'bottom' => '50', 'left' => '0' ) ),
				array(
					self::column(
						100,
						array(
							self::widget( 'devco-section-title', array( 'style' => 'center', 'eyebrow' => 'تجربه دیوکو', 'title' => 'اتاق پرو مجازی؛ قبل از خرید ببین', 'subtitle' => 'دوربین را روشن کن یا عکس خودت را آپلود کن و لباس را روی اندامت امتحان کن.' ) ),
							self::widget( 'devco-fitting-room', array( 'mode' => 'embed', 'title' => 'اتاق پرو مجازی' ) ),
						)
					),
				)
			),
			self::section(
				array( 'padding' => array( 'unit' => 'px', 'top' => '60', 'right' => '0', 'bottom' => '60', 'left' => '0' ) ),
				array(
					self::column(
						100,
						array(
							self::widget( 'devco-section-title', array( 'eyebrow' => 'دسته‌بندی', 'title' => 'خرید بر اساس سلیقه' ) ),
							self::widget( 'devco-product-categories', array( 'title' => '', 'limit' => 6, 'columns' => '3' ) ),
						)
					),
				)
			),
		);

		update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
		update_post_meta( $page_id, '_wp_page_template', 'elementor_header_footer' );
		delete_post_meta( $page_id, '_elementor_css' );

		return array( 'message' => 'صفحه اصلی با المنتور و ۷ بخش خلاق ساخته شد.' );
	}

	/**
	 * Build an Elementor section.
	 *
	 * @param array $settings Settings.
	 * @param array $columns  Columns.
	 * @return array
	 */
	protected static function section( $settings, $columns ) {
		return array(
			'id'       => substr( md5( wp_rand() ), 0, 7 ),
			'elType'   => 'section',
			'settings' => $settings,
			'elements' => $columns,
		);
	}

	/**
	 * Build an Elementor column.
	 *
	 * @param int   $size     Size.
	 * @param array $widgets  Widgets.
	 * @return array
	 */
	protected static function column( $size, $widgets ) {
		return array(
			'id'       => substr( md5( wp_rand() ), 0, 7 ),
			'elType'   => 'column',
			'settings' => array( '_column_size' => $size, '_inline_size' => $size ),
			'elements' => $widgets,
		);
	}

	/**
	 * Build an Elementor widget.
	 *
	 * @param string $type     Widget type.
	 * @param array  $settings Settings.
	 * @return array
	 */
	protected static function widget( $type, $settings ) {
		return array(
			'id'         => substr( md5( wp_rand() ), 0, 7 ),
			'elType'     => 'widget',
			'widgetType' => $type,
			'settings'   => $settings,
			'elements'   => array(),
		);
	}

	/**
	 * Create Elementor header/footer templates.
	 *
	 * @return array
	 */
	protected static function import_templates() {
		if ( ! devco_is_elementor() ) {
			return new WP_Error( 'devco_no_elementor', 'المنتور فعال نیست.' );
		}

		$header = self::template_post(
			'هدر دیوکو',
			array(
				self::section(
					array(
						'background_color' => '#ffffff',
						'padding'          => array( 'unit' => 'px', 'top' => '10', 'right' => '0', 'bottom' => '10', 'left' => '0' ),
					),
					array(
						self::column( 20, array( self::widget( 'devco-logo', array() ) ) ),
						self::column( 55, array( self::widget( 'devco-menu', array( 'layout' => 'classic', 'mode' => 'mega' ) ) ) ),
						self::column( 25, array( self::widget( 'devco-search', array( 'style' => 'icon' ) ), self::widget( 'devco-login-button', array( 'preset' => 'split' ) ), self::widget( 'devco-cart', array() ) ) ),
					)
				),
				self::section(
					array(
						'background_color' => '#f7f5f1',
						'padding'          => array( 'unit' => 'px', 'top' => '6', 'right' => '0', 'bottom' => '6', 'left' => '0' ),
					),
					array(
						self::column( 30, array( self::widget( 'devco-category-menu', array( 'layout' => 'dropdown' ) ) ) ),
						self::column( 70, array( self::widget( 'devco-marquee', array( 'speed' => array( 'unit' => 's', 'size' => 34 ) ) ) ) ),
					)
				),
			),
			'header'
		);

		$footer = self::template_post(
			'فوتر دیوکو',
			array(
				self::section(
					array(
						'background_color' => '#111827',
						'padding'          => array( 'unit' => 'px', 'top' => '50', 'right' => '0', 'bottom' => '20', 'left' => '0' ),
					),
					array(
						self::column(
							25,
							array(
								self::widget( 'devco-logo', array( 'source' => 'text' ) ),
								self::widget( 'text-editor', array( 'editor' => '<p style="color:#9ca3af">پوشاک دوخت سفارشی با پارچه‌های ممتاز. اتاق پرو آنلاین، اندازه دقیق و ارسال سریع.</p>' ) ),
								self::widget( 'devco-login-button', array( 'preset' => 'outline' ) ),
							)
						),
						self::column(
							25,
							array(
								self::widget( 'devco-section-title', array( 'title' => 'دسترسی سریع', 'eyebrow' => '', 'title_color' => '#ffffff' ) ),
								self::widget( 'devco-menu', array( 'mode' => 'dropdown' ) ),
							)
						),
						self::column(
							25,
							array(
								self::widget( 'devco-section-title', array( 'title' => 'خدمات مشتریان', 'eyebrow' => '', 'title_color' => '#ffffff' ) ),
								self::widget( 'devco-features', array( 'layout' => 'minimal', 'columns' => '1' ) ),
							)
						),
						self::column(
							25,
							array(
								self::widget( 'devco-section-title', array( 'title' => 'ارتباط با ما', 'eyebrow' => '', 'title_color' => '#ffffff' ) ),
								self::widget( 'devco-features', array( 'layout' => 'minimal', 'columns' => '1' ) ),
								self::widget( 'devco-floating-actions', array( 'position' => 'custom', 'layout' => 'horizontal', 'style' => 'circle' ) ),
							)
						),
					)
				),
				self::section(
					array(
						'background_color' => '#0b1220',
						'padding'          => array( 'unit' => 'px', 'top' => '14', 'right' => '0', 'bottom' => '14', 'left' => '0' ),
					),
					array(
						self::column(
							100,
							array(
								self::widget( 'text-editor', array( 'editor' => '<p style="text-align:center;color:#6b7280;font-size:13px">© تمامی حقوق برای فروشگاه دیوکو محفوظ است.</p>' ) ),
							)
						),
					)
				),
			),
			'footer'
		);

		$options                        = devco_options();
		$options['header_source']       = 'elementor';
		$options['header.elementor_id'] = $header;
		$options['footer_source']       = 'elementor';
		$options['footer.elementor_id'] = $footer;
		update_option( DEVCO_OPTION, $options );

		return array( 'message' => 'قالب هدر و فوتر المنتوری ساخته و در تنظیمات قالب فعال شد.' );
	}

	/**
	 * Create an Elementor library template.
	 *
	 * @param string $title Title.
	 * @param array  $data  Elementor data.
	 * @param string $type  header|footer.
	 * @return int
	 */
	protected static function template_post( $title, $data, $type ) {
		$existing = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'title'          => $title,
			)
		);

		$post_id = $existing ? (int) $existing[0]->ID : wp_insert_post(
			array(
				'post_type'   => 'elementor_library',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', $type );
		update_post_meta( $post_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
		delete_post_meta( $post_id, '_elementor_css' );

		return (int) $post_id;
	}

	/**
	 * Create demo products.
	 *
	 * @return array
	 */
	protected static function import_products() {
		if ( ! devco_is_woo() ) {
			return new WP_Error( 'devco_no_woo', 'ووکامرس فعال نیست.' );
		}

		$existing = wp_count_posts( 'product' );

		if ( ! empty( $existing->publish ) && (int) $existing->publish > 0 ) {
			return array( 'message' => 'محصولات از قبل وجود دارند؛ نمونه‌ها اضافه نشد.' );
		}

		$categories = array( 'مانتو و پالتو', 'شومیز و بلوز', 'کت و شلوار', 'لباس مجلسی' );
		$cat_ids    = array();

		foreach ( $categories as $category ) {
			$term = term_exists( $category, 'product_cat' );

			if ( ! $term ) {
				$term = wp_insert_term( $category, 'product_cat' );
			}

			if ( ! is_wp_error( $term ) ) {
				$cat_ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
		}

		$products = array(
			array( 'مانتو کرپ مدل آوا', 2450000, 1980000 ),
			array( 'شومیز ابریشمی مدل نیلا', 1290000, 0 ),
			array( 'کت و شلوار مدل راوی', 4890000, 4290000 ),
			array( 'پیراهن مجلسی مدل درسا', 3690000, 0 ),
			array( 'پالتو کتان مدل باران', 5290000, 4690000 ),
			array( 'بلوز ساتن مدل روشا', 980000, 0 ),
			array( 'دامن پلیسه مدل سایه', 1450000, 0 ),
			array( 'شلوار پارچه‌ای مدل ماهان', 1690000, 1390000 ),
		);

		$created = 0;

		foreach ( $products as $index => $data ) {
			$product = new WC_Product_Simple();
			$product->set_name( $data[0] );
			$product->set_status( 'publish' );
			$product->set_regular_price( $data[1] );

			if ( $data[2] ) {
				$product->set_sale_price( $data[2] );
			}

			$product->set_stock_status( 'instock' );
			$product->set_short_description( 'دوخت اختصاصی دیوکو با پارچه ممتاز و امکان سفارشی‌سازی اندازه.' );
			$product->set_description( '<p>این محصول با دقت در کارگاه دیوکو تولید می‌شود. امکان ثبت اندازه اختصاصی و انتخاب پارچه دلخواه وجود دارد.</p>' );

			$id = $product->save();

			if ( $id && $cat_ids ) {
				wp_set_object_terms( $id, array( $cat_ids[ $index % count( $cat_ids ) ] ), 'product_cat' );
			}

			// Attach a generated placeholder image.
			$image_id = self::placeholder_image( 'محصول نمونه ' . ( $index + 1 ), $index );

			if ( $image_id && $id ) {
				set_post_thumbnail( $id, $image_id );
			}

			$created++;
		}

		return array( 'message' => 'تعداد ' . devco_num( $created ) . ' محصول نمونه با دسته‌بندی ساخته شد.' );
	}

	/**
	 * Import the default tailoring form.
	 *
	 * @return array
	 */
	protected static function import_forms() {
		$form_id = DeVco_Tailoring::create_default_form();

		return array( 'message' => $form_id ? 'فرم سفارش دوخت با مانکن سه‌بعدی ساخته شد.' : 'ساخت فرم ناموفق بود.' );
	}

	/**
	 * Apply recommended settings.
	 *
	 * @return array
	 */
	protected static function import_settings() {
		$options = devco_options();

		$defaults = array(
			'layout.site_width'          => 1280,
			'layout.radius'              => 16,
			'colors.accent'              => '#c8a96a',
			'colors.accent_dark'         => '#a8894c',
			'colors.primary'             => '#111827',
			'colors.dark_mode'           => 1,
			'header.sticky'              => 1,
			'header.menu_mode'           => 'mega',
			'card.style'                 => 'overlay',
			'card.hover'                 => 'zoom',
			'card.fly_animation'         => 'fly_burst',
			'mobile.bottom_bar'          => 1,
			'mobile.style'               => 'glass',
			'floating.cart'              => 1,
			'floating.cart_style'        => 'dock',
			'floating.actions'           => 1,
			'floating.actions_layout'    => 'expand',
			'fitting.enabled'            => 1,
			'fitting.show_on'            => 'shop',
			'auth.otp'                   => 1,
			'auth.register'              => 1,
			'auth.layout'                => 'modal',
			'auth.style'                 => 'elegant',
			'dashboard.enabled'          => 1,
			'dashboard.style'            => 'light',
			'tailoring.enabled'          => 1,
			'tailoring.body_3d'          => 1,
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $value;
			}
		}

		$options['wizard.done'] = 1;
		update_option( DEVCO_OPTION, $options );
		set_theme_mod( 'devco_wizard_done', 1 );

		return array( 'message' => 'تنظیمات پیشنهادی اعمال شد. قالب آماده استفاده است.' );
	}

	/**
	 * Generate a simple placeholder product image and store it in the media library.
	 *
	 * @param string $title Title.
	 * @param int    $seed  Seed.
	 * @return int
	 */
	protected static function placeholder_image( $title, $seed = 0 ) {
		$palettes = array(
			array( '#efe7dc', '#c8a96a' ),
			array( '#e7e3f5', '#8b7cf6' ),
			array( '#e2efe9', '#34d399' ),
			array( '#fde8e8', '#f87171' ),
			array( '#e0ecf8', '#60a5fa' ),
			array( '#f5ead8', '#d4a373' ),
		);

		$palette = $palettes[ $seed % count( $palettes ) ];
		$svg     = sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" width="600" height="750" viewBox="0 0 600 750"><rect width="600" height="750" fill="%1$s"/><circle cx="300" cy="280" r="120" fill="none" stroke="%2$s" stroke-width="6" opacity="0.55"/><path d="M180 700c0-140 54-240 120-240s120 100 120 240" fill="none" stroke="%2$s" stroke-width="8" opacity="0.7"/><text x="300" y="620" text-anchor="middle" font-family="sans-serif" font-size="30" fill="%2$s">DeVco</text></svg>',
			$palette[0],
			$palette[1]
		);

		$upload = wp_upload_bits( 'devco-demo-' . $seed . '-' . time() . '.svg', null, $svg );

		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$attachment = array(
			'post_mime_type' => 'image/svg+xml',
			'post_title'     => sanitize_text_field( $title ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );

		if ( is_wp_error( $attach_id ) ) {
			return 0;
		}

		update_post_meta( $attach_id, '_wp_attachment_metadata', array() );

		return (int) $attach_id;
	}
}
