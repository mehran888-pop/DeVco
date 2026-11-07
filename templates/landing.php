<?php
/**
 * Template Name: لندینگ (بدون هدر و فوتر)
 * Template Post Type: page
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'devco-landing' ); ?>>
<?php wp_body_open(); ?>

<div class="devco-landing__bar">
	<?php devco_site_logo(); ?>
	<a class="devco-btn devco-btn--sm devco-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به فروشگاه</a>
</div>

<main id="devco-content" class="devco-landing__content">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<?php do_action( 'devco_after_footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>
