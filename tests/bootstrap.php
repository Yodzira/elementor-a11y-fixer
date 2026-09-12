<?php
/**
 * Standalone bootstrap: parser + engine + golden master are pure.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/wp/' );
}
if ( ! defined( 'EAF_DIR' ) ) {
	define( 'EAF_DIR', dirname( __DIR__ ) );
}

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
