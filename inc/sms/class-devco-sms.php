<?php
/**
 * SMS gateways (Melipayamak, sms.ir, IPPanel, RayganSMS / "رایگان اس ام اس").
 *
 * @package DeVco
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class DeVco_SMS
 */
class DeVco_SMS {

	/**
	 * Normalise a mobile number to the local Iranian format (09xxxxxxxxx).
	 *
	 * @param string $mobile Raw number.
	 * @return string
	 */
	public static function normalize( $mobile ) {
		$mobile = strtr(
			(string) $mobile,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);

		$mobile = preg_replace( '/[^0-9]/', '', $mobile );
		$mobile = ltrim( $mobile, '0' );

		if ( 0 === strpos( $mobile, '98' ) ) {
			$mobile = substr( $mobile, 2 );
		}

		if ( 10 === strlen( $mobile ) && 0 === strpos( $mobile, '9' ) ) {
			return '0' . $mobile;
		}

		return $mobile;
	}

	/**
	 * Whether a number looks like an Iranian mobile.
	 *
	 * @param string $mobile Number.
	 * @return bool
	 */
	public static function is_valid_mobile( $mobile ) {
		$mobile = self::normalize( $mobile );
		return (bool) preg_match( '/^09\d{9}$/', $mobile );
	}

	/**
	 * Bare international number (98912...).
	 *
	 * @param string $mobile Mobile.
	 * @return string
	 */
	public static function intl( $mobile ) {
		$mobile = self::normalize( $mobile );
		return '98' . ltrim( $mobile, '0' );
	}

	/**
	 * Provider slug.
	 *
	 * @return string
	 */
	public static function provider() {
		return (string) devco_opt( 'sms.provider', '' );
	}

	/**
	 * Whether a gateway is configured.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return '' !== self::provider();
	}

	/**
	 * Pattern id for an event.
	 *
	 * @param string $event Event key.
	 * @return string
	 */
	public static function pattern_for( $event ) {
		$patterns = devco_opt( 'sms.patterns', array() );

		if ( is_array( $patterns ) ) {
			foreach ( $patterns as $row ) {
				if ( isset( $row['event'] ) && $row['event'] === $event && ! empty( $row['pattern'] ) ) {
					return (string) $row['pattern'];
				}
			}
		}

		if ( 'otp' === $event ) {
			return (string) devco_opt( 'sms.pattern_body_id', '' );
		}

		return '';
	}

	/**
	 * Template text for an event (fallback for non-pattern gateways).
	 *
	 * @param string $event Event.
	 * @return string
	 */
	public static function template_for( $event ) {
		$patterns = devco_opt( 'sms.patterns', array() );

		if ( is_array( $patterns ) ) {
			foreach ( $patterns as $row ) {
				if ( isset( $row['event'] ) && $row['event'] === $event && ! empty( $row['text'] ) ) {
					return (string) $row['text'];
				}
			}
		}

		$defaults = array(
			'otp'              => 'کد ورود شما به دیوکو: {code}',
			'order'            => '{name} عزیز، سفارش {order_id} با موفقیت ثبت شد.',
			'order_status'     => 'وضعیت سفارش {order_id}: {status}',
			'tailoring'        => '{name} عزیز، درخواست دوخت {request_id} ثبت شد و در حال بررسی است.',
			'tailoring_status' => 'وضعیت درخواست دوخت {request_id}: {status}',
			'invoice'          => 'فاکتور {invoice_id} به مبلغ {total} صادر شد. مشاهده: {link}',
			'welcome'          => '{name} عزیز، به دیوکو خوش آمدید.',
		);

		return isset( $defaults[ $event ] ) ? $defaults[ $event ] : '';
	}

	/**
	 * Replace {placeholders} in a template.
	 *
	 * @param string $template Template.
	 * @param array  $values   Values keyed by placeholder.
	 * @return string
	 */
	public static function interpolate( $template, $values ) {
		$replace = array();

		foreach ( (array) $values as $key => $value ) {
			$replace[ '{' . $key . '}' ] = is_scalar( $value ) ? (string) $value : '';
		}

		return strtr( $template, $replace );
	}

