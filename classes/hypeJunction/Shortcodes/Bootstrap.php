<?php

namespace hypeJunction\Shortcodes;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {

	public function init(): void {
		\elgg_extend_view('elgg.css', 'shortcodes.css');
	}
}
