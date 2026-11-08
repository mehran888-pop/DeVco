<?php
/**
 * Content page blocks: page hero, about us, contact us and product detail.
 *
 * The templates in /templates and the static demo in /preview/demo share the
 * exact same markup contract (`.devco-page-hero`, `.devco-about__*`,
 * `.devco-contact__*`, `.devco-pd__*`) so what you see in the demo is what the
 * theme renders inside WordPress.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * 1. Page hero + breadcrumb
 * ---------------------------------------------------------------------- */

/**
 * Breadcrumb list.
 *
 * @param array $items List of array( url, label ). Empty url = current page.
 * @return void
 */
function devco_page_breadcrumb( $items = array() ) {
	if ( ! $items ) {
		$items = array(
			array( home_url( '/' ), 'خانه' ),
			array( '', get_the_title() ),
		);
	}

	echo '<ol class="devco-breadcrumb">';

	foreach ( $items as $item ) {
		$url   = isset( $item[0] ) ? $item[0] : '';
		$label = isset( $item[1] ) ? $item[1] : '';

		if ( ! $label ) {
			continue;
		}

		echo '<li>';
		echo $url
			? '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'
			: '<span aria-current="page">' . esc_html( $label ) . '</span>';
		echo '</li>';
	}

	echo '</ol>';
}

/**
 * Page hero with breadcrumb, eyebrow, title and lead.
 *
 * @param array $args eyebrow, title, lead, crumbs.
 * @return void
 */