	/**
	 * Send a plain message.
	 *
	 * @param string $mobile  Mobile number.
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	public static function send( $mobile, $message ) {
		$mobile = self::normalize( $mobile );

		if ( ! self::is_valid_mobile( $mobile ) ) {
			return new WP_Error( 'devco_sms_mobile', 'شماره موبایل نامعتبر است.' );
		}

		if ( ! self::is_enabled() ) {
			return new WP_Error( 'devco_sms_disabled', 'سرویس پیامک تنظیم نشده است.' );
		}

		$provider = self::provider();
		$result   = null;

		switch ( $provider ) {
			case 'melipayamak':
				$result = self::send_melipayamak( $mobile, $message );
				break;
			case 'sms_ir':
				$result = self::send_sms_ir( $mobile, $message );
				break;
			case 'ippanel':
				$result = self::send_ippanel( $mobile, $message );
				break;
			case 'freesms':
				$result = self::send_raygansms( $mobile, $message );
				break;
			case 'custom':
				$result = self::send_custom( $mobile, $message, '' );
				break;
			default:
				$result = new WP_Error( 'devco_sms_provider', 'سرویس‌دهنده پیامک پشتیبانی نمی‌شود.' );
		}

		self::log( $mobile, $message, $result );

		return $result;
	}

	/**
	 * Send an event message using the configured pattern when available.
	 *
	 * @param string $mobile Mobile.
	 * @param string $event  Event key.
	 * @param array  $values Placeholder values.
	 * @return true|WP_Error
	 */
	public static function send_event( $mobile, $event, $values = array() ) {
		$mobile  = self::normalize( $mobile );
		$pattern = self::pattern_for( $event );
		$text    = self::interpolate( self::template_for( $event ), $values );

		if ( ! self::is_valid_mobile( $mobile ) ) {
			return new WP_Error( 'devco_sms_mobile', 'شماره موبایل نامعتبر است.' );
		}

		if ( ! self::is_enabled() ) {
			return new WP_Error( 'devco_sms_disabled', 'سرویس پیامک تنظیم نشده است.' );
		}

		$provider = self::provider();
		$result   = null;

		/**
		 * Filter the pattern parameters before sending (useful for custom template keys).
		 *
		 * @param array  $values   Values.
		 * @param string $event    Event.
		 * @param string $provider Provider.
		 */
		$values = apply_filters( 'devco_sms_pattern_values', $values, $event, $provider );

		if ( $pattern ) {
			switch ( $provider ) {
				case 'melipayamak':
					$result = self::send_melipayamak_pattern( $mobile, $pattern, $values, $text );
					break;
				case 'sms_ir':
					$result = self::send_sms_ir_pattern( $mobile, $pattern, $values );
					break;
				case 'ippanel':
					$result = self::send_ippanel_pattern( $mobile, $pattern, $values );
					break;
				case 'freesms':
					$result = self::send_raygansms_pattern( $mobile, $pattern, $values, $text );
					break;
				case 'custom':
					$result = self::send_custom( $mobile, $text, $pattern );
					break;
			}
		}

		if ( null === $result || false === $result ) {
			$result = self::send( $mobile, $text );
			return $result;
		}

		self::log( $mobile, $text, $result, $event );

		return $result;
	}

	/**
	 * HTTP POST helper.
	 *
	 * @param string $url     URL.
	 * @param array  $body    Body (json or form).
	 * @param array  $headers Headers.
	 * @param bool   $json    Send JSON.
	 * @return array|WP_Error
	 */
	protected static function post( $url, $body, $headers = array(), $json = true ) {
		$args = array(
			'timeout' => 20,
			'headers' => array_merge(
				array(
					'Content-Type' => $json ? 'application/json' : 'application/x-www-form-urlencoded',
					'Accept'       => 'application/json',
				),
				$headers
			),
			'body'    => $json ? wp_json_encode( $body ) : $body,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code >= 400 ) {
			return new WP_Error(
				'devco_sms_http',
				sprintf( 'خطای سرویس پیامک (%s): %s', $code, is_array( $data ) ? wp_json_encode( $data ) : wp_strip_all_tags( $raw ) )
			);
		}

		return is_array( $data ) ? $data : array( 'raw' => $raw );
	}

	/* --------------------------------------------------------------------- */
	/* Melipayamak                                                            */
	/* --------------------------------------------------------------------- */

