<?php
/**
 * Split classes.
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_Front {

	public static function boot() {
		$settings = EAF_Settings::get();
		if ( ! $settings['master_enabled'] ) {
			return;
		}
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ) );
	}

	public static function start_buffer() {
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		ob_start( array( __CLASS__, 'buffer_end' ) );
	}

	/**
	 * Apply enabled repairs to the rendered page.
	 *
	 * @param string $html Rendered page.
	 * @return string
	 */
	public static function buffer_end( $html ) {
		try {
			if ( ! is_string( $html ) || strlen( $html ) > 1536 * 1024 || ! EAF_Parser::is_elementor( $html ) ) {
				return $html; // Non-Elementor pages are never even parsed.
			}
			$settings = EAF_Settings::get();

			$result = EAF_Engine::run(
				$html,
				$settings['repairs'],
				array(
					'alt_lookup' => array( 'EAF_Lookup', 'alt_by_url' ),
				)
			);

			return $result['html'];
		} catch ( Throwable $e ) {
			return $html; // Never break a page.
		}
	}
}

class EAF_Lookup {

	/**
	 * Alt for an attachment URL, capped lookups per request.
	 *
	 * @param string $src Image URL.
	 * @return string
	 */
	public static function alt_by_url( $src ) {
		static $cache = array();
		static $misses = 0;

		if ( array_key_exists( $src, $cache ) ) {
			return $cache[ $src ];
		}
		$alt  = '';
		$miss = true;
		if ( function_exists( 'attachment_url_to_postid' ) && $misses < 30 ) {
			$id = attachment_url_to_postid( $src );
			if ( $id ) {
				$alt  = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
				$miss = false;
			}
		}
		if ( $miss ) {
			$misses++;
		}
		$cache[ $src ] = $alt;

		return $alt;
	}
}