function devco_page_hero( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow' => '',
			'title'   => get_the_title(),
			'lead'    => '',
			'crumbs'  => array(),
		)
	);

	echo '<section class="devco-page-hero">';
	echo '<div class="' . esc_attr( devco_container_class() ) . '">';
	echo '<div class="devco-page-hero__inner">';

	devco_page_breadcrumb( $args['crumbs'] );

	if ( $args['eyebrow'] ) {
		echo '<span class="devco-page-hero__eyebrow">' . devco_icon( 'sparkle', array( 'size' => 14 ) ) . esc_html( $args['eyebrow'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '<h1 class="devco-page-hero__title">' . esc_html( $args['title'] ) . '</h1>';

	if ( $args['lead'] ) {
		echo '<p class="devco-page-hero__lead">' . esc_html( $args['lead'] ) . '</p>';
	}

	echo '</div></div></section>';
}

/**
 * Closing call to action band.
 *
 * @param array $args title, text, primary, secondary.
 * @return void
 */
function devco_page_cta( $args = array() ) {
	$shop   = devco_is_woo() ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
	$tailor = absint( devco_opt( 'tailoring.page_id', 0 ) );

	$args = wp_parse_args(
		$args,
		array(
			'title'     => 'می‌خواهید لباس روی اندام خودتان دوخته شود؟',
			'text'      => 'اندازه‌هایتان را ثبت کنید؛ خیاط دیوکو الگوی اختصاصی شما را آماده می‌کند.',
			'primary'   => array( $tailor ? get_permalink( $tailor ) : home_url( '/tailoring/' ), 'شروع سفارش دوخت' ),
			'secondary' => array( $shop, 'دیدن فروشگاه' ),
		)
	);

	echo '<div class="devco-page-cta">';
	echo '<div><h2>' . esc_html( $args['title'] ) . '</h2><p>' . esc_html( $args['text'] ) . '</p></div>';
	echo '<div class="devco-page-cta__actions">';
	printf(
		'<a class="devco-btn devco-btn--accent" href="%1$s">%2$s %3$s</a>',
		esc_url( $args['primary'][0] ),
		devco_icon( 'scissors', array( 'size' => 17 ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $args['primary'][1] )
	);
	printf(
		'<a class="devco-btn devco-btn--ghost" href="%1$s">%2$s</a>',
		esc_url( $args['secondary'][0] ),
		esc_html( $args['secondary'][1] )
	);
	echo '</div></div>';
}

/* -------------------------------------------------------------------------
 * 2. About us
 * ---------------------------------------------------------------------- */

/**
 * The about page sections (used when the page has no editor content).
 *
 * @return void
 */
function devco_about_sections() {
	$shop   = devco_is_woo() ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop/' );
	$tailor = absint( devco_opt( 'tailoring.page_id', 0 ) );

	$stats = apply_filters(
		'devco_about_stats',
		array(
			array( '۱۲ هزار+', 'مشتری راضی' ),
			array( '۹ سال', 'تجربه دوخت' ),
			array( '۴۸ ساعت', 'ارسال سریع' ),
			array( '۴.۹ / ۵', 'امتیاز مشتریان' ),
		)
	);

	$values = apply_filters(
		'devco_about_values',
		array(
			array( 'scissors', 'دوخت اختصاصی', 'هر سفارش با الگوی شخصی و کنترل کیفیت سه‌مرحله‌ای دوخته می‌شود؛ نه تولید انبوه.' ),
			array( 'tshirt', 'پارچه‌های طبیعی', 'پنبه، کتان، ابریشم و پشم از تأمین‌کنندگان شناخته‌شده با تست رنگ و آب‌رفت.' ),
			array( 'truck', 'ارسال و بازگشت آسان', 'ارسال ۲۴ ساعته در تهران و ۷ روز مهلت بازگشت بدون قید و شرط.' ),
		)
	);

	$timeline = apply_filters(
		'devco_about_timeline',
		array(
			array( '۱۳۹۵', 'شروع با یک اتاق خیاطی', 'دو چرخ خیاطی، یک مانکن و سفارش‌های کوچک محلی.' ),
			array( '۱۳۹۸', 'راه‌اندازی اتاق پرو آنلاین', 'اولین نسخه اندازه‌گیری تصویری برای سفارش بدون حضور.' ),
			array( '۱۴۰۱', 'کارگاه بزرگ و تیم دوخت', 'تیم ۱۴ نفره دوخت، الگوسازی و کنترل کیفیت.' ),
			array( '۱۴۰۳', 'کالکشن‌های فصلی و ارسال کشوری', 'ارسال به تمام ایران و باشگاه مشتریان دیوکو.' ),
		)
	);

	$team = apply_filters(
		'devco_about_team',
		array(
			array( 'سارا دیوکو', 'بنیان‌گذار و مدیر خلاق', 'س' ),
			array( 'مهدی راستین', 'سرپرست الگوسازی', 'م' ),
			array( 'نگار پارسا', 'مشاور استایل', 'ن' ),
			array( 'آرش کاویان', 'مدیر کارگاه دوخت', 'آ' ),
		)
	);
	?>
	<section class="devco-page-section devco-page-section--tight">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-about__split">
				<div class="devco-about__copy">
					<div class="devco-page-head">
						<span class="devco-page-head__eyebrow">داستان دیوکو</span>
						<h2>از یک اتاق خیاطی کوچک تا کالکشن‌های فصلی</h2>
					</div>
					<p>دیوکو با یک باور ساده شروع شد: <strong>لباس باید اندازه‌ی خودِ آدم باشد، نه اندازه‌ی قالب.</strong> برای همین از روز اول، الگوی هر سفارش را برای اندام همان مشتری می‌کشیم و پارچه را پیش از برش، تست می‌کنیم.</p>
					<p>امروز تیم ما از الگوساز، خیاط، کنترل کیفیت و مشاور استایل تشکیل شده است. هر سفارش سه مرحله کنترل کیفیت دارد: دوخت، اتو و پرو نهایی. اگر سفارشی مطابق انتظار شما نبود، تا ۷ روز بدون پرسش برمی‌گردانیم.</p>
					<p>اتاق پرو آنلاین دیوکو هم برای همین ساخته شد؛ می‌توانید پیش از سفارش، اندازه‌ها را ثبت کنید و لباس را روی اندام خودتان ببینید.</p>
					<div class="devco-about__actions">
						<a class="devco-btn devco-btn--primary devco-btn--sm" href="<?php echo esc_url( $shop ); ?>"><?php devco_icon_e( 'bag', array( 'size' => 17 ) ); ?> دیدن کالکشن</a>
						<?php if ( $tailor ) : ?>
							<a class="devco-btn devco-btn--ghost devco-btn--sm" href="<?php echo esc_url( get_permalink( $tailor ) ); ?>"><?php devco_icon_e( 'scissors', array( 'size' => 17 ) ); ?> سفارش دوخت</a>
						<?php endif; ?>
					</div>
				</div>

				<figure class="devco-about__media">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'large', array( 'class' => 'devco-about__img' ) );
					} else {
						devco_home_art( 34, 'scissors', 'کارگاه دیوکو' );
					}
					?>
					<figcaption class="devco-about__chip">
						<strong>۹ سال تجربه</strong>
						<span>دوخت اختصاصی در تهران</span>
					</figcaption>
				</figure>
			</div>

			<ul class="devco-about__stats">
				<?php foreach ( $stats as $stat ) : ?>
					<li class="devco-stat"><strong><?php echo esc_html( $stat[0] ); ?></strong><span><?php echo esc_html( $stat[1] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="devco-page-section devco-page-section--alt">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-page-head">
				<span class="devco-page-head__eyebrow">اصول ما</span>
				<h2>سه چیزی که هرگز از آن‌ها نمی‌گذریم</h2>
			</div>
			<div class="devco-values">
				<?php foreach ( $values as $value ) : ?>
					<article class="devco-value">
						<span class="devco-value__icon"><?php devco_icon_e( $value[0], array( 'size' => 20 ) ); ?></span>
						<h3><?php echo esc_html( $value[1] ); ?></h3>
						<p><?php echo esc_html( $value[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="devco-page-head" style="margin-top:clamp(24px,3.4vw,40px)">
				<span class="devco-page-head__eyebrow">مسیر ما</span>
				<h2>از کجا شروع کردیم</h2>
			</div>
			<ul class="devco-timeline">
				<?php foreach ( $timeline as $row ) : ?>
					<li><b><?php echo esc_html( $row[0] ); ?></b><p><strong><?php echo esc_html( $row[1] ); ?></strong> — <?php echo esc_html( $row[2] ); ?></p></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="devco-page-section">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-page-head">
				<span class="devco-page-head__eyebrow">تیم دیوکو</span>
				<h2>آدم‌هایی که لباس شما را می‌دوزند</h2>
				<p>از الگوسازی تا پرو نهایی، هر سفارش از دست یک تیم مشخص می‌گذرد.</p>
			</div>
			<div class="devco-team">
				<?php foreach ( $team as $member ) : ?>
					<article class="devco-team__card">
						<span class="devco-team__avatar" aria-hidden="true"><?php echo esc_html( $member[2] ); ?></span>
						<strong><?php echo esc_html( $member[0] ); ?></strong>
						<small><?php echo esc_html( $member[1] ); ?></small>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="devco-page-section devco-page-section--tight">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php devco_page_cta(); ?>
		</div>
	</section>
	<?php
}

/* -------------------------------------------------------------------------
 * 3. Contact us
 * ---------------------------------------------------------------------- */

/**
 * Inbox for the contact form.
 *
 * @return string
 */
function devco_contact_email() {
	$email = sanitize_email( (string) devco_opt( 'general.email', '' ) );

	return is_email( $email ) ? $email : (string) get_option( 'admin_email' );
}

/**
 * Human readable labels.
 *
 * @return array
 */
function devco_contact_subjects() {
	return apply_filters(
		'devco_contact_subjects',
		array(
			'order'    => 'سفارش و پیگیری ارسال',
			'tailor'   => 'سفارش دوخت اختصاصی',
			'return'   => 'مرجوعی و تعویض',
			'style'    => 'مشاوره استایل',
			'partner'  => 'همکاری و عمده‌فروشی',
			'other'    => 'سایر موارد',
		)
	);
}

/**
 * Status of the last submission.
 *
 * @return string
 */
function devco_contact_status() {
	$status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return in_array( $status, array( 'sent', 'error', 'invalid' ), true ) ? $status : '';
}

/**
 * One form field.
 *
 * @param array $args name, label, type, placeholder, required, options, rows.
 * @return void
 */
function devco_contact_field( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'name'        => '',
			'label'       => '',
			'type'        => 'text',
			'placeholder' => '',
			'required'    => false,
			'options'     => array(),
			'rows'        => 5,
			'value'       => '',
		)
	);

	$id = 'devco-contact-' . $args['name'];

	echo '<div class="devco-contact__field">';
	printf( '<label for="%1$s">%2$s%3$s</label>', esc_attr( $id ), esc_html( $args['label'] ), $args['required'] ? ' <span aria-hidden="true">*</span>' : '' );

	if ( 'textarea' === $args['type'] ) {
		printf(
			'<textarea id="%1$s" name="%2$s" rows="%3$d" placeholder="%4$s"%5$s></textarea>',
			esc_attr( $id ),
			esc_attr( $args['name'] ),
			(int) $args['rows'],
			esc_attr( $args['placeholder'] ),
			$args['required'] ? ' required' : ''
		);
	} elseif ( 'select' === $args['type'] ) {
		printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $args['name'] ) );
		foreach ( $args['options'] as $value => $label ) {
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $value ), esc_html( $label ) );
		}
		echo '</select>';
	} else {
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" placeholder="%4$s" value="%5$s"%6$s />',
			esc_attr( $args['type'] ),
			esc_attr( $id ),
			esc_attr( $args['name'] ),
			esc_attr( $args['placeholder'] ),
			esc_attr( $args['value'] ),
			$args['required'] ? ' required' : ''
		);
	}

	echo '</div>';
}

