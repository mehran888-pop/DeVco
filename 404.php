<?php
/**
 * 404 template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="<?php echo esc_attr( devco_container_class() ); ?>">
	<div class="devco-404">
		<div class="devco-404__art">
			<span class="devco-404__code">۴۰۴</span>
			<span class="devco-404__blob"></span>
		</div>
		<h1 class="devco-404__title">صفحه‌ای که دنبالش بودید پیدا نشد</h1>
		<p class="devco-404__text">شاید آدرس تغییر کرده باشد. از جستجو یا لینک‌های زیر استفاده کنید.</p>

		<div class="devco-404__search"><?php devco_product_search_form(); ?></div>

		<div class="devco-404__actions">
			<a class="devco-btn devco-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به خانه</a>
			<?php if ( devco_is_woo() ) : ?>
				<a class="devco-btn devco-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">مشاهده فروشگاه</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
