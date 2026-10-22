<?php
/**
 * Public API.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default settings.
 *
 * @return array
 */
function ndk_default_settings() {
	return array(
		'lang'                  => 'ne',
		'timezone'              => 'nepal', // 'nepal' (Asia/Kathmandu) or 'site'.
		'convert_date'          => true,
		'convert_time'          => false,
		'convert_modified_date' => true,
		'convert_modified_time' => false,
		'date_format'           => '', // Empty = keep the format the theme asks for.
		'post_types'            => array( 'post', 'page' ),
		'relative'              => false,
		'relative_days'         => 7,
	);
}

/**
 * Get one setting, or all of them.
 *
 * @param string|null $key Setting name.
 * @return mixed
 */
function ndk_get_setting( $key = null ) {
	$saved    = get_option( 'ndk_settings', array() );
	$settings = array_merge( ndk_default_settings(), is_array( $saved ) ? $saved : array() );
	if ( null === $key ) {
		return $settings;
	}
	return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
}

/**
 * Timezone used for Nepali dates.
 *
 * @return DateTimeZone
 */
function ndk_timezone() {
	return 'site' === ndk_get_setting( 'timezone' ) ? wp_timezone() : new DateTimeZone( 'Asia/Kathmandu' );
}

/**
 * Current time in the plugin's timezone.
 *
 * @return DateTimeImmutable
 */
function ndk_now() {
	return new DateTimeImmutable( 'now', ndk_timezone() );
}

/**
 * Normalise a date given as 'YYYY-MM-DD', [y, m, d], ['year'=>, 'month'=>, 'day'=>]
 * or a DateTimeInterface into [y, m, d].
 *
 * @param mixed $date Date.
 * @return int[]|null
 */
function ndk_parse_date( $date ) {
	if ( $date instanceof DateTimeInterface ) {
		return array_map( 'intval', explode( '-', $date->format( 'Y-n-j' ) ) );
	}
	if ( is_array( $date ) ) {
		if ( isset( $date['year'], $date['month'], $date['day'] ) ) {
			$date = array( $date['year'], $date['month'], $date['day'] );
		}
		$date = array_values( $date );
		if ( 3 !== count( $date ) ) {
			return null;
		}
		foreach ( $date as $part ) {
			if ( ! is_int( $part ) && ! ( is_string( $part ) && ctype_digit( trim( $part ) ) ) ) {
				return null;
			}
		}
		return array_map( 'intval', $date );
	}
	if ( is_string( $date ) && preg_match( '/^\s*(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})\s*$/', $date, $m ) ) {
		return array( (int) $m[1], (int) $m[2], (int) $m[3] );
	}
	return null;
}

/**
 * Convert an AD date to BS.
 *
 * @param mixed $date See ndk_parse_date().
 * @return array|WP_Error ['year', 'month', 'day', 'weekday' (0 = Sunday)].
 */
function ndk_ad_to_bs( $date ) {
	$parts = ndk_parse_date( $date );
	$bs    = $parts ? NDK_Calendar::ad_to_bs( $parts[0], $parts[1], $parts[2] ) : null;
	if ( ! $bs ) {
		return ndk_range_error( 'ad', $parts );
	}
	return $bs;
}

/**
 * Convert a BS date to AD.
 *
 * @param mixed $date See ndk_parse_date().
 * @return array|WP_Error ['year', 'month', 'day', 'weekday' (0 = Sunday)].
 */
function ndk_bs_to_ad( $date ) {
	$parts = ndk_parse_date( $date );
	$ad    = $parts ? NDK_Calendar::bs_to_ad( $parts[0], $parts[1], $parts[2] ) : null;
	if ( ! $ad ) {
		return ndk_range_error( 'bs', $parts );
	}
	return $ad;
}

/**
 * Format a BS date array.
 *
 * @param array                  $bs     From ndk_ad_to_bs().
 * @param string                 $format PHP date() format.
 * @param string|null            $lang   'ne' or 'en'; defaults to the setting.
 * @param DateTimeInterface|null $time   Time of day for time tokens.
 * @return string
 */
