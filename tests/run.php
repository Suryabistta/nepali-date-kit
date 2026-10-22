<?php
/**
 * Dependency-free tests for the calendar engine and formatter.
 *
 * Run: php tests/run.php
 *
 * @package Nepali_Date_Kit
 */

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/includes/class-ndk-calendar.php';
require dirname( __DIR__ ) . '/includes/class-ndk-formatter.php';

$failures = 0;
$checks   = 0;

function check( $label, $actual, $expected ) {
	global $failures, $checks;
	++$checks;
	if ( $actual !== $expected ) {
		++$failures;
		fwrite( STDERR, "FAIL: $label\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
	}
}

function ymd( $d ) {
	return null === $d ? null : sprintf( '%04d-%02d-%02d', $d['year'], $d['month'], $d['day'] );
}

/*
 * Month boundaries checked against the published calendar (hamropatro.com).
 * AD => BS.
 */
$known = array(
	'1943-04-14' => '2000-01-01',
	'2005-04-14' => '2062-01-01',
	'2005-05-14' => '2062-01-31',
	'2005-05-15' => '2062-02-01',
	'2005-06-15' => '2062-03-01',
	'2005-07-16' => '2062-04-01',
	'2024-04-13' => '2081-01-01',
	'2024-05-14' => '2081-02-01',
	'2024-07-16' => '2081-04-01',
	'2024-10-17' => '2081-07-01',
	'2025-01-14' => '2081-10-01',
	'2025-04-14' => '2082-01-01',
	'2025-05-15' => '2082-02-01',
	'2025-05-29' => '2082-02-15', // Republic Day.
	'2025-07-17' => '2082-04-01',
	'2025-09-17' => '2082-06-01',
	'2025-10-17' => '2082-06-31',
	'2025-11-17' => '2082-08-01',
	'2025-12-16' => '2082-09-01',
	'2026-01-15' => '2082-10-01',
	'2026-02-13' => '2082-11-01',
	'2026-04-13' => '2082-12-30',
	'2026-04-14' => '2083-01-01',
	'2026-06-15' => '2083-03-01',
	'2026-07-17' => '2083-04-01',
	'2026-08-17' => '2083-05-01',
	'2026-09-17' => '2083-06-01',
	'2026-10-17' => '2083-06-31',
	'2026-10-18' => '2083-07-01',
	'2026-10-22' => '2083-07-05',
	'2026-11-17' => '2083-08-01',
	'2026-12-16' => '2083-09-01',
	'2027-01-15' => '2083-10-01',
	'2027-02-13' => '2083-11-01',
	'2027-03-15' => '2083-12-01',
	'2027-04-13' => '2083-12-30',
	'2027-04-14' => '2084-01-01',
);
foreach ( $known as $ad => $bs ) {
	list( $y, $m, $d ) = array_map( 'intval', explode( '-', $ad ) );
	check( "AD $ad -> BS", ymd( NDK_Calendar::ad_to_bs( $y, $m, $d ) ), $bs );
	list( $y, $m, $d ) = array_map( 'intval', explode( '-', $bs ) );
	check( "BS $bs -> AD", ymd( NDK_Calendar::bs_to_ad( $y, $m, $d ) ), $ad );
}

// Every day in range round-trips and has the right weekday.
$range = NDK_Calendar::range();
$day   = new DateTimeImmutable( vsprintf( '%04d-%02d-%02d', $range['ad'][0] ), new DateTimeZone( 'UTC' ) );
$end   = new DateTimeImmutable( vsprintf( '%04d-%02d-%02d', $range['ad'][1] ), new DateTimeZone( 'UTC' ) );
$bad   = 0;
for ( ; $day <= $end; $day = $day->modify( '+1 day' ) ) {
	list( $y, $m, $d ) = array_map( 'intval', explode( '-', $day->format( 'Y-n-j' ) ) );
	$bs = NDK_Calendar::ad_to_bs( $y, $m, $d );
	$ad = $bs ? NDK_Calendar::bs_to_ad( $bs['year'], $bs['month'], $bs['day'] ) : null;
	if ( ! $bs || ymd( $ad ) !== $day->format( 'Y-m-d' ) || $bs['weekday'] !== (int) $day->format( 'w' ) ) {
		++$bad;
	}
}
check( 'full-range round trip + weekday', $bad, 0 );
check( 'range end AD', $range['ad'][1], array( 2043, 4, 15 ) );
check( 'range end BS', $range['bs'][1], array( 2099, 12, 31 ) );

// Every year in the table is 365 or 366 days.
foreach ( NDK_Calendar::month_table() as $year => $lens ) {
	check( "BS $year length", in_array( array_sum( $lens ), array( 365, 366 ), true ), true );
}

// Invalid input never throws and returns null.
$invalid = array(
	array( 'ad', 1943, 4, 13 ),  // Before range.
	array( 'ad', 2043, 4, 16 ),  // After range.
	array( 'ad', 2023, 2, 30 ),  // Impossible.
	array( 'ad', 2023, 2, 29 ),  // Not a leap year.
	array( 'ad', 2024, 13, 1 ),
	array( 'ad', 'abc', 1, 1 ),
	array( 'ad', '2024', '1x', 1 ),
	array( 'bs', 1999, 12, 30 ),
	array( 'bs', 2100, 1, 1 ),
	array( 'bs', 2083, 8, 30 ),  // Mangsir 2083 has 29 days.
	array( 'bs', 2081, 1, 32 ),
	array( 'bs', 2083, 0, 1 ),
	array( 'bs', 2083, 1, 0 ),
	array( 'bs', null, 1, 1 ),
);
foreach ( $invalid as $case ) {
	list( $dir, $y, $m, $d ) = $case;
	$result = 'ad' === $dir ? NDK_Calendar::ad_to_bs( $y, $m, $d ) : NDK_Calendar::bs_to_ad( $y, $m, $d );
	check( "invalid $dir " . var_export( array( $y, $m, $d ), true ), $result, null );
}
check( 'numeric strings accepted', ymd( NDK_Calendar::ad_to_bs( '2026', '10', '22' ) ), '2083-07-05' );
check( 'days_in_month', NDK_Calendar::days_in_month( 2083, 8 ), 29 );
check( 'day_of_year', NDK_Calendar::day_of_year( 2083, 1, 1 ), 0 );
check( 'projected 2083', NDK_Calendar::is_projected( 2083 ), false );
check( 'projected 2084', NDK_Calendar::is_projected( 2084 ), true );

// Formatter.
$bs   = NDK_Calendar::ad_to_bs( 2026, 10, 22 ); // Thursday.
$time = new DateTimeImmutable( '2026-10-22 14:05:09', new DateTimeZone( 'Asia/Kathmandu' ) );
$f    = function ( $format, $lang = 'ne', $t = null ) use ( $bs ) {
	return NDK_Formatter::format( $bs, $format, $lang, $t );
};
check( 'ne default', $f( 'l, j F Y' ), 'बिहिबार, ५ कात्तिक २०८३' );
check( 'en default', $f( 'l, j F Y', 'en' ), 'Bihibar, 5 Kartik 2083' );
check( 'numeric', $f( 'Y-m-d', 'en' ), '2083-07-05' );
check( 'numeric ne', $f( 'Y-m-d' ), '२०८३-०७-०५' );
check( 'short names', $f( 'D, M y', 'en' ), 'Bihi, Kar 83' );
check( 'escapes', $f( 'j \o\f F', 'en' ), '5 of Kartik' );
check( 'escaped backslash', $f( 'Y\\\\m', 'en' ), '2083\\07' );
check( 'literal text kept', $f( 'आज: l', 'ne' ), 'आज: बिहिबार' );
check( 'S is empty', $f( 'jS', 'en' ), '5' );
check( 'N w t z', $f( 'N w t z', 'en' ), '4 4 30 191' );
check( 'time en', $f( 'g:i:s a / A / H h G', 'en', $time ), '2:05:09 pm / PM / 14 02 14' );
check( 'time ne', $f( 'g:i a', 'ne', $time ), '२:०५ अपराह्न' );
check( 'no time -> midnight', $f( 'H:i', 'en' ), '00:00' );
check( 'unknown chars pass through', $f( 'Y-W', 'en' ), '2083-W' );
check( 'tuesday spelling', NDK_Formatter::format( NDK_Calendar::ad_to_bs( 2026, 10, 20 ), 'l' ), 'मङ्गलबार' );

// Name overrides (month spelling setting / ndk_month_names filter).
NDK_Formatter::set_names( 'months', array( 'ne' => NDK_Formatter::MONTHS_FORMAL['ne'] ) );
check( 'formal month name', $f( 'F' ), 'कार्तिक' );
check( 'english names untouched by ne override', $f( 'F', 'en' ), 'Kartik' );
NDK_Formatter::set_names( 'months', array( 'ne' => array( 'too', 'short' ) ) );
check( 'wrong-length list ignored', $f( 'F' ), 'कार्तिक' );
NDK_Formatter::reset_names();
check( 'reset to everyday names', $f( 'F' ), 'कात्तिक' );

foreach ( array( 'c', 'r', 'U', 'G', DATE_W3C, DATE_ATOM, DATE_RFC2822, DATE_RSS, 'Y-m-d\TH:i:sP', 'T' ) as $fmt ) {
	check( "machine format $fmt", NDK_Formatter::is_machine_format( $fmt ), true );
}
foreach ( array( 'F j, Y', 'Y-m-d', 'g:i a', 'l, j F Y', 'j \o\f F', 'H:i', '\T\o\d\a\y' ) as $fmt ) {
	check( "display format $fmt", NDK_Formatter::is_machine_format( $fmt ), false );
}

foreach ( array( 'Y-m-d', 'Y-m-d H:i:s', 'Ymd', 'Y/m/d', 'H:i' ) as $fmt ) {
	check( "numeric format $fmt", NDK_Formatter::is_numeric_format( $fmt ), true );
}
foreach ( array( 'j F Y', 'F j, Y', 'l', 'Y-n-j', 'g:i a', 'j \\o\\f F', '' ) as $fmt ) {
	check( "not numeric format $fmt", NDK_Formatter::is_numeric_format( $fmt ), false );
}

// Speed: conversions should be cheap enough for long post lists.
$t0 = microtime( true );
for ( $i = 0; $i < 10000; $i++ ) {
	NDK_Calendar::ad_to_bs( 2043, 4, 1 );
}
$per_call_us = ( microtime( true ) - $t0 ) / 10000 * 1e6;
check( 'ad_to_bs under 50µs', $per_call_us < 50, true );

printf( "%d checks, %d failures (ad_to_bs %.1fµs/call)\n", $checks, $failures, $per_call_us );
exit( $failures ? 1 : 0 );
