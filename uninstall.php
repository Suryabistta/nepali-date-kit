<?php
/**
 * Remove plugin data on uninstall.
 *
 * @package Nepali_Date_Kit
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ndk_settings' );