function ndk_format_bs( array $bs, $format = 'l, j F Y', $lang = null, $time = null ) {
	return NDK_Formatter::format( $bs, (string) $format, $lang ? $lang : ndk_get_setting( 'lang' ), $time );
}

/**
 * Format a moment as a BS date string, in the plugin's timezone.
 *
 * @param DateTimeInterface $datetime Moment.
 * @param string            $format   PHP date() format.
 * @param string|null       $lang     'ne' or 'en'.
 * @return string Empty string if the date is out of range.
 */
function ndk_format_datetime( DateTimeInterface $datetime, $format = 'l, j F Y', $lang = null ) {
	$immutable = $datetime instanceof DateTimeImmutable ? $datetime : DateTimeImmutable::createFromMutable( $datetime );
	$local     = $immutable->setTimezone( ndk_timezone() );
	$bs    = ndk_ad_to_bs( $local );
	if ( is_wp_error( $bs ) ) {
		return '';
	}
	return ndk_format_bs( $bs, $format, $lang, $local );
}

/**
 * Today's BS date.
 *
 * @return array
 */
function ndk_today() {
	return ndk_ad_to_bs( ndk_now() );
}

/**
 * Human-readable difference, e.g. "३ दिन अगाडि" / "3 days ago".
 *
 * @param int         $from Unix timestamp.
 * @param int         $to   Unix timestamp (default now).
 * @param string|null $lang 'ne' or 'en'.
 * @return string
 */
function ndk_human_time_diff( $from, $to = 0, $lang = null ) {
	$to     = $to ? $to : time();
	$lang   = $lang ? $lang : ndk_get_setting( 'lang' );
	$diff   = abs( $to - $from );
	$future = $from > $to;

	$units = array(
		array( YEAR_IN_SECONDS, 'वर्ष', 'year' ),
		array( MONTH_IN_SECONDS, 'महिना', 'month' ),
		array( WEEK_IN_SECONDS, 'हप्ता', 'week' ),
		array( DAY_IN_SECONDS, 'दिन', 'day' ),
		array( HOUR_IN_SECONDS, 'घण्टा', 'hour' ),
		array( MINUTE_IN_SECONDS, 'मिनेट', 'minute' ),
		array( 1, 'सेकेन्ड', 'second' ),
	);
	foreach ( $units as $u ) {
		if ( $diff >= $u[0] || 1 === $u[0] ) {
			$n = max( 1, (int) round( $diff / $u[0] ) );
			break;
		}
	}

	if ( 'en' === $lang ) {
		$text = $n . ' ' . $u[2] . ( 1 === $n ? '' : 's' );
		$text = $future ? 'in ' . $text : $text . ' ago';
	} else {
		$text = NDK_Formatter::nepali_digits( $n ) . ' ' . $u[1] . ' ' . ( $future ? 'पछि' : 'अगाडि' );
	}
	return apply_filters( 'ndk_human_time_diff', $text, $from, $to, $lang );
}

/**
 * Build the out-of-range error.
 *
 * @param string     $from  'ad' or 'bs'.
 * @param int[]|null $parts Parsed input.
 * @return WP_Error
 */
function ndk_range_error( $from, $parts ) {
	$range = NDK_Calendar::range();
	$r     = $range[ $from ];
	$fmt   = function ( $d ) {
		return sprintf( '%04d-%02d-%02d', $d[0], $d[1], $d[2] );
	};
	if ( ! $parts ) {
		$message = __( 'Invalid date. Use the format YYYY-MM-DD.', 'nepali-date-kit' );
	} else {
		$message = sprintf(
			/* translators: 1: calendar (BS/AD), 2: first supported date, 3: last supported date. */
			__( 'Invalid or unsupported %1$s date. Supported range: %2$s to %3$s.', 'nepali-date-kit' ),
			'ad' === $from ? 'AD' : 'BS',
			$fmt( $r[0] ),
			$fmt( $r[1] )
		);
	}
	return new WP_Error( 'ndk_invalid_date', $message, array( 'status' => 400 ) );
}
