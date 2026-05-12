<?php

namespace hypeJunction\Shortcodes;

use Elgg\DefaultPluginBootstrap;

class Bootstrap extends DefaultPluginBootstrap {

	/**
     * @return void
     */
    public function init(): void {
		elgg_extend_view('elgg.css', 'shortcodes.css');
	}
}
