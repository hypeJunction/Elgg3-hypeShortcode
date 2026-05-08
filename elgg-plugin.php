<?php

return [
	'plugin' => [
		'name' => 'hypeShortcode',
	],
	'bootstrap' => \hypeJunction\Shortcodes\Bootstrap::class,
	'events' => [
		'prepare' => [
			'html' => [
				\hypeJunction\Shortcodes\PrepareHtmlOutput::class => ['priority' => 9999],
			],
		],
		'view_vars' => [
			'output/plaintext' => [
				\hypeJunction\Shortcodes\StripPlaintextShortcodes::class => ['priority' => 9999],
			],
			'output/excerpt' => [
				\hypeJunction\Shortcodes\StripExcerptShortcodes::class => ['priority' => 9999],
			],
		],
	],
];
