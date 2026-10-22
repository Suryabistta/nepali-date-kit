<?php
/**
 * Shows post dates and times in BS on the front end.
 *
 * Covers classic themes (get_the_date() and friends) and block themes (the
 * core/post-date block, which formats with wp_date() since WP 6.9 and so
 * bypasses the classic filters).
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Post_Dates {

	/**
	 * Filter => [ post field, kind, setting ].
	 */
	const FILTERS = array(
		'get_the_date'          => array( 'date', 'date', 'convert_date' ),
		'get_the_time'          => array( 'date', 'time', 'convert_time' ),
		'get_the_modified_date' => array( 'modified', 'date', 'convert_modified_date' ),
		'get_the_modified_time' => array( 'modified', 'time', 'convert_modified_time' ),
	);

	/** @var bool */
	private static $registered = false;

	public static function init() {
		// Priority 0: themes such as JNews answer their own AJAX requests on 'wp' and exit.
		add_action( 'wp', array( __CLASS__, 'register' ), 0 );
		// "Load more" and infinite scroll through admin-ajax.php never fire 'wp'.
		add_action( 'admin_init', array( __CLASS__, 'register_for_frontend_ajax' ), 0 );
	}

	/**
	 * Hook the filters once the main query is known (front end only).
	 */
	public static function register() {
		if ( is_admin() || is_feed() ) {
			return;
		}
		self::add_filters();
	}

	/**
	 * AJAX requests sent by front-end pages (not by wp-admin screens).
	 */
	public static function register_for_frontend_ajax() {
		if ( ! wp_doing_ajax() ) {
			return;
		}
		$referer = (string) wp_get_referer();
		if ( '' === $referer || 0 === strpos( $referer, admin_url() ) ) {
			return;
		}
		self::add_filters();
	}

	private static function add_filters() {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;

		if ( ndk_get_setting( 'convert_comments' ) ) {
			add_filter( 'get_comment_date', array( __CLASS__, 'convert_comment_date' ), 10, 3 );
			add_filter( 'get_comment_time', array( __CLASS__, 'convert_comment_time' ), 10, 5 );
		}
		foreach ( self::FILTERS as $filter => $spec ) {
			if ( ! ndk_get_setting( $spec[2] ) ) {
				continue;
			}
			add_filter(
				$filter,
				function ( $value, $format = '', $post = null ) use ( $spec ) {
					return NDK_Post_Dates::convert( $value, $format, $post, $spec[0], $spec[1] );
				},
				10,
				3
			);
		}
		add_filter( 'render_block_core/post-date', array( __CLASS__, 'render_post_date_block' ), 10, 3 );
	}

	/**
	 * Classic filters: convert one date/time value.
	 *
	 * @param string|int       $value  Value WordPress produced.
	 * @param string           $format Requested format ('' = site default).
	 * @param WP_Post|int|null $post   Post.
	 * @param string           $field  'date' or 'modified'.
	 * @param string           $kind   'date' or 'time'.
	 * @return string|int
	 */
	public static function convert( $value, $format, $post, $field, $kind ) {
		$post = self::enabled_post( $post );
		if ( ! $post ) {
			return $value;
		}
		$datetime = get_post_datetime( $post, $field );
		if ( ! $datetime ) {
			return $value;
		}
		$converted = self::format_moment( $datetime, (string) $format, $kind );
		return null === $converted ? $value : $converted;
	}

	/**
	 * Comment dates.
	 *
	 * @param string          $value   Formatted date.
	 * @param string          $format  Requested format.
	 * @param WP_Comment|null $comment Comment.
	 * @return string
	 */
	public static function convert_comment_date( $value, $format = '', $comment = null ) {
		$datetime = self::comment_datetime( $comment );
		$text     = $datetime ? self::format_moment( $datetime, (string) $format, 'date', false ) : null;
		return null === $text ? $value : $text;
	}

	/**
	 * Comment times.
	 *
	 * @param string          $value     Formatted time.
	 * @param string          $format    Requested format.
	 * @param bool            $gmt       Whether GMT was requested.
	 * @param bool            $translate Whether to translate.
	 * @param WP_Comment|null $comment   Comment.
	 * @return string
	 */
	public static function convert_comment_time( $value, $format = '', $gmt = false, $translate = true, $comment = null ) {
		$datetime = self::comment_datetime( $comment );
		$text     = $datetime ? self::format_moment( $datetime, (string) $format, 'time', false ) : null;
		return null === $text ? $value : $text;
	}

	private static function comment_datetime( $comment ) {
		$comment = get_comment( $comment );
		if ( ! $comment || ! $comment->comment_date_gmt || '0000-00-00 00:00:00' === $comment->comment_date_gmt ) {
			return null;
		}
		try {
			return new DateTimeImmutable( $comment->comment_date_gmt, new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Block themes: rewrite the visible text of core/post-date, keeping the
	 * AD datetime attribute as-is.
	 *
	 * @param string        $html   Rendered block.
	 * @param array         $parsed Parsed block.
	 * @param WP_Block|null $block  Block instance.
	 * @return string
	 */
	public static function render_post_date_block( $html, $parsed, $block = null ) {
		$attrs   = isset( $parsed['attrs'] ) ? $parsed['attrs'] : array();
		$binding = isset( $attrs['metadata']['bindings']['datetime'] ) ? $attrs['metadata']['bindings']['datetime'] : null;
		if ( $binding && ( ! isset( $binding['source'] ) || 'core/post-data' !== $binding['source'] ) ) {
			return $html; // Bound to something other than the post's own dates.
		}
		$field = ( isset( $attrs['displayType'] ) && 'modified' === $attrs['displayType'] )
			|| ( isset( $binding['args']['field'] ) && 'modified' === $binding['args']['field'] )
			? 'modified' : 'date';

		if ( ! ndk_get_setting( 'modified' === $field ? 'convert_modified_date' : 'convert_date' ) ) {
			return $html;
		}
		$post_id = $block instanceof WP_Block && isset( $block->context['postId'] ) ? $block->context['postId'] : null;
		if ( ! self::enabled_post( $post_id ) ) {
			return $html;
		}

		$format = isset( $attrs['format'] ) ? (string) $attrs['format'] : '';

		return preg_replace_callback(
			'#(<time\b[^>]*\bdatetime="([^"]+)"[^>]*>)(.*?)(</time>)#s',
			function ( $m ) use ( $format ) {
				try {
					$datetime = new DateTimeImmutable( html_entity_decode( $m[2], ENT_QUOTES ) );
				} catch ( Exception $e ) {
					return $m[0];
				}
				$text = 'human-diff' === $format
					? ndk_human_time_diff( $datetime->getTimestamp() )
					: NDK_Post_Dates::format_moment( $datetime, $format, 'date' );
				return null === $text ? $m[0] : $m[1] . esc_html( $text ) . $m[4];
			},
			$html,
			1
		);
	}

	/**
	 * Format a moment for display, applying the plugin settings.
	 *
	 * @param DateTimeInterface $datetime Moment.
	 * @param string            $format   Requested format ('' = site default).
	 * @param string            $kind     'date' or 'time'.
	 * @param bool              $relative Whether the "३ दिन अगाडि" setting may apply.
	 * @return string|null Null when the format is machine-readable or the date is out of range.
	 */
	public static function format_moment( DateTimeInterface $datetime, $format, $kind, $relative = true ) {
		if ( '' !== $format && ( NDK_Formatter::is_machine_format( $format ) || NDK_Formatter::is_numeric_format( $format ) ) ) {
			return null;
		}

		if ( 'date' === $kind ) {
			// The override replaces the site's default format only, so explicit
			// formats a theme asks for (e.g. 'l' for a weekday label) are kept.
			$site_format = (string) get_option( 'date_format' );
			$is_default  = '' === $format || $format === $site_format;
			$override    = (string) ndk_get_setting( 'date_format' );
			if ( $is_default ) {
				$format = '' !== $override ? $override : $site_format;
			}

			if ( $relative && ndk_get_setting( 'relative' ) ) {
				$age = time() - $datetime->getTimestamp();
				if ( abs( $age ) < (int) ndk_get_setting( 'relative_days' ) * DAY_IN_SECONDS ) {
					return ndk_human_time_diff( $datetime->getTimestamp() );
				}
			}
		} elseif ( '' === $format ) {
			$format = get_option( 'time_format' );
		}

		$converted = ndk_format_datetime( $datetime, $format );
		return '' === $converted ? null : $converted;
	}

	/**
	 * The post, if its type is enabled in the settings.
	 *
	 * @param WP_Post|int|null $post Post.
	 * @return WP_Post|null
	 */
	private static function enabled_post( $post ) {
		$post = get_post( $post );
		if ( ! $post || ! in_array( $post->post_type, (array) ndk_get_setting( 'post_types' ), true ) ) {
			return null;
		}
		return $post;
	}
}
