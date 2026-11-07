<?php
/**
 * Search form.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

$id = 'devco-search-form-' . wp_rand( 100, 999 );
?>
<form role="search" method="get" class="devco-search__form devco-search__form--generic" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>">جستجو</label>
	<span class="devco-search__icon"><?php devco_icon_e( 'search', array( 'size' => 18 ) ); ?></span>
	<input type="search" id="<?php echo esc_attr( $id ); ?>" class="devco-search__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="جستجو…" />
	<button type="submit" class="devco-search__submit">جستجو</button>
</form>
