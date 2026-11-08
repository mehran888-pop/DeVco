<?php
/**
 * Template Name: تماس با ما (DeVco)
 * Template Post Type: page
 *
 * Ready-made contact page: hero, working contact form (admin-post + nonce),
 * contact details, map, socials and an FAQ accordion.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

$devco_lead = 'سفارش، اندازه، پارچه یا پیگیری ارسال؛ هر سوالی دارید بنویسید یا تماس بگیرید. در ساعات کاری تا ۴ ساعت پاسخ می‌دهیم.';
?>
<main id="main" class="devco-page devco-page--contact">
	<?php
	while ( have_posts() ) :
		the_post();

		devco_page_hero(
			array(
				'title'   => get_the_title(),
				'eyebrow' => 'پشتیبانی دیوکو',
				'lead'    => has_excerpt() ? get_the_excerpt() : $devco_lead,
				'crumbs'  => array(
					array( home_url( '/' ), 'خانه' ),
					array( '', get_the_title() ),
				),
			)
		);

		if ( trim( get_the_content() ) ) {
			?>
			<section class="devco-page-section devco-page-section--tight">
				<div class="<?php echo esc_attr( devco_container_class() ); ?>">
					<div class="devco-entry"><?php the_content(); ?></div>
				</div>
			</section>
			<?php
		}
	endwhile;

	devco_contact_sections();
	?>
</main>
<?php
get_footer();
