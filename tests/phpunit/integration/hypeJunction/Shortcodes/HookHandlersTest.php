<?php

namespace hypeJunction\Shortcodes;

use Elgg\IntegrationTestCase;

class HookHandlersTest extends IntegrationTestCase {

	/**
     * @return string
     */
    public function getPluginID(): string {
		return 'hypeshortcode';
	}

	public function up() {
		elgg()->shortcodes->register('testsc');
	}

	public function down() {}

	// --- handler registration ---

	public function testPrepareHtmlHandlerRegistered() {
		$handlers = _elgg_services()->events->getAllHandlers();
		$this->assertArrayHasKey('prepare', $handlers);
		$this->assertArrayHasKey('html', $handlers['prepare']);
	}

	public function testStripPlaintextHandlerRegistered() {
		$handlers = _elgg_services()->events->getAllHandlers();
		$this->assertArrayHasKey('view_vars', $handlers);
		$this->assertArrayHasKey('output/plaintext', $handlers['view_vars']);
	}

	public function testStripExcerptHandlerRegistered() {
		$handlers = _elgg_services()->events->getAllHandlers();
		$this->assertArrayHasKey('view_vars', $handlers);
		$this->assertArrayHasKey('output/excerpt', $handlers['view_vars']);
	}

	// --- handler behavior ---

	public function testPrepareHtmlStripsShortcodesWhenOptionSet() {
		$result = elgg_trigger_event_results('prepare', 'html', [], [
			'html' => '[testsc key="value"] Hello world',
			'options' => [
				'strip_shortcodes' => true,
				'sanitize' => false,
				'autop' => false,
				'parse_urls' => false,
			],
		]);
		$this->assertIsArray($result);
		$this->assertStringNotContainsString('[testsc', $result['html']);
		$this->assertStringContainsString('Hello world', $result['html']);
	}

	public function testPrepareHtmlPassesThroughCleanText() {
		$result = elgg_trigger_event_results('prepare', 'html', [], [
			'html' => 'Plain text without shortcodes',
			'options' => [
				'sanitize' => false,
				'autop' => false,
				'parse_urls' => false,
			],
		]);
		$this->assertIsArray($result);
		$this->assertStringContainsString('Plain text', $result['html']);
	}

	public function testStripPlaintextStripsShortcodes() {
		$result = elgg_trigger_event_results('view_vars', 'output/plaintext', [], [
			'value' => '[testsc key="value"] plain text content',
			'text' => '',
		]);
		$this->assertIsArray($result);
		$this->assertStringNotContainsString('[testsc', $result['text']);
		$this->assertStringContainsString('plain text content', $result['text']);
	}

	public function testStripExcerptStripsShortcodes() {
		$result = elgg_trigger_event_results('view_vars', 'output/excerpt', [], [
			'text' => '[testsc key="value"] excerpt content here',
		]);
		$this->assertIsArray($result);
		$this->assertStringNotContainsString('[testsc', $result['text']);
		$this->assertStringContainsString('excerpt content', $result['text']);
	}
}