/**
 * Contact form card.
 *
 * @return void
 */
function devco_contact_form() {
	$status = devco_contact_status();
	?>
	<div class="devco-contact__card" id="devco-contact-form">
		<div class="devco-contact__head">
			<h2>پیام خود را بنویسید</h2>
			<p>در ساعات کاری حداکثر تا ۴ ساعت پاسخ می‌دهیم. برای پیگیری سفارش، شماره سفارش را در متن پیام بنویسید.</p>
		</div>

		<?php if ( 'sent' === $status ) : ?>
			<p class="devco-contact__alert is-success" role="status"><?php devco_icon_e( 'check', array( 'size' => 17 ) ); ?> پیام شما ثبت شد؛ به‌زودی تماس می‌گیریم.</p>
		<?php elseif ( 'error' === $status ) : ?>
			<p class="devco-contact__alert is-error" role="alert"><?php devco_icon_e( 'close', array( 'size' => 17 ) ); ?> ارسال پیام انجام نشد. لطفاً فیلدها را کامل کنید یا با ما تماس بگیرید.</p>
		<?php elseif ( 'invalid' === $status ) : ?>
			<p class="devco-contact__alert is-error" role="alert"><?php devco_icon_e( 'close', array( 'size' => 17 ) ); ?> نشست شما منقضی شده است؛ صفحه را دوباره بارگذاری کنید.</p>
		<?php endif; ?>

		<form class="devco-contact__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="devco_contact" />
			<?php wp_nonce_field( 'devco_contact', 'devco_contact_nonce' ); ?>

			<div class="devco-contact__row">
				<?php
				devco_contact_field(
					array(
						'name'        => 'name',
						'label'       => 'نام و نام خانوادگی',
						'placeholder' => 'مثلاً سارا محمدی',
						'required'    => true,
					)
				);
				devco_contact_field(
					array(
						'name'        => 'email',
						'label'       => 'ایمیل',
						'type'        => 'email',
						'placeholder' => 'you@example.com',
						'required'    => true,
					)
				);
				?>
			</div>

			<div class="devco-contact__row">
				<?php
				devco_contact_field(
					array(
						'name'        => 'phone',
						'label'       => 'شماره تماس (اختیاری)',
						'type'        => 'tel',
						'placeholder' => '۰۹۱۲۳۴۵۶۷۸۹',
					)
				);
				devco_contact_field(
					array(
						'name'    => 'subject',
						'label'   => 'موضوع',
						'type'    => 'select',
						'options' => devco_contact_subjects(),
					)
				);
				?>
			</div>

			<?php
			devco_contact_field(
				array(
					'name'        => 'message',
					'label'       => 'متن پیام',
					'type'        => 'textarea',
					'placeholder' => 'چه کمکی می‌توانیم بکنیم؟',
					'required'    => true,
				)
			);
			?>

			<div class="devco-contact__hp" aria-hidden="true">
				<label for="devco-contact-hp">وبسایت</label>
				<input type="text" id="devco-contact-hp" name="devco_hp" tabindex="-1" autocomplete="off" />
			</div>

			<div class="devco-contact__actions">
				<button type="submit" class="devco-btn devco-btn--primary"><?php devco_icon_e( 'mail', array( 'size' => 17 ) ); ?> ارسال پیام</button>
				<p class="devco-contact__note">با ارسال این فرم، با تماس تیم پشتیبانی دیوکو موافقت می‌کنید.</p>
			</div>
		</form>
	</div>
	<?php
}

