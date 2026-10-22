<?php
/**
 * Remove plugin data on uninstall, on every site of a network.
 *
 * @package Nepali_Date_Kit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $ndk_site_id ) {
		switch_to_blog( $ndk_site_id );
		delete_option( 'ndk_settings' );
		restore_current_blog();
	}
} else {
	delete_option( 'ndk_settings' );
}
