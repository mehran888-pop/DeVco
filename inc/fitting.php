<?php
/**
 * Virtual fitting room ("اتاق پرو").
 *
 * Renders a side panel where a customer can preview garments over their own
 * photo or live webcam feed, drag/scale the garment and add it to the cart.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collect the garment list for the fitting room.
 *
 * @param array $args Overrides.
 * @return array
 */
function devco_fitting_items( $args = array() ) {
	$items = array();

	if ( ! empty( $args['items'] ) && is_array( $args['items'] ) ) {
		foreach ( $args['items'] as $item ) {
			$image = '';

			if ( ! empty( $item['image'] ) ) {
				$image = is_array( $item['image'] ) ? $item['image']['url'] : $item['image'];
			}

			if ( ! $image ) {
				continue;
			}

			$items[] = array(
				'image'   => $image,
				'title'   => isset( $item['title'] ) ? $item['title'] : '',
				'product' => isset( $item['product'] ) ? $item['product'] : '',
				'color'   => isset( $item['color'] ) ? $item['color'] : '',
			);
		}

		return $items;
	}

	$stored = devco_opt( 'fitting.clothes', array() );

	if ( is_array( $stored ) ) {
		foreach ( $stored as $item ) {
			if ( empty( $item['image'] ) ) {
				continue;
			}

			$items[] = array(
				'image'   => $item['image'],
				'title'   => isset( $item['title'] ) ? $item['title'] : 'لباس',
				'product' => isset( $item['product'] ) ? $item['product'] : '',
				'color'   => isset( $item['color'] ) ? $item['color'] : '',
			);
		}
	}

	// Fall back to the current product image when on a single product page.
	if ( ! $items && devco_is_woo() && is_product() ) {
		$image = get_the_post_thumbnail_url( get_the_ID(), 'large' );

		if ( $image ) {
			$items[] = array(
				'image'   => $image,
				'title'   => get_the_title(),
				'product' => get_the_ID(),
				'color'   => '',
			);
		}
	}

	return apply_filters( 'devco_fitting_items', $items, $args );
}

/**
 * Render a fitting-room trigger button.
 *
 * @param array $args Args.
 */
function devco_fitting_trigger( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'label' => devco_opt( 'fitting.trigger_label', 'اتاق پرو' ),
			'icon'  => devco_opt( 'fitting.trigger_icon', 'tshirt' ),
			'class' => '',
			'float' => false,
		)
	);

	$classes = array( 'devco-fit-trigger', 'js-devco-fitting-open' );

	if ( $args['float'] ) {
		$classes[] = 'devco-fit-trigger--float devco-fit-trigger--' . devco_opt( 'fitting.position', 'right' );
	}

	if ( $args['class'] ) {
		$classes[] = $args['class'];
	}

	printf(
		'<button type="button" class="%1$s" data-fitting-trigger>%2$s<span>%3$s</span></button>',
		esc_attr( implode( ' ', $classes ) ),
		devco_icon( $args['icon'], array( 'size' => 20 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $args['label'] )
	);
}

/**
 * Render the fitting room panel.
 *
 * @param array $args Args: items, side, product_id.
 */
