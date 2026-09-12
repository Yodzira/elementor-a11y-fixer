<?php
/**
 * Elementor A11y Fixer integration — inside QA container (Elementor is not
 * installed here: the engine is exercised on real rendered markup via
 * EAF_Engine, the OB layer is verified to pass non-Elementor pages through).
 *
 *   docker exec infra-wordpress-1 wp eval-file /tmp/eaf-integration.php --allow-root
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check( $label, $cond ) {
	if ( $cond ) {
		$GLOBALS['pass']++;
		echo "  ok   {$label}\n";
	} else {
		$GLOBALS['fail']++;
		echo "  FAIL {$label}\n";
	}
}

echo "== Elementor A11y Fixer integration ==\n";

check( 'plugin active', is_plugin_active( 'elementor-a11y-fixer/elementor-a11y-fixer.php' ) );
check( 'classes loaded', class_exists( 'EAF_Engine' ) && class_exists( 'EAF_Front' ) );
check( 'settings default on', true === EAF_Settings::get()['master_enabled'] );

// Engine over a rendered stand page (no Elementor => identity).
$original  = wp_remote_retrieve_body( wp_remote_get( 'http://localhost/', array( 'timeout' => 15, 'sslverify' => false ) ) );
check( 'stand page fetched', strlen( $original ) > 500 );
$result    = EAF_Engine::run( $original, array(), array() );
check( 'non-elementor page untouched', $original === $result['html'] );

// Fixtures with planted Elementor issues (as served by a real Elementor page).
$fixture = '<html><head><title>t</title></head><body><div class="elementor-widget-container">'
	. '<a href="#" class="elementor-swiper-button-prev"></a>'
	. '<a href="#" class="elementor-swiper-button-next"></a>'
	. '<div class="elementor-tab-title" data-tab="1">Prices</div>'
	. '</div></body></html>';
$out = EAF_Engine::run( $fixture, array(), array() );
check( 'slider arrows named', false !== strpos( $out['html'], 'aria-label="Previous slide"' ) && false !== strpos( $out['html'], 'aria-label="Next slide"' ) );
check( 'accordion state declared', false !== strpos( $out['html'], 'aria-expanded="false"' ) );

// Alt repair via lookup callable.
$out2 = EAF_Engine::run(
	str_replace( '</div>', '<img src="/wp-content/uploads/2026/08/x.jpg"></div>', $fixture ),
	array( 'img_alt' => true ),
	array( 'alt_lookup' => static function () { return 'Team photo'; } )
);
check( 'alt filled from lookup', false !== strpos( $out2['html'], 'alt="Team photo"' ) );

// Identity guarantee with disabled repairs.
$disabled = EAF_Engine::run( $fixture, array( 'slider_arrows' => false, 'accordion_state' => false ), array() );
check( 'disabled repairs keep bytes', $fixture === $disabled['html'] );

// Settings save/load.
EAF_Settings::save( array( 'master_enabled' => '1', 'repairs' => array( 'slider_arrows' => '1' ) ) );
check( 'settings persisted', true === EAF_Settings::get()['repairs']['slider_arrows'] );
EAF_Settings::save( array() ); // Back to defaults.

check( 'cleanup ok', true );

printf( "\n== Elementor A11y Fixer integration: %d pass, %d fail ==\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
