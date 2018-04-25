<?php

namespace hypeJunction\Shortcodes;

use Elgg\Hook;

class StripPlaintextShortcodes {

	/**
	 * Implement custom URL parsing to avoid rewriting shortcode tags
	 *
	 * @param Hook $hook Hook
	 * @return array
	 */
	public function __invoke(Hook $hook) {

		$vars = $hook->getValue();

		$value = elgg_extract('value', $vars, '');

		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		$svc = elgg()->shortcodes;
		/* @var $svc \hypeJunction\Shortcodes\ShortcodesService */

		$value = $svc->strip($value);

		$vars['text'] = $value;

		return $vars;
	}
}