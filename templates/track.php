<?php
/**
 * Template Name: پیگیری سفارش
 * Template Post Type: page
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="<?php echo esc_attr( devco_container_class() ); ?>">
	<div class="devco-track-page">
		<div class="devco-track-page__head">
			<span class="devco-track-page__icon"><?php devco_icon_e( 'truck', array( 'size' => 30 ) ); ?></span>
			<h1><?php the_title(); ?></h1>
			<p>شماره سفارش و شماره موبایل خود را وارد کنید تا وضعیت لحظه‌ای سفارش را ببینید.</p>
		</div>

		<form class="devco-track-form devco-track-form--page" data-track-form>
			<div class="devco-track-form__row">
				<label>
					<span>شماره سفارش</span>
					<input type="text" name="order_id" placeholder="1024" required />
				</label>
				<label>
					<span>شماره موبایل یا ایمیل</span>
					<input type="text" name="contact" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required />
				</label>
				<button type="submit" class="devco-btn devco-btn--primary"><?php devco_icon_e( 'search', array( 'size' => 16 ) ); ?> پیگیری سفارش</button>
			</div>
			<p class="devco-track-form__message" data-track-message role="status"></p>
		</form>

		<div class="devco-track-page__result" data-track-result></div>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( trim( get_the_content() ) ) {
				echo '<div class="devco-entry devco-track-page__content">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</div>

<?php
get_footer();
