<?php
/**
 *
 */

namespace hypeJunction\Shortcodes;

use Elgg\Hook;

class ExpandLongtextShortcodes {

	/**
	 * Expand shortcodes
	 *
	 * @param Hook $hook Hook
	 *
	 * @return array
	 */
	public function __invoke(Hook $hook) {

		$vars = $hook->getValue();

		$value = elgg_extract('value', $vars, '');

		$value = $this->replaceLegacyCodes($value);

		$svc = elgg()->shortcodes;
		/* @var $svc ShortcodesService */

		$parse_urls = elgg_extract('parse_urls', $vars, true);
		$vars['parse_urls'] = false;

		$sanitize = elgg_extract('sanitize', $vars, true);
		$vars['sanitize'] = false;

		$autop = elgg_extract('autop', $vars, true);
		$vars['autop'] = false;

		$value = $svc->expand($value, $parse_urls, $sanitize, $autop);

		$vars['value'] = $value;

		return $vars;
	}

	/**
	 * Update some legacy values
	 *
	 * @param string $text Text
	 *
	 * @return null|string|string[]
	 */
	public function replaceLegacyCodes($text) {

		$image_callback = function($matches) {
			$hash = $matches[1];
			$ext = $matches[2];
			if (!$hash || !$ext) {
				return $matches[0];
			}

			$files = elgg_get_entities([
				'types' => 'object',
				'subtypes' => 'embed_file',
				'limit' => 1,
				'metadata_name_value_pairs' => [
					'hash' => $hash,
				],
			]);

			if (!$files) {
				return $matches[0];
			}

			$file = array_shift($files);

			$url = elgg_get_embed_url($file, 'large');
			if (!$url) {
				return $matches[0];
			}

			return str_replace(elgg_get_site_url(), '', $url);
		};

		$asset_callback = function($matches) {
			if ($matches[1]) {
				return "embed/asset/{$matches[1]}";
			}
			return $matches[0];
		};

		$linkembed_callback = function($matches) {
			if ($matches[1]) {
				return elgg()->shortcodes->generate('player', [
					'url' => $matches[1],
				]);
			}
			return $matches[0];
		};

		$text = preg_replace_callback('/ckeditor\/image\/\d+\/(.*?)\/(\w+)/i', $image_callback, $text);
		$text = preg_replace_callback('/ckeditor\/assets\/((\w+\/?)*)/i', $asset_callback, $text);
		$text = preg_replace_callback('/<a.*?href=\"(.*?)\".*?><img.*?alt=\"linkembed\".*?><\/a>/i', $linkembed_callback, $text);

		return $text;
	}
}