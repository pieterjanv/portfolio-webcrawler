<?php
/**
 * Defines the Url class
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/** Dependencies */
require_once __DIR__ . '/../Domain.php';
require_once __DIR__ . '/InvalidUrlException.php';

/**
 * An object representing a url.
 */
class Url {

	/**
	 * @var string The url
	 */
	protected $raw;

	/**
	 * @param string $url The url
	 * @throws InvalidUrlException
	 */
	public function __construct(string $url) {

		if (!static::is_valid($url)) {
			throw new InvalidUrlException('Invalid url: ' . $url);
		}

		$this->raw = $url;
	}

	/**
	 * Get the url as string
	 * 
	 * @return string The url
	 */
	public function getRaw() {
		return $this->raw;
	}

	/**
	 * Get the url's domain
	 * 
	 * @return \Domain
	 */
	public function getDomain() {
		$full_domain = parse_url($this->getRaw(), PHP_URL_HOST);
		$exploded = explode('.', $full_domain);
		$domain = implode('.', array_slice($exploded, -2));
		return new Domain($domain);
	}

	public function getRelativePrefix() {
		$raw = $this->getRaw();
		$last_slash_pos = strpos($raw, '/', -1);
		return substr($raw, 0, false !== $last_slash_pos
			? $last_slash_pos + 1
			: strlen($raw)
		);
	}

	/**
	 * Test a url for validity
	 * 
	 * @param string $url The url
	 * @return bool
	 */
	protected static function is_valid($url) {
		return !(false === filter_var($url, FILTER_VALIDATE_URL));
	}
}
