<?php
/**
 * Plugin Name:       Elementor A11y Fixer
 * Plugin URI:        https://github.com/Yodzira/elementor-a11y-fixer
 * Description:      Accessibility fixes that know Elementor markup: slider arrows, accordion states, icon-only links, lazy images. Every change is previewable; nothing is removed from your markup. Works standalone, pairs with A11yFix.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Yodzira
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       elementor-a11y-fixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EAF_VERSION', '0.1.0' );
define( 'EAF_FILE', __FILE__ );
define( 'EAF_DIR', __DIR__ );

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'EAF_' ) ) {
			return;
		}
		$snake = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', substr( $class, 4 ) ) );
		$snake = str_replace( '_', '-', $snake );
		$file  = EAF_DIR . '/includes/class-eaf-' . $snake . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', array( 'EAF_Plugin', 'boot' ), 20 );
