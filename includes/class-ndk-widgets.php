<?php
/**
 * Classic widgets (also usable in block themes via the Legacy Widget block).
 *
 * @package Nepali_Date_Kit
 */

defined( 'ABSPATH' ) || exit;

final class NDK_Widgets {
	public static function init() {
		add_action(
			'widgets_init',
			function () {
				register_widget( 'NDK_Widget_Today' );
				register_widget( 'NDK_Widget_Converter' );
			}
		);
	}
}

/**
 * Shared form/update helpers.
 */
abstract class NDK_Widget_Base extends WP_Widget {

	/** @return array Field => [label, type, default]. */
	abstract protected function fields();

	abstract protected function body( array $instance );

	public function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- theme markup.
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput -- theme markup.
		}
		echo $this->body( $instance ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the shortcode renderers.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput -- theme markup.
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );
		foreach ( $this->fields() as $key => $field ) {
			list( $label, $type ) = $field;
			$id   = $this->get_field_id( $key );
			$name = $this->get_field_name( $key );
			echo '<p>';
			if ( 'checkbox' === $type ) {
				printf( '<input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> <label for="%1$s">%4$s</label>', esc_attr( $id ), esc_attr( $name ), checked( ! empty( $instance[ $key ] ), true, false ), esc_html( $label ) );
			} elseif ( 'lang' === $type ) {
				printf( '<label for="%1$s">%2$s</label> <select id="%1$s" name="%3$s" class="widefat">', esc_attr( $id ), esc_html( $label ), esc_attr( $name ) );
				foreach ( array( 'ne' => 'नेपाली', 'en' => 'English' ) as $value => $text ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $instance[ $key ], $value, false ), esc_html( $text ) );
				}
				echo '</select>';
			} else {
				printf( '<label for="%1$s">%2$s</label> <input type="text" class="widefat" id="%1$s" name="%3$s" value="%4$s">', esc_attr( $id ), esc_html( $label ), esc_attr( $name ), esc_attr( $instance[ $key ] ) );
			}
			echo '</p>';
		}
		return '';
	}

	public function update( $new_instance, $old_instance ) {
		$out = array();
		foreach ( $this->fields() as $key => $field ) {
			$type = $field[1];
			if ( 'checkbox' === $type ) {
				$out[ $key ] = ! empty( $new_instance[ $key ] );
			} elseif ( 'lang' === $type ) {
				$out[ $key ] = isset( $new_instance[ $key ] ) && 'en' === $new_instance[ $key ] ? 'en' : 'ne';
			} else {
				$value       = isset( $new_instance[ $key ] ) ? sanitize_text_field( $new_instance[ $key ] ) : '';
				// An emptied format falls back to its default; an emptied title stays empty.
				$out[ $key ] = ( '' === $value && 'title' !== $key ) ? $field[2] : $value;
			}
		}
		return $out;
	}

	protected function defaults() {
		$defaults = array();
		foreach ( $this->fields() as $key => $field ) {
			$defaults[ $key ] = $field[2];
		}
		return $defaults;
	}
}

class NDK_Widget_Today extends NDK_Widget_Base {
	public function __construct() {
		parent::__construct( 'ndk_today', __( 'Nepali Date: Today', 'nepali-date-kit' ), array( 'description' => __( "Today's date in Bikram Sambat.", 'nepali-date-kit' ) ) );
	}

	protected function fields() {
		return array(
			'title'     => array( __( 'Title:', 'nepali-date-kit' ), 'text', '' ),
			'format'    => array( __( 'BS format:', 'nepali-date-kit' ), 'text', 'l, j F Y' ),
			'lang'      => array( __( 'Language:', 'nepali-date-kit' ), 'lang', ndk_get_setting( 'lang' ) ),
			'show_ad'   => array( __( 'Also show the AD date', 'nepali-date-kit' ), 'checkbox', true ),
			'ad_format' => array( __( 'AD format:', 'nepali-date-kit' ), 'text', 'l, F j, Y' ),
		);
	}

	protected function body( array $instance ) {
		$instance['show_ad'] = $instance['show_ad'] ? 'yes' : 'no';
		return NDK_Shortcodes::today( $instance );
	}
}

class NDK_Widget_Converter extends NDK_Widget_Base {
	public function __construct() {
		parent::__construct( 'ndk_converter', __( 'Nepali Date: Converter', 'nepali-date-kit' ), array( 'description' => __( 'Convert dates between BS and AD.', 'nepali-date-kit' ) ) );
	}

	protected function fields() {
		return array(
			'title' => array( __( 'Title:', 'nepali-date-kit' ), 'text', __( 'Date converter', 'nepali-date-kit' ) ),
			'lang'  => array( __( 'Language:', 'nepali-date-kit' ), 'lang', ndk_get_setting( 'lang' ) ),
		);
	}

	protected function body( array $instance ) {
		return NDK_Shortcodes::converter( array( 'lang' => $instance['lang'] ) );
	}
}
