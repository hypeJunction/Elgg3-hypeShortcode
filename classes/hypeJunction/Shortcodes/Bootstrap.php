<?php

namespace hypeJunction\Shortcodes;

use Elgg\DefaultPluginBootstrap;

/**
 * Plugin bootstrap.
 */
class Bootstrap extends DefaultPluginBootstrap {

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		elgg_extend_view('elgg.css', 'shortcodes.css');
	}
}
