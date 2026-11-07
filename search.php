<?php
/**
 * Search results.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

devco_page_header(
	array(
		'title'    => 'نتایج جستجو',
		'subtitle' => 'عبارت: ' . get_search_query(),
	)
);
?>

<div class="<?php echo esc_attr( devco_container_class() ); ?>">
	<div class="devco-blog-layout">
		<main class="devco-blog-main" id="main">
			<?php if ( have_posts() ) : ?>
				<p class="devco-results-count"><?php echo esc_html( devco_num( $wp_query->found_posts ) ); ?> نتیجه پیدا شد.</p>
				<div class="devco-post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<article <?php post_class( 'devco-post-card' ); ?>>
							<a class="devco-post-card__media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
								<?php has_post_thumbnail() ? the_post_thumbnail( 'devco-card', array( 'loading' => 'lazy' ) ) : print( '<span class="devco-post-card__ph">' . devco_icon( 'search', array( 'size' => 30 ) ) . '</span>' ); ?>
							</a>
							<div class="devco-post-card__body">
								<h2 class="devco-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="devco-post-card__excerpt"><?php echo esc_html( devco_trim( get_the_excerpt(), 18 ) ); ?></p>
							</div>
						</article>
					<?php endwhile; ?>
				</div>
				<?php devco_pagination(); ?>
			<?php else : ?>
				<div class="devco-empty">
					<?php devco_icon_e( 'search', array( 'size' => 40 ) ); ?>
					<h2>نتیجه‌ای پیدا نشد</h2>
					<p>عبارت دیگری را امتحان کنید.</p>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</main>
		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
