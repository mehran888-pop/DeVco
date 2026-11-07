<?php
/**
 * Template Name: داشبورد دیوکو
 * Template Post Type: page
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="devco-dashboard-page">
	<div class="<?php echo esc_attr( devco_container_class() ); ?>">
		<?php echo DeVco_Dashboard::render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>

<?php
get_footer();
