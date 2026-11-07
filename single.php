<?php
/**
 * Single post.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	devco_page_header( array( 'title' => get_the_title() ) );
	?>
	<div class="<?php echo esc_attr( devco_container_class() ); ?>">
		<div class="devco-blog-layout devco-blog-layout--single">
			<article <?php post_class( 'devco-single-post' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="devco-single-post__thumb"><?php the_post_thumbnail( 'devco-banner' ); ?></figure>
				<?php endif; ?>

				<div class="devco-single-post__meta"><?php devco_post_meta(); ?></div>

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

				<footer class="devco-single-post__footer">
					<?php the_tags( '<div class="devco-tags">', '', '</div>' ); ?>
					<?php
					the_post_navigation(
						array(
							'prev_text' => '<span class="devco-nav-post__label">مطلب قبلی</span><span class="devco-nav-post__title">%title</span>',
							'next_text' => '<span class="devco-nav-post__label">مطلب بعدی</span><span class="devco-nav-post__title">%title</span>',
						)
					);
					?>
				</footer>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</article>

			<?php get_sidebar(); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
