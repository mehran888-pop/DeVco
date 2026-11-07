<?php
/**
 * Sidebar.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

$sidebar = devco_is_woo() && ( is_shop() || is_product_taxonomy() ) ? 'sidebar-shop' : 'sidebar-blog';

if ( ! is_active_sidebar( $sidebar ) ) {
	return;
}
?>
<aside class="devco-sidebar" aria-label="نوار کناری">
	<?php dynamic_sidebar( $sidebar ); ?>
</aside>
