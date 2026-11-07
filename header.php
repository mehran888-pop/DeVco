<?php
/**
 * Header template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#devco-content">رفتن به محتوا</a>

<?php if ( devco_opt_bool( 'layout.preloader', false ) ) : ?>
	<div class="devco-preloader" data-preloader>
		<div class="devco-preloader__inner">
			<span class="devco-preloader__spinner"></span>
			<span class="devco-preloader__text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</div>
	</div>
<?php endif; ?>

<div id="page" class="devco-site">
	<?php DeVco_Builder::instance()->header(); ?>

	<?php if ( devco_opt_bool( 'shop.progress_bar', false ) ) : ?>
		<div class="devco-scroll-progress" data-scroll-progress></div>
	<?php endif; ?>

	<div id="devco-content" class="devco-content">
