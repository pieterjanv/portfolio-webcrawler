<?php
/**
 * This file defines getWordpressVersionGemsFilter().
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/**
 * Filter the WordPress version out of $content
 * 
 * This function can be passed as callback to the
 * {@see Crawler::__construct() Crawler constructor} as the gems filter.
 * 
 * @param string $content The content at a url
 * @return array|false Returns an array with key 'wp_version' and value a string
 *   with the version number if found, false otherwise.
 */
function getWordpressVersionGemsFilter($content) {
	preg_match_all('/<meta.+?name="?generator.+?>/i', $content, $generator_tags_result);
	$generator_tags = $generator_tags_result[0];
	foreach ($generator_tags as $tag) {
		preg_match('/content="wordpress\s+?(\S+?)"/i', $tag, $wp_version_result);
		if ($wp_version = $wp_version_result[1] ?? false) {
			return ['wp_version' => $wp_version];
		}
	}
	return false;
}