	/**
	 * Melipayamak plain SMS.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	protected static function send_melipayamak( $mobile, $message ) {
		$username = devco_opt( 'sms.username', '' );
		$password = devco_opt( 'sms.password', '' );
		$from     = devco_opt( 'sms.sender', '' );

		if ( ! $username || ! $password ) {
			return new WP_Error( 'devco_sms_config', 'نام کاربری یا رمز ملی پیامک تنظیم نشده است.' );
		}

		$response = self::post(
			'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
			array(
				'username' => $username,
				'password' => $password,
				'to'       => self::intl( $mobile ),
				'from'     => $from,
				'text'     => $message,
				'isflash'  => false,
			),
			array(),
			false
		);

		return self::interpret( $response, array( 'RetStatus', 'Value' ) );
	}

	/**
	 * Melipayamak shared pattern.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $pattern Body id.
	 * @param array  $values  Values (ordered by template).
	 * @param string $text    Fallback text.
	 * @return true|WP_Error
	 */
	protected static function send_melipayamak_pattern( $mobile, $pattern, $values, $text ) {
		$raw = '';

		// Read the raw template so we can keep the original placeholder order.
		$patterns = devco_opt( 'sms.patterns', array() );

		$ordered = array();

		foreach ( (array) $patterns as $row ) {
			if ( isset( $row['pattern'] ) && (string) $row['pattern'] === (string) $pattern && ! empty( $row['text'] ) ) {
				preg_match_all( '/\{([a-zA-Z0-9_]+)\}/', $row['text'], $matches );
				foreach ( $matches[1] as $key ) {
					$ordered[] = isset( $values[ $key ] ) ? $values[ $key ] : '';
				}
			}
		}

		if ( ! $ordered ) {
			$ordered = array_values( $values );
		}

		$body = array(
			'username' => devco_opt( 'sms.username', '' ),
			'password' => devco_opt( 'sms.password', '' ),
			'text'     => implode( ';', array_map( 'strval', $ordered ) ),
			'to'       => self::intl( $mobile ),
			'bodyId'   => (int) $pattern,
		);

		$response = self::post( 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', $body, array(), false );

		return self::interpret( $response, array( 'RetStatus', 'Value' ) );
	}

	/* --------------------------------------------------------------------- */
	/* sms.ir                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * sms.ir plain message (bulk endpoint).
	 *
	 * @param string $mobile  Mobile.
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	protected static function send_sms_ir( $mobile, $message ) {
		$key = devco_opt( 'sms.api_key', devco_opt( 'sms.password', '' ) );

		if ( ! $key ) {
			return new WP_Error( 'devco_sms_config', 'کلید API سرویس sms.ir تنظیم نشده است.' );
		}

		$response = self::post(
			'https://api.sms.ir/v1/send/bulk',
			array(
				'lineNumber' => devco_opt( 'sms.sender', '' ),
				'messageText' => $message,
				'mobiles'    => array( self::normalize( $mobile ) ),
			),
			array( 'x-api-key' => $key )
		);

		return self::interpret( $response, array( 'status' ) );
	}

	/**
	 * sms.ir verify/pattern message.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $pattern Template id.
	 * @param array  $values  Values.
	 * @return true|WP_Error
	 */
	protected static function send_sms_ir_pattern( $mobile, $pattern, $values ) {
		$key = devco_opt( 'sms.api_key', devco_opt( 'sms.password', '' ) );

		if ( ! $key ) {
			return new WP_Error( 'devco_sms_config', 'کلید API سرویس sms.ir تنظیم نشده است.' );
		}

		$params = array();

		foreach ( (array) $values as $name => $value ) {
			$params[] = array(
				'name'  => strtoupper( (string) $name ),
				'value' => is_scalar( $value ) ? (string) $value : '',
			);
		}

		$response = self::post(
			'https://api.sms.ir/v1/send/verify',
			array(
				'mobile'     => self::normalize( $mobile ),
				'templateId' => (int) $pattern,
				'parameters' => $params,
			),
			array( 'x-api-key' => $key )
		);

		return self::interpret( $response, array( 'status' ) );
	}

	/* --------------------------------------------------------------------- */
	/* IPPanel                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * IPPanel plain message.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	protected static function send_ippanel( $mobile, $message ) {
		$key = devco_opt( 'sms.api_key', devco_opt( 'sms.password', '' ) );

		if ( ! $key ) {
			return new WP_Error( 'devco_sms_config', 'کلید API آی‌پی پنل تنظیم نشده است.' );
		}

		$response = self::post(
			'https://api2.ippanel.com/api/v1/sms/send/webservice/single',
			array(
				'recipient' => array( self::intl( $mobile ) ),
				'sender'    => devco_opt( 'sms.sender', '' ),
				'message'   => $message,
			),
			array( 'Authorization' => $key )
		);

		return self::interpret( $response, array( 'status' ) );
	}

	/**
	 * IPPanel pattern.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $pattern Pattern code.
	 * @param array  $values  Variables.
	 * @return true|WP_Error
	 */
	protected static function send_ippanel_pattern( $mobile, $pattern, $values ) {
		$key = devco_opt( 'sms.api_key', devco_opt( 'sms.password', '' ) );

		if ( ! $key ) {
			return new WP_Error( 'devco_sms_config', 'کلید API آی‌پی پنل تنظیم نشده است.' );
		}

		$variables = array();

		foreach ( (array) $values as $name => $value ) {
			$variables[ (string) $name ] = is_scalar( $value ) ? (string) $value : '';
		}

		$response = self::post(
			'https://api2.ippanel.com/api/v1/sms/pattern/normal/send',
			array(
				'code'      => (string) $pattern,
				'sender'    => devco_opt( 'sms.sender', '' ),
				'recipient' => self::intl( $mobile ),
				'variable'  => $variables,
			),
			array( 'Authorization' => $key )
		);

		return self::interpret( $response, array( 'status' ) );
	}

	/* --------------------------------------------------------------------- */
	/* RayganSMS ("رایگان اس ام اس" / freesms)                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * RayganSMS plain message.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	protected static function send_raygansms( $mobile, $message ) {
		$username = devco_opt( 'sms.username', '' );
		$password = devco_opt( 'sms.password', '' );

		if ( ! $username || ! $password ) {
			return new WP_Error( 'devco_sms_config', 'نام کاربری یا رمز رایگان اس‌ام‌اس تنظیم نشده است.' );
		}

		$response = self::post(
			'https://api.raygansms.com/SendSimpleSMS',
			array(
				'UserName'    => $username,
				'Password'    => $password,
				'PhoneNumber' => self::normalize( $mobile ),
				'Message'     => $message,
				'issms'       => 'true',
			),
			array(),
			false
		);

		return self::interpret( $response, array( 'Status', 'Code' ) );
	}

	/**
	 * RayganSMS pattern.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $pattern Pattern id.
	 * @param array  $values  Values.
	 * @param string $text    Fallback text.
	 * @return true|WP_Error
	 */
	protected static function send_raygansms_pattern( $mobile, $pattern, $values, $text ) {
		$username = devco_opt( 'sms.username', '' );
		$password = devco_opt( 'sms.password', '' );

		if ( ! $username || ! $password ) {
			return new WP_Error( 'devco_sms_config', 'نام کاربری رایگان اس‌ام‌اس تنظیم نشده است.' );
		}

		$response = self::post(
			'https://api.raygansms.com/SendPatternSMS',
			array(
				'UserName'    => $username,
				'Password'    => $password,
				'PhoneNumber' => self::normalize( $mobile ),
				'PatternCode' => (string) $pattern,
				'PatternValues' => implode( ';', array_map( 'strval', array_values( (array) $values ) ) ),
			),
			array(),
			false
		);

		return self::interpret( $response, array( 'Status', 'Code' ) );
	}

	/* --------------------------------------------------------------------- */
	/* Custom HTTP                                                            */
	/* --------------------------------------------------------------------- */

	/**
	 * Custom endpoint.
	 *
	 * @param string $mobile  Mobile.
	 * @param string $message Message.
	 * @param string $pattern Pattern.
	 * @return true|WP_Error
	 */
	protected static function send_custom( $mobile, $message, $pattern ) {
		$endpoint = devco_opt( 'sms.custom_endpoint', '' );

		if ( ! $endpoint ) {
			return new WP_Error( 'devco_sms_config', 'آدرس سرویس اختصاصی تنظیم نشده است.' );
		}

		$endpoint = strtr(
			$endpoint,
			array(
				'{mobile}'  => rawurlencode( self::normalize( $mobile ) ),
				'{message}' => rawurlencode( $message ),
				'{pattern}' => rawurlencode( (string) $pattern ),
				'{key}'     => rawurlencode( devco_opt( 'sms.api_key', '' ) ),
			)
		);

		$response = wp_remote_get( $endpoint, array( 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 ) {
			return new WP_Error( 'devco_sms_http', 'سرویس اختصاصی خطا برگرداند: ' . $code );
		}

		return true;
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Interpret provider responses.
	 *
	 * @param array|WP_Error $response Response.
	 * @param array          $ok_keys  Keys that indicate success.
	 * @return true|WP_Error
	 */
	protected static function interpret( $response, $ok_keys = array() ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		foreach ( $ok_keys as $key ) {
			if ( ! isset( $response[ $key ] ) || ! is_array( $response[ $key ] ) ) {
				continue;
			}

			if ( isset( $response[ $key ][0] ) ) {
				$first = $response[ $key ][0];

				// Melipayamak: RetStatus == 1 means success.
				if ( 'RetStatus' === $key ) {
					return (int) $first === 1 ? true : new WP_Error( 'devco_sms_failed', 'ارسال پیامک ناموفق بود. کد: ' . $first );
				}

				// sms.ir / ippanel: status == 1 means success.
				if ( in_array( $key, array( 'status', 'Status' ), true ) ) {
					return (int) $first === 1 ? true : new WP_Error( 'devco_sms_failed', 'ارسال پیامک ناموفق بود. کد: ' . $first );
				}

				return (int) $first >= 0 ? true : new WP_Error( 'devco_sms_failed', 'ارسال پیامک ناموفق بود.' );
			}

			return true;
		}

		return true;
	}

	/**
	 * Store a log entry.
	 *
	 * @param string          $mobile  Mobile.
	 * @param string          $message Message.
	 * @param true|WP_Error   $result  Result.
	 * @param string          $event   Event.
	 */
	public static function log( $mobile, $message, $result, $event = 'plain' ) {
		if ( ! devco_opt_bool( 'sms.log', true ) ) {
			return;
		}

		$logs = get_option( 'devco_sms_log', array() );
		$logs = is_array( $logs ) ? $logs : array();

		array_unshift(
			$logs,
			array(
				'time'    => current_time( 'mysql' ),
				'mobile'  => $mobile,
				'message' => $message,
				'event'   => $event,
				'ok'      => ! is_wp_error( $result ),
				'error'   => is_wp_error( $result ) ? $result->get_error_message() : '',
			)
		);

		update_option( 'devco_sms_log', array_slice( $logs, 0, 200 ), false );
	}

	/**
	 * Send an OTP code.
	 *
	 * @param string $mobile Mobile.
	 * @param string $code   Code.
	 * @return true|WP_Error
	 */
	public static function send_otp( $mobile, $code ) {
		return self::send_event(
			$mobile,
			'otp',
			array(
				'code'   => $code,
				'token'  => $code,
				'otp'    => $code,
				'name'   => get_bloginfo( 'name' ),
				'mobile' => self::normalize( $mobile ),
			)
		);
	}

	/**
	 * Notify the shop manager.
	 *
	 * @param string $message Message.
	 * @return true|WP_Error
	 */
	public static function notify_admin( $message ) {
		if ( ! devco_opt_bool( 'sms.admin_notify', true ) ) {
			return new WP_Error( 'devco_sms_admin_off', 'اطلاع‌رسانی مدیر غیرفعال است.' );
		}

		$phone = devco_opt( 'sms.admin_phone', '' );

		if ( ! $phone ) {
			return new WP_Error( 'devco_sms_admin_phone', 'شماره مدیر تنظیم نشده است.' );
		}

		return self::send( $phone, $message );
	}

	/**
	 * Recent logs.
	 *
	 * @return array
	 */
	public static function logs() {
		$logs = get_option( 'devco_sms_log', array() );
		return is_array( $logs ) ? $logs : array();
	}
}
