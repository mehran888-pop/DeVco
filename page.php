<?php
/**
 * Page template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$hide_title = get_post_meta( get_the_ID(), '_devco_hide_title', true );

	if ( ! $hide_title && ! devco_is_elementor_page() ) {
		devco_page_header( array( 'title' => get_the_title() ) );
	}
	?>
	<article <?php post_class( 'devco-page' ); ?>>
		<?php if ( has_post_thumbnail() && ! devco_is_elementor_page() ) : ?>
			<div class="<?php echo esc_attr( devco_container_class() ); ?>">
				<figure class="devco-page__thumb"><?php the_post_thumbnail( 'devco-banner' ); ?></figure>
			</div>
		<?php endif; ?>

		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-entry">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					array(
						'before' => '<div class="devco-page-links">',
						'after'  => '</div>',
					)
				);
				?>
			</div>
		</div>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		echo '<div class="' . esc_attr( devco_container_class() ) . '">';
		comments_template();
		echo '</div>';
	}

endwhile;

get_footer();
