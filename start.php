<?php

require_once __DIR__ . '/autoloader.php';

return function () {

	elgg_register_event_handler('init', 'system', function () {

		elgg_register_plugin_hook_handler('view_vars', 'output/plaintext', \hypeJunction\Shortcodes\ExpandLongtextShortcodes::class, 9999);
		elgg_register_plugin_hook_handler('view_vars', 'output/longtext', \hypeJunction\Shortcodes\ExpandLongtextShortcodes::class, 9999);

		elgg_register_plugin_hook_handler('view_vars', 'output/excerpt', \hypeJunction\Shortcodes\StripExcerptShortcodes::class, 9999);

		elgg_extend_view('elgg.css', 'shortcodes.css');
	});

};
