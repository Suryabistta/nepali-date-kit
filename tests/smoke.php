<?php
/**
 * Integration smoke test inside a real WordPress install.
 *
 * Run: wp eval-file tests/smoke.php
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

$ndk_failures = 0;

$ndk_check = function ( $label, $ok, $detail = '' ) use ( &$ndk_failures ) {
	if ( ! $ok ) {
		++$ndk_failures;
	}
	WP_CLI::log( ( $ok ? 'ok   ' : 'FAIL ' ) . $label . ( '' !== $detail ? " ($detail)" : '' ) );
};

update_option( 'timezone_string', 'Asia/Kathmandu' );
delete_option( 'ndk_settings' );

$ndk_check( 'plugin loaded', function_exists( 'ndk_ad_to_bs' ) && class_exists( 'NDK_Calendar' ) );
$ndk_check( 'block registered', WP_Block_Type_Registry::get_instance()->is_registered( 'nepali-date-kit/today' ) );
$ndk_check( 'shortcodes registered', shortcode_exists( 'ndk_today' ) && shortcode_exists( 'ndk_date' ) && shortcode_exists( 'ndk_converter' ) );

$ndk_out = do_shortcode( '[ndk_date date="2026-10-22" format="Y-m-d" lang="en"]' );
$ndk_check( '[ndk_date] AD->BS', false !== strpos( $ndk_out, '2083-07-05' ), wp_strip_all_tags( $ndk_out ) );
$ndk_out = do_shortcode( '[ndk_date date="2083-07-05" from="bs" format="Y-m-d"]' );
$ndk_check( '[ndk_date] BS->AD', false !== strpos( $ndk_out, '2026-10-22' ), wp_strip_all_tags( $ndk_out ) );
$ndk_check( '[ndk_date] out of range is silent for visitors', '' === do_shortcode( '[ndk_date date="1900-01-01"]' ) );
$ndk_check( '[ndk_converter] renders', false !== strpos( do_shortcode( '[ndk_converter]' ), 'ndk-converter__panel' ) );
$ndk_check( 'converter script enqueued', wp_script_is( 'ndk-converter', 'enqueued' ) );

$ndk_response = rest_do_request( new WP_REST_Request( 'GET', '/ndk/v1/convert' ) );
$ndk_check( 'REST rejects missing params', 400 === $ndk_response->get_status() );
$ndk_request = new WP_REST_Request( 'GET', '/ndk/v1/convert' );
$ndk_request->set_query_params( array( 'from' => 'ad', 'date' => '2026-10-22', 'format' => 'Y-m-d', 'lang' => 'en' ) );
$ndk_data = rest_do_request( $ndk_request )->get_data();
$ndk_check( 'REST converts', isset( $ndk_data['formatted'] ) && '2083-07-05' === $ndk_data['formatted'] );

// Post date filters, as on the front end.
$ndk_post = wp_insert_post(
	array(
		'post_title'  => 'NDK smoke',
		'post_status' => 'publish',
		'post_date'   => '2026-10-22 08:30:00',
	)
);
$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $ndk_post ) );
NDK_Post_Dates::register();
$ndk_check( 'get_the_date() in BS', 'कात्तिक ५, २०८३' === get_the_date( 'F j, Y', $ndk_post ), get_the_date( 'F j, Y', $ndk_post ) );
$ndk_check( 'get_the_date("c") stays AD', 0 === strpos( get_the_date( 'c', $ndk_post ), '2026-10-22T08:30:00' ) );
$ndk_check( 'get_the_date("U") stays a timestamp', is_numeric( get_the_date( 'U', $ndk_post ) ) );
$ndk_check( 'get_the_date("Y-m-d H:i:s") stays AD (schema, comparisons)', '2026-10-22 08:30:00' === get_the_date( 'Y-m-d H:i:s', $ndk_post ), get_the_date( 'Y-m-d H:i:s', $ndk_post ) );
update_option( 'ndk_settings', array( 'date_format' => 'j F Y,' ) );
$ndk_check( 'override applies to the default format', '५ कात्तिक २०८३,' === get_the_date( '', $ndk_post ), get_the_date( '', $ndk_post ) );
$ndk_check( 'override leaves explicit formats alone', 'बिहिबार' === get_the_date( 'l', $ndk_post ), get_the_date( 'l', $ndk_post ) );
delete_option( 'ndk_settings' );

$ndk_block = new WP_Block(
	array(
		'blockName'    => 'core/post-date',
		'attrs'        => array( 'format' => 'F j, Y' ),
		'innerHTML'    => '',
		'innerContent' => array(),
		'innerBlocks'  => array(),
	),
	array(
		'postId'   => $ndk_post,
		'postType' => 'post',
	)
);
$ndk_html = $ndk_block->render();
$ndk_check( 'core/post-date block in BS', false !== strpos( $ndk_html, 'कात्तिक ५, २०८३' ), wp_strip_all_tags( $ndk_html ) );
$ndk_check( 'core/post-date datetime attr stays AD', false !== strpos( $ndk_html, 'datetime="2026-10-22T08:30:00' ) );

$ndk_sanitized = NDK_Settings::sanitize( array( 'date_format' => 'c', 'post_types' => array( 'post', 'not-a-type' ) ) );
$ndk_check( 'settings reject machine formats and unknown post types', '' === $ndk_sanitized['date_format'] && array( 'post' ) === $ndk_sanitized['post_types'] );
$ndk_check( 'settings survive garbage input', is_array( NDK_Settings::sanitize( 'garbage' ) ) );

wp_delete_post( $ndk_post, true );

if ( $ndk_failures ) {
	WP_CLI::error( "$ndk_failures smoke check(s) failed." );
}
WP_CLI::success( 'All smoke checks passed.' );
