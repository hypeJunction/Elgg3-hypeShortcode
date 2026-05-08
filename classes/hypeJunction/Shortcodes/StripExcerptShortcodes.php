<?php

namespace hypeJunction\Shortcodes;

use Elgg\Event;

/**
 * Hook handler that strips shortcode tags from excerpt output.
 */
class StripExcerptShortcodes {

	/**
	 * Implement custom URL parsing to avoid rewriting shortcode tags
	 *
	 * @param Event $event Event
	 * @return array
	 */
	public function __invoke(Event $event) {

		$vars = $event->getValue();

		$text = elgg_extract('text', $vars, '');

		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		$svc = elgg()->shortcodes;
		/* @var $svc \hypeJunction\Shortcodes\ShortcodesService */

		$text = $svc->strip($text);

		$vars['text'] = $text;

		return $vars;
	}
}
