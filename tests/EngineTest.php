<?php

use PHPUnit\Framework\TestCase;

/**
 * Detector, repairs, Golden Master on Elementor fixture markup.
 */
class EngineTest extends TestCase {

	const FIXTURE = <<<'HTML'
<!DOCTYPE html><html><head><title>Elementor page</title></head><body>
<div class="elementor elementor-123">
 <div class="elementor-swiper-wrapper">
  <a href="#" class="elementor-swiper-button-prev" role="button"></a>
  <a href="#" class="elementor-swiper-button-next" role="button"></a>
 </div>
 <div class="elementor-accordion">
  <div class="elementor-accordion-item"><div class="elementor-tab-title" data-tab="1">Prices</div></div>
  <div class="elementor-accordion-item"><div class="elementor-tab-title elementor-active" data-tab="2">Contacts</div></div>
 </div>
 <a href="/contacts/" class="elementor-icon elementor-animation"><i class="fas fa-envelope"></i></a>
 <img src="/wp-content/uploads/2026/08/team.jpg">
 <p>Обычный текст на русском</p>
</div>
</body></html>
HTML;

	public function test_detector() {
		$this->assertTrue( EAF_Parser::is_elementor( self::FIXTURE ) );
		$this->assertFalse( EAF_Parser::is_elementor( '<html><body><p>plain page</p></body></html>' ) );
		// The word "elementor" in plain text alone is not enough.
		$this->assertFalse( EAF_Parser::is_elementor( '<html><body><p>we love elementor-widgets</p></body></html>' ) );
	}

	public function test_all_safe_repairs_apply_and_record_changes() {
		$result = EAF_Engine::run( self::FIXTURE, array(), array() );

		$this->assertGreaterThan( 0, count( $result['changes'] ) );
		$html = $result['html'];
		$this->assertStringContainsString( 'aria-label="Previous slide"', $html );
		$this->assertStringContainsString( 'aria-label="Next slide"', $html );
		$this->assertStringContainsString( 'aria-expanded="false"', $html );
		$this->assertStringContainsString( 'Обычный текст на русском', $html ); // Text intact.
	}

	public function test_risky_repair_off_by_default() {
		$result = EAF_Engine::run( self::FIXTURE, array() );
		$ids = array_column( $result['changes'], 'repair' );

		$this->assertContains( 'slider_arrows', $ids );
		$this->assertNotContains( 'icon_link_name', $ids ); // Risky: off by default.
	}

	public function test_risky_repair_enabled_names_icon_link() {
		$result = EAF_Engine::run( self::FIXTURE, array( 'icon_link_name' => true ), array() );
		$this->assertStringContainsString( 'aria-label="Contacts"', $result['html'] );
	}

	public function test_identity_when_nothing_to_fix() {
		$plain = '<html><body><p>No elementor here</p></body></html>';
		$this->assertSame( $plain, EAF_Engine::run( $plain )['html'] );

		$perfect = '<html><body><div class="elementor"><a href="#" class="elementor-swiper-button-prev" aria-label="Prev"></a></div></body></html>';
		$this->assertSame( $perfect, EAF_Engine::run( $perfect )['html'] );
	}

	public function test_golden_master_no_unexpected_changes() {
		$allow = array(
			'a'     => array( 'aria-label', 'aria-expanded' ),
			'div'   => array( 'aria-expanded' ),
			'img'   => array( 'alt' ),
			'html'  => array(),
		);
		$result = EAF_Engine::run(
			self::FIXTURE,
			array_fill_keys( array_keys( EAF_Engine::repairs() ), true ),
			array( 'alt_lookup' => static function () { return 'Our team'; } )
		);
		$violations = EAF_GoldenMaster::compare( self::FIXTURE, $result['html'], $allow );

		$this->assertSame( array(), $violations, implode( "\n", $violations ) );
		$this->assertStringContainsString( 'alt="Our team"', $result['html'] );
	}
}