/**
 * Contact details + map + socials.
 *
 * @return void
 */
function devco_contact_details() {
	$phone   = (string) devco_opt( 'general.phone', '' );
	$address = (string) devco_opt( 'general.address', '' );
	$hours   = (string) devco_opt( 'general.working_hours', '' );
	$email   = devco_contact_email();
	$map     = (string) devco_opt( 'general.map_embed', '' );

	$rows = array();

	if ( $phone ) {
		$rows[] = array( 'phone', 'تلفن', '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>' );
	}

	$rows[] = array( 'mail', 'ایمیل', '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' );

	if ( $address ) {
		$rows[] = array( 'pin', 'آدرس', '<span>' . esc_html( $address ) . '</span>' );
	}

	if ( $hours ) {
		$rows[] = array( 'clock', 'ساعات پاسخگویی', '<span>' . esc_html( $hours ) . '</span>' );
	}

	$rows[] = array( 'support', 'پیام‌رسان', '<span>پاسخ سریع در واتساپ و تلگرام</span>' );
	?>
	<div class="devco-contact__card">
		<div class="devco-contact__head">
			<h2>راه‌های ارتباطی</h2>
			<p>اگر عجله دارید، تماس تلفنی سریع‌ترین راه است.</p>
		</div>

		<ul class="devco-contact__list">
			<?php foreach ( $rows as $row ) : ?>
				<li class="devco-contact__item">
					<span class="devco-contact__icon"><?php devco_icon_e( $row[0], array( 'size' => 18 ) ); ?></span>
					<span><b><?php echo esc_html( $row[1] ); ?></b><?php echo wp_kses_post( $row[2] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $map && filter_var( $map, FILTER_VALIDATE_URL ) ) : ?>
			<div class="devco-contact__map">
				<iframe src="<?php echo esc_url( $map ); ?>" title="نقشه موقعیت فروشگاه" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="border:0;width:100%;height:100%"></iframe>
			</div>
		<?php else : ?>
			<div class="devco-contact__map" role="img" aria-label="نقشه موقعیت فروشگاه">
				<span class="devco-contact__pin" aria-hidden="true"><?php devco_icon_e( 'pin', array( 'size' => 18 ) ); ?></span>
				<span class="devco-contact__tag"><?php echo esc_html( $address ? $address : 'تهران' ); ?></span>
			</div>
		<?php endif; ?>

		<?php devco_social_icons( array( 'class' => 'devco-contact__socials' ) ); ?>
	</div>
	<?php
}

