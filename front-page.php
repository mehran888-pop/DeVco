<?php
/**
 * Front page: creative landing layout.
 *
 * Used automatically when the site shows the latest posts, and also when a
 * static front page is assigned but no page template is chosen.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

$is_builder_page = is_page()
	&& function_exists( 'devco_is_elementor_page' )
	&& devco_is_elementor_page();
?>
<main id="main" class="devco-home">
	<?php
	if ( $is_builder_page ) {
		// Respect a front page designed in Elementor instead of the demo layout.
		while ( have_posts() ) :
			the_post();
			?>
			<div class="<?php echo esc_attr( devco_container_class() ); ?>">
				<div class="devco-entry"><?php the_content(); ?></div>
			</div>
			<?php
		endwhile;
	} else {
		devco_home_page_content();
	}
	?>
</main>
<?php
get_footer();
