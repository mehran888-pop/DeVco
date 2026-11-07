<?php
/**
 * Template Name: سفارش دوخت (مانکن سه‌بعدی)
 * Template Post Type: page
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="devco-tailoring-hero">
	<div class="<?php echo esc_attr( devco_container_class() ); ?>">
		<div class="devco-tailoring-hero__inner">
			<div class="devco-tailoring-hero__text">
				<span class="devco-tailoring-hero__eyebrow"><?php devco_icon_e( 'scissors', array( 'size' => 16 ) ); ?> دوخت اختصاصی دیوکو</span>
				<h1><?php the_title(); ?></h1>
				<p><?php echo esc_html( devco_opt( 'tailoring.intro', '' ) ); ?></p>

				<ul class="devco-tailoring-hero__list">
					<li><?php devco_icon_e( 'ruler', array( 'size' => 17 ) ); ?> مانکن سه‌بعدی با راهنمای اندازه</li>
					<li><?php devco_icon_e( 'upload', array( 'size' => 17 ) ); ?> آپلود طرح و الگوی دلخواه</li>
					<li><?php devco_icon_e( 'invoice', array( 'size' => 17 ) ); ?> فاکتور و پرداخت آنلاین در پنل</li>
					<li><?php devco_icon_e( 'sms', array( 'size' => 17 ) ); ?> اطلاع‌رسانی پیامکی در هر مرحله</li>
				</ul>
			</div>

			<div class="devco-tailoring-hero__art" aria-hidden="true">
				<span class="devco-tailoring-hero__ring"></span>
				<span class="devco-tailoring-hero__ring devco-tailoring-hero__ring--2"></span>
				<?php devco_icon_e( 'scissors', array( 'size' => 64 ) ); ?>
			</div>
		</div>
	</div>
</section>

<div class="<?php echo esc_attr( devco_container_class() ); ?>">
	<div class="devco-tailoring-page">
		<?php
		while ( have_posts() ) :
			the_post();

			$content = get_the_content();

			if ( trim( $content ) ) {
				echo '<div class="devco-entry devco-tailoring-page__content">';
				the_content();
				echo '</div>';
			}

			devco_tailoring_form();
		endwhile;
		?>
	</div>
</div>

<?php
get_footer();
