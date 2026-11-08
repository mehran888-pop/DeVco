<?php
/**
 * Template Name: درباره ما (DeVco)
 * Template Post Type: page
 *
 * A ready-made «about us» page: story, numbers, values, timeline, team and a
 * closing call to action. Any content you add in the editor is printed under
 * the hero, so the template can also be used as a starting point.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

$devco_lead = 'دیوکو یک خانه‌ی دوخت است: پارچه‌های طبیعی، الگوی اختصاصی و کنترلی که تا لباس به دست شما نرسد تمام نمی‌شود.';
?>
<main id="main" class="devco-page devco-page--about">
	<?php
	while ( have_posts() ) :
		the_post();

		devco_page_hero(
			array(
				'title'   => get_the_title(),
				'eyebrow' => 'درباره دیوکو',
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

	devco_about_sections();
	?>
</main>
<?php
get_footer();