function devco_fitting_room( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'items'      => array(),
			'side'       => devco_opt( 'fitting.position', 'right' ),
			'opacity'    => (float) devco_opt( 'fitting.cloth_opacity', 0.88 ),
			'product_id' => 0,
			'id'         => 'devco-fitting-' . wp_rand( 100, 999 ),
		)
	);

	if ( 'none' === $args['side'] ) {
		$args['side'] = 'right';
	}

	$items     = devco_fitting_items( $args );
	$mannequin = devco_opt( 'fitting.mannequin', '' );
	$webcam    = devco_opt_bool( 'fitting.webcam', true );
	$upload    = devco_opt_bool( 'fitting.upload', true );
	?>
	<div class="devco-fitting" id="<?php echo esc_attr( $args['id'] ); ?>" data-fitting data-side="<?php echo esc_attr( $args['side'] ); ?>" data-opacity="<?php echo esc_attr( $args['opacity'] ); ?>" aria-hidden="true">
		<div class="devco-fitting__backdrop" data-fit-close></div>

		<aside class="devco-fitting__panel" role="dialog" aria-modal="true" aria-label="اتاق پرو مجازی">
			<header class="devco-fitting__head">
				<div>
					<span class="devco-fitting__eyebrow"><?php devco_icon_e( 'sparkle', array( 'size' => 15 ) ); ?> پرو مجازی</span>
					<h3 class="devco-fitting__title">لباس را روی خودتان ببینید</h3>
				</div>
				<button type="button" class="devco-fitting__close" data-fit-close aria-label="بستن"><?php devco_icon_e( 'close', array( 'size' => 20 ) ); ?></button>
			</header>

			<div class="devco-fitting__stage-wrap">
				<div class="devco-fitting__stage" data-fit-stage>
					<video class="devco-fitting__video" data-fit-video playsinline autoplay muted hidden></video>
					<img class="devco-fitting__photo" data-fit-photo alt="تصویر شما" hidden />

					<?php if ( $mannequin ) : ?>
						<img class="devco-fitting__base" data-fit-base src="<?php echo esc_url( $mannequin ); ?>" alt="مانکن" />
					<?php else : ?>
						<div class="devco-fitting__base devco-fitting__base--css" data-fit-base aria-hidden="true">
							<span class="devco-fit-body">
								<span class="devco-fit-body__head"></span>
								<span class="devco-fit-body__neck"></span>
								<span class="devco-fit-body__torso"></span>
								<span class="devco-fit-body__arm devco-fit-body__arm--l"></span>
								<span class="devco-fit-body__arm devco-fit-body__arm--r"></span>
								<span class="devco-fit-body__leg devco-fit-body__leg--l"></span>
								<span class="devco-fit-body__leg devco-fit-body__leg--r"></span>
							</span>
						</div>
					<?php endif; ?>

					<div class="devco-fitting__cloth" data-fit-cloth hidden>
						<img data-fit-cloth-img src="" alt="لباس انتخاب‌شده" draggable="false" />
						<span class="devco-fitting__handle" aria-hidden="true"><?php devco_icon_e( 'drag', array( 'size' => 16 ) ); ?></span>
					</div>

					<div class="devco-fitting__empty" data-fit-empty>
						<?php devco_icon_e( 'camera', array( 'size' => 30 ) ); ?>
						<p>برای شروع، عکس خود را آپلود کنید یا دوربین را روشن کنید.</p>
					</div>
				</div>

				<div class="devco-fitting__stage-tools">
					<?php if ( $upload ) : ?>
						<label class="devco-fit-tool">
							<?php devco_icon_e( 'upload', array( 'size' => 17 ) ); ?>
							<span>آپلود عکس</span>
							<input type="file" accept="image/*" data-fit-upload hidden />
						</label>
					<?php endif; ?>

					<?php if ( $webcam ) : ?>
						<button type="button" class="devco-fit-tool" data-fit-webcam><?php devco_icon_e( 'webcam', array( 'size' => 17 ) ); ?><span>دوربین</span></button>
					<?php endif; ?>

					<button type="button" class="devco-fit-tool" data-fit-reset><?php devco_icon_e( 'refresh', array( 'size' => 17 ) ); ?><span>ریست</span></button>
					<button type="button" class="devco-fit-tool" data-fit-snapshot><?php devco_icon_e( 'download', array( 'size' => 17 ) ); ?><span>ذخیره عکس</span></button>
				</div>
			</div>

			<div class="devco-fitting__controls">
				<label class="devco-fit-range">
					<span>شفافیت</span>
					<input type="range" min="20" max="100" value="<?php echo esc_attr( (int) ( $args['opacity'] * 100 ) ); ?>" data-fit-range="opacity" />
				</label>
				<label class="devco-fit-range">
					<span>اندازه</span>
					<input type="range" min="50" max="180" value="100" data-fit-range="scale" />
				</label>
				<label class="devco-fit-range">
					<span>چرخش</span>
					<input type="range" min="-45" max="45" value="0" data-fit-range="rotate" />
				</label>
			</div>

			<?php if ( $items ) : ?>
				<div class="devco-fitting__clothes" data-fit-clothes>
					<p class="devco-fitting__clothes-title">انتخاب لباس <span><?php echo esc_html( devco_num( count( $items ) ) ); ?> مدل</span></p>
					<div class="devco-fitting__clothes-list">
						<?php foreach ( $items as $index => $item ) : ?>
							<button type="button"
								class="devco-fit-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
								data-fit-item
								data-image="<?php echo esc_url( $item['image'] ); ?>"
								data-title="<?php echo esc_attr( $item['title'] ); ?>"
								data-product="<?php echo esc_attr( $item['product'] ); ?>">
								<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" />
								<span><?php echo esc_html( $item['title'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			<?php else : ?>
				<p class="devco-fitting__no-items">هنوز لباسی برای پرو تعریف نشده است. از تنظیمات قالب ← اتاق پرو لباس اضافه کنید.</p>
			<?php endif; ?>

			<footer class="devco-fitting__footer">
				<div class="devco-fitting__selected" data-fit-selected-title>یک لباس را انتخاب کنید</div>
				<button type="button" class="devco-btn devco-btn--primary devco-btn--block js-devco-fit-add" data-fit-add>
					<?php devco_icon_e( 'cart', array( 'size' => 18 ) ); ?>
					افزودن لباس به سبد خرید
				</button>
				<p class="devco-fitting__hint">تصویر روی دستگاه شما پردازش می‌شود و هرگز آپلود نمی‌شود.</p>
			</footer>
		</aside>
	</div>
	<?php
}
