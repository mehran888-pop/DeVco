<?php
/**
 * The template for displaying product content within loops.
 *
 * Overrides woocommerce/content-product.php and delegates the markup to
 * devco_product_card() so the shop loop matches the Elementor widgets.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( '', $product ); ?>>
	<?php devco_product_card( $product ); ?>
</li>
