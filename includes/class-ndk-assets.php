<?php
/**
 * Front-end assets, loaded only where a converter is rendered.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( wp_script_is( 'ndk-converter', 'registered' ) ) {
			return;
		}
		wp_register_style( 'ndk-converter', NDK_URL . 'assets/css/converter.css', array(), NDK_VERSION );
		wp_register_script( 'ndk-converter', NDK_URL . 'assets/js/converter.js', array(), NDK_VERSION, true );
		wp_add_inline_script(
			'ndk-converter',
			'window.ndkConverter = ' . wp_json_encode(
				array(
					'endpoint' => esc_url_raw( rest_url( NDK_REST::NAMESPACE_V1 . '/convert' ) ),
					'months'   => NDK_Calendar::month_table(),
					'i18n'     => array(
						'error' => __( 'Could not convert this date.', 'nepali-date-kit' ),
					),
				)
			) . ';',
			'before'
		);
	}

	/** Refreshes [ndk_today] dates on cached pages after midnight. */
	public static function enqueue_today() {
		if ( ! wp_script_is( 'ndk-today', 'registered' ) ) {
			wp_register_script( 'ndk-today', NDK_URL . 'assets/js/today.js', array(), NDK_VERSION, true );
			wp_add_inline_script( 'ndk-today', 'window.ndkToday = ' . wp_json_encode( array( 'endpoint' => esc_url_raw( rest_url( NDK_REST::NAMESPACE_V1 . '/convert' ) ) ) ) . ';', 'before' );
		}
		wp_enqueue_script( 'ndk-today' );
	}

	public static function enqueue_converter() {
		self::register();
		wp_enqueue_style( 'ndk-converter' );
		wp_enqueue_script( 'ndk-converter' );
	}
}
