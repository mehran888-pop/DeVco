<?php
/**
 * Creative front-page sections.
 *
 * The sections below are intentionally self contained: they work with or
 * without WooCommerce and fall back to curated demo products so a fresh
 * install still looks like a finished shop.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Demo catalogue used when WooCommerce is missing or has no products yet.
 *
 * @return array
 */
function devco_home_demo_products() {
	$products = array(
		array(
			'title' => 'پیراهن کتان تابستانی',
			'cat'   => 'پیراهن',
			'price' => 1280000,
			'old'   => 0,
			'badge' => 'جدید',
			'hue'   => 34,
			'rating' => 4.8,
			'reviews' => 42,
		),
		array(
			'title' => 'کت بلند پشمی',
			'cat'   => 'کت و مانتو',
			'price' => 3160000,
			'old'   => 3950000,
			'badge' => '',
			'hue'   => 210,
			'rating' => 4.6,
			'reviews' => 27,
		),
		array(
			'title' => 'شلوار پارچه‌ای راسته',
			'cat'   => 'شلوار',
			'price' => 980000,
			'old'   => 0,
			'badge' => 'پرفروش',
			'hue'   => 154,
			'rating' => 4.9,
			'reviews' => 68,
		),
		array(
			'title' => 'مانتو کیمونو ابریشمی',
			'cat'   => 'مانتو',
			'price' => 2450000,
			'old'   => 0,
			'badge' => '',
			'hue'   => 320,
			'rating' => 4.7,
			'reviews' => 19,
		),
		array(
			'title' => 'بلوز کشمیر یقه‌گرد',
			'cat'   => 'بلوز',
			'price' => 1650000,
			'old'   => 1980000,
			'badge' => '',
			'hue'   => 12,
			'rating' => 4.5,
			'reviews' => 33,
		),
		array(
			'title' => 'دامن پلیسه میدی',
			'cat'   => 'دامن',
			'price' => 1150000,
			'old'   => 0,
			'badge' => '',
			'hue'   => 268,
			'rating' => 4.4,
			'reviews' => 12,
		),
		array(
			'title' => 'کاپشن کوتاه ضدآب',
			'cat'   => 'کاپشن',
			'price' => 2980000,
			'old'   => 0,
			'badge' => 'ویژه',
			'hue'   => 190,
			'rating' => 4.8,
			'reviews' => 51,
		),
		array(
			'title' => 'ست دوخته‌شده سفارشی',
			'cat'   => 'سفارشی',
			'price' => 5200000,
			'old'   => 0,
			'badge' => 'سفارشی',
			'hue'   => 42,
			'rating' => 5,
			'reviews' => 9,
		),
	);

	return apply_filters( 'devco_home_demo_products', $products );
}

/**
 * Shop / catalogue URL used by demo links.
 *
 * @return string
 */
function devco_home_shop_url() {
	if ( devco_is_woo() && function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );

		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/' );
}

/**
 * Render the art block used as the "photo" of a demo product.
 *
 * @param int    $hue   Base hue for the gradient.
 * @param string $icon  Icon slug.
 * @param string $label Small caption inside the art.
 */
