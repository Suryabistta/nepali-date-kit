<?php
/**
 * Plugin Name:       Nepali Date Kit
 * Description:       Bikram Sambat (BS) dates for WordPress: post dates in BS, today's date, and a BS ⇄ AD converter (shortcodes, widgets and a block).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Surya Bista
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nepali-date-kit
 * Domain Path:       /languages
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

define( 'NDK_VERSION', '1.0.0' );
define( 'NDK_FILE', __FILE__ );
define( 'NDK_DIR', plugin_dir_path( __FILE__ ) );
define( 'NDK_URL', plugin_dir_url( __FILE__ ) );

require_once NDK_DIR . 'includes/class-ndk-calendar.php';
require_once NDK_DIR . 'includes/class-ndk-formatter.php';
require_once NDK_DIR . 'includes/functions.php';
require_once NDK_DIR . 'includes/class-ndk-assets.php';
require_once NDK_DIR . 'includes/class-ndk-shortcodes.php';
require_once NDK_DIR . 'includes/class-ndk-rest.php';
require_once NDK_DIR . 'includes/class-ndk-post-dates.php';
require_once NDK_DIR . 'includes/class-ndk-widgets.php';
require_once NDK_DIR . 'includes/class-ndk-settings.php';

NDK_Assets::init();
NDK_Shortcodes::init();
NDK_REST::init();
NDK_Post_Dates::init();
NDK_Widgets::init();

if ( is_admin() ) {
	NDK_Settings::init();
}

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'nepali-date-kit', false, dirname( plugin_basename( NDK_FILE ) ) . '/languages' );

		register_block_type(
			NDK_DIR . 'blocks/today',
			array(
				'render_callback' => function ( $attributes ) {
					$html = NDK_Shortcodes::today(
						array(
							'format'    => isset( $attributes['format'] ) ? $attributes['format'] : '',
							'lang'      => ! empty( $attributes['lang'] ) ? $attributes['lang'] : ndk_get_setting( 'lang' ),
							'show_ad'   => ! empty( $attributes['showAd'] ) ? 'yes' : 'no',
							'ad_format' => isset( $attributes['adFormat'] ) ? $attributes['adFormat'] : '',
						)
					);
					return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
				},
			)
		);
	}
);
