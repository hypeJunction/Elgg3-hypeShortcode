<?php

namespace hypeJunction\Shortcodes;

use Elgg\IntegrationTestCase;

class ShortcodesServiceTest extends IntegrationTestCase {

	public function getPluginID(): string {
		return 'hypeshortcode';
	}

	/** @var ShortcodesService */
	private $svc;

	public function up() {
		$this->svc = elgg()->shortcodes;
		$this->svc->register('testsc');
	}

	public function down() {}

	public function testServiceAccessibleViaDi() {
		$this->assertInstanceOf(ShortcodesService::class, elgg()->shortcodes);
	}

	public function testStripRemovesRegisteredShortcode() {
		$text = 'Before [testsc key="value"] after';
		$result = $this->svc->strip($text);
		$this->assertStringNotContainsString('[testsc', $result);
		$this->assertStringContainsString('Before', $result);
		$this->assertStringContainsString('after', $result);
	}

	public function testStripLeavesUnregisteredShortcodeUnchanged() {
		$text = 'Hello [unknowncode foo="bar"] world';
		$result = $this->svc->strip($text);
		$this->assertStringContainsString('[unknowncode', $result);
	}

	public function testStripHandlesTextWithNoShortcodes() {
		$text = 'Just plain text with no shortcode tags';
		$result = $this->svc->strip($text);
		$this->assertStringContainsString('Just plain text', $result);
	}

	public function testExtractReturnsShortcodeAttributes() {
		$text = '[testsc key="value" num="42"]';
		$result = $this->svc->extract($text);
		$this->assertArrayHasKey('testsc', $result);
		$this->assertCount(1, $result['testsc']);
		$this->assertSame('value', $result['testsc'][0]['key']);
		$this->assertSame('42', $result['testsc'][0]['num']);
	}

	public function testExtractReturnsFirstOccurrence() {
		// NOTE: extract() has a variable-shadowing bug ($i reused in inner loop)
		// that causes only the first shortcode occurrence to be returned when
		// the same shortcode appears multiple times. This test captures current
		// (pre-migration) behavior so the regression suite catches any change.
		$text = '[testsc key="first"] and [testsc key="second"]';
		$result = $this->svc->extract($text);
		$this->assertArrayHasKey('testsc', $result);
		$this->assertNotEmpty($result['testsc']);
		$this->assertSame('first', $result['testsc'][0]['key']);
	}

	public function testExtractReturnsEmptyForUnregisteredShortcode() {
		$text = '[unknowncode foo="bar"]';
		$result = $this->svc->extract($text);
		$this->assertArrayNotHasKey('unknowncode', $result);
	}

	public function testGenerateProducesShortcodeTag() {
		$result = $this->svc->generate('mycode', ['key' => 'value']);
		$this->assertStringStartsWith('[mycode', $result);
		$this->assertStringEndsWith(']', $result);
		$this->assertStringContainsString('key="value"', $result);
	}

	public function testGenerateEncodesSpecialCharacters() {
		// Values containing [, ], or " should be base64-encoded as x_<base64>
		$result = $this->svc->generate('test', ['data' => 'a[b"c]']);
		$this->assertStringContainsString('x_', $result);
	}

	public function testGenerateOmitsUrlWhenMatchesSiteUrl() {
		$siteUrl = elgg_get_site_url();
		$result = $this->svc->generate('test', ['url' => $siteUrl, 'key' => 'val']);
		$this->assertStringNotContainsString('url=', $result);
	}

	public function testExpandLeavesTagUnchangedWhenViewDoesNotExist() {
		// 'testsc' is registered, but no shortcodes/testsc view exists.
		// expand() returns the original tag when the view is missing.
		$text = 'Hello [testsc key="value"] world';
		$result = $this->svc->expand($text, false, false, false);
		$this->assertStringContainsString('[testsc', $result);
	}

	public function testExpandDoesNotCorruptTextWithNoShortcodes() {
		$text = 'Just a plain sentence with no shortcodes';
		$result = $this->svc->expand($text, false, false, false);
		$this->assertStringContainsString('Just a plain sentence', $result);
	}
}
