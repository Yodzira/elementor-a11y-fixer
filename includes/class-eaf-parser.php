<?php
/**
 * DOM parser and Elementor detector (pure).
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_Parser {

	/**
	 * Parse HTML as UTF-8 without entity bloat on save.
	 *
	 * @param string $html HTML.
	 * @return DOMDocument|null
	 */
	public static function load( $html ) {
		$dom = new DOMDocument();
		$internal = libxml_use_internal_errors( true );
		$ok       = $dom->loadHTML( '<?xml encoding="utf-8"?>' . $html );
		libxml_clear_errors();
		libxml_use_internal_errors( $internal );
		if ( ! $ok || ! $dom->documentElement ) {
			return null;
		}
		$child = $dom->firstChild;
		while ( $child ) {
			$next = $child->nextSibling;
			if ( XML_PI_NODE === $child->nodeType ) {
				$dom->removeChild( $child );
			}
			$child = $next;
		}
		$dom->encoding = 'UTF-8';

		return $dom;
	}

	/**
	 * Serialize back to HTML (documentElement keeps UTF-8 raw).
	 *
	 * @param DOMDocument $dom Document.
	 * @return string
	 */
	public static function save( DOMDocument $dom ) {
		return $dom->saveHTML( $dom->documentElement );
	}

	/**
	 * Detect Elementor markup.
	 *
	 * @param string $html HTML.
	 * @return bool
	 */
	public static function is_elementor( $html ) {
		// Only class attributes count — the word "elementor-" in plain text
		// is not Elementor markup.
		return is_string( $html ) && (bool) preg_match( '/class="[^"]*elementor-|class=\'[^\']*elementor-/i', (string) $html );
	}

	/**
	 * Visible text of a node.
	 *
	 * @param DOMNode $node Node.
	 * @return string
	 */
	public static function text_of( DOMNode $node ) {
		return trim( (string) preg_replace( '/\s+/u', ' ', $node->textContent ) );
	}
}
