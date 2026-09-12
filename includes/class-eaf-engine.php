<?php
/**
 * Widget-specific repairs and the application engine (pure).
 *
 * @package ElementorA11yFixer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EAF_Engine {

	/**
	 * Repair registry: id => [label, risky, apply(DOMDocument, ctx): array[]].
	 *
	 * @return array
	 */
	public static function repairs() {
		return array(
			'slider_arrows' => array(
				'label' => 'Name slider navigation arrows',
				'risky' => false,
				'apply' => array( __CLASS__, 'repair_slider_arrows' ),
			),
			'accordion_state' => array(
				'label' => 'Declare accordion expand state',
				'risky' => false,
				'apply' => array( __CLASS__, 'repair_accordion_state' ),
			),
			'icon_link_name' => array(
				'label' => 'Name icon-only links from their URL',
				'risky' => true,
				'apply' => array( __CLASS__, 'repair_icon_link_name' ),
			),
			'img_alt' => array(
				'label' => 'Fill missing image alt text',
				'risky' => false,
				'apply' => array( __CLASS__, 'repair_img_alt' ),
			),
		);
	}

	/**
	 * Defaults: safe ON, risky OFF.
	 *
	 * @return array<string,bool>
	 */
	public static function defaults() {
		$map = array();
		foreach ( self::repairs() as $id => $repair ) {
			$map[ $id ] = ! $repair['risky'];
		}

		return $map;
	}

	/**
	 * Apply the enabled repairs to an HTML string.
	 * Identity guarantee: no changes => original bytes untouched.
	 *
	 * @param string             $html Page HTML.
	 * @param array<string,bool> $map  Enabled map (missing = default).
	 * @param array              $ctx  Injectables (alt_lookup).
	 * @return array {html, changes}
	 */
	public static function run( $html, array $map = array(), array $ctx = array() ) {
		$changes = array();
		if ( ! EAF_Parser::is_elementor( $html ) ) {
			return array( 'html' => $html, 'changes' => $changes );
		}

		$dom = EAF_Parser::load( $html );
		if ( ! $dom ) {
			return array( 'html' => $html, 'changes' => $changes );
		}

		foreach ( self::repairs() as $id => $repair ) {
			$enabled = array_key_exists( $id, $map ) ? (bool) $map[ $id ] : ! $repair['risky'];
			if ( ! $enabled ) {
				continue;
			}
			foreach ( call_user_func( $repair['apply'], $dom, $ctx ) as $change ) {
				$change['repair'] = $id;
				$changes[]        = $change;
			}
		}

		return array(
			'html'    => $changes ? EAF_Parser::save( $dom ) : $html,
			'changes' => $changes,
		);
	}

	/**
	 * Slider prev/next arrows without names.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx Unused.
	 * @return array[]
	 */
	public static function repair_slider_arrows( $dom, $ctx = array() ) {
		$out = array();
		$map = array(
			'elementor-swiper-button-prev' => 'Previous slide',
			'elementor-swiper-button-next' => 'Next slide',
		);
		foreach ( $dom->getElementsByTagName( 'a' ) as $a ) {
			if ( ! $a instanceof DOMElement || 'a' !== strtolower( $a->tagName ) ) {
				continue;
			}
			$class = (string) $a->getAttribute( 'class' );
			foreach ( $map as $needle => $label ) {
				if ( '' !== trim( (string) $a->getAttribute( 'aria-label' ) ) || false === strpos( $class, $needle ) ) {
					continue;
				}
				$a->setAttribute( 'aria-label', $label );
				$out[] = array( 'target' => 'a.' . $needle, 'attr' => 'aria-label', 'before' => '', 'after' => $label );
			}
		}

		return $out;
	}

	/**
	 * Accordion/tab titles without aria-expanded.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx Unused.
	 * @return array[]
	 */
	public static function repair_accordion_state( $dom, $ctx = array() ) {
		$out = array();
		foreach ( $dom->getElementsByTagName( '*' ) as $el ) {
			if ( ! $el instanceof DOMElement ) {
				continue;
			}
			$class = (string) $el->getAttribute( 'class' );
			if ( false === strpos( $class, 'elementor-tab-title' ) || '' !== trim( (string) $el->getAttribute( 'aria-expanded' ) ) ) {
				continue;
			}
			$el->setAttribute( 'aria-expanded', 'false' );
			$out[] = array( 'target' => '.elementor-tab-title', 'attr' => 'aria-expanded', 'before' => '', 'after' => 'false' );
		}

		return $out;
	}

	/**
	 * Icon-only links named from the URL slug (risky).
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx Unused.
	 * @return array[]
	 */
	public static function repair_icon_link_name( $dom, $ctx = array() ) {
		$out = array();
		foreach ( $dom->getElementsByTagName( 'a' ) as $el ) {
			if ( ! $el instanceof DOMElement || ! $el->hasAttribute( 'href' ) ) {
				continue;
			}
			if ( '' !== trim( (string) $el->getAttribute( 'aria-label' ) ) || '' !== EAF_Text::of( $el ) ) {
				continue;
			}
			$class = (string) $el->getAttribute( 'class' );
			$href  = (string) $el->getAttribute( 'href' );
			$isIcon = false !== strpos( $class, 'elementor-icon' ) || (bool) $el->getElementsByTagName( 'svg' )->length || (bool) $el->getElementsByTagName( 'i' )->length;
			if ( ! $isIcon ) {
				continue;
			}
			$name = EAF_Text::name_from_url( $href );
			if ( '' === $name ) {
				continue;
			}
			$el->setAttribute( 'aria-label', $name );
			$out[] = array( 'target' => 'a[href="' . $href . '"]', 'attr' => 'aria-label', 'before' => '', 'after' => $name );
		}

		return $out;
	}

	/**
	 * Images without alt, filled from the lookup callable.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx alt_lookup callable.
	 * @return array[]
	 */
	public static function repair_img_alt( $dom, $ctx = array() ) {
		$out = array();
		$lookup = isset( $ctx['alt_lookup'] ) && is_callable( $ctx['alt_lookup'] ) ? $ctx['alt_lookup'] : null;
		if ( ! $lookup ) {
			return $out;
		}
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
			if ( ! $img instanceof DOMElement || ( $img->hasAttribute( 'alt' ) && '' !== trim( $img->getAttribute( 'alt' ) ) ) ) {
				continue;
			}
			$src = (string) $img->getAttribute( 'src' );
			if ( '' === $src ) {
				continue;
			}
			$alt = trim( (string) call_user_func( $lookup, $src ) );
			if ( '' === $alt ) {
				continue;
			}
			$img->setAttribute( 'alt', $alt );
			$out[] = array( 'target' => 'img', 'attr' => 'alt', 'before' => '', 'after' => $alt );
		}

		return $out;
	}
}

/**
 * Text helpers (pure).
 */
class EAF_Text {

	/**
	 * Visible text of a node.
	 *
	 * @param DOMElement $el Element.
	 * @return string
	 */
	public static function of( DOMElement $el ) {
		return trim( (string) preg_replace( '/\s+/u', ' ', $el->textContent ) );
	}

	/**
	 * Name from URL slug: /about-team/ -> "About team".
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function name_from_url( $url ) {
		$parts = function_exists( 'wp_parse_url' ) ? wp_parse_url( $url, PHP_URL_PATH ) : parse_url( $url, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure core fallback.
		$slug  = basename( trim( (string) $parts, '/' ) );
		if ( '' === $slug || ! preg_match( '/[a-z]/i', $slug ) ) {
			return '';
		}
		$slug = preg_replace( '/[-_]+/', ' ', $slug );
		$slug = trim( (string) $slug );
		if ( '' === $slug || strlen( $slug ) > 60 ) {
			return '';
		}

		return ucfirst( $slug );
	}
}
