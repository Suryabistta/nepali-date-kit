<?php
/**
 * GET /wp-json/ndk/v1/convert?from=ad&date=2026-10-22
 *
 * Public and read-only, so it needs no nonce and works behind page caches.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_REST {

	const NAMESPACE_V1 = 'ndk/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/convert',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'convert' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'from'   => array(
						'type'     => 'string',
						'enum'     => array( 'ad', 'bs' ),
						'required' => true,
					),
					'date'   => array(
						'type'     => 'string',
						'pattern'  => '^\d{4}-\d{1,2}-\d{1,2}$',
						'required' => true,
					),
					'format' => array(
						'type'              => 'string',
						'default'           => '',
						'maxLength'         => 64,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'lang'   => array(
						'type'    => 'string',
						'enum'    => array( 'ne', 'en' ),
						'default' => 'ne',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function convert( WP_REST_Request $request ) {
		$result = NDK_Shortcodes::convert(
			$request['from'],
			$request['date'],
			$request['format'],
			$request['lang']
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'public, max-age=86400' );
		return $response;
	}
}