/**
 * Contact page sections (used when the page has no editor content).
 *
 * @return void
 */
function devco_contact_sections() {
	$faq = apply_filters(
		'devco_contact_faq',
		array(
			array( 'سفارش دوخت چقدر زمان می‌برد؟', 'بعد از تأیید اندازه‌ها و پارچه، دوخت بین ۵ تا ۸ روز کاری زمان می‌برد. سفارش‌های فوری با هماهنگی تلفنی در ۴۸ ساعت آماده می‌شوند.' ),
			array( 'اگر اندازه نخورد چه می‌شود؟', 'تا ۷ روز پس از دریافت، اصلاح رایگان اندازه یا بازگشت کامل وجه انجام می‌شود. هزینه ارسال مرجوعی با ماست.' ),
			array( 'امکان پرداخت در محل هست؟', 'برای سفارش‌های تهران بله؛ برای شهرستان پرداخت آنلاین یا کارت‌به‌کارت است.' ),
			array( 'پارچه را خودم می‌توانم بیاورم؟', 'بله. در فرم سفارش دوخت گزینه «پارچه شخصی» را انتخاب کنید و متراژ لازم را با پشتیبانی هماهنگ کنید.' ),
		)
	);
	?>
	<section class="devco-page-section devco-page-section--tight">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-contact__grid">
				<?php devco_contact_form(); ?>
				<?php devco_contact_details(); ?>
			</div>
		</div>
	</section>

	<section class="devco-page-section devco-page-section--alt">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<div class="devco-page-head">
				<span class="devco-page-head__eyebrow">سوالات متداول</span>
				<h2>قبل از تماس، شاید پاسخ اینجا باشد</h2>
			</div>
			<div class="devco-faq">
				<?php foreach ( $faq as $item ) : ?>
					<details>
						<summary><?php echo esc_html( $item[0] ); ?></summary>
						<p><?php echo esc_html( $item[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="devco-page-section devco-page-section--tight">
		<div class="<?php echo esc_attr( devco_container_class() ); ?>">
			<?php devco_page_cta( array( 'title' => 'هنوز سوالی مانده؟', 'text' => 'تیم پشتیبانی همه‌روزه از ۹ تا ۱۸ پاسخ می‌دهد؛ حتی برای انتخاب سایز و پارچه.' ) ); ?>
		</div>
	</section>
	<?php
}

/**
 * Handle the contact form submission.
 *
 * @return void
 */
function devco_handle_contact_form() {
	$referer = wp_get_referer();
	$referer = $referer ? $referer : home_url( '/' );
	$referer = remove_query_arg( 'contact', $referer );

	$nonce = isset( $_POST['devco_contact_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['devco_contact_nonce'] ) ) : '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'devco_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'invalid', $referer ) . '#devco-contact-form' );
		exit;
	}

	// Honeypot: pretend everything went fine so bots do not retry.
	if ( ! empty( $_POST['devco_hp'] ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'sent', $referer ) . '#devco-contact-form' );
		exit;
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_key( wp_unslash( $_POST['subject'] ) ) : 'other';

	if ( ! $name || ! is_email( $email ) || ! $message ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $referer ) . '#devco-contact-form' );
		exit;
	}

	$subjects = devco_contact_subjects();
	$label    = isset( $subjects[ $subject ] ) ? $subjects[ $subject ] : $subjects['other'];

	$lines = array(
		'پیام تازه از فرم تماس سایت:',
		'',
		'نام: ' . $name,
		'ایمیل: ' . $email,
		'تلفن: ' . ( $phone ? $phone : '—' ),
		'موضوع: ' . $label,
		'',
		$message,
		'',
		'---',
		'صفحه: ' . esc_url_raw( $referer ),
		'زمان: ' . wp_date( 'Y-m-d H:i' ),
	);

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$sent = wp_mail(
		devco_contact_email(),
		sprintf( '[%s] پیام فرم تماس: %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $label ),
		implode( "\n", $lines ),
		$headers
	);

	/**
	 * Fires after a contact form submission.
	 *
	 * @param bool  $sent    Whether wp_mail accepted the message.
	 * @param array $payload Submitted values.
	 */
	do_action(
		'devco_contact_submitted',
		$sent,
		array(
			'name'    => $name,
			'email'   => $email,
			'phone'   => $phone,
			'subject' => $subject,
			'message' => $message,
		)
	);

	wp_safe_redirect( add_query_arg( 'contact', $sent ? 'sent' : 'error', $referer ) . '#devco-contact-form' );
	exit;
}
add_action( 'admin_post_devco_contact', 'devco_handle_contact_form' );
add_action( 'admin_post_nopriv_devco_contact', 'devco_handle_contact_form' );
