<?php
/**
 * Elementor integration: category, widget loader and editor assets.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Elementor
 */
class DeVco_Elementor {

	/**
	 * Instance.
	 *
	 * @var DeVco_Elementor|null
	 */
	protected static $instance = null;

	/**
	 * Widget map: class file => class name.
	 *
	 * @return array
	 */
	public static function widgets() {
		return array(
			'logo'           => 'DeVco_Widget_Logo',
			'menu'           => 'DeVco_Widget_Menu',
			'category-menu'  => 'DeVco_Widget_Category_Menu',
			'search'         => 'DeVco_Widget_Search',
			'account'        => 'DeVco_Widget_Account',
			'cart'           => 'DeVco_Widget_Cart',
			'product-grid'   => 'DeVco_Widget_Product_Grid',
			'product-carousel' => 'DeVco_Widget_Product_Carousel',
			'product-categories' => 'DeVco_Widget_Product_Categories',
			'fitting-room'   => 'DeVco_Widget_Fitting_Room',
			'floating-cart'  => 'DeVco_Widget_Floating_Cart',
			'floating-actions' => 'DeVco_Widget_Floating_Actions',
			'auth-form'      => 'DeVco_Widget_Auth_Form',
			'section-title'  => 'DeVco_Widget_Section_Title',
			'features'       => 'DeVco_Widget_Features',
			'marquee'        => 'DeVco_Widget_Marquee',
			'product-showcase' => 'DeVco_Widget_Product_Showcase',
			'body-measure'   => 'DeVco_Widget_Body_Measure',
			'login-button'   => 'DeVco_Widget_Login_Button',
		);
	}

	/**
	 * Init hooks.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets_legacy' ) );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_frontend_assets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'editor_assets' ) );
		add_filter( 'elementor/editor/localize_settings', array( $this, 'editor_settings' ) );

		// Header/footer support with free Elementor.
		add_action( 'elementor/theme/register_locations', array( $this, 'register_locations' ) );
	}

	/**
	 * Register the DeVco widget category.
	 *
	 * @param \Elementor\Elements_Manager $manager Manager.
	 */
	public function register_category( $manager ) {
		$manager->add_category(
			'devco',
			array(
				'title' => 'دیوکو | المان‌های فروشگاهی',
				'icon'  => 'eicon-woocommerce',
			)
		);

		$manager->add_category(
			'devco-header',
			array(
				'title' => 'دیوکو | هدر و فوتر',
				'icon'  => 'eicon-header',
			)
		);
	}

	/**
	 * Register widgets (Elementor 3.5+).
	 *
	 * @param \Elementor\Widgets_Manager $manager Manager.
	 */
	public function register_widgets( $manager ) {
		self::load_widget_files();

		foreach ( self::widgets() as $class ) {
			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}

	/**
	 * Legacy registration hook.
	 */
	public function register_widgets_legacy() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$manager = \Elementor\Plugin::instance()->widgets_manager;

		if ( method_exists( $manager, 'register' ) ) {
			return; // New hook already handled it.
		}

		self::load_widget_files();

		foreach ( self::widgets() as $class ) {
			if ( class_exists( $class ) ) {
				$manager->register_widget_type( new $class() );
			}
		}
	}

	/**
	 * Include all widget classes.
	 */
	public static function load_widget_files() {
		require_once DEVCO_DIR . '/inc/elementor/class-devco-widget-base.php';

		foreach ( array_keys( self::widgets() ) as $file ) {
			$path = DEVCO_DIR . '/inc/elementor/widgets/class-devco-widget-' . $file . '.php';
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	}

	/**
	 * Register handles used by widgets so Elementor can load them on demand.
	 */
	public function register_frontend_assets() {
		wp_register_style( 'devco-widgets', devco_asset( 'css/widgets.css' ), array(), devco_asset_ver( 'css/widgets.css' ) );
		wp_register_style( 'devco-fitting', devco_asset( 'css/fitting.css' ), array(), devco_asset_ver( 'css/fitting.css' ) );
		wp_register_style( 'devco-floating', devco_asset( 'css/floating.css' ), array(), devco_asset_ver( 'css/floating.css' ) );
		wp_register_style( 'devco-auth', devco_asset( 'css/auth.css' ), array(), devco_asset_ver( 'css/auth.css' ) );
		wp_register_script( 'devco-fitting', devco_asset( 'js/fitting.js' ), array(), devco_asset_ver( 'js/fitting.js' ), true );
		wp_register_script( 'devco-floating', devco_asset( 'js/floating.js' ), array(), devco_asset_ver( 'js/floating.js' ), true );
		wp_register_script( 'devco-auth', devco_asset( 'js/auth.js' ), array(), devco_asset_ver( 'js/auth.js' ), true );
	}

	/**
	 * Elementor editor tweaks.
	 */
	public function editor_assets() {
		wp_enqueue_style( 'devco-editor', devco_asset( 'css/editor.css' ), array(), devco_asset_ver( 'css/editor.css' ) );
	}

	/**
	 * Tell Elementor the theme supports its locations.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function editor_settings( $settings ) {
		$settings['devco_notice'] = 'برای ساخت هدر/فوتر با المنتور رایگان، یک قالب بسازید و از تنظیمات قالب دیوکو آن را انتخاب کنید.';
		return $settings;
	}

	/**
	 * Register theme locations for Elementor Pro compatibility (no-op if Pro absent).
	 *
	 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $manager Manager.
	 */
	public function register_locations( $manager ) {
		if ( is_object( $manager ) && method_exists( $manager, 'register_core_location' ) ) {
			$manager->register_core_location( 'header' );
			$manager->register_core_location( 'footer' );
		}
	}
}

DeVco_Elementor::instance();
