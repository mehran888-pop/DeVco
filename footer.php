<?php
/**
 * Footer template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;
?>
	</div><!-- #devco-content -->

	<?php DeVco_Builder::instance()->footer(); ?>
</div><!-- #page -->

<?php devco_bottom_nav(); ?>

<?php if ( devco_opt_bool( 'layout.back_to_top', true ) ) : ?>
	<button type="button" class="devco-to-top js-devco-to-top" aria-label="بازگشت به بالا">
		<?php devco_icon_e( 'arrow-up', array( 'size' => 20 ) ); ?>
	</button>
<?php endif; ?>

<?php do_action( 'devco_after_footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
