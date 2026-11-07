<?php
/**
 * Template Name: تمام عرض (بدون حاشیه)
 * Template Post Type: page, post
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	if ( ! devco_is_elementor_page() ) {
		devco_page_header( array( 'title' => get_the_title() ) );
	}
	?>
	<article <?php post_class( 'devco-page devco-page--full' ); ?>>
		<div class="devco-wide">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
