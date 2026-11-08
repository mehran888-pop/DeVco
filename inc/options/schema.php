<?php
/**
 * Theme options schema.
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

$fonts = array(
	'vazirmatn' => 'وزیرمتن (Vazirmatn)',
	'iransans'  => 'ایران‌سنس (IRANSansX)',
	'dana'      => 'دانا (Dana)',
	'shabnam'   => 'شبنم (Shabnam)',
	'yekanbakh' => 'یکان‌بخ (Yekan Bakh)',
	'estedad'   => 'استعداد (Estedad)',
	'system'    => 'فونت سیستم (پیش‌فرض مرورگر)',
	'custom'    => 'فونت اختصاصی (آپلود/آدرس)',
);

$slot_components = array(
	'logo'          => 'لوگو',
	'menu'          => 'منوی اصلی',
	'category_menu' => 'منوی دسته‌بندی',
	'search'        => 'جستجو',
	'account'       => 'حساب کاربری / ورود',
	'cart'          => 'سبد خرید',
	'wishlist'      => 'علاقه‌مندی‌ها',
	'socials'       => 'شبکه‌های اجتماعی',
	'phone'         => 'شماره تماس',
	'text'          => 'متن کوتاه',
	'topbar_menu'   => 'منوی نوار بالا',
	'widget_1'      => 'ابزارک جایگاه ۱',
	'widget_2'      => 'ابزارک جایگاه ۲',
	'widget_3'      => 'ابزارک جایگاه ۳',
	'divider'       => 'جداکننده',
);

$social_fields = array();

foreach ( devco_social_networks() as $key => $network ) {
	$social_fields[] = array(
		'id'          => 'social.' . $key,
		'type'        => 'url',
		'title'       => $network['label'],
		'placeholder' => 'https://',
		'default'     => '',
	);
}

$sms_providers = array(
	''             => '— غیرفعال —',
	'melipayamak'  => 'ملی پیامک (melipayamak)',
	'sms_ir'       => 'sms.ir',
	'ippanel'      => 'آی‌پی پنل (ippanel)',
	'freesms'      => 'رایگان اس‌ام‌اس (freesms)',
	'custom'       => 'سرویس اختصاصی (HTTP)',
);

return array(

	/* ---------------------------------------------------------------------- */
	'general'        => array(
		'title'       => 'عمومی و چیدمان',
		'icon'        => 'dashboard',
		'description' => 'عرض سایت، ظاهر کلی و رفتار پایه قالب را از این بخش کنترل کنید.',
		'sections'    => array(
			array(
				'title'  => 'عرض و چیدمان سایت',
				'fields' => array(
					array(
						'id'      => 'layout.site_width',
						'type'    => 'range',
						'title'   => 'عرض محتوا (پیکسل)',
						'min'     => 960,
						'max'     => 1920,
						'step'    => 10,
						'unit'    => 'px',
						'default' => 1280,
					),
					array(
						'id'      => 'layout.container',
						'type'    => 'radio',
						'title'   => 'حالت عرض',
						'options' => array(
							'boxed' => 'باکس‌شده (مرکز صفحه)',
							'full'  => 'تمام عرض',
						),
						'default' => 'boxed',
					),
					array(
						'id'      => 'layout.sidebar_width',
						'type'    => 'range',
						'title'   => 'عرض نوار کناری',
						'min'     => 220,
						'max'     => 420,
						'step'    => 5,
						'unit'    => 'px',
						'default' => 280,
					),
					array(
						'id'      => 'layout.radius',
						'type'    => 'range',
						'title'   => 'گردی گوشه‌ها',
						'min'     => 0,
						'max'     => 40,
						'step'    => 1,
						'unit'    => 'px',
						'default' => 16,
					),
					array(
						'id'      => 'layout.persian_digits',
						'type'    => 'checkbox',
						'title'   => 'نمایش اعداد به فارسی',
						'default' => 1,
					),
					array(
						'id'      => 'layout.preloader',
						'type'    => 'checkbox',
						'title'   => 'پیش‌بارگذار صفحه',
						'default' => 0,
					),
					array(
						'id'      => 'layout.back_to_top',
						'type'    => 'checkbox',
						'title'   => 'دکمه بازگشت به بالا',
						'default' => 1,
					),
					array(
						'id'      => 'layout.page_header',
						'type'    => 'select',
						'title'   => 'سربرگ صفحات داخلی',
						'options' => array(
							'minimal' => 'مینیمال (عنوان + مسیر)',
							'image'   => 'با تصویر پس‌زمینه',
							'hidden'  => 'بدون سربرگ',
						),
						'default' => 'minimal',
					),
					array(
						'id'    => 'layout.page_header_image',
						'type'  => 'media',
						'title' => 'تصویر سربرگ صفحات',
					),
				),
			),
			array(
				'title'  => 'شبکه‌های اجتماعی',
				'fields' => $social_fields,
			),
			array(
				'title'  => 'اطلاعات فروشگاه',
				'fields' => array(
					array(
						'id'          => 'general.phone',
						'type'        => 'text',
						'title'       => 'شماره تماس',
						'placeholder' => '021-12345678',
						'default'     => '',
					),
					array(
						'id'          => 'general.email',
						'type'        => 'text',
						'title'       => 'ایمیل تماس',
						'description' => 'پیام‌های فرم «تماس با ما» به این ایمیل ارسال می‌شود. خالی = ایمیل مدیر سایت.',
						'placeholder' => 'hello@example.com',
						'default'     => '',
					),
					array(
						'id'      => 'general.address',
						'type'    => 'textarea',
						'title'   => 'آدرس',
						'default' => '',
					),
					array(
						'id'      => 'general.working_hours',
						'type'    => 'text',
						'title'   => 'ساعات کاری',
						'default' => 'شنبه تا پنجشنبه ۹ الی ۱۸',
					),
					array(
						'id'          => 'general.map_embed',
						'type'        => 'text',
						'title'       => 'نشانی نقشه',
						'description' => 'آدرس embed نقشه (مثلاً لینک Google Maps یا نشان). خالی = نقشه طرح‌واره‌ی تم نمایش داده می‌شود.',
						'placeholder' => 'https://maps.google.com/maps?q=...&output=embed',
						'default'     => '',
					),
					array(
						'id'      => 'general.about_text',
						'type'    => 'textarea',
						'title'   => 'متن کوتاه درباره فروشگاه',
						'default' => 'دیوکو؛ پوشاک دوخت سفارشی با پارچه‌های ممتاز و اندازه‌های دقیق.',
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'colors'         => array(
		'title'       => 'رنگ‌ها و فونت‌ها',
		'icon'        => 'sparkle',
		'description' => 'هویت بصری برند شما: پالت رنگ، تایپوگرافی و فاصله‌ها.',
		'sections'    => array(
			array(
				'title'  => 'پالت رنگ',
				'fields' => array(
					array(
						'id'      => 'colors.primary',
						'type'    => 'color',
						'title'   => 'رنگ اصلی برند',
						'default' => '#111827',
					),
					array(
						'id'      => 'colors.accent',
						'type'    => 'color',
						'title'   => 'رنگ تاکیدی (CTA)',
						'default' => '#c8a96a',
					),
					array(
						'id'      => 'colors.accent_dark',
						'type'    => 'color',
						'title'   => 'رنگ تاکیدی تیره',
						'default' => '#a8894c',
					),
					array(
						'id'      => 'colors.text',
						'type'    => 'color',
						'title'   => 'رنگ متن',
						'default' => '#1f2937',
					),
					array(
						'id'      => 'colors.muted',
						'type'    => 'color',
						'title'   => 'رنگ متن کم‌رنگ',
						'default' => '#6b7280',
					),
					array(
						'id'      => 'colors.border',
						'type'    => 'color',
						'title'   => 'رنگ خطوط و حاشیه',
						'default' => '#e8e6e1',
					),
					array(
						'id'      => 'colors.surface',
						'type'    => 'color',
						'title'   => 'رنگ کارت‌ها',
						'default' => '#ffffff',
					),
					array(
						'id'      => 'colors.background',
						'type'    => 'color',
						'title'   => 'رنگ پس‌زمینه سایت',
						'default' => '#faf9f7',
					),
					array(
						'id'      => 'colors.header_bg',
						'type'    => 'color',
						'title'   => 'پس‌زمینه هدر',
						'default' => '#ffffff',
					),
					array(
						'id'      => 'colors.footer_bg',
						'type'    => 'color',
						'title'   => 'پس‌زمینه فوتر',
						'default' => '#111827',
					),
					array(
						'id'      => 'colors.success',
						'type'    => 'color',
						'title'   => 'رنگ موفقیت',
						'default' => '#10b981',
					),
					array(
						'id'      => 'colors.danger',
						'type'    => 'color',
						'title'   => 'رنگ هشدار/فروش ویژه',
						'default' => '#e11d48',
					),
				),
			),
			array(
				'title'  => 'تایپوگرافی',
				'fields' => array(
					array(
						'id'      => 'fonts.body',
						'type'    => 'select',
						'title'   => 'فونت متن',
						'options' => $fonts,
						'default' => 'vazirmatn',
					),
					array(
						'id'      => 'fonts.heading',
						'type'    => 'select',
						'title'   => 'فونت تیترها',
						'options' => $fonts,
						'default' => 'vazirmatn',
					),
					array(
						'id'      => 'fonts.body_size',
						'type'    => 'range',
						'title'   => 'اندازه پایه متن',
						'min'     => 12,
						'max'     => 20,
						'step'    => 0.5,
						'unit'    => 'px',
						'default' => 15,
					),
					array(
						'id'      => 'fonts.heading_weight',
						'type'    => 'select',
						'title'   => 'ضخامت تیترها',
						'options' => array(
							'500' => 'نیمه‌ضخیم',
							'600' => 'ضخیم',
							'700' => 'خیلی ضخیم',
							'800' => 'فوق ضخیم',
						),
						'default' => '700',
					),
					array(
						'id'          => 'fonts.custom_url',
						'type'        => 'url',
						'title'       => 'آدرس فایل فونت اختصاصی (woff2)',
						'description' => 'اگر «فونت اختصاصی» را انتخاب کردید، آدرس فایل woff2 را وارد کنید.',
						'placeholder' => 'https://example.com/font.woff2',
						'default'     => '',
					),
					array(
						'id'          => 'fonts.custom_family',
						'type'        => 'text',
						'title'       => 'نام خانواده فونت اختصاصی',
						'placeholder' => 'MyFont',
						'default'     => '',
					),
					array(
						'id'          => 'fonts.cdn_vazirmatn',
						'type'        => 'checkbox',
						'title'       => 'بارگذاری وزیرمتن از CDN',
						'description' => 'در صورت فعال بودن، فونت وزیرمتن از jsDelivr بارگذاری می‌شود.',
						'default'     => 0,
					),
				),
			),
			array(
				'title'  => 'ظاهر المان‌ها',
				'fields' => array(
					array(
						'id'      => 'colors.button_style',
						'type'    => 'radio',
						'title'   => 'استایل دکمه‌ها',
						'options' => array(
							'solid'  => 'توپر',
							'outline' => 'خطی',
							'soft'   => 'نرم (پس‌زمینه کم‌رنگ)',
							'pill'   => 'قرصی',
						),
						'default' => 'solid',
					),
					array(
						'id'      => 'colors.card_style',
						'type'    => 'radio',
						'title'   => 'استایل کارت‌ها',
						'options' => array(
							'flat'    => 'مسطح با حاشیه',
							'shadow'  => 'سایه ملایم',
							'glass'   => 'شیشه‌ای',
							'bordered' => 'قاب‌دار',
						),
						'default' => 'shadow',
					),
					array(
						'id'      => 'colors.dark_mode',
						'type'    => 'checkbox',
						'title'   => 'نمایش دکمه حالت شب',
						'default' => 1,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'header'         => array(
		'title'       => 'هدر و منو',
		'icon'        => 'menu',
		'description' => 'هدر را با سازنده اختصاصی یا با المنتور (رایگان) بسازید.',
		'sections'    => array(
			array(
				'title'  => 'منبع هدر',
				'fields' => array(
					array(
						'id'      => 'header_source',
						'type'    => 'radio',
						'title'   => 'روش ساخت هدر',
						'options' => array(
							'builder'  => 'سازنده هدر دیوکو (بدون نیاز به المنتور پرو)',
							'elementor' => 'الگوی المنتور (Header Template)',
						),
						'default' => 'builder',
					),
					array(
						'id'          => 'header.elementor_id',
						'type'        => 'page',
						'title'       => 'الگوی هدر المنتور',
						'description' => 'یک «قالب هدر» در المنتور بسازید (الگوها ← ذخیره قالب) و اینجا انتخاب کنید. با المنتور رایگان هم کار می‌کند.',
						'default'     => 0,
					),
					array(
						'id'      => 'header.sticky',
						'type'    => 'checkbox',
						'title'   => 'هدر چسبان (Sticky)',
						'default' => 1,
					),
					array(
						'id'      => 'header.transparent',
						'type'    => 'checkbox',
						'title'   => 'هدر شفاف در صفحه اصلی',
						'default' => 0,
					),
					array(
						'id'      => 'header.height',
						'type'    => 'range',
						'title'   => 'ارتفاع ردیف اصلی',
						'min'     => 56,
						'max'     => 140,
						'step'    => 2,
						'unit'    => 'px',
						'default' => 82,
					),
					array(
						'id'      => 'header.shadow',
						'type'    => 'checkbox',
						'title'   => 'سایه زیر هدر',
						'default' => 1,
					),
					array(
						'id'      => 'header.menu_layout',
						'type'    => 'select',
						'title'   => 'چیدمان منوی اصلی',
						'options' => array(
							'classic' => 'کلاسیک (لیست افقی)',
							'pill'    => 'قرصی با پس‌زمینه',
							'underline' => 'خط زیر آیتم فعال',
							'center'  => 'وسط‌چین',
						),
						'default' => 'classic',
					),
					array(
						'id'      => 'header.menu_mode',
						'type'    => 'select',
						'title'   => 'حالت نمایش زیرمنو',
						'options' => array(
							'dropdown' => 'کرکره‌ای ساده',
							'mega'     => 'مگا منو (چند ستونه)',
							'flyout'   => 'کشویی از کنار',
						),
						'default' => 'mega',
					),
					array(
						'id'      => 'header.mega_columns',
						'type'    => 'range',
						'title'   => 'تعداد ستون مگا منو',
						'min'     => 2,
						'max'     => 5,
						'step'    => 1,
						'default' => 4,
					),
					array(
						'id'      => 'header.cat_layout',
						'type'    => 'select',
						'title'   => 'چیدمان منوی دسته‌بندی',
						'options' => array(
							'vertical' => 'عمودی (ستون دسته‌ها)',
							'horizontal' => 'افقی (نوار دسته‌ها)',
							'dropdown' => 'دکمه کشویی',
						),
						'default' => 'dropdown',
					),
					array(
						'id'      => 'header.cat_icon',
						'type'    => 'icon',
						'title'   => 'آیکون منوی دسته‌بندی',
						'default' => 'categories',
					),
					array(
						'id'      => 'header.search_style',
						'type'    => 'select',
						'title'   => 'استایل جستجو',
						'options' => array(
							'inline' => 'کادر همیشه باز',
							'icon'   => 'آیکون بازشونده',
							'popup'  => 'پنجره جستجوی تمام‌صفحه',
						),
						'default' => 'inline',
					),
					array(
						'id'      => 'header.topbar_text',
						'type'    => 'text',
						'title'   => 'متن نوار بالا',
						'default' => 'ارسال رایگان برای خرید بالای ۲ میلیون تومان',
					),
					array(
						'id'      => 'header.enable_topbar',
						'type'    => 'checkbox',
						'title'   => 'نمایش نوار بالا',
						'default' => 1,
					),
				),
			),
			array(
				'title'       => 'چیدمان ردیف‌های هدر',
				'description' => 'برای هر ردیف، اجزای سمت راست، وسط و چپ را انتخاب کنید.',
				'collapsed'   => 1,
				'fields'      => array(
					array(
						'id'         => 'header.slots',
						'type'       => 'slot_matrix',
						'title'      => 'جایگاه اجزا',
						'rows'       => array(
							'top'    => 'نوار بالا',
							'main'   => 'ردیف اصلی',
							'bottom' => 'ردیف پایین',
						),
						'components' => $slot_components,
						'default'    => array(
							'top'    => array(
								'right'  => array( 'text' ),
								'center' => array(),
								'left'   => array( 'phone', 'socials' ),
							),
							'main'   => array(
								'right'  => array( 'logo' ),
								'center' => array( 'menu' ),
								'left'   => array( 'search', 'account', 'cart' ),
							),
							'bottom' => array(
								'right'  => array( 'category_menu' ),
								'center' => array(),
								'left'   => array( 'wishlist' ),
							),
						),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'footer'         => array(
		'title'       => 'فوتر',
		'icon'        => 'grid',
		'description' => 'فوتر را با ستون‌های ابزارک یا الگوی المنتور بسازید.',
		'sections'    => array(
			array(
				'title'  => 'ساختار فوتر',
				'fields' => array(
					array(
						'id'      => 'footer_source',
						'type'    => 'radio',
						'title'   => 'روش ساخت فوتر',
						'options' => array(
							'builder'   => 'سازنده فوتر دیوکو',
							'elementor' => 'الگوی المنتور (Footer Template)',
						),
						'default' => 'builder',
					),
					array(
						'id'      => 'footer.elementor_id',
						'type'    => 'page',
						'title'   => 'الگوی فوتر المنتور',
						'default' => 0,
					),
					array(
						'id'      => 'footer.columns',
						'type'    => 'range',
						'title'   => 'تعداد ستون‌ها',
						'min'     => 1,
						'max'     => 4,
						'step'    => 1,
						'default' => 4,
					),
					array(
						'id'      => 'footer.style',
						'type'    => 'select',
						'title'   => 'استایل فوتر',
						'options' => array(
							'dark'   => 'تیره',
							'light'  => 'روشن',
							'glass'  => 'شیشه‌ای',
						),
						'default' => 'dark',
					),
					array(
						'id'      => 'footer.newsletter',
						'type'    => 'checkbox',
						'title'   => 'نمایش خبرنامه',
						'default' => 1,
					),
					array(
						'id'      => 'footer.newsletter_title',
						'type'    => 'text',
						'title'   => 'عنوان خبرنامه',
						'default' => 'از تخفیف‌های ویژه باخبر شوید',
					),
					array(
						'id'      => 'footer.trust_badges',
						'type'    => 'checkbox',
						'title'   => 'نمایش نشان‌های اعتماد و نمادها',
						'default' => 1,
					),
					array(
						'id'      => 'footer.badge_images',
						'type'    => 'repeater',
						'title'   => 'نشان‌های اعتماد',
						'item_title' => 'نشان',
						'fields'  => array(
							array( 'id' => 'image', 'type' => 'media', 'title' => 'تصویر' ),
							array( 'id' => 'link', 'type' => 'text', 'title' => 'لینک' ),
						),
						'default' => array(),
					),
					array(
						'id'      => 'footer.copyright',
						'type'    => 'text',
						'title'   => 'متن کپی‌رایت',
						'default' => '© تمامی حقوق برای فروشگاه دیوکو محفوظ است.',
					),
					array(
						'id'      => 'footer.payment_images',
						'type'    => 'media',
						'title'   => 'تصویر نمادهای پرداخت',
						'default' => '',
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'shop'           => array(
		'title'       => 'صفحه فروشگاه',
		'icon'        => 'bag',
		'description' => 'چیدمان آرشیو محصولات، کارت محصول و فیلترها.',
		'sections'    => array(
			array(
				'title'  => 'چیدمان آرشیو',
				'fields' => array(
					array(
						'id'      => 'shop.columns',
						'type'    => 'radio',
						'title'   => 'تعداد ستون‌ها',
						'options' => array( '2' => '۲ ستون', '3' => '۳ ستون', '4' => '۴ ستون', '5' => '۵ ستون' ),
						'default' => '4',
					),
					array(
						'id'      => 'shop.sidebar',
						'type'    => 'checkbox',
						'title'   => 'نمایش نوار کناری',
						'default' => 1,
					),
					array(
						'id'      => 'shop.currency_label',
						'type'    => 'text',
						'title'   => 'واحد پول نمایشی',
						'desc'    => 'در کنار قیمت‌ها نشان داده می‌شود. مثال: تومان',
						'default' => 'تومان',
					),
					array(
						'id'      => 'shop.sidebar_position',
						'type'    => 'radio',
						'title'   => 'موقعیت نوار کناری',
						'options' => array( 'right' => 'سمت راست', 'left' => 'سمت چپ' ),
						'default' => 'right',
					),
					array(
						'id'      => 'shop.per_page',
						'type'    => 'number',
						'title'   => 'تعداد محصول در هر صفحه',
						'min'     => 4,
						'max'     => 60,
						'default' => 16,
					),
					array(
						'id'      => 'shop.filter_bar',
						'type'    => 'checkbox',
						'title'   => 'نوار فیلتر بالای محصولات',
						'default' => 1,
					),
					array(
						'id'      => 'shop.progress_bar',
						'type'    => 'checkbox',
						'title'   => 'نوار پیشرفت اسکرول',
						'default' => 0,
					),
					array(
						'id'      => 'shop.banner_id',
						'type'    => 'media',
						'title'   => 'بنر بالای فروشگاه',
						'default' => '',
					),
					array(
						'id'      => 'shop.title',
						'type'    => 'text',
						'title'   => 'عنوان صفحه فروشگاه',
						'default' => 'همه محصولات',
					),
					array(
						'id'      => 'shop.subtitle',
						'type'    => 'text',
						'title'   => 'زیرعنوان فروشگاه',
						'default' => 'کالکشن جدید پوشاک و اکسسوری',
					),
				),
			),
			array(
				'title'  => 'کارت محصول',
				'fields' => array(
					array(
						'id'      => 'card.style',
						'type'    => 'radio',
						'title'   => 'استایل کارت',
						'options' => array(
							'clean'   => 'تمیز (تصویر بزرگ)',
							'overlay' => 'همبرگری (اکشن روی تصویر)',
							'editorial' => 'ادیتوریال (متن زیر تصویر)',
							'glass'   => 'شیشه‌ای',
						),
						'default' => 'overlay',
					),
					array(
						'id'      => 'card.hover',
						'type'    => 'select',
						'title'   => 'افکت هاور تصویر',
						'options' => array(
							'zoom'    => 'زوم ملایم',
							'lift'    => 'بلند شدن کارت',
							'reveal'  => 'نمایش تصویر دوم',
							'tilt'    => 'چرخش سه‌بعدی',
							'none'    => 'بدون افکت',
						),
						'default' => 'zoom',
					),
					array(
						'id'      => 'card.ratio',
						'type'    => 'select',
						'title'   => 'نسبت تصویر',
						'options' => array(
							'1-1'  => 'مربع (۱:۱)',
							'3-4'  => 'پرتره (۳:۴)',
							'4-5'  => 'پرتره بلند (۴:۵)',
							'4-3'  => 'افقی (۴:۳)',
						),
						'default' => '3-4',
					),
					array(
						'id'      => 'card.quick_add',
						'type'    => 'checkbox',
						'title'   => 'دکمه افزودن سریع به سبد',
						'default' => 1,
					),
					array(
						'id'      => 'card.quick_view',
						'type'    => 'checkbox',
						'title'   => 'دکمه نمایش سریع',
						'default' => 1,
					),
					array(
						'id'      => 'card.fly_animation',
						'type'    => 'select',
						'title'   => 'افکت انتقال محصول به سبد',
						'options' => array(
							'fly'     => 'پرواز تصویر به سبد',
							'fly_burst' => 'پرواز + جرقه',
							'slide'   => 'کشویی از پایین',
							'none'    => 'بدون افکت',
						),
						'default' => 'fly_burst',
					),
					array(
						'id'      => 'card.show_rating',
						'type'    => 'checkbox',
						'title'   => 'نمایش امتیاز',
						'default' => 1,
					),
					array(
						'id'      => 'card.show_category',
						'type'    => 'checkbox',
						'title'   => 'نمایش دسته‌بندی',
						'default' => 1,
					),
					array(
						'id'      => 'card.badge_style',
						'type'    => 'select',
						'title'   => 'استایل برچسب تخفیف',
						'options' => array(
							'pill'   => 'قرصی',
							'square' => 'مربعی',
							'corner' => 'گوشه تصویر',
							'text'   => 'فقط متن',
						),
						'default' => 'pill',
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'product'        => array(
		'title'       => 'محصول تکی',
		'icon'        => 'tag',
		'description' => 'چیدمان صفحه محصول، گالری، خرید سریع و اتاق پرو.',
		'sections'    => array(
			array(
				'title'  => 'چیدمان صفحه محصول',
				'fields' => array(
					array(
						'id'      => 'product.layout',
						'type'    => 'image_select',
						'title'   => 'چیدمان صفحه',
						'options' => array(
							'split'    => array( 'label' => 'تصویر و اطلاعات کنار هم' ),
							'sticky'   => array( 'label' => 'اطلاعات چسبان هنگام اسکرول' ),
							'wide'     => array( 'label' => 'تصویر تمام‌عرض بالا' ),
							'minimal'  => array( 'label' => 'مینیمال با فوکوس روی خرید' ),
						),
						'default' => 'split',
					),
					array(
						'id'      => 'product.gallery',
						'type'    => 'select',
						'title'   => 'موقعیت گالری',
						'options' => array(
							'right'  => 'سمت راست',
							'left'   => 'سمت چپ',
							'bottom' => 'زیر تصویر (اسلایدر)',
						),
						'default' => 'right',
					),
					array(
						'id'      => 'product.thumb_position',
						'type'    => 'select',
						'title'   => 'محل تصاویر کوچک',
						'options' => array(
							'side'   => 'کنار تصویر اصلی',
							'bottom' => 'زیر تصویر اصلی',
							'hidden' => 'مخفی',
						),
						'default' => 'side',
					),
					array(
						'id'      => 'product.sticky_atc',
						'type'    => 'checkbox',
						'title'   => 'نوار خرید چسبان پایین صفحه',
						'default' => 1,
					),
					array(
						'id'      => 'product.tabs_style',
						'type'    => 'select',
						'title'   => 'استایل تب‌ها (توضیحات)',
						'options' => array(
							'accordion' => 'آکاردئونی',
							'tabs'      => 'تب کلاسیک',
							'full'      => 'محتوای کامل بدون تب',
						),
						'default' => 'accordion',
					),
					array(
						'id'      => 'product.trust_badges',
						'type'    => 'checkbox',
						'title'   => 'نمایش نشان‌های اعتماد',
						'default' => 1,
					),
					array(
						'id'      => 'product.trust_items',
						'type'    => 'repeater',
						'title'   => 'آیتم‌های اعتماد',
						'item_title' => 'آیتم',
						'fields'  => array(
							array( 'id' => 'icon', 'type' => 'icon', 'title' => 'آیکون' ),
							array( 'id' => 'title', 'type' => 'text', 'title' => 'عنوان' ),
							array( 'id' => 'text', 'type' => 'text', 'title' => 'توضیح' ),
						),
						'default' => array(
							array( 'icon' => 'truck', 'title' => 'ارسال سریع', 'text' => 'تهران ۲۴ ساعته' ),
							array( 'icon' => 'refresh', 'title' => '۷ روز ضمانت بازگشت', 'text' => 'بدون قید و شرط' ),
							array( 'icon' => 'shield', 'title' => 'ضمانت اصالت', 'text' => 'تولید اختصاصی' ),
						),
					),
					array(
						'id'      => 'product.related_count',
						'type'    => 'range',
						'title'   => 'تعداد محصولات مشابه',
						'min'     => 0,
						'max'     => 8,
						'step'    => 1,
						'default' => 4,
					),
					array(
						'id'      => 'product.size_chart',
						'type'    => 'checkbox',
						'title'   => 'نمایش راهنمای اندازه',
						'default' => 1,
					),
					array(
						'id'      => 'product.fitting_link',
						'type'    => 'checkbox',
						'title'   => 'دکمه اتاق پرو در صفحه محصول',
						'default' => 1,
					),
					array(
						'id'      => 'product.whatsapp_order',
						'type'    => 'checkbox',
						'title'   => 'دکمه سفارش در واتساپ',
						'default' => 1,
					),
					array(
						'id'      => 'product.reviews',
						'type'    => 'checkbox',
						'title'   => 'نمایش دیدگاه‌ها',
						'default' => 1,
					),
				),
			),
			array(
				'title'  => 'خرید سریع',
				'fields' => array(
					array(
						'id'      => 'product.buy_now',
						'type'    => 'checkbox',
						'title'   => 'دکمه «خرید فوری»',
						'default' => 1,
					),
					array(
						'id'      => 'product.buy_now_skip_cart',
						'type'    => 'checkbox',
						'title'   => 'پرش از سبد خرید (رفتن مستقیم به پرداخت)',
						'default' => 1,
					),
					array(
						'id'      => 'product.ajax_add',
						'type'    => 'checkbox',
						'title'   => 'افزودن به سبد بدون رفرش',
						'default' => 1,
					),
					array(
						'id'      => 'product.variation_swatches',
						'type'    => 'checkbox',
						'title'   => 'نمایش رنگ و سایز به صورت سواچ',
						'default' => 1,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'cart_checkout'  => array(
		'title'       => 'سبد خرید و پرداخت',
		'icon'        => 'credit-card',
		'description' => 'چیدمان سبد خرید، صورتحساب و نکات اعتماد.',
		'sections'    => array(
			array(
				'title'  => 'سبد خرید',
				'fields' => array(
					array(
						'id'      => 'cart.layout',
						'type'    => 'select',
						'title'   => 'چیدمان سبد خرید',
						'options' => array(
							'two_col' => 'جدول + خلاصه کنار هم',
							'stacked' => 'جدول بالا، خلاصه پایین',
							'minimal' => 'مینیمال تمام‌عرض',
						),
						'default' => 'two_col',
					),
					array(
						'id'      => 'cart.sticky_summary',
						'type'    => 'checkbox',
						'title'   => 'خلاصه سفارش چسبان',
						'default' => 1,
					),
					array(
						'id'      => 'cart.cross_sell',
						'type'    => 'checkbox',
						'title'   => 'نمایش پیشنهاد محصولات',
						'default' => 1,
					),
					array(
						'id'      => 'cart.empty_text',
						'type'    => 'textarea',
						'title'   => 'متن سبد خالی',
						'default' => 'سبد خرید شما خالی است؛ از کالکشن جدید ما دیدن کنید.',
					),
					array(
						'id'      => 'cart.empty_button',
						'type'    => 'text',
						'title'   => 'متن دکمه سبد خالی',
						'default' => 'مشاهده محصولات',
					),
				),
			),
			array(
				'title'  => 'صورتحساب (Checkout)',
				'fields' => array(
					array(
						'id'      => 'checkout.layout',
						'type'    => 'select',
						'title'   => 'چیدمان صفحه پرداخت',
						'options' => array(
							'two_col' => 'فرم راست، خلاصه چپ',
							'multistep' => 'چند مرحله‌ای',
							'single' => 'تک‌ستونی',
						),
						'default' => 'two_col',
					),
					array(
						'id'      => 'checkout.steps',
						'type'    => 'checkbox',
						'title'   => 'نمایش نشانگر مراحل خرید',
						'default' => 1,
					),
					array(
						'id'      => 'checkout.trust_note',
						'type'    => 'textarea',
						'title'   => 'متن اعتماد زیر دکمه پرداخت',
						'default' => 'پرداخت امن با درگاه‌های معتبر بانکی • امکان مرجوعی ۷ روزه',
					),
					array(
						'id'      => 'checkout.coupon_inline',
						'type'    => 'checkbox',
						'title'   => 'کد تخفیف داخل خلاصه سفارش',
						'default' => 1,
					),
					array(
						'id'      => 'checkout.terms_text',
						'type'    => 'textarea',
						'title'   => 'متن قوانین زیر فرم پرداخت',
						'default' => 'با ثبت سفارش، قوانین و مقررات فروشگاه را می‌پذیرم.',
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'mobile'         => array(
		'title'       => 'موبایل و ناوبری',
		'icon'        => 'phone',
		'description' => 'هدر موبایل و نوار ناوبری پایین صفحه.',
		'sections'    => array(
			array(
				'title'  => 'هدر موبایل',
				'fields' => array(
					array(
						'id'      => 'mobile.header_style',
						'type'    => 'select',
						'title'   => 'استایل هدر موبایل',
						'options' => array(
							'glass'   => 'شیشه‌ای چسبان',
							'minimal' => 'مینیمال',
							'classic' => 'کلاسیک با جستجو',
							'centered' => 'لوگوی وسط',
						),
						'default' => 'glass',
					),
					array(
						'id'      => 'mobile.header_logo',
						'type'    => 'media',
						'title'   => 'لوگوی موبایل (اختیاری)',
						'default' => '',
					),
					array(
						'id'      => 'mobile.header_height',
						'type'    => 'range',
						'title'   => 'ارتفاع هدر موبایل',
						'min'     => 48,
						'max'     => 96,
						'step'    => 2,
						'unit'    => 'px',
						'default' => 62,
					),
					array(
						'id'      => 'mobile.drawer_style',
						'type'    => 'select',
						'title'   => 'استایل منوی کشویی',
						'options' => array(
							'slide'  => 'کشویی از کنار',
							'full'   => 'تمام‌صفحه',
							'bottom' => 'از پایین',
						),
						'default' => 'slide',
					),
					array(
						'id'      => 'mobile.sticky_header',
						'type'    => 'checkbox',
						'title'   => 'هدر موبایل چسبان',
						'default' => 1,
					),
					array(
						'id'      => 'mobile.search',
						'type'    => 'checkbox',
						'title'   => 'جستجو در هدر موبایل',
						'default' => 1,
					),
					array(
						'id'      => 'mobile.hide_icons_on_scroll',
						'type'    => 'checkbox',
						'title'   => 'مخفی شدن هدر در اسکرول به پایین',
						'default' => 1,
					),
				),
			),
			array(
				'title'  => 'نوار ناوبری پایین',
				'fields' => array(
					array(
						'id'      => 'mobile.bottom_bar',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی نوار ناوبری پایین',
						'default' => 1,
					),
					array(
						'id'      => 'mobile.style',
						'type'    => 'select',
						'title'   => 'استایل نوار ناوبری',
						'options' => array(
							'bar'    => 'نوار چسبیده به لبه',
							'glass'  => 'شیشه‌ای شناور',
							'floating' => 'کپسولی شناور',
							'minimal' => 'مینیمال بدون حاشیه',
							'pill'   => 'قرصی با آیکون فعال رنگی',
						),
						'default' => 'glass',
					),
					array(
						'id'      => 'mobile.bottom_bar_items',
						'type'    => 'repeater',
						'title'   => 'آیتم‌های نوار پایین',
						'item_title' => 'آیتم',
						'add_label'  => 'افزودن آیتم',
						'fields'  => array(
							array(
								'id'      => 'icon',
								'type'    => 'icon',
								'title'   => 'آیکون',
								'default' => 'home',
							),
							array( 'id' => 'label', 'type' => 'text', 'title' => 'برچسب' ),
							array(
								'id'      => 'action',
								'type'    => 'select',
								'title'   => 'کارکرد',
								'options' => array(
									'home'     => 'صفحه اصلی',
									'shop'     => 'فروشگاه',
									'categories' => 'دسته‌بندی‌ها',
									'cart'     => 'سبد خرید',
									'account'  => 'حساب کاربری',
									'orders'   => 'سفارش‌های من',
									'tailoring' => 'سفارش دوخت',
									'search'   => 'جستجو',
									'wishlist' => 'علاقه‌مندی',
									'link'     => 'لینک دلخواه',
								),
								'default' => 'home',
							),
							array( 'id' => 'link', 'type' => 'text', 'title' => 'لینک (در صورت انتخاب لینک دلخواه)' ),
						),
						'default' => array(
							array( 'icon' => 'home', 'label' => 'خانه', 'action' => 'home', 'link' => '' ),
							array( 'icon' => 'categories', 'label' => 'دسته‌ها', 'action' => 'categories', 'link' => '' ),
							array( 'icon' => 'bag', 'label' => 'فروشگاه', 'action' => 'shop', 'link' => '' ),
							array( 'icon' => 'cart', 'label' => 'سبد', 'action' => 'cart', 'link' => '' ),
							array( 'icon' => 'user', 'label' => 'حساب من', 'action' => 'account', 'link' => '' ),
						),
					),
					array(
						'id'      => 'mobile.center_item',
						'type'    => 'checkbox',
						'title'   => 'آیتم وسط برجسته (دایره‌ای)',
						'default' => 1,
					),
					array(
						'id'      => 'mobile.hide_on_scroll',
						'type'    => 'checkbox',
						'title'   => 'مخفی شدن نوار در اسکرول',
						'default' => 0,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'fitting'        => array(
		'title'       => 'اتاق پرو مجازی',
		'icon'        => 'tshirt',
		'description' => 'المان پرو لباس: نمایش لباس روی تصویر یا دوربین مشتری.',
		'sections'    => array(
			array(
				'title'  => 'تنظیمات اتاق پرو',
				'fields' => array(
					array(
						'id'      => 'fitting.enabled',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی اتاق پرو در فروشگاه',
						'default' => 1,
					),
					array(
						'id'      => 'fitting.position',
						'type'    => 'radio',
						'title'   => 'موقعیت در صفحه فروشگاه',
						'options' => array( 'left' => 'سمت چپ', 'right' => 'سمت راست', 'none' => 'فقط با المان المنتور' ),
						'default' => 'right',
					),
					array(
						'id'      => 'fitting.trigger_label',
						'type'    => 'text',
						'title'   => 'متن دکمه شناور',
						'default' => 'اتاق پرو',
					),
					array(
						'id'      => 'fitting.trigger_icon',
						'type'    => 'icon',
						'title'   => 'آیکون دکمه',
						'default' => 'tshirt',
					),
					array(
						'id'      => 'fitting.webcam',
						'type'    => 'checkbox',
						'title'   => 'پشتیبانی از دوربین (وب‌کم)',
						'default' => 1,
					),
					array(
						'id'      => 'fitting.upload',
						'type'    => 'checkbox',
						'title'   => 'پشتیبانی از آپلود عکس',
						'default' => 1,
					),
					array(
						'id'      => 'fitting.mannequin',
						'type'    => 'media',
						'title'   => 'تصویر مانکن پیش‌فرض',
						'default' => '',
					),
					array(
						'id'      => 'fitting.cloth_opacity',
						'type'    => 'range',
						'title'   => 'شفافیت لباس روی تصویر',
						'min'     => 0.3,
						'max'     => 1,
						'step'    => 0.01,
						'default' => 0.88,
					),
					array(
						'id'      => 'fitting.show_on',
						'type'    => 'select',
						'title'   => 'نمایش خودکار در صفحات',
						'options' => array(
							'shop'     => 'فروشگاه و دسته‌بندی‌ها',
							'product'  => 'صفحه محصول',
							'all'      => 'همه صفحات',
							'none'     => 'هیچ‌کدام (فقط المان)',
						),
						'default' => 'shop',
					),
					array(
						'id'      => 'fitting.box_bg',
						'type'    => 'color',
						'title'   => 'رنگ پس‌زمینه پنل',
						'default' => '#0f172a',
					),
					array(
						'id'      => 'fitting.accent',
						'type'    => 'color',
						'title'   => 'رنگ تاکیدی اتاق پرو',
						'default' => '#c8a96a',
					),
					array(
						'id'      => 'fitting.clothes',
						'type'    => 'repeater',
						'title'   => 'لباس‌های اتاق پرو',
						'description' => 'هر آیتم یک تصویر PNG با پس‌زمینه شفاف است که روی تصویر مشتری قرار می‌گیرد.',
						'item_title' => 'لباس',
						'fields'  => array(
							array( 'id' => 'image', 'type' => 'media', 'title' => 'تصویر لباس (PNG)' ),
							array( 'id' => 'title', 'type' => 'text', 'title' => 'نام لباس' ),
							array( 'id' => 'product', 'type' => 'text', 'title' => 'شناسه محصول (اختیاری)' ),
							array( 'id' => 'color', 'type' => 'text', 'title' => 'رنگ (اختیاری)' ),
						),
						'default' => array(),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'floating'       => array(
		'title'       => 'سبد شناور و دکمه‌ها',
		'icon'        => 'cart',
		'description' => 'سبد خرید شناور و دکمه‌های تماس/شبکه‌های اجتماعی.',
		'sections'    => array(
			array(
				'title'  => 'سبد خرید شناور',
				'fields' => array(
					array(
						'id'      => 'floating.cart',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی سبد شناور',
						'default' => 1,
					),
					array(
						'id'      => 'floating.cart_position',
						'type'    => 'radio',
						'title'   => 'موقعیت',
						'options' => array( 'bottom-right' => 'پایین راست', 'bottom-left' => 'پایین چپ' ),
						'default' => 'bottom-left',
					),
					array(
						'id'      => 'floating.cart_style',
						'type'    => 'select',
						'title'   => 'استایل',
						'options' => array(
							'bubble'  => 'حبابی با شمارنده',
							'card'    => 'کارت جمع‌وجور با مبلغ',
							'dock'    => 'داک کشیده با لیست آیتم‌ها',
							'circle'  => 'دایره‌ای ساده',
						),
						'default' => 'card',
					),
					array(
						'id'      => 'floating.cart_hide_empty',
						'type'    => 'checkbox',
						'title'   => 'مخفی شدن وقتی سبد خالی است',
						'default' => 0,
					),
					array(
						'id'      => 'floating.cart_hide_mobile',
						'type'    => 'checkbox',
						'title'   => 'مخفی در موبایل (نوار پایین جایگزین است)',
						'default' => 1,
					),
					array(
						'id'      => 'floating.cart_items_preview',
						'type'    => 'checkbox',
						'title'   => 'نمایش پیش‌نمایش آیتم‌ها در داک',
						'default' => 1,
					),
					array(
						'id'          => 'floating.cart_threshold',
						'type'        => 'number',
						'title'       => 'سقف ارسال رایگان',
						'description' => 'مبلغی که با رسیدن جمع سبد به آن، نوار پیشرفت «ارسال رایگان» کامل می‌شود. صفر = غیرفعال.',
						'min'         => 0,
						'step'        => 100000,
						'default'     => 0,
					),
					array(
						'id'          => 'floating.cart_size',
						'type'        => 'range',
						'title'       => 'اندازه دکمه شناور',
						'description' => 'قطر دکمه گرد (استایل حبابی و دایره‌ای) و اندازه آیکون کارت/داک را تعیین می‌کند.',
						'min'         => 44,
						'max'         => 96,
						'step'        => 2,
						'unit'        => 'px',
						'default'     => 58,
					),
					array(
						'id'          => 'floating.cart_color',
						'type'        => 'color',
						'title'       => 'رنگ دکمه شناور',
						'description' => 'رنگ پس‌زمینه دکمه (و آیکون کارت/داک). خالی = رنگ پیش‌فرض هر استایل.',
						'default'     => '',
					),
					array(
						'id'          => 'floating.cart_icon_color',
						'type'        => 'color',
						'title'       => 'رنگ آیکون سبد',
						'description' => 'رنگ آیکون سبد روی دکمه. خالی = رنگ خودکار متناسب با استایل.',
						'default'     => '',
					),
				),
			),
			array(
				'title'  => 'دکمه‌های شناور تماس و شبکه‌ها',
				'fields' => array(
					array(
						'id'      => 'floating.actions',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی دکمه‌های شناور',
						'default' => 1,
					),
					array(
						'id'      => 'floating.actions_position',
						'type'    => 'radio',
						'title'   => 'موقعیت',
						'options' => array( 'bottom-right' => 'پایین راست', 'bottom-left' => 'پایین چپ' ),
						'default' => 'bottom-right',
					),
					array(
						'id'      => 'floating.actions_layout',
						'type'    => 'select',
						'title'   => 'چیدمان',
						'options' => array(
							'vertical' => 'ستونی',
							'horizontal' => 'افقی',
							'arc'     => 'قوسی (بازشو)',
							'expand'  => 'دکمه اصلی + بازشو',
						),
						'default' => 'expand',
					),
					array(
						'id'      => 'floating.actions_style',
						'type'    => 'select',
						'title'   => 'استایل دکمه‌ها',
						'options' => array(
							'circle' => 'دایره‌ای رنگی',
							'soft'   => 'نرم با سایه',
							'glass'  => 'شیشه‌ای',
							'label'  => 'با برچسب متنی',
						),
						'default' => 'circle',
					),
					array(
						'id'      => 'floating.actions_color',
						'type'    => 'color',
						'title'   => 'رنگ دکمه اصلی',
						'default' => '#c8a96a',
					),
					array(
						'id'      => 'floating.quick_call',
						'type'    => 'checkbox',
						'title'   => 'نمایش دکمه تماس سریع',
						'default' => 1,
					),
					array(
						'id'      => 'floating.whatsapp',
						'type'    => 'text',
						'title'   => 'شماره واتساپ',
						'placeholder' => '98912xxxxxxx',
						'default' => '',
					),
					array(
						'id'      => 'floating.telegram',
						'type'    => 'text',
						'title'   => 'آیدی تلگرام',
						'placeholder' => 'username',
						'default' => '',
					),
					array(
						'id'      => 'floating.instagram',
						'type'    => 'url',
						'title'   => 'لینک اینستاگرام',
						'default' => '',
					),
					array(
						'id'      => 'floating.phone',
						'type'    => 'text',
						'title'   => 'شماره تماس',
						'default' => '',
					),
					array(
						'id'      => 'floating.actions_mobile',
						'type'    => 'checkbox',
						'title'   => 'نمایش در موبایل',
						'default' => 1,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'sms'            => array(
		'title'       => 'سامانه پیامک',
		'icon'        => 'sms',
		'description' => 'اتصال به ملی پیامک، sms.ir، آی‌پی پنل و رایگان اس‌ام‌اس.',
		'sections'    => array(
			array(
				'title'  => 'درگاه پیامک',
				'fields' => array(
					array(
						'id'      => 'sms.provider',
						'type'    => 'select',
						'title'   => 'سرویس‌دهنده',
						'options' => $sms_providers,
						'default' => '',
					),
					array(
						'id'          => 'sms.username',
						'type'        => 'text',
						'title'       => 'نام کاربری',
						'description' => 'در sms.ir و آی‌پی پنل معمولاً خالی است.',
						'default'     => '',
					),
					array(
						'id'      => 'sms.password',
						'type'    => 'text',
						'title'   => 'رمز عبور / کلید API',
						'default' => '',
					),
					array(
						'id'      => 'sms.api_key',
						'type'    => 'text',
						'title'   => 'کلید API اختصاصی',
						'description' => 'برای sms.ir (x-api-key) یا آی‌پی پنل.',
						'default' => '',
					),
					array(
						'id'      => 'sms.sender',
						'type'    => 'text',
						'title'   => 'شماره فرستنده / خط خدماتی',
						'default' => '',
					),
					array(
						'id'      => 'sms.pattern_body_id',
						'type'    => 'text',
						'title'   => 'شناسه الگوی پیش‌فرض (bodyId / templateId)',
						'default' => '',
					),
					array(
						'id'      => 'sms.custom_endpoint',
						'type'    => 'url',
						'title'   => 'آدرس Endpoint اختصاصی',
						'description' => 'برای سرویس سفارشی؛ متغیرهای {mobile}، {message}، {pattern}، {key} جایگزین می‌شوند.',
						'default' => '',
					),
					array(
						'id'      => 'sms.admin_phone',
						'type'    => 'text',
						'title'   => 'شماره موبایل مدیر برای اطلاع‌رسانی',
						'default' => '',
					),
					array(
						'id'      => 'sms.admin_notify',
						'type'    => 'checkbox',
						'title'   => 'ارسال پیامک به مدیر در رویدادهای مهم',
						'default' => 1,
					),
					array(
						'id'      => 'sms.log',
						'type'    => 'checkbox',
						'title'   => 'ثبت گزارش پیامک‌ها',
						'default' => 1,
					),
				),
			),
			array(
				'title'  => 'الگوهای پیامکی',
				'description' => 'شناسه الگو و متن هر رویداد را وارد کنید. متغیرها بین { } قرار می‌گیرند.',
				'fields' => array(
					array(
						'id'      => 'sms.patterns',
						'type'    => 'repeater',
						'title'   => 'الگوها',
						'item_title' => 'الگو',
						'fields'  => array(
							array(
								'id'      => 'event',
								'type'    => 'select',
								'title'   => 'رویداد',
								'options' => array(
									'otp'        => 'کد ورود (OTP)',
									'order'      => 'ثبت سفارش',
									'order_status' => 'تغییر وضعیت سفارش',
									'tailoring'  => 'ثبت درخواست دوخت',
									'tailoring_status' => 'تغییر وضعیت دوخت',
									'invoice'    => 'صدور فاکتور',
									'welcome'    => 'خوش‌آمدگویی',
								),
								'default' => 'otp',
							),
							array( 'id' => 'pattern', 'type' => 'text', 'title' => 'شناسه الگو' ),
							array( 'id' => 'text', 'type' => 'textarea', 'title' => 'متن (برای درگاه‌های غیر پترنی)' ),
						),
						'default' => array(
							array( 'event' => 'otp', 'pattern' => '', 'text' => 'کد ورود شما به دیوکو: {code}' ),
							array( 'event' => 'order', 'pattern' => '', 'text' => '{name} عزیز، سفارش {order_id} ثبت شد.' ),
							array( 'event' => 'order_status', 'pattern' => '', 'text' => 'وضعیت سفارش {order_id}: {status}' ),
							array( 'event' => 'invoice', 'pattern' => '', 'text' => 'فاکتور {invoice_id} به مبلغ {total} صادر شد.' ),
						),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'auth'           => array(
		'title'       => 'ورود و ثبت‌نام',
		'icon'        => 'lock',
		'description' => 'ورود پیامکی یکپارچه، فرم اختصاصی و استایل آن.',
		'sections'    => array(
			array(
				'title'  => 'روش‌های ورود',
				'fields' => array(
					array(
						'id'      => 'auth.otp',
						'type'    => 'checkbox',
						'title'   => 'ورود/ثبت‌نام با کد پیامکی',
						'default' => 1,
					),
					array(
						'id'      => 'auth.password',
						'type'    => 'checkbox',
						'title'   => 'ورود با نام کاربری و رمز عبور',
						'default' => 1,
					),
					array(
						'id'      => 'auth.register',
						'type'    => 'checkbox',
						'title'   => 'ثبت‌نام یکپارچه (کاربر جدید خودکار ساخته شود)',
						'default' => 1,
					),
					array(
						'id'      => 'auth.default_method',
						'type'    => 'radio',
						'title'   => 'روش پیش‌فرض',
						'options' => array( 'otp' => 'پیامکی', 'password' => 'رمز عبور' ),
						'default' => 'otp',
					),
					array(
						'id'      => 'auth.otp_length',
						'type'    => 'range',
						'title'   => 'طول کد تایید',
						'min'     => 4,
						'max'     => 6,
						'step'    => 1,
						'default' => 5,
					),
					array(
						'id'      => 'auth.otp_expiry',
						'type'    => 'range',
						'title'   => 'اعتبار کد (دقیقه)',
						'min'     => 1,
						'max'     => 15,
						'step'    => 1,
						'default' => 3,
					),
					array(
						'id'      => 'auth.test_mode',
						'type'    => 'checkbox',
						'title'   => 'حالت آزمایشی (نمایش کد در پاسخ برای مدیر)',
						'default' => 0,
					),
					array(
						'id'      => 'auth.terms_page',
						'type'    => 'page',
						'title'   => 'صفحه قوانین و مقررات',
						'default' => 0,
					),
					array(
						'id'      => 'auth.redirect',
						'type'    => 'text',
						'title'   => 'آدرس بازگشت پس از ورود',
						'description' => 'خالی بگذارید تا به داشبورد کاربری برود.',
						'default' => '',
					),
				),
			),
			array(
				'title'  => 'ظاهر و چیدمان فرم',
				'fields' => array(
					array(
						'id'      => 'auth.layout',
						'type'    => 'select',
						'title'   => 'نوع نمایش',
						'options' => array(
							'card'     => 'کارت وسط صفحه',
							'split'    => 'دو ستونه (فرم + تصویر)',
							'side'     => 'کشویی از کنار',
							'modal'    => 'پنجره شناور',
							'inline'   => 'داخل صفحه (کامپوننت)',
						),
						'default' => 'modal',
					),
					array(
						'id'      => 'auth.style',
						'type'    => 'select',
						'title'   => 'استایل فرم',
						'options' => array(
							'elegant' => 'لوکس (طلایی)',
							'modern'  => 'مدرن (مینیمال)',
							'glass'   => 'شیشه‌ای',
							'dark'    => 'تیره',
							'neon'    => 'نئون',
						),
						'default' => 'elegant',
					),
					array(
						'id'      => 'auth.button_text',
						'type'    => 'text',
						'title'   => 'متن دکمه ورود',
						'default' => 'ورود | ثبت‌نام',
					),
					array(
						'id'      => 'auth.welcome_text',
						'type'    => 'textarea',
						'title'   => 'متن خوش‌آمدگویی',
						'default' => 'به دیوکو خوش آمدید؛ با شماره موبایل خود سریع وارد شوید.',
					),
					array(
						'id'      => 'auth.image',
						'type'    => 'media',
						'title'   => 'تصویر ستون کنار فرم',
						'default' => '',
					),
					array(
						'id'      => 'auth.button_style',
						'type'    => 'select',
						'title'   => 'استایل دکمه ورود در هدر',
						'options' => array(
							'ghost'   => 'خطی با آیکون',
							'solid'   => 'توپر',
							'outline' => 'قاب‌دار',
							'pill'    => 'قرصی با آواتار',
							'text'    => 'متن ساده',
						),
						'default' => 'ghost',
					),
					array(
						'id'      => 'auth.button_logged_text',
						'type'    => 'text',
						'title'   => 'متن دکمه وقتی کاربر وارد شده',
						'default' => 'حساب کاربری',
					),
					array(
						'id'      => 'auth.social_login',
						'type'    => 'checkbox',
						'title'   => 'نمایش دکمه‌های ورود شبکه‌های اجتماعی (نیازمند افزونه)',
						'default' => 1,
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'dashboard'      => array(
		'title'       => 'داشبورد کاربری',
		'icon'        => 'user',
		'description' => 'پنل مشتری، پیگیری سفارش و منوی داشبورد.',
		'sections'    => array(
			array(
				'title'  => 'تنظیمات داشبورد',
				'fields' => array(
					array(
						'id'      => 'dashboard.enabled',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی داشبورد اختصاصی دیوکو',
						'default' => 1,
					),
					array(
						'id'      => 'dashboard.page_id',
						'type'    => 'page',
						'title'   => 'صفحه داشبورد',
						'description' => 'صفحه‌ای که قالب «داشبورد دیوکو» دارد.',
						'default' => 0,
					),
					array(
						'id'      => 'dashboard.layout',
						'type'    => 'select',
						'title'   => 'چیدمان داشبورد',
						'options' => array(
							'sidebar' => 'سایدبار ثابت',
							'topbar'  => 'منوی بالا',
							'floating' => 'منوی شناور پایین (موبایل‌محور)',
						),
						'default' => 'sidebar',
					),
					array(
						'id'      => 'dashboard.style',
						'type'    => 'select',
						'title'   => 'تم داشبورد',
						'options' => array(
							'light' => 'روشن',
							'dark'  => 'تیره',
							'glass' => 'شیشه‌ای',
						),
						'default' => 'light',
					),
					array(
						'id'      => 'dashboard.welcome',
						'type'    => 'text',
						'title'   => 'متن خوش‌آمدگویی',
						'default' => 'خوش آمدید',
					),
					array(
						'id'      => 'dashboard.menu',
						'type'    => 'sortable',
						'title'   => 'آیتم‌های منوی داشبورد',
						'options' => array(
							'overview'   => 'نمای کلی',
							'orders'     => 'سفارش‌های من',
							'track'      => 'پیگیری سفارش',
							'tailoring'  => 'سفارش‌های دوخت',
							'invoices'   => 'فاکتورها و پرداخت',
							'measurements' => 'اندازه‌های من',
							'addresses'  => 'آدرس‌ها',
							'wishlist'   => 'علاقه‌مندی‌ها',
							'account'    => 'اطلاعات حساب',
							'support'    => 'پشتیبانی',
						),
						'default' => array( 'overview', 'orders', 'track', 'tailoring', 'invoices', 'measurements', 'addresses', 'account' ),
					),
					array(
						'id'      => 'dashboard.mobile_nav',
						'type'    => 'checkbox',
						'title'   => 'منوی پایین مخصوص داشبورد در موبایل',
						'default' => 1,
					),
					array(
						'id'      => 'dashboard.support_phone',
						'type'    => 'text',
						'title'   => 'شماره پشتیبانی داشبورد',
						'default' => '',
					),
				),
			),
			array(
				'title'  => 'پیگیری سفارش',
				'fields' => array(
					array(
						'id'      => 'track.enabled',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی صفحه پیگیری سفارش',
						'default' => 1,
					),
					array(
						'id'      => 'track.require_phone',
						'type'    => 'checkbox',
						'title'   => 'الزام شماره موبایل برای پیگیری',
						'default' => 1,
					),
					array(
						'id'      => 'track.statuses',
						'type'    => 'repeater',
						'title'   => 'مراحل نمایش داده‌شده',
						'item_title' => 'مرحله',
						'fields'  => array(
							array( 'id' => 'status', 'type' => 'text', 'title' => 'شناسه وضعیت ووکامرس' ),
							array( 'id' => 'label', 'type' => 'text', 'title' => 'عنوان نمایشی' ),
							array( 'id' => 'icon', 'type' => 'icon', 'title' => 'آیکون' ),
						),
						'default' => array(
							array( 'status' => 'pending', 'label' => 'در انتظار پرداخت', 'icon' => 'clock' ),
							array( 'status' => 'processing', 'label' => 'در حال آماده‌سازی', 'icon' => 'box' ),
							array( 'status' => 'ready', 'label' => 'آماده ارسال', 'icon' => 'check-badge' ),
							array( 'status' => 'shipped', 'label' => 'ارسال شده', 'icon' => 'truck' ),
							array( 'status' => 'completed', 'label' => 'تحویل شده', 'icon' => 'check' ),
						),
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'tailoring'      => array(
		'title'       => 'سفارش دوخت',
		'icon'        => 'scissors',
		'description' => 'فرم اندازه‌گیری، مانکن سه‌بعدی، فاکتور و اطلاع‌رسانی.',
		'sections'    => array(
			array(
				'title'  => 'عمومی',
				'fields' => array(
					array(
						'id'      => 'tailoring.enabled',
						'type'    => 'checkbox',
						'title'   => 'فعال‌سازی سامانه سفارش دوخت',
						'default' => 1,
					),
					array(
						'id'      => 'tailoring.title',
						'type'    => 'text',
						'title'   => 'عنوان بخش',
						'default' => 'سفارش دوخت اختصاصی',
					),
					array(
						'id'      => 'tailoring.intro',
						'type'    => 'textarea',
						'title'   => 'توضیح کوتاه',
						'default' => 'اندازه‌های خود را روی مانکن سه‌بعدی ثبت کنید، طرح دلخواه را آپلود کنید و منتظر تایید و قیمت‌گذاری بمانید.',
					),
					array(
						'id'      => 'tailoring.page_id',
						'type'    => 'page',
						'title'   => 'صفحه سفارش دوخت',
						'default' => 0,
					),
					array(
						'id'      => 'tailoring.measurements',
						'type'    => 'measurements',
						'title'   => 'فهرست اندازه‌ها',
						'default' => devco_default_measurements(),
					),
					array(
						'id'      => 'tailoring.body_3d',
						'type'    => 'checkbox',
						'title'   => 'نمایش مانکن سه‌بعدی قابل چرخش',
						'default' => 1,
					),
					array(
						'id'      => 'tailoring.body_model',
						'type'    => 'select',
						'title'   => 'مدل مانکن',
						'options' => array(
							'female' => 'زنانه',
							'male'   => 'مردانه',
							'neutral' => 'خنثی',
						),
						'default' => 'female',
					),
					array(
						'id'      => 'tailoring.allow_upload',
						'type'    => 'checkbox',
						'title'   => 'امکان آپلود طرح',
						'default' => 1,
					),
					array(
						'id'      => 'tailoring.max_upload',
						'type'    => 'range',
						'title'   => 'حداکثر حجم فایل',
						'min'     => 1,
						'max'     => 20,
						'step'    => 1,
						'unit'    => 'MB',
						'default' => 8,
					),
					array(
						'id'      => 'tailoring.guest',
						'type'    => 'checkbox',
						'title'   => 'اجازه ثبت درخواست بدون ورود',
						'default' => 0,
					),
					array(
						'id'      => 'tailoring.requires_approval',
						'type'    => 'checkbox',
						'title'   => 'نیاز به تایید مدیر قبل از قیمت‌گذاری',
						'default' => 1,
					),
					array(
						'id'      => 'tailoring.notify_admin',
						'type'    => 'checkbox',
						'title'   => 'اطلاع‌رسانی پیامکی به مدیر',
						'default' => 1,
					),
					array(
						'id'      => 'tailoring.payment',
						'type'    => 'select',
						'title'   => 'روش پرداخت فاکتور دوخت',
						'options' => array(
							'woo'   => 'ایجاد سفارش ووکامرس و انتقال به درگاه',
							'manual' => 'پرداخت کارت به کارت / هماهنگی تلفنی',
						),
						'default' => 'woo',
					),
					array(
						'id'      => 'tailoring.terms',
						'type'    => 'textarea',
						'title'   => 'متن شرایط سفارش دوخت',
						'default' => 'اندازه‌های اعلام‌شده پس از تایید نهایی قابل تغییر نیست. زمان دوخت ۷ تا ۱۰ روز کاری است.',
					),
				),
			),
		),
	),

	/* ---------------------------------------------------------------------- */
	'advanced'       => array(
		'title'       => 'پیشرفته',
		'icon'        => 'sparkle',
		'description' => 'کد سفارشی، بهینه‌سازی و ابزارهای توسعه‌دهنده.',
		'sections'    => array(
			array(
				'title'  => 'کد سفارشی',
				'fields' => array(
					array(
						'id'      => 'advanced.css',
						'type'    => 'code',
						'title'   => 'CSS سفارشی',
						'default' => '',
					),
					array(
						'id'      => 'advanced.js',
						'type'    => 'code',
						'title'   => 'JavaScript سفارشی',
						'default' => '',
					),
					array(
						'id'      => 'advanced.head',
						'type'    => 'code',
						'title'   => 'کد در head',
						'default' => '',
					),
					array(
						'id'      => 'advanced.body',
						'type'    => 'code',
						'title'   => 'کد در انتهای body',
						'default' => '',
					),
				),
			),
			array(
				'title'  => 'بهینه‌سازی',
				'collapsed' => 1,
				'fields' => array(
					array(
						'id'      => 'advanced.disable_emojis',
						'type'    => 'checkbox',
						'title'   => 'حذف اسکریپت ایموجی وردپرس',
						'default' => 1,
					),
					array(
						'id'      => 'advanced.lazy_images',
						'type'    => 'checkbox',
						'title'   => 'بارگذاری تنبل تصاویر',
						'default' => 1,
					),
					array(
						'id'      => 'advanced.critical_css',
						'type'    => 'checkbox',
						'title'   => 'درج استایل‌های متغیر در head',
						'default' => 1,
					),
					array(
						'id'      => 'advanced.remove_wp_version',
						'type'    => 'checkbox',
						'title'   => 'حذف نسخه وردپرس از سورس',
						'default' => 1,
					),
					array(
						'id'      => 'advanced.elementor_optimized',
						'type'    => 'checkbox',
						'title'   => 'بهینه‌سازی بارگذاری المنتور',
						'default' => 0,
					),
				),
			),
			array(
				'title'  => 'وضعیت سیستم',
				'collapsed' => 1,
				'fields' => array(
					array(
						'id'   => 'advanced.status',
						'type' => 'html',
						'html' => devco_system_status_html(),
					),
				),
			),
		),
	),
);
