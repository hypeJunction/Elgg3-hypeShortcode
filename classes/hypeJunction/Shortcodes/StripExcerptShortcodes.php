<?php

namespace hypeJunction\Shortcodes;

use Elgg\Hook;

class StripExcerptShortcodes {

	/**
	 * Implement custom URL parsing to avoid rewriting shortcode tags
	 *
	 * @param Hook $hook Hook
	 * @return array
	 */
	public function __invoke(Hook $hook) {

		$vars = $hook->getValue();

		$text = elgg_extract('text', $vars, '');

		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		$svc = elgg()->shortcodes;
		/* @var $svc \hypeJunction\Shortcodes\ShortcodesService */

		$text = $svc->strip($text);

		$vars['text'] = $text;

		return $vars;
	}
}