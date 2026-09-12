<?php
/**
 * Uninstall cleanup.
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return;
}

delete_option( 'eaf_settings' );
