<?php
/**
 * Golden Master comparator (trimmed from the A11yFix pattern).
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_GoldenMaster {

	/**
	 * Compare before/after: only allowlisted attributes may appear or change.
	 *
	 * @param string $before Original HTML.
	 * @param string $after  Repaired HTML.
	 * @param array  $allow  tag => allowed attributes.
	 * @return string[] Violations (empty = pass).
	 */
	public static function compare( $before, $after, array $allow ) {
		$beforeDom = EAF_Parser::load( $before );
		$afterDom  = EAF_Parser::load( $after );
		if ( ! $beforeDom || ! $afterDom || ! $beforeDom->documentElement || ! $afterDom->documentElement ) {
			return array( 'unparsable document' );
		}

		$violations = array();
		self::walk( $beforeDom->documentElement, $afterDom->documentElement, $allow, $violations );

		return $violations;
	}

	private static function walk( DOMElement $before, DOMElement $after, array $allow, array &$violations ) {
		if ( strtolower( $before->tagName ) !== strtolower( $after->tagName ) ) {
			$violations[] = 'tag changed: ' . $before->tagName . ' -> ' . $after->tagName;

			return;
		}
		$tag      = strtolower( $before->tagName );
		$allowed  = isset( $allow[ $tag ] ) ? $allow[ $tag ] : array();
		$beforeA  = self::attrs( $before );
		$afterA   = self::attrs( $after );

		foreach ( array_unique( array_merge( array_keys( $beforeA ), array_keys( $afterA ) ) ) as $name ) {
			$had = isset( $beforeA[ $name ] );
			$has = isset( $afterA[ $name ] );
			if ( in_array( $name, $allowed, true ) ) {
				if ( $had && '' !== trim( $beforeA[ $name ] ) && ( ! $has || $beforeA[ $name ] !== $afterA[ $name ] ) ) {
					$violations[] = $tag . '@' . $name . ' changed';
				}
				continue;
			}
			if ( $had !== $has || ( $has && $beforeA[ $name ] !== $afterA[ $name ] ) ) {
				$violations[] = $tag . '@' . $name . ' changed';
			}
		}

		$kidsB = self::children( $before );
		$kidsA = self::children( $after );
		if ( count( $kidsB ) !== count( $kidsA ) ) {
			$violations[] = 'child count changed at <' . $tag . '>';

			return;
		}
		for ( $i = 0; $i < count( $kidsB ); $i++ ) {
			$a = $kidsB[ $i ];
			$b = $kidsA[ $i ];
			if ( $a instanceof DOMElement && $b instanceof DOMElement ) {
				self::walk( $a, $b, $allow, $violations );
				continue;
			}
			if ( get_class( $a ) !== get_class( $b ) || ( method_exists( $a, 'data' ) && $a->data !== $b->data ) ) {
				$violations[] = 'content changed at <' . $tag . '> child #' . $i;
			}
		}
	}

	private static function attrs( DOMElement $el ) {
		$out = array();
		foreach ( $el->attributes as $attr ) {
			$out[ $attr->nodeName ] = $attr->nodeValue;
		}

		return $out;
	}

	private static function children( DOMElement $el ) {
		$out = array();
		foreach ( $el->childNodes as $node ) {
			if ( $node instanceof DOMElement || $node instanceof DOMText || $node instanceof DOMComment ) {
				$out[] = $node;
			}
		}

		return $out;
	}
}
