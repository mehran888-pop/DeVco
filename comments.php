<?php
/**
 * Comments template.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="devco-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="devco-comments__title">
			<?php
			$count = get_comments_number();
			echo esc_html( devco_num( $count ) . ' دیدگاه' );
			?>
		</h2>

		<ol class="devco-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'avatar_size' => 52,
					'short_ping'  => true,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination( array( 'class' => 'devco-comments__pagination' ) ); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit'  => 'devco-btn devco-btn--primary',
			'title_reply'   => 'دیدگاه خود را بنویسید',
			'label_submit'  => 'ارسال دیدگاه',
			'comment_field' => '<p class="comment-form-comment"><label for="comment">دیدگاه</label><textarea id="comment" name="comment" rows="5" required></textarea></p>',
		)
	);
	?>
</div>
