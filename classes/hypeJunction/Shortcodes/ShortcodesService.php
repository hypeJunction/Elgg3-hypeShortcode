<?php

namespace hypeJunction\Shortcodes;

class ShortcodesService {

	/**
	 * @var string
	 */
	protected $shortcodes = [];

	/**
	 * Register a shortcode
	 * Shortcode must correspond to a view in shortcodes/<shortcode>
	 *
	 * @param string $shortcode Shortcode name
	 *
	 * @return void
	 */
	public function register($shortcode) {
		$this->shortcodes[] = $shortcode;
	}

	/**
	 * Generate a shortcode tag
	 *
	 * @param string $shortcode Shortcode name
	 * @param array  $attrs     Shortcode attrs
	 *
	 * @return string
	 */
	public function generate($shortcode, array $attrs = []) {
		foreach ($attrs as &$value) {
			if (preg_match_all('/[\[\]\"]/i', $value)) {
				$value = 'x_' . base64_encode($value);
			}
		}

		if (isset($attrs['url']) && $attrs['url'] == elgg_get_site_url()) {
			unset($attrs['url']);
		}

		$attributes = elgg_format_attributes(array_filter($attrs));

		return "[$shortcode $attributes]";
	}

	/**
	 * Expand shortcodes
	 *
	 * @param string $text     Text
	 *                         * @param bool $parse_urls Parse and linkify URLs
	 * @param bool   $sanitize Sanitize the text
	 * @param bool   $autop    Add paragraphs
	 *
	 * @return string
	 */
	public function expand($text, $parse_urls = true, $sanitize = true, $autop = true) {

		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		$text = $this->replaceLegacyCodes($text);

		if ($sanitize) {
			$text = filter_tags($text);
		}

		if ($autop) {
			$text = elgg_autop($text);
		}

		$shortcodes = implode('|', $this->shortcodes);

		$text = preg_replace_callback("/\[({$shortcodes})(.*?)\]/", function ($matches) {

			$full = $matches[0];

			$shortcode = $matches[1];

			if (!elgg_view_exists("shortcodes/$shortcode")) {
				return $full;
			}

			preg_match_all('/(\s+)([a-z0-9]+)(\=\"(.*?)\")?/', $matches[2], $attribute_matches);

			$attributes = [];
			for ($i = 0; $i < count($attribute_matches[0]); $i++) {
				$key = filter_tags($attribute_matches[2][$i]);
				$value = filter_tags($attribute_matches[4][$i]);
				if (strpos($value, 'x_') === 0) {
					$value = substr($value, 2);
					$value = base64_decode($value);
				}
				$value = htmlspecialchars_decode($value, ENT_QUOTES);
				$attributes[$key] = $value;
			}

			$output = elgg_view("shortcodes/$shortcode", $attributes);

			return $output;
		}, $text);

		if ($parse_urls) {
			$text = parse_urls($text);
			$text = elgg_parse_emails($text);
		}

		return $text;
	}

	/**
	 * Update some legacy values
	 *
	 * @param string $text Text
	 *
	 * @return null|string|string[]
	 */
	protected function replaceLegacyCodes($text) {

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

	/**
	 * Extract shortcodes from text
	 *
	 * @param string $text Text
	 *
	 * @return array
	 */
	public function extract($text) {

		$shortcodes = implode('|', $this->shortcodes);

		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		preg_match_all("/\[({$shortcodes})(.*?)\]/", $text, $matches);

		$result = [];

		for ($i = 0; $i < count($matches[0]); $i++) {
			$shortcode = $matches[1][$i];

			preg_match_all('/(\s+)([a-z0-9]+)(\=\"(.*?)\")?/', $matches[2][$i], $attribute_matches);

			$attributes = [];
			for ($i = 0; $i < count($attribute_matches[0]); $i++) {
				$key = filter_tags($attribute_matches[2][$i]);
				$value = filter_tags($attribute_matches[4][$i]);
				if (strpos($value, 'x_') === 0) {
					$value = substr($value, 2);
					$value = base64_decode($value);
				}
				$value = htmlspecialchars_decode($value, ENT_QUOTES);
				$attributes[$key] = $value;
			}

			$result[$shortcode][] = $attributes;
		}

		return $result;
	}

	/**
	 * Strip shortcodes from text
	 *
	 * @param string $text Text
	 *
	 * @return string
	 */
	public function strip($text) {

		$shortcodes = implode('|', $this->shortcodes);

		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		$text = preg_replace_callback("/\[({$shortcodes})\s(.*?)\]/", function ($matches) {
			return '';
		}, $text);

		return $text;
	}

	/**
	 * Callback function for url preg_replace_callback
	 *
	 * @param array $matches An array of matches
	 *
	 * @return string
	 */
	protected function parseUrlCallback($matches) {
		if (empty($matches[2])) {
			return $matches[0];
		}

		$text = $matches[2];

		return $matches[1] . elgg_format_element('a', [
				'href' => $matches[2],
				'rel' => 'nofollow',
			], $text);
	}
}