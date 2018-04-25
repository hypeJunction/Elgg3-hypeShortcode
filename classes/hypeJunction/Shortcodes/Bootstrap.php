<?php

namespace hypeJunction\Shortcodes;

use Elgg\Includer;
use Elgg\PluginBootstrap;

class Bootstrap extends PluginBootstrap {

	/**
	 * Get plugin root
	 * @return string
	 */
	protected function getRoot() {
		return dirname(dirname(dirname(dirname(__FILE__))));
	}

	/**
	 * {@inheritdoc}
	 */
	public function load() {
		Includer::requireFileOnce($this->getRoot() . '/autoloader.php');
	}

	/**
	 * {@inheritdoc}
	 */
	public function boot() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function init() {
		elgg_register_plugin_hook_handler('prepare', 'html', \hypeJunction\Shortcodes\PrepareHtmlOutput::class, 9999);

		elgg_register_plugin_hook_handler('view_vars', 'output/plaintext', \hypeJunction\Shortcodes\StripPlaintextShortcodes::class, 9999);
		elgg_register_plugin_hook_handler('view_vars', 'output/excerpt', \hypeJunction\Shortcodes\StripExcerptShortcodes::class, 9999);

		elgg_extend_view('elgg.css', 'shortcodes.css');
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function shutdown() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function activate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function deactivate() {

	}

	/**
	 * {@inheritdoc}
	 */
	public function upgrade() {

	}

}