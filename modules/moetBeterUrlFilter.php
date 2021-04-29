<?php
/**
 * Defines moetBeterUrlFilter()
 * 
 * @author Nick <n.postema@ictmaatwerk.com>
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/** Dependencies */
require_once __DIR__ . '/url/Url.php';

/**
 * Tests whether $url_raw is suitable for storage.
 * 
 * This function can be passed as a callback to the
 * {@see Crawler::__construct() Crawler constructor} as the url filter.
 * 
 * @param string $url_raw The url to filter
 * @return boolean Whether to store the url
 */
function moetBeterUrlFilter($url_raw) {

	if (
		strpos($url_raw, '.webm') ||
		strpos($url_raw, '.gif') ||
		strpos($url_raw, '.mp4') ||
		strpos($url_raw, '.jpg') || 
		strpos($url_raw, '.png') || 
		strpos($url_raw, '.js') || 
		strpos($url_raw, '.css') || 
		strpos($url_raw, '.woff') || 
		strpos($url_raw, '.xml') || 
		strpos($url_raw, '=') || 
		strpos($url_raw, 'istats') || 
		strpos($url_raw, 'goedbegin') || 
		strpos($url_raw, 'warenhuis-shop') || 
		strpos($url_raw, 'plaza') || 
		strpos($url_raw, 'start') || 
		strpos($url_raw, 'pagina') || 
		strpos($url_raw, 'bestellen') ||
		strpos($url_raw, 'twitter') ||
		strpos($url_raw, 'facebook')
	) {
		return false;
	}

	try {
		$url = new Url($url_raw);
	} catch (InvalidUrlException $exc) {
		return false;
	}

	if(!$url->getDomain()->is_nl()) {
		return false;
	}

	return true;
}
