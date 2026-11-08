<?php
/**
 * Template Name: صفحه اصلی خلاق (DeVco)
 * Template Post Type: page
 *
 * Front page style layout that can be assigned to any page.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="devco-home devco-home--page">
	<?php devco_home_page_content(); ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) :
			?>
			<section class="devco-home__section devco-home__extra">
				<div class="<?php echo esc_attr( devco_container_class() ); ?>">
					<div class="devco-entry"><?php the_content(); ?></div>
				</div>
			</section>
			<?php
		endif;
	endwhile;
	?>
</main>
<?php
get_footer();