function devco_home_art( $hue = 34, $icon = 'tshirt', $label = '' ) {
	printf(
		'<span class="devco-art" style="--dv-hue:%1$d" aria-hidden="true"><span class="devco-art__glow"></span><span class="devco-art__icon">%2$s</span>',
		(int) $hue,
		devco_icon( $icon, array( 'size' => 54 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	if ( $label ) {
		echo '<span class="devco-art__label">' . esc_html( $label ) . '</span>';
	}

	echo '<span class="devco-art__shine"></span></span>';
}

/**
 * Demo product card (mirrors the real card markup/classes).
 *
 * @param array $item Demo item.
 */
function devco_home_demo_card( $item ) {
	$item    = wp_parse_args(
		$item,
		array(
			'title'   => 'محصول نمونه',
			'cat'     => 'دسته‌بندی',
			'price'   => 0,
			'old'     => 0,
			'badge'   => '',
			'hue'     => 34,
			'rating'  => 4.6,
			'reviews' => 0,
		)
	);
	$link    = devco_home_shop_url();
	$percent = $item['old'] > 0 && $item['old'] > $item['price'] ? (int) round( ( ( $item['old'] - $item['price'] ) / $item['old'] ) * 100 ) : 0;

	echo '<article class="devco-product-card devco-product-card--overlay devco-product-card--hover-zoom devco-product-card--ratio-3-4 devco-product-card--demo">';
	echo '<div class="devco-product-card__inner">';
	echo '<div class="devco-product-card__media">';
	echo '<a class="devco-product-card__link" href="' . esc_url( $link ) . '" aria-label="' . esc_attr( $item['title'] ) . '">';
	devco_home_art( $item['hue'], 'tshirt' );
	echo '</a>';

	echo '<div class="devco-product-card__badges">';
	if ( $item['badge'] ) {
		echo '<span class="devco-badge devco-badge--featured">' . esc_html( $item['badge'] ) . '</span>';
	}
	if ( $percent ) {
		echo '<span class="devco-badge devco-badge--sale">' . esc_html( devco_num( $percent ) ) . '٪</span>';
	}
	echo '</div>';

	echo '<div class="devco-product-card__actions">';
	printf(
		'<a class="devco-card-action" href="%1$s" aria-label="مشاهده در فروشگاه">%2$s</a>',
		esc_url( $link ),
		devco_icon( 'bag', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	printf(
		'<a class="devco-card-action" href="%1$s" aria-label="راهنمای سایز">%2$s</a>',
		esc_url( $link ),
		devco_icon( 'ruler', array( 'size' => 18 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '</div></div>';

	echo '<div class="devco-product-card__body">';
	echo '<span class="devco-product-card__cat">' . esc_html( $item['cat'] ) . '</span>';
	echo '<h3 class="devco-product-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $item['title'] ) . '</a></h3>';
	echo '<div class="devco-product-card__rating">' . devco_stars( (float) $item['rating'] ) . '<span>' . esc_html( devco_num( $item['reviews'] ) ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div class="devco-product-card__price">';
	echo '<ins>' . wp_kses_post( devco_price( $item['price'] ) ) . '</ins>';
	if ( $percent ) {
		echo ' <del>' . wp_kses_post( devco_price( $item['old'] ) ) . '</del>';
	}
	echo '</div></div></div></article>';
}

/**
 * Product grid: real WooCommerce products when available, demo cards otherwise.
 *
 * @param array $args Args: limit, title, subtitle, columns.
 */
function devco_home_products( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'    => 8,
			'columns'  => 4,
			'orderby'  => 'date',
			'featured' => false,
		)
	);

	$products = array();

	if ( devco_is_woo() && function_exists( 'wc_get_products' ) ) {
		$query = array(
			'limit'   => (int) $args['limit'],
			'status'  => 'publish',
			'orderby' => $args['orderby'],
			'order'   => 'DESC',
		);

		if ( $args['featured'] ) {
			$query['featured'] = true;
		}

		$products = wc_get_products( $query );
	}

	echo '<div class="devco-home__grid devco-home__grid--cols-' . (int) $args['columns'] . '">';

	if ( $products && function_exists( 'devco_product_card' ) ) {
		foreach ( $products as $product ) {
			devco_product_card( $product );
		}
	} else {
		$demo = array_slice( devco_home_demo_products(), 0, (int) $args['limit'] );

		foreach ( $demo as $item ) {
			devco_home_demo_card( $item );
		}
	}

	echo '</div>';
}

/**
 * Section heading helper.
 *
 * @param string $eyebrow Small label above the title.
 * @param string $title   Title.
 * @param string $desc    Description.
 * @param array  $link    Optional link array: url + label.
 */
function devco_home_heading( $eyebrow, $title, $desc = '', $link = array() ) {
	echo '<header class="devco-home__head">';

	if ( $eyebrow ) {
		echo '<span class="devco-home__eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}

	echo '<h2 class="devco-home__title">' . wp_kses_post( $title ) . '</h2>';

	if ( $desc ) {
		echo '<p class="devco-home__desc">' . wp_kses_post( $desc ) . '</p>';
	}

	if ( ! empty( $link['url'] ) ) {
		printf(
			'<a class="devco-home__head-link" href="%1$s">%2$s %3$s</a>',
			esc_url( $link['url'] ),
			esc_html( isset( $link['label'] ) ? $link['label'] : 'مشاهده همه' ),
			devco_icon( 'arrow-left', array( 'size' => 16 ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	echo '</header>';
}

/**
 * Hero section.
 */
function devco_home_section_hero() {
	$demo    = devco_home_demo_products();
	$shop    = devco_home_shop_url();
	$fitting = devco_opt_bool( 'fitting.enabled', true );
	?>
	<section class="devco-hero">
		<span class="devco-hero__blob devco-hero__blob--a" aria-hidden="true"></span>
		<span class="devco-hero__blob devco-hero__blob--b" aria-hidden="true"></span>
		<span class="devco-hero__grid-lines" aria-hidden="true"></span>

		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-hero__inner">
				<div class="devco-hero__copy">
					<span class="devco-hero__eyebrow">
						<?php devco_icon_e( 'sparkle', array( 'size' => 15 ) ); ?>
						کالکشن جدید · دوخت سفارشی
					</span>

					<h1 class="devco-hero__title">
						لباسی که <em>اندازه‌ی شما</em> است،<br>
						نه اندازه‌ی قالب
					</h1>

					<p class="devco-hero__lead">
						پارچه‌های طبیعی، الگوی اختصاصی و اندازه‌گیری آنلاین در سه دقیقه.
						سفارش خود را ثبت کنید؛ خیاط دیوکو دقیقاً برای اندام شما می‌دوزد.
					</p>

					<div class="devco-hero__actions">
						<a class="devco-btn devco-btn--primary devco-btn--lg" href="<?php echo esc_url( $shop ); ?>">
							<?php devco_icon_e( 'bag', array( 'size' => 18 ) ); ?>
							خرید کالکشن
						</a>

						<?php if ( $fitting ) : ?>
							<button type="button" class="devco-btn devco-btn--ghost devco-btn--lg js-devco-fitting-open" data-fitting>
								<?php devco_icon_e( 'ruler', array( 'size' => 18 ) ); ?>
								اندازه‌گیری آنلاین
							</button>
						<?php else : ?>
							<a class="devco-btn devco-btn--ghost devco-btn--lg" href="<?php echo esc_url( devco_dashboard_url( 'measurements' ) ); ?>">
								<?php devco_icon_e( 'ruler', array( 'size' => 18 ) ); ?>
								اندازه‌گیری آنلاین
							</a>
						<?php endif; ?>
					</div>

					<ul class="devco-hero__stats">
						<li><strong><?php echo esc_html( devco_num( '۱۲' ) ); ?>هزار</strong><span>مشتری راضی</span></li>
						<li><strong><?php echo esc_html( devco_num( '۴.۹' ) ); ?></strong><span>امتیاز خرید</span></li>
						<li><strong><?php echo esc_html( devco_num( '۴۸' ) ); ?> ساعت</strong><span>ارسال سریع</span></li>
					</ul>
				</div>

				<div class="devco-hero__visual">
					<figure class="devco-hero__card devco-hero__card--main">
						<?php devco_home_art( (int) $demo[0]['hue'], 'tshirt', '' ); ?>
						<figcaption>
							<span class="devco-hero__card-cat"><?php echo esc_html( $demo[0]['cat'] ); ?></span>
							<strong><?php echo esc_html( $demo[0]['title'] ); ?></strong>
							<span class="devco-hero__card-price"><?php echo wp_kses_post( devco_price( $demo[0]['price'] ) ); ?></span>
						</figcaption>
					</figure>

					<figure class="devco-hero__card devco-hero__card--sm">
						<?php devco_home_art( (int) $demo[2]['hue'], 'tshirt', '' ); ?>
					</figure>

					<div class="devco-hero__chip devco-hero__chip--ship">
						<?php devco_icon_e( 'truck', array( 'size' => 17 ) ); ?>
						<span>ارسال رایگان بالای <?php echo wp_kses_post( devco_price( 2000000 ) ); ?></span>
					</div>

					<div class="devco-hero__chip devco-hero__chip--rate">
						<span class="devco-hero__chip-stars"><?php devco_icon_e( 'star', array( 'size' => 14 ) ); ?></span>
						<span><strong><?php echo esc_html( devco_num( '۴.۹' ) ); ?></strong> از <?php echo esc_html( devco_num( '۵' ) ); ?> · <?php echo esc_html( devco_num( '۲۳۰' ) ); ?> نظر</span>
					</div>

					<span class="devco-hero__ring" aria-hidden="true"></span>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Trust strip.
 */
function devco_home_section_trust() {
	$items = array(
		array( 'truck', 'ارسال سریع', 'تهران ۲۴ ساعته، شهرستان ۴۸ ساعته' ),
		array( 'shield', 'ضمانت اصالت', '۷ روز مهلت بازگشت بی‌قید و شرط' ),
		array( 'scissors', 'دوخت سفارشی', 'الگوی اختصاصی بر اساس اندام شما' ),
		array( 'headset', 'پشتیبانی واقعی', 'مشاوره استایل با خیاط، همه‌روزه' ),
	);
	?>
	<section class="devco-home__trust">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li>
						<span class="devco-home__trust-icon"><?php devco_icon_e( $item[0], array( 'size' => 22 ) ); ?></span>
						<span class="devco-home__trust-text">
							<strong><?php echo esc_html( $item[1] ); ?></strong>
							<small><?php echo esc_html( $item[2] ); ?></small>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Category tiles.
 */
function devco_home_section_categories() {
	$tiles = array(
		array( 'پیراهن و بلوز', 118, 'tshirt' ),
		array( 'کت و مانتو', 212, 'tshirt' ),
		array( 'شلوار و دامن', 158, 'tshirt' ),
		array( 'کاپشن و پالتو', 20, 'tshirt' ),
		array( 'دوخت سفارشی', 40, 'scissors' ),
		array( 'اکسسوری', 292, 'gift' ),
	);

	$shop = devco_home_shop_url();
	?>
	<section class="devco-home__section devco-home__categories">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php
			devco_home_heading(
				'دسته‌بندی‌ها',
				'از کجای کالکشن شروع کنیم؟',
				'شش دسته‌ی اصلی دیوکو، هر کدام با الگوی اختصاصی و راهنمای سایز.',
				array(
					'url'   => $shop,
					'label' => 'همه دسته‌ها',
				)
			);
			?>

			<ul class="devco-home__cats">
				<?php foreach ( $tiles as $index => $tile ) : ?>
					<li>
						<a class="devco-home__cat" href="<?php echo esc_url( $shop ); ?>" style="--dv-hue:<?php echo (int) $tile[1]; ?>">
							<span class="devco-home__cat-art">
								<?php devco_icon_e( $tile[2], array( 'size' => 26 ) ); ?>
							</span>
							<span class="devco-home__cat-text">
								<strong><?php echo esc_html( $tile[0] ); ?></strong>
								<small><?php echo esc_html( devco_num( 12 + $index * 7 ) ); ?> محصول</small>
							</span>
							<span class="devco-home__cat-arrow"><?php devco_icon_e( 'arrow-left', array( 'size' => 16 ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Product demo section.
 */
function devco_home_section_products() {
	$shop = devco_home_shop_url();
	?>
	<section class="devco-home__section devco-home__products">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php
			devco_home_heading(
				'دمو محصولات',
				'پیشنهاد این هفته',
				'نمونه‌چیدمان کارت محصولات؛ با اتصال ووکامرس همین بخش به‌صورت خودکار محصولات واقعی را نمایش می‌دهد.',
				array(
					'url'   => $shop,
					'label' => 'مشاهده فروشگاه',
				)
			);

			devco_home_products( array( 'limit' => 8, 'columns' => 4 ) );
			?>
		</div>
	</section>
	<?php
}

/**
 * Editorial split: tailor made / virtual fitting room.
 */
function devco_home_section_editorial() {
	$steps = array(
		'اندازه‌ها را در سه دقیقه ثبت کنید',
		'الگو بر اساس اندام شما برش می‌خورد',
		'تحویل با پرو و اصلاح رایگان',
	);
	?>
	<section class="devco-home__section devco-home__editorial">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-home__split">
				<div class="devco-home__split-media">
					<span class="devco-home__split-ring" aria-hidden="true"></span>
					<figure class="devco-home__split-card">
						<?php devco_home_art( 38, 'scissors', 'اتاق پرو دیوکو' ); ?>
					</figure>
					<div class="devco-home__split-badge">
						<?php devco_icon_e( 'check-badge', array( 'size' => 18 ) ); ?>
						<span><strong>۷ روز</strong> اصلاح رایگان</span>
					</div>
				</div>

				<div class="devco-home__split-copy">
					<span class="devco-home__eyebrow">دوخت اختصاصی</span>
					<h2 class="devco-home__title">اندازه‌های شما، الگوی ما</h2>
					<p class="devco-home__desc">
						با نقشه‌ی بدن آنلاین، جای هر اندازه را دقیق می‌بینید. نتیجه یک الگوی شخصی است
						که در کارگاه دیوکو برش می‌خورد و پیش از ارسال پرو می‌شود.
					</p>

					<ul class="devco-home__steps">
						<?php foreach ( $steps as $index => $step ) : ?>
							<li>
								<span class="devco-home__step-num"><?php echo esc_html( devco_num( $index + 1 ) ); ?></span>
								<span><?php echo esc_html( $step ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="devco-home__split-actions">
						<a class="devco-btn devco-btn--primary" href="<?php echo esc_url( devco_dashboard_url( 'tailoring' ) ); ?>">
							<?php devco_icon_e( 'scissors', array( 'size' => 17 ) ); ?>
							ثبت سفارش دوخت
						</a>
						<?php if ( devco_opt_bool( 'fitting.enabled', true ) ) : ?>
							<button type="button" class="devco-btn devco-btn--ghost js-devco-fitting-open" data-fitting>
								<?php devco_icon_e( 'camera', array( 'size' => 17 ) ); ?>
								اتاق پرو مجازی
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Feature cards.
 */
function devco_home_section_features() {
	$features = array(
		array( 'ruler', 'راهنمای سایز دقیق', 'هر محصول جدول اندازه و توضیح پارچه دارد؛ حدس نزنید.' ),
		array( 'refresh', 'تعویض آسان', 'سایز نخورد؟ تعویض یا بازگشت وجه تا ۷ روز.' ),
		array( 'truck', 'ارسال رایگان', 'برای سفارش‌های بالای ' . devco_price( 2000000 ) . '.' ),
		array( 'sparkle', 'پارچه‌ی دست‌چین', 'کتان، پشم و ابریشم با شناسنامه‌ی پارچه.' ),
	);
	?>
	<section class="devco-home__section devco-home__features">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<ul class="devco-home__feature-grid">
				<?php foreach ( $features as $feature ) : ?>
					<li class="devco-home__feature">
						<span class="devco-home__feature-icon"><?php devco_icon_e( $feature[0], array( 'size' => 22 ) ); ?></span>
						<h3><?php echo esc_html( $feature[1] ); ?></h3>
						<p><?php echo wp_kses_post( $feature[2] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Testimonials.
 */
function devco_home_section_testimonials() {
	$items = array(
		array( 'سارا م.', 'مانتو کیمونو', 'پارچه دقیقاً همان رنگی بود که در سایت دیدم و سایز کاملاً اندازه‌ام شد.', 5 ),
		array( 'نگار ک.', 'کت بلند پشمی', 'برای اولین بار لباسی خریدم که آستینش بلند نیست! راهنمای سایز واقعاً کار می‌کند.', 5 ),
		array( 'امیر ر.', 'ست سفارشی', 'اندازه‌گیری آنلاین ساده بود و سفارش سه روزه رسید. کیفیت دوخت بالاست.', 4 ),
	);
	?>
	<section class="devco-home__section devco-home__quotes">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php devco_home_heading( 'تجربه‌ی مشتریان', 'چیزی که می‌گویند', '' ); ?>

			<ul class="devco-home__quote-grid">
				<?php foreach ( $items as $item ) : ?>
					<li class="devco-home__quote">
						<div class="devco-home__quote-stars"><?php echo devco_stars( (float) $item[3] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<p><?php echo esc_html( $item[2] ); ?></p>
						<footer>
							<span class="devco-home__quote-avatar"><?php echo esc_html( mb_substr( $item[0], 0, 1 ) ); ?></span>
							<span><strong><?php echo esc_html( $item[0] ); ?></strong><small><?php echo esc_html( $item[1] ); ?></small></span>
							<?php devco_icon_e( 'check-badge', array( 'size' => 16 ) ); ?>
						</footer>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php
}

/**
 * Stats + newsletter call to action.
 */
function devco_home_section_cta() {
	?>
	<section class="devco-home__section devco-home__cta">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-home__cta-card">
				<span class="devco-home__cta-glow" aria-hidden="true"></span>
				<div class="devco-home__cta-copy">
					<span class="devco-home__eyebrow devco-home__eyebrow--light">باشگاه دیوکو</span>
					<h2>اولین نفر از کالکشن‌ها باخبر شوید</h2>
					<p>هفته‌ای یک ایمیل: کالکشن تازه، راهنمای استایل و کد تخفیف اختصاصی.</p>
				</div>

				<form class="devco-home__cta-form" data-newsletter>
					<label class="screen-reader-text" for="devco-home-news">ایمیل شما</label>
					<input type="email" id="devco-home-news" name="email" placeholder="ایمیل شما" required>
					<button type="submit" class="devco-btn devco-btn--primary">
						<?php devco_icon_e( 'mail', array( 'size' => 17 ) ); ?>
						عضویت
					</button>
					<span class="devco-home__cta-note" data-newsletter-message role="status"></span>
				</form>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Latest blog posts.
 */
function devco_home_section_blog() {
	$posts = get_posts(
		array(
			'numberposts'      => 3,
			'post_status'      => 'publish',
			'suppress_filters' => false,
		)
	);

	if ( ! $posts ) {
		return;
	}
	?>
	<section class="devco-home__section devco-home__blog">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php
			devco_home_heading(
				'مجله دیوکو',
				'راهنمای پوشیدن و دوختن',
				'',
				array(
					'url'   => get_permalink( get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : home_url( '/' ),
					'label' => 'همه مقاله‌ها',
				)
			);
			?>

			<div class="devco-home__blog-grid">
				<?php
				foreach ( $posts as $post ) :
					setup_postdata( $post );
					?>
					<article class="devco-home__post">
						<a class="devco-home__post-media" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'devco-card', array( 'loading' => 'lazy' ) ); ?>
							<?php else : ?>
								<span class="devco-home__post-ph"><?php devco_icon_e( 'sparkle', array( 'size' => 30 ) ); ?></span>
							<?php endif; ?>
						</a>
						<div class="devco-home__post-body">
							<span class="devco-home__post-date"><?php echo esc_html( get_the_date() ); ?></span>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( devco_trim( get_the_excerpt(), 16 ) ); ?></p>
						</div>
					</article>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Print the whole front page.
 */
function devco_home_page_content() {
	/**
	 * Fires before the front page sections.
	 */
	do_action( 'devco_home_before' );

	devco_home_section_hero();
	devco_home_section_trust();
	devco_home_section_categories();
	devco_home_section_products();
	devco_home_section_editorial();
	devco_home_section_features();
	devco_home_section_testimonials();
	devco_home_section_cta();
	devco_home_section_blog();

	/**
	 * Fires after the front page sections.
	 */
	do_action( 'devco_home_after' );
}
