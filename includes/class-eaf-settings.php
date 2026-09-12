<?php
/**
 * Split classes.
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_Settings {

	const OPTION = 'eaf_settings';

	public static function get() {
		$stored = get_option( self::OPTION, array() );
		$merged = array_merge(
			array( 'master_enabled' => true ),
			is_array( $stored ) ? $stored : array()
		);
		$merged['repairs'] = array_merge( EAF_Engine::defaults(), isset( $stored['repairs'] ) && is_array( $stored['repairs'] ) ? $stored['repairs'] : array() );

		return $merged;
	}

	public static function save( $in ) {
		$in   = is_array( $in ) ? $in : array();
		$clean = array(
			'master_enabled' => ! empty( $in['master_enabled'] ),
			'repairs'        => array(),
		);
		foreach ( EAF_Engine::repairs() as $id => $repair ) {
			$clean['repairs'][ $id ] = ! empty( $in['repairs'][ $id ] );
		}
		update_option( self::OPTION, $clean );

		return $clean;
	}
}
