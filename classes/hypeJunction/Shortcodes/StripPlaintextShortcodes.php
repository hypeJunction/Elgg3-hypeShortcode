<?php

namespace hypeJunction\Shortcodes;

use Elgg\Event;

/**
 * Hook handler that strips shortcode tags from plaintext output.
 */
class StripPlaintextShortcodes {

	/**
	 * Implement custom URL parsing to avoid rewriting shortcode tags
	 *
	 * @param Event $event Event
	 * @return array
	 */
	public function __invoke(Event $event) {

		$vars = $event->getValue();

		$value = \elgg_extract('value', $vars, '');

		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

		$svc = elgg()->shortcodes;
		/* @var $svc \hypeJunction\Shortcodes\ShortcodesService */

		$value = $svc->strip($value);

		$vars['text'] = $value;

		return $vars;
	}
}
