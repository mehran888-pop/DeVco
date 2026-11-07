<?php
/**
 * Main blog index.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();

devco_page_header(
	array(
		'title'    => is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'بلاگ دیوکو',
		'subtitle' => 'ایده‌ها، استایل و راهنمای اندازه‌گیری',
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
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'devco-card', array( 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<span class="devco-post-card__ph"><?php devco_icon_e( 'sparkle', array( 'size' => 34 ) ); ?></span>
								<?php endif; ?>
							</a>
							<div class="devco-post-card__body">
								<?php devco_post_meta(); ?>
								<h2 class="devco-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="devco-post-card__excerpt"><?php echo esc_html( devco_trim( get_the_excerpt(), 22 ) ); ?></p>
								<a class="devco-post-card__more" href="<?php the_permalink(); ?>">ادامه مطلب <?php devco_icon_e( 'arrow-left', array( 'size' => 15 ) ); ?></a>
							</div>
						</article>
					<?php endwhile; ?>
				</div>

				<?php devco_pagination(); ?>
			<?php else : ?>
				<div class="devco-empty">
					<?php devco_icon_e( 'search', array( 'size' => 40 ) ); ?>
					<h2>مطلبی یافت نشد</h2>
					<p>عبارت دیگری را جستجو کنید.</p>
					<?php get_search_form(); ?>
				</div>
			<?php endif; ?>
		</main>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
