<?php
/**
 * Defines the Domain class
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/**
 * Represents an Internet domain name.
 */
class Domain {

	/**
	 * @var string The domain name
	 */
	protected $raw;

	/**
	 * @param string $domain The domain name
	 */
	public function __construct(string $domain) {
		$this->raw = $domain;
	}

	/**
	 * Get the domain name
	 * 
	 * @return string The domain name
	 */
	public function getRaw() {
		return $this->raw;
	}

	/**
	 * Test whether the domain name is part of the .nl top level domain.
	 * 
	 * @return bool
	 */
	public function is_nl() {
		$top_level_domain = array_slice(explode('.', $this->raw), -1)[0];
		return 0 === strcasecmp('nl', $top_level_domain);
	}

	/**
	 * Get the top levels of the domain
	 * 
	 * @param int $n_levels The # of levels to return
	 * 
	 * @return string The top $n_levels levels of the domain
	 */
	public function getTopLevels($n_levels) {
		$levels = explode('.', $this->getRaw());
		return implode('.', array_slice($levels, - $n_levels));
	}
}
