<?php
/**
 * Formats BS dates using PHP date() format characters.
 *
 * Pure PHP, no WordPress dependency. Walks the format one character at a
 * time, so backslash escapes and literal text are kept intact.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Formatter {

	const LANG_NEPALI  = 'ne';
	const LANG_ENGLISH = 'en';

	const DIGITS = array( '०', '१', '२', '३', '४', '५', '६', '७', '८', '९' );

	const MONTHS = array(
		'ne' => array( 'बैशाख', 'जेठ', 'असार', 'साउन', 'भदौ', 'असोज', 'कात्तिक', 'मंसिर', 'पुस', 'माघ', 'फागुन', 'चैत' ),
		'en' => array( 'Baishakh', 'Jestha', 'Asar', 'Shrawan', 'Bhadra', 'Ashwin', 'Kartik', 'Mangsir', 'Poush', 'Magh', 'Falgun', 'Chaitra' ),
	);

	const MONTHS_SHORT = array(
		// No standard Nepali abbreviations exist (असार/असोज would collide), so use full names.
		'ne' => array( 'बैशाख', 'जेठ', 'असार', 'साउन', 'भदौ', 'असोज', 'कात्तिक', 'मंसिर', 'पुस', 'माघ', 'फागुन', 'चैत' ),
		'en' => array( 'Bai', 'Jes', 'Asa', 'Shr', 'Bha', 'Ash', 'Kar', 'Man', 'Pou', 'Mag', 'Fal', 'Cha' ),
	);

	/* Sunday first, matching NDK_Calendar weekday numbers. */
	const DAYS = array(
		'ne' => array( 'आइतबार', 'सोमबार', 'मङ्गलबार', 'बुधबार', 'बिहिबार', 'शुक्रबार', 'शनिबार' ),
		'en' => array( 'Aaitabar', 'Sombar', 'Mangalbar', 'Budhabar', 'Bihibar', 'Shukrabar', 'Shanibar' ),
	);

	const DAYS_SHORT = array(
		'ne' => array( 'आइत', 'सोम', 'मङ्गल', 'बुध', 'बिहि', 'शुक्र', 'शनि' ),
		'en' => array( 'Aaita', 'Som', 'Mangal', 'Budha', 'Bihi', 'Shukra', 'Shani' ),
	);

	const MERIDIEM = array(
		'ne' => array( 'पूर्वाह्न', 'अपराह्न' ),
		'en' => array( 'am', 'pm' ),
	);

	/**
	 * Format characters whose output is meant for machines (timestamps, ISO
	 * dates, timezones). A format containing any of them is not a display
	 * format and should be left to PHP/WordPress.
	 */
	const MACHINE_TOKENS = 'crUueIOPpTZvB';

	/**
	 * Format a BS date.
	 *
	 * @param array                   $bs     As returned by NDK_Calendar (year, month, day, weekday).
	 * @param string                  $format PHP date() style format.
	 * @param string                  $lang   'ne' (Devanagari) or 'en' (romanised).
	 * @param DateTimeInterface|null  $time   Supplies the time of day for a/A/g/G/h/H/i/s.
	 * @return string
	 */
	public static function format( array $bs, $format, $lang = self::LANG_NEPALI, $time = null ) {
		$lang = self::LANG_ENGLISH === $lang ? self::LANG_ENGLISH : self::LANG_NEPALI;
		$out  = '';
		$len  = strlen( $format );

		for ( $i = 0; $i < $len; $i++ ) {
			$c = $format[ $i ];
			if ( '\\' === $c ) {
				++$i;
				if ( $i < $len ) {
					$out .= self::utf8_char_at( $format, $i );
				}
				continue;
			}
			$token = self::token( $c, $bs, $lang, $time );
			if ( null === $token ) {
				$out .= self::utf8_char_at( $format, $i );
			} else {
				$out .= $token;
			}
		}
		return $out;
	}

	/**
	 * Whether a format is for machines (e.g. 'c', 'U', DATE_W3C) rather than display.
	 */
	public static function is_machine_format( $format ) {
		if ( 'G' === $format ) {
			return true; // get_post_time() treats a bare 'G' like 'U'.
		}
		$len = strlen( $format );
		for ( $i = 0; $i < $len; $i++ ) {
			if ( '\\' === $format[ $i ] ) {
				++$i;
				continue;
			}
			if ( false !== strpos( self::MACHINE_TOKENS, $format[ $i ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a format only uses zero-padded numeric tokens, e.g. 'Y-m-d' or
	 * 'Y-m-d H:i:s'. Themes and plugins request these for structured data,
	 * sorting and date comparisons, so post-date filters leave them in AD.
	 */
	public static function is_numeric_format( $format ) {
		$len     = strlen( $format );
		$letters = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$c = $format[ $i ];
			if ( '\\' === $c ) {
				++$i;
				continue;
			}
			if ( ctype_alpha( $c ) ) {
				if ( false === strpos( 'YymdHisvu', $c ) ) {
					return false;
				}
				++$letters;
			}
		}
		return $letters > 0;
	}

	/**
	 * Convert ASCII digits to Devanagari.
	 */
	public static function nepali_digits( $str ) {
		return strtr( (string) $str, array_combine( range( 0, 9 ), self::DIGITS ) );
	}

	/* ------------------------------------------------------------------ */

	private static function token( $c, array $bs, $lang, $time ) {
		$num = function ( $n, $pad = 0 ) use ( $lang ) {
			$s = $pad ? str_pad( (string) $n, $pad, '0', STR_PAD_LEFT ) : (string) $n;
			return self::LANG_NEPALI === $lang ? self::nepali_digits( $s ) : $s;
		};

		switch ( $c ) {
			case 'd':
				return $num( $bs['day'], 2 );
			case 'j':
				return $num( $bs['day'] );
			case 'S':
				return '';
			case 'l':
				return self::DAYS[ $lang ][ $bs['weekday'] ];
			case 'D':
				return self::DAYS_SHORT[ $lang ][ $bs['weekday'] ];
			case 'N':
				return $num( 0 === $bs['weekday'] ? 7 : $bs['weekday'] );
			case 'w':
				return $num( $bs['weekday'] );
			case 'z':
				return $num( (int) NDK_Calendar::day_of_year( $bs['year'], $bs['month'], $bs['day'] ) );
			case 't':
				return $num( (int) NDK_Calendar::days_in_month( $bs['year'], $bs['month'] ) );
			case 'm':
				return $num( $bs['month'], 2 );
			case 'n':
				return $num( $bs['month'] );
			case 'F':
				return self::MONTHS[ $lang ][ $bs['month'] - 1 ];
			case 'M':
				return self::MONTHS_SHORT[ $lang ][ $bs['month'] - 1 ];
			case 'Y':
				return $num( $bs['year'] );
			case 'y':
				return $num( $bs['year'] % 100, 2 );
		}

		if ( false === strpos( 'aAgGhHis', $c ) ) {
			return null;
		}

		$hour = $time instanceof DateTimeInterface ? (int) $time->format( 'G' ) : 0;
		switch ( $c ) {
			case 'a':
				return self::MERIDIEM[ $lang ][ $hour >= 12 ? 1 : 0 ];
			case 'A':
				$m = self::MERIDIEM[ $lang ][ $hour >= 12 ? 1 : 0 ];
				return self::LANG_ENGLISH === $lang ? strtoupper( $m ) : $m;
			case 'g':
				return $num( 0 === $hour % 12 ? 12 : $hour % 12 );
			case 'h':
				return $num( 0 === $hour % 12 ? 12 : $hour % 12, 2 );
			case 'G':
				return $num( $hour );
			case 'H':
				return $num( $hour, 2 );
			case 'i':
				return $num( $time instanceof DateTimeInterface ? (int) $time->format( 'i' ) : 0, 2 );
			case 's':
				return $num( $time instanceof DateTimeInterface ? (int) $time->format( 's' ) : 0, 2 );
		}
		return null;
	}

	/**
	 * Return the full UTF-8 character starting at byte $i and advance $i past it.
	 */
	private static function utf8_char_at( $str, &$i ) {
		$b = ord( $str[ $i ] );
		$n = $b < 0x80 ? 1 : ( $b < 0xE0 ? 2 : ( $b < 0xF0 ? 3 : 4 ) );
		$ch = substr( $str, $i, $n );
		$i += $n - 1;
		return $ch;
	}
}
