<?php
/**
 *
 */

namespace hypeJunction\Shortcodes;

use Elgg\Hook;

class PrepareHtmlOutput {

	/**
	 * Expand shortcodes
	 *
	 * @param Hook $hook Hook
	 *
	 * @return array
	 */
	public function __invoke(Hook $hook) {

		$value = $hook->getValue();

		$html = elgg_extract('html', $value, '');
		$options = elgg_extract('options', $value, []);

		$svc = elgg()->shortcodes;
		/* @var $svc ShortcodesService */

		$parse_urls = elgg_extract('parse_urls', $options, true);
		$options['parse_urls'] = false;
		$options['parse_emails'] = false;

		$sanitize = elgg_extract('sanitize', $options, true);
		$options['sanitize'] = false;

		$autop = elgg_extract('autop', $options, true);
		$options['autop'] = false;

		$strip_shortcodes = elgg_extract('strip_shortcodes', $options, false);
		if ($strip_shortcodes) {
			$html = $svc->strip($html);
		}

		$html = $svc->expand($html, $parse_urls, $sanitize, $autop);

		return [
			'html' => $html,
			'options' => $options,
		];
	}
}