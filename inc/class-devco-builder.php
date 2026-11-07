<?php
/**
 * Header / footer builder.
 *
 * Renders the header and footer from either the theme's own slot builder or
 * an Elementor template (works with the free version of Elementor).
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_Builder
 */
class DeVco_Builder {

	/**
	 * Instance.
	 *
	 * @var DeVco_Builder|null
	 */
	protected static $instance = null;

	/**
	 * Init.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Render an Elementor template by ID.
	 *
	 * @param int $template_id Template post ID.
	 * @return bool
	 */
	public static function render_elementor_template( $template_id ) {
		$template_id = (int) $template_id;

		if ( ! $template_id || ! devco_is_elementor() ) {
			return false;
		}

		if ( 'publish' !== get_post_status( $template_id ) && ! current_user_can( 'edit_post', $template_id ) ) {
			return false;
		}

		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );

		if ( ! $content ) {
			return false;
		}

		echo '<div class="devco-elementor-part devco-elementor-part--' . esc_attr( $template_id ) . '">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return true;
	}

	/**
	 * Render the site header.
	 */
	public static function header() {
		$source = devco_opt( 'header_source', 'builder' );

		if ( 'elementor' === $source ) {
			$template_id = (int) devco_opt( 'header.elementor_id', 0 );

			if ( self::render_elementor_template( $template_id ) ) {
				devco_mobile_header();
				return;
			}
		}

		$slots = devco_opt( 'header.slots', array() );
		$slots = is_array( $slots ) ? $slots : array();

		$classes = array( 'devco-header' );

		if ( devco_opt_bool( 'header.sticky', true ) ) {
			$classes[] = 'is-sticky';
		}

		if ( devco_opt_bool( 'header.transparent', false ) && ( is_front_page() || is_page_template( 'templates/landing.php' ) ) ) {
			$classes[] = 'is-transparent';
		}
		?>
		<header id="devco-header" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-header>
			<?php foreach ( array( 'top', 'main', 'bottom' ) as $row ) : ?>
				<?php
				$row_slots = isset( $slots[ $row ] ) && is_array( $slots[ $row ] ) ? $slots[ $row ] : array();
				$has_items = false;

				foreach ( array( 'right', 'center', 'left' ) as $col ) {
					if ( ! empty( $row_slots[ $col ] ) ) {
						$has_items = true;
						break;
					}
				}

				if ( ! $has_items && 'top' === $row && devco_opt_bool( 'header.enable_topbar', true ) && devco_opt( 'header.topbar_text', '' ) ) {
					$row_slots = array( 'right' => array( 'text' ), 'center' => array(), 'left' => array( 'socials' ) );
					$has_items = true;
				}

				if ( ! $has_items ) {
					continue;
				}
				?>
				<div class="devco-header__row devco-header__row--<?php echo esc_attr( $row ); ?>">
					<div class="<?php echo esc_attr( devco_container_class() ); ?>">
						<div class="devco-header__row-inner">
							<?php foreach ( array( 'right', 'center', 'left' ) as $col ) : ?>
								<?php
								$items = isset( $row_slots[ $col ] ) ? (array) $row_slots[ $col ] : array();

								$is_main_mobile_actions = ( 'main' === $row && 'left' === $col );

								printf(
									'<div class="devco-header__slot devco-header__slot--%1$s %2$s">',
									esc_attr( $col ),
									$is_main_mobile_actions ? 'devco-header__slot--actions' : ''
								);

								foreach ( $items as $item ) {
									devco_render_component( $item, array( 'row' => $row ) );
								}

								echo '</div>';
								?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			<?php if ( ! devco_opt( 'header.slots', array() ) ) : ?>
				<div class="devco-header__row devco-header__row--main">
					<div class="<?php echo esc_attr( devco_container_class() ); ?>">
						<div class="devco-header__row-inner">
							<div class="devco-header__slot devco-header__slot--right"><?php devco_site_logo( array( 'class' => 'devco-header__logo' ) ); ?></div>
							<div class="devco-header__slot devco-header__slot--center"><?php devco_component_menu(); ?></div>
							<div class="devco-header__slot devco-header__slot--left"><?php devco_component_search(); devco_component_account(); devco_component_cart(); ?></div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</header>

		<?php devco_mobile_header(); ?>
		<?php
	}

	/**
	 * Render the site footer.
	 */
	public static function footer() {
		$source = devco_opt( 'footer_source', 'builder' );

		if ( 'elementor' === $source ) {
			$template_id = (int) devco_opt( 'footer.elementor_id', 0 );

			if ( self::render_elementor_template( $template_id ) ) {
				return;
			}
		}

		$columns   = max( 1, min( 4, (int) devco_opt( 'footer.columns', 4 ) ) );
		$style     = devco_opt( 'footer.style', 'dark' );
		$active    = 0;

		for ( $i = 1; $i <= $columns; $i++ ) {
			if ( is_active_sidebar( 'footer-' . $i ) ) {
				$active++;
			}
		}
		?>
		<footer class="devco-footer devco-footer--<?php echo esc_attr( $style ); ?>" data-footer>
			<?php if ( devco_opt_bool( 'footer.newsletter', true ) ) : ?>
				<div class="devco-footer__newsletter">
					<div class="<?php echo esc_attr( devco_container_class() ); ?>">
						<div class="devco-newsletter">
							<div class="devco-newsletter__text">
								<h3><?php echo esc_html( devco_opt( 'footer.newsletter_title', 'از تخفیف‌های ویژه باخبر شوید' ) ); ?></h3>
								<p>عضو خانواده دیوکو شوید و ۱۰٪ تخفیف اولین خرید را دریافت کنید.</p>
							</div>
							<form class="devco-newsletter__form js-devco-newsletter" action="#" method="post">
								<label class="screen-reader-text" for="devco-newsletter-email">ایمیل</label>
								<input type="email" id="devco-newsletter-email" name="email" placeholder="ایمیل خود را وارد کنید" required />
								<button type="submit" class="devco-btn devco-btn--primary">عضویت</button>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="devco-footer__main">
				<div class="<?php echo esc_attr( devco_container_class() ); ?>">
					<div class="devco-footer__grid devco-footer__grid--cols-<?php echo (int) ( $active ? $columns : 4 ); ?>">
						<div class="devco-footer__col devco-footer__col--brand">
							<?php devco_site_logo( array( 'class' => 'devco-footer__logo' ) ); ?>
							<p class="devco-footer__about"><?php echo esc_html( devco_opt( 'general.about_text', '' ) ); ?></p>
							<?php devco_social_icons( array( 'class' => 'devco-socials--footer' ) ); ?>

							<?php if ( devco_opt( 'general.phone', '' ) ) : ?>
								<a class="devco-footer__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', devco_opt( 'general.phone', '' ) ) ); ?>">
									<?php devco_icon_e( 'phone', array( 'size' => 18 ) ); ?>
									<span><?php echo esc_html( devco_opt( 'general.phone', '' ) ); ?></span>
								</a>
							<?php endif; ?>
						</div>

						<?php if ( $active ) : ?>
							<?php for ( $i = 1; $i <= $columns; $i++ ) : ?>
								<?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
									<div class="devco-footer__col"><?php dynamic_sidebar( 'footer-' . $i ); ?></div>
								<?php endif; ?>
							<?php endfor; ?>
						<?php else : ?>
							<div class="devco-footer__col">
								<h4 class="devco-footer__title">دسترسی سریع</h4>
								<?php devco_nav_menu( 'footer', array( 'menu_class' => 'devco-footer__menu' ) ); ?>
							</div>
							<div class="devco-footer__col">
								<h4 class="devco-footer__title">خدمات مشتریان</h4>
								<ul class="devco-footer__menu">
									<li><a href="<?php echo esc_url( devco_dashboard_url( 'track' ) ); ?>">پیگیری سفارش</a></li>
									<li><a href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>">سفارش دوخت</a></li>
									<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">درباره ما</a></li>
									<li><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">قوانین و مقررات</a></li>
								</ul>
							</div>
							<div class="devco-footer__col">
								<h4 class="devco-footer__title">تماس با ما</h4>
								<ul class="devco-footer__contact">
									<?php if ( devco_opt( 'general.address', '' ) ) : ?>
										<li><?php devco_icon_e( 'pin', array( 'size' => 17 ) ); ?><span><?php echo esc_html( devco_opt( 'general.address', '' ) ); ?></span></li>
									<?php endif; ?>
									<?php if ( devco_opt( 'general.working_hours', '' ) ) : ?>
										<li><?php devco_icon_e( 'clock', array( 'size' => 17 ) ); ?><span><?php echo esc_html( devco_opt( 'general.working_hours', '' ) ); ?></span></li>
									<?php endif; ?>
									<li><?php devco_icon_e( 'mail', array( 'size' => 17 ) ); ?><span><?php echo esc_html( get_option( 'admin_email' ) ); ?></span></li>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<?php if ( devco_opt_bool( 'footer.trust_badges', true ) ) : ?>
				<div class="devco-footer__badges">
					<div class="<?php echo esc_attr( devco_container_class() ); ?>">
						<?php
						$badges = devco_opt( 'footer.badge_images', array() );

						if ( is_array( $badges ) && $badges ) {
							echo '<div class="devco-footer__badge-list">';
							foreach ( $badges as $badge ) {
								if ( empty( $badge['image'] ) ) {
									continue;
								}
								$img = '<img src="' . esc_url( $badge['image'] ) . '" alt="نشان اعتماد" loading="lazy" />';
								echo ! empty( $badge['link'] ) ? '<a href="' . esc_url( $badge['link'] ) . '" target="_blank" rel="noopener">' . $img . '</a>' : $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							echo '</div>';
						}

						if ( devco_opt( 'footer.payment_images', '' ) ) {
							echo '<img class="devco-footer__payments" src="' . esc_url( devco_opt( 'footer.payment_images', '' ) ) . '" alt="روش‌های پرداخت" loading="lazy" />';
						}
						?>
					</div>
				</div>
			<?php endif; ?>

			<div class="devco-footer__bottom">
				<div class="<?php echo esc_attr( devco_container_class() ); ?>">
					<div class="devco-footer__bottom-inner">
						<p class="devco-footer__copyright"><?php echo esc_html( devco_opt( 'footer.copyright', '© تمامی حقوق محفوظ است.' ) ); ?></p>
						<?php devco_nav_menu( 'topbar', array( 'menu_class' => 'devco-footer__links' ) ); ?>
					</div>
				</div>
			</div>
		</footer>
		<?php
	}
}
