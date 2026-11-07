<?php
/**
 * Archive template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

devco_page_header(
	array(
		'title'    => wp_strip_all_tags( get_the_archive_title() ),
		'subtitle' => wp_strip_all_tags( get_the_archive_description() ),
	)
);
?>

<div class="<?php echo esc_attr( devco_container_class() ); ?>">
	<div class="devco-blog-layout">
		<main class="devco-blog-main" id="main">
			<?php if ( have_posts() ) : ?>
				<div class="devco-post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<article <?php post_class( 'devco-post-card' ); ?>>
							<a class="devco-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php has_post_thumbnail() ? the_post_thumbnail( 'devco-card', array( 'loading' => 'lazy' ) ) : print( '<span class="devco-post-card__ph">' . devco_icon( 'sparkle', array( 'size' => 34 ) ) . '</span>' ); ?>
							</a>
							<div class="devco-post-card__body">
								<?php devco_post_meta(); ?>
								<h2 class="devco-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="devco-post-card__excerpt"><?php echo esc_html( devco_trim( get_the_excerpt(), 20 ) ); ?></p>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php devco_pagination(); ?>
			<?php else : ?>
				<div class="devco-empty"><h2>محتوایی یافت نشد</h2></div>
			<?php endif; ?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
