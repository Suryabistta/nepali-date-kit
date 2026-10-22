<?php
/**
 * Bikram Sambat (BS) <-> Gregorian (AD) calendar engine.
 *
 * Pure PHP, no WordPress dependency. Conversion works on day numbers
 * (days since BS 2000-01-01 = AD 1943-04-14), so every conversion is a
 * handful of array lookups instead of a day-by-day loop.
 *
 * All public methods return null for invalid or out-of-range input; they
 * never throw, die or emit warnings.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Calendar {

	/** First BS year in the table. */
	const FIRST_BS_YEAR = 2000;

	/** AD date of BS 2000-01-01. */
	const EPOCH_AD = array( 1943, 4, 14 );

	/**
	 * Last BS year checked against the published Nepal calendar (Hamro Patro).
	 * Later years are projections and may change once officially published.
	 */
	const LAST_VERIFIED_BS_YEAR = 2083;

	/**
	 * Days in each BS month, Baishakh..Chaitra.
	 *
	 * @var array<int, int[]>
	 */
	private static $months = array(
		2000 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2001 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2002 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2003 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2004 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2005 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2006 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2007 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2008 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31 ),
		2009 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2010 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2011 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2012 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30 ),
		2013 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2014 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2015 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2016 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30 ),
		2017 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2018 => array( 31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2019 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2020 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2021 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2022 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30 ),
		2023 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2024 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2025 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2026 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2027 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2028 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2029 => array( 31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30 ),
		2030 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2031 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2032 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2033 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2034 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2035 => array( 30, 32, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31 ),
		2036 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2037 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2038 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2039 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30 ),
		2040 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2041 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2042 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2043 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30 ),
		2044 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2045 => array( 31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2046 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2047 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2048 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2049 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30 ),
		2050 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2051 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2052 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2053 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30 ),
		2054 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2055 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2056 => array( 31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30 ),
		2057 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2058 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2059 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2060 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2061 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2062 => array( 31, 31, 31, 32, 31, 31, 29, 30, 29, 30, 29, 31 ),
		2063 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2064 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2065 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2066 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31 ),
		2067 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2068 => array( 31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2069 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2070 => array( 31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30 ),
		2071 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2072 => array( 31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30 ),
		2073 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
		2074 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2075 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2076 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30 ),
		2077 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2078 => array( 31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2079 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2080 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30 ),
		2081 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31 ),
		2082 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2083 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30 ),
		2084 => array( 31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2085 => array( 31, 32, 31, 32, 30, 31, 30, 30, 29, 30, 30, 30 ),
		2086 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2087 => array( 31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30 ),
		2088 => array( 30, 31, 32, 32, 30, 31, 30, 30, 29, 30, 30, 30 ),
		2089 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2090 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2091 => array( 31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30 ),
		2092 => array( 30, 31, 32, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2093 => array( 30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2094 => array( 31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2095 => array( 31, 31, 32, 31, 31, 31, 30, 29, 30, 30, 30, 30 ),
		2096 => array( 30, 31, 32, 32, 32, 30, 30, 29, 30, 29, 30, 30 ),
		2097 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30 ),
		2098 => array( 31, 31, 32, 31, 31, 31, 29, 30, 29, 30, 29, 31 ),
		2099 => array( 31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31 ),
	);

	/** @var int[]|null Day number of Baishakh 1 for each year, plus one past the end. */
	private static $year_starts = null;

	/** @var int|null */
	private static $epoch_days = null;

	/**
	 * Convert an AD date to BS.
	 *
	 * @return array{year:int,month:int,day:int,weekday:int}|null weekday: 0 = Sunday.
	 */
	public static function ad_to_bs( $year, $month, $day ) {
		if ( ! self::is_int_like( $year, $month, $day ) ) {
			return null;
		}
		list( $year, $month, $day ) = array( (int) $year, (int) $month, (int) $day );
		if ( ! checkdate( $month, $day, $year ) ) {
			return null;
		}
		return self::from_day_number( self::days_from_civil( $year, $month, $day ) - self::epoch_days() );
	}

	/**
	 * Convert a BS date to AD.
	 *
	 * @return array{year:int,month:int,day:int,weekday:int}|null weekday: 0 = Sunday.
	 */
	public static function bs_to_ad( $year, $month, $day ) {
		$n = self::bs_day_number( $year, $month, $day );
		if ( null === $n ) {
			return null;
		}
		list( $y, $m, $d ) = self::civil_from_days( $n + self::epoch_days() );
		return array(
			'year'    => $y,
			'month'   => $m,
			'day'     => $d,
			'weekday' => self::weekday( $n ),
		);
	}

	/**
	 * Whether a BS date exists in the table.
	 */
	public static function is_valid_bs( $year, $month, $day ) {
		return null !== self::bs_day_number( $year, $month, $day );
	}

	/**
	 * Days in a BS month, or null if unknown.
	 */
	public static function days_in_month( $year, $month ) {
		$year  = (int) $year;
		$month = (int) $month;
		if ( ! isset( self::$months[ $year ] ) || $month < 1 || $month > 12 ) {
			return null;
		}
		return self::$months[ $year ][ $month - 1 ];
	}

	/**
	 * Day of the BS year, 0-based (Baishakh 1 = 0).
	 */
	public static function day_of_year( $year, $month, $day ) {
		$n = self::bs_day_number( $year, $month, $day );
		return null === $n ? null : $n - self::year_starts()[ (int) $year - self::FIRST_BS_YEAR ];
	}

	/**
	 * Whether a BS year's data is a projection rather than the published calendar.
	 */
	public static function is_projected( $bs_year ) {
		return (int) $bs_year > self::LAST_VERIFIED_BS_YEAR;
	}

	/**
	 * Supported range, as BS and AD [year, month, day] triples.
	 *
	 * @return array{bs:array{0:int[],1:int[]},ad:array{0:int[],1:int[]}}
	 */
	public static function range() {
		$last_year = self::last_bs_year();
		$last_bs   = array( $last_year, 12, self::$months[ $last_year ][11] );
		$last_ad   = self::bs_to_ad( $last_bs[0], $last_bs[1], $last_bs[2] );
		return array(
			'bs' => array( array( self::FIRST_BS_YEAR, 1, 1 ), $last_bs ),
			'ad' => array( self::EPOCH_AD, array( $last_ad['year'], $last_ad['month'], $last_ad['day'] ) ),
		);
	}

	/**
	 * @return int
	 */
	public static function last_bs_year() {
		return self::FIRST_BS_YEAR + count( self::$months ) - 1;
	}

	/**
	 * Raw month-length table, for client-side day pickers.
	 *
	 * @return array<int, int[]>
	 */
	public static function month_table() {
		return self::$months;
	}

	/* ------------------------------------------------------------------ */

	private static function bs_day_number( $year, $month, $day ) {
		if ( ! self::is_int_like( $year, $month, $day ) ) {
			return null;
		}
		$len = self::days_in_month( $year, $month );
		$day = (int) $day;
		if ( null === $len || $day < 1 || $day > $len ) {
			return null;
		}
		$year  = (int) $year;
		$month = (int) $month;
		$n     = self::year_starts()[ $year - self::FIRST_BS_YEAR ];
		for ( $i = 0; $i < $month - 1; $i++ ) {
			$n += self::$months[ $year ][ $i ];
		}
		return $n + $day - 1;
	}

	private static function from_day_number( $n ) {
		$starts = self::year_starts();
		if ( $n < 0 || $n >= end( $starts ) ) {
			return null;
		}
		// Binary search for the year whose start is <= $n.
		$lo = 0;
		$hi = count( $starts ) - 2;
		while ( $lo < $hi ) {
			$mid = ( $lo + $hi + 1 ) >> 1;
			if ( $starts[ $mid ] <= $n ) {
				$lo = $mid;
			} else {
				$hi = $mid - 1;
			}
		}
		$year = self::FIRST_BS_YEAR + $lo;
		$rem  = $n - $starts[ $lo ];
		$month = 1;
		foreach ( self::$months[ $year ] as $len ) {
			if ( $rem < $len ) {
				break;
			}
			$rem -= $len;
			++$month;
		}
		return array(
			'year'    => $year,
			'month'   => $month,
			'day'     => $rem + 1,
			'weekday' => self::weekday( $n ),
		);
	}

	private static function year_starts() {
		if ( null === self::$year_starts ) {
			$acc   = 0;
			$start = array();
			foreach ( self::$months as $lens ) {
				$start[] = $acc;
				$acc    += array_sum( $lens );
			}
			$start[]           = $acc;
			self::$year_starts = $start;
		}
		return self::$year_starts;
	}

	private static function epoch_days() {
		if ( null === self::$epoch_days ) {
			self::$epoch_days = self::days_from_civil( self::EPOCH_AD[0], self::EPOCH_AD[1], self::EPOCH_AD[2] );
		}
		return self::$epoch_days;
	}

	/** 0 = Sunday. BS 2000-01-01 (AD 1943-04-14) was a Wednesday. */
	private static function weekday( $n ) {
		return ( $n + 3 ) % 7;
	}

	/**
	 * Days since 1970-01-01 for a proleptic Gregorian date (H. Hinnant's algorithm).
	 */
	private static function days_from_civil( $y, $m, $d ) {
		$y  -= $m <= 2 ? 1 : 0;
		$era = intdiv( $y >= 0 ? $y : $y - 399, 400 );
		$yoe = $y - $era * 400;
		$doy = intdiv( 153 * ( $m + ( $m > 2 ? -3 : 9 ) ) + 2, 5 ) + $d - 1;
		$doe = $yoe * 365 + intdiv( $yoe, 4 ) - intdiv( $yoe, 100 ) + $doy;
		return $era * 146097 + $doe - 719468;
	}

	/**
	 * Inverse of days_from_civil().
	 *
	 * @return int[] [year, month, day]
	 */
	private static function civil_from_days( $z ) {
		$z  += 719468;
		$era = intdiv( $z >= 0 ? $z : $z - 146096, 146097 );
		$doe = $z - $era * 146097;
		$yoe = intdiv( $doe - intdiv( $doe, 1460 ) + intdiv( $doe, 36524 ) - intdiv( $doe, 146096 ), 365 );
		$y   = $yoe + $era * 400;
		$doy = $doe - ( 365 * $yoe + intdiv( $yoe, 4 ) - intdiv( $yoe, 100 ) );
		$mp  = intdiv( 5 * $doy + 2, 153 );
		$d   = $doy - intdiv( 153 * $mp + 2, 5 ) + 1;
		$m   = $mp + ( $mp < 10 ? 3 : -9 );
		return array( $y + ( $m <= 2 ? 1 : 0 ), $m, $d );
	}

	private static function is_int_like( ...$values ) {
		foreach ( $values as $v ) {
			if ( is_int( $v ) ) {
				continue;
			}
			if ( ! is_string( $v ) || ! preg_match( '/^\s*\d{1,4}\s*$/', $v ) ) {
				return false;
			}
		}
		return true;
	}
}
