<?php
/**
 * Settings > Nepali Date.
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Settings {

	const PAGE   = 'nepali-date-kit';
	const OPTION = 'ndk_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( NDK_FILE ), array( __CLASS__, 'action_link' ) );
	}

	public static function menu() {
		add_options_page(
			__( 'Nepali Date', 'nepali-date-kit' ),
			__( 'Nepali Date', 'nepali-date-kit' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function action_link( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ), esc_html__( 'Settings', 'nepali-date-kit' ) )
		);
		return $links;
	}

	public static function register() {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => ndk_default_settings(),
			)
		);

		add_settings_section( 'ndk_display', __( 'Display', 'nepali-date-kit' ), '__return_false', self::PAGE );
		add_settings_section( 'ndk_posts', __( 'Post dates', 'nepali-date-kit' ), array( __CLASS__, 'posts_intro' ), self::PAGE );

		self::field( 'lang', __( 'Language', 'nepali-date-kit' ), 'ndk_display' );
		self::field( 'timezone', __( 'Timezone', 'nepali-date-kit' ), 'ndk_display' );
		self::field( 'month_names', __( 'Month names', 'nepali-date-kit' ), 'ndk_display' );
		self::field( 'convert', __( 'Convert', 'nepali-date-kit' ), 'ndk_posts' );
		self::field( 'post_types', __( 'Post types', 'nepali-date-kit' ), 'ndk_posts' );
		self::field( 'date_format', __( 'Date format', 'nepali-date-kit' ), 'ndk_posts' );
		self::field( 'relative', __( 'Relative dates', 'nepali-date-kit' ), 'ndk_posts' );
	}

	private static function field( $id, $title, $section ) {
		add_settings_field( 'ndk_' . $id, $title, array( __CLASS__, 'field_' . $id ), self::PAGE, $section );
	}

	/**
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = ndk_default_settings();
		$out      = array();

		$out['lang']     = isset( $input['lang'] ) && 'en' === $input['lang'] ? 'en' : 'ne';
		$out['timezone'] = isset( $input['timezone'] ) && 'site' === $input['timezone'] ? 'site' : 'nepal';
		$out['month_names'] = isset( $input['month_names'] ) && 'formal' === $input['month_names'] ? 'formal' : 'colloquial';

		foreach ( array( 'convert_date', 'convert_time', 'convert_modified_date', 'convert_modified_time', 'convert_comments', 'relative' ) as $key ) {
			$out[ $key ] = ! empty( $input[ $key ] );
		}

		// options.php has already unslashed the input; unslashing again would eat format escapes like "\o\f".
		$out['date_format'] = isset( $input['date_format'] ) ? sanitize_text_field( $input['date_format'] ) : '';
		if ( NDK_Formatter::is_machine_format( $out['date_format'] ) ) {
			add_settings_error( self::OPTION, 'ndk_format', __( 'That date format contains machine-only characters (such as c, r, U, T) and was not saved.', 'nepali-date-kit' ) );
			$out['date_format'] = '';
		}

		$days                 = isset( $input['relative_days'] ) ? absint( $input['relative_days'] ) : $defaults['relative_days'];
		$out['relative_days'] = min( 365, max( 1, $days ) );

		$public            = get_post_types( array( 'public' => true ) );
		$types             = isset( $input['post_types'] ) ? array_map( 'sanitize_key', (array) $input['post_types'] ) : array();
		$out['post_types'] = array_values( array_intersect( $types, $public ) );

		return $out;
	}

	public static function posts_intro() {
		echo '<p>' . esc_html__( 'Replaces dates printed by your theme (get_the_date(), the_time() and friends) with Bikram Sambat dates. Machine-readable dates, such as datetime attributes and feeds, are always left in AD.', 'nepali-date-kit' ) . '</p>';
	}

	public static function field_lang() {
		$v   = ndk_get_setting( 'lang' );
		$now = ndk_now();
		self::radio(
			'lang',
			array(
				'ne' => 'नेपाली (' . ndk_format_datetime( $now, 'j F Y', 'ne' ) . ')',
				'en' => 'English (' . ndk_format_datetime( $now, 'j F Y', 'en' ) . ')',
			),
			$v
		);
	}

	public static function field_timezone() {
		$v = ndk_get_setting( 'timezone' );
		self::radio(
			'timezone',
			array(
				'nepal' => __( 'Nepal time (Asia/Kathmandu)', 'nepali-date-kit' ),
				/* translators: %s: site timezone name. */
				'site'  => sprintf( __( 'Site timezone (%s)', 'nepali-date-kit' ), wp_timezone_string() ),
			),
			$v
		);
	}

	public static function field_month_names() {
		self::radio(
			'month_names',
			array(
				'colloquial' => __( 'Everyday names: बैशाख, जेठ, … कात्तिक, … चैत', 'nepali-date-kit' ),
				'formal'     => __( 'Formal names: वैशाख, ज्येष्ठ, … कार्तिक, … चैत्र', 'nepali-date-kit' ),
			),
			ndk_get_setting( 'month_names' )
		);
	}

	public static function field_convert() {
		$labels = array(
			'convert_date'          => __( 'Published date', 'nepali-date-kit' ),
			'convert_time'          => __( 'Published time', 'nepali-date-kit' ),
			'convert_modified_date' => __( 'Modified date', 'nepali-date-kit' ),
			'convert_modified_time' => __( 'Modified time', 'nepali-date-kit' ),
			'convert_comments'      => __( 'Comment dates and times', 'nepali-date-kit' ),
		);
		foreach ( $labels as $key => $label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label><br>',
				esc_attr( self::OPTION ),
				esc_attr( $key ),
				checked( ndk_get_setting( $key ), true, false ),
				esc_html( $label )
			);
		}
	}

	public static function field_post_types() {
		$enabled = (array) ndk_get_setting( 'post_types' );
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			printf(
				'<label><input type="checkbox" name="%1$s[post_types][]" value="%2$s" %3$s> %4$s</label><br>',
				esc_attr( self::OPTION ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $enabled, true ), true, false ),
				esc_html( $type->labels->name )
			);
		}
	}

	public static function field_date_format() {
		$v = (string) ndk_get_setting( 'date_format' );
		printf(
			'<input type="text" class="regular-text code" name="%1$s[date_format]" value="%2$s" placeholder="l, j F Y">',
			esc_attr( self::OPTION ),
			esc_attr( $v )
		);
		echo '<p class="description">' . esc_html__( 'Used wherever your theme shows the site\'s default date format. Leave empty to keep that format. Uses PHP date characters, e.g. "l, j F Y" or "j F Y".', 'nepali-date-kit' ) . '</p>';
		$example = ndk_format_datetime( ndk_now(), '' !== $v ? $v : get_option( 'date_format' ) );
		/* translators: %s: example date. */
		echo '<p class="description">' . esc_html( sprintf( __( 'Today: %s', 'nepali-date-kit' ), $example ) ) . '</p>';
	}

	public static function field_relative() {
		printf(
			'<label><input type="checkbox" name="%1$s[relative]" value="1" %2$s> %3$s</label> ',
			esc_attr( self::OPTION ),
			checked( ndk_get_setting( 'relative' ), true, false ),
			esc_html__( 'Show "३ दिन अगाडि" for posts newer than', 'nepali-date-kit' )
		);
		printf(
			'<input type="number" min="1" max="365" class="small-text" name="%1$s[relative_days]" value="%2$d"> %3$s',
			esc_attr( self::OPTION ),
			(int) ndk_get_setting( 'relative_days' ),
			esc_html__( 'days', 'nepali-date-kit' )
		);
	}

	private static function radio( $key, array $choices, $current ) {
		foreach ( $choices as $value => $label ) {
			printf(
				'<label><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s> %5$s</label><br>',
				esc_attr( self::OPTION ),
				esc_attr( $key ),
				esc_attr( $value ),
				checked( $current, $value, false ),
				esc_html( $label )
			);
		}
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( self::PAGE );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form>';
		echo '<h2>' . esc_html__( 'Shortcodes', 'nepali-date-kit' ) . '</h2>';
		echo '<p><code>[ndk_today]</code> <code>[ndk_today format="Y-m-d" lang="en" show_ad="yes"]</code></p>';
		echo '<p><code>[ndk_date date="2026-10-22"]</code> <code>[ndk_date date="2083-07-05" from="bs" format="F j, Y"]</code></p>';
		echo '<p><code>[ndk_converter]</code></p>';
		echo '</div>';
	}
}
