<?php
/**
 * [ndk_today], [ndk_date] and [ndk_converter].
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Shortcodes {

	public static function init() {
		add_shortcode( 'ndk_today', array( __CLASS__, 'today' ) );
		add_shortcode( 'ndk_date', array( __CLASS__, 'date' ) );
		add_shortcode( 'ndk_converter', array( __CLASS__, 'converter' ) );
	}

	/**
	 * Today's date.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function today( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'format'    => 'l, j F Y',
				'lang'      => ndk_get_setting( 'lang' ),
				'show_ad'   => 'no',
				'ad_format' => 'l, F j, Y',
			),
			$atts,
			'ndk_today'
		);

		$now = ndk_now();
		$bs  = ndk_format_datetime( $now, self::display_format( $atts['format'], 'l, j F Y' ), self::lang( $atts['lang'] ) );

		$html = sprintf(
			'<time class="ndk-today__bs" datetime="%s">%s</time>',
			esc_attr( $now->format( 'Y-m-d' ) ),
			esc_html( $bs )
		);
		if ( self::truthy( $atts['show_ad'] ) ) {
			$ad    = wp_date( self::display_format( $atts['ad_format'], 'l, F j, Y' ), $now->getTimestamp(), $now->getTimezone() );
			$html .= ' <span class="ndk-today__ad">' . esc_html( $ad ) . '</span>';
		}
		return '<span class="ndk-today">' . $html . '</span>';
	}

	/**
	 * Convert a fixed date: [ndk_date date="2026-10-22"] or [ndk_date date="2083-07-05" from="bs"].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function date( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'date'   => '',
				'from'   => 'ad',
				'format' => '',
				'lang'   => ndk_get_setting( 'lang' ),
			),
			$atts,
			'ndk_date'
		);

		$result = self::convert( $atts['from'], $atts['date'], $atts['format'], self::lang( $atts['lang'] ) );
		if ( is_wp_error( $result ) ) {
			return current_user_can( 'edit_posts' )
				? '<span class="ndk-error">' . esc_html( $result->get_error_message() ) . '</span>'
				: '';
		}
		return '<span class="ndk-date">' . esc_html( $result['formatted'] ) . '</span>';
	}

	/**
	 * Interactive BS <-> AD converter.
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function converter( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'lang'      => ndk_get_setting( 'lang' ),
				'bs_format' => 'l, j F Y',
				'ad_format' => 'l, F j, Y',
			),
			$atts,
			'ndk_converter'
		);
		NDK_Assets::enqueue_converter();

		$lang  = self::lang( $atts['lang'] );
		$range = NDK_Calendar::range();
		$now   = ndk_now();
		$today = ndk_ad_to_bs( $now );
		$ad    = array_map( 'intval', explode( '-', $now->format( 'Y-n-j' ) ) );

		$bs_months = NDK_Formatter::MONTHS[ $lang ];
		$ad_months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$ad_months[] = wp_date( 'F', gmmktime( 0, 0, 0, $m, 1, 2000 ), new DateTimeZone( 'UTC' ) );
		}

		ob_start();
		?>
		<div class="ndk-converter" data-lang="<?php echo esc_attr( $lang ); ?>"
			data-bs-format="<?php echo esc_attr( self::display_format( $atts['bs_format'], 'l, j F Y' ) ); ?>"
			data-ad-format="<?php echo esc_attr( self::display_format( $atts['ad_format'], 'l, F j, Y' ) ); ?>">
			<?php
			self::panel( 'bs', __( 'BS to AD', 'nepali-date-kit' ), __( 'Convert to AD', 'nepali-date-kit' ), $range['bs'][0][0], $range['bs'][1][0], $bs_months, array( $today['year'], $today['month'], $today['day'] ) );
			self::panel( 'ad', __( 'AD to BS', 'nepali-date-kit' ), __( 'Convert to BS', 'nepali-date-kit' ), $range['ad'][0][0], $range['ad'][1][0], $ad_months, $ad );
			?>
		</div>
		<?php
		return trim( ob_get_clean() );
	}

	private static function panel( $from, $legend, $button, $first_year, $last_year, array $months, array $selected ) {
		$uid = wp_unique_id( 'ndk-' . $from . '-' );
		?>
		<form class="ndk-converter__panel" data-from="<?php echo esc_attr( $from ); ?>">
			<fieldset>
				<legend><?php echo esc_html( $legend ); ?></legend>
				<label for="<?php echo esc_attr( $uid ); ?>-y" class="screen-reader-text"><?php esc_html_e( 'Year', 'nepali-date-kit' ); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-y" name="year">
					<?php for ( $y = $first_year; $y <= $last_year; $y++ ) : ?>
						<option value="<?php echo (int) $y; ?>" <?php selected( $selected[0], $y ); ?>><?php echo (int) $y; ?></option>
					<?php endfor; ?>
				</select>
				<label for="<?php echo esc_attr( $uid ); ?>-m" class="screen-reader-text"><?php esc_html_e( 'Month', 'nepali-date-kit' ); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-m" name="month">
					<?php foreach ( $months as $i => $name ) : ?>
						<option value="<?php echo (int) ( $i + 1 ); ?>" <?php selected( $selected[1], $i + 1 ); ?>><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="<?php echo esc_attr( $uid ); ?>-d" class="screen-reader-text"><?php esc_html_e( 'Day', 'nepali-date-kit' ); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-d" name="day">
					<?php for ( $d = 1; $d <= 32; $d++ ) : ?>
						<option value="<?php echo (int) $d; ?>" <?php selected( $selected[2], $d ); ?>><?php echo (int) $d; ?></option>
					<?php endfor; ?>
				</select>
				<button type="submit"><?php echo esc_html( $button ); ?></button>
			</fieldset>
			<output class="ndk-converter__result" aria-live="polite"></output>
		</form>
		<?php
	}

	/**
	 * Shared by [ndk_date] and the REST endpoint.
	 *
	 * @param string $from   'ad' or 'bs'.
	 * @param string $date   YYYY-MM-DD.
	 * @param string $format Output format ('' = default).
	 * @param string $lang   'ne' or 'en' (BS output only).
	 * @return array|WP_Error
	 */
	public static function convert( $from, $date, $format, $lang ) {
		if ( 'bs' === $from ) {
			$ad = ndk_bs_to_ad( $date );
			if ( is_wp_error( $ad ) ) {
				return $ad;
			}
			$ts = gmmktime( 0, 0, 0, $ad['month'], $ad['day'], $ad['year'] );
			return array(
				'calendar'  => 'ad',
				'date'      => sprintf( '%04d-%02d-%02d', $ad['year'], $ad['month'], $ad['day'] ),
				'weekday'   => $ad['weekday'],
				'formatted' => wp_date( self::display_format( $format, 'l, F j, Y' ), $ts, new DateTimeZone( 'UTC' ) ),
			);
		}

		$bs = ndk_ad_to_bs( $date );
		if ( is_wp_error( $bs ) ) {
			return $bs;
		}
		return array(
			'calendar'  => 'bs',
			'date'      => sprintf( '%04d-%02d-%02d', $bs['year'], $bs['month'], $bs['day'] ),
			'weekday'   => $bs['weekday'],
			'formatted' => ndk_format_bs( $bs, self::display_format( $format, 'l, j F Y' ), $lang ),
			'projected' => NDK_Calendar::is_projected( $bs['year'] ),
		);
	}

	/** Use $fallback for empty or machine-only formats. */
	public static function display_format( $format, $fallback ) {
		$format = trim( (string) $format );
		return ( '' === $format || NDK_Formatter::is_machine_format( $format ) ) ? $fallback : $format;
	}

	public static function lang( $lang ) {
		return 'en' === $lang ? 'en' : 'ne';
	}

	private static function truthy( $v ) {
		return in_array( strtolower( (string) $v ), array( '1', 'yes', 'true', 'on' ), true );
	}
}
