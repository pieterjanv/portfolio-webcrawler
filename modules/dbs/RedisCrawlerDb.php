<?php
/**
 * Defines RedisCrawlerDb class
 * 
 * A Redis interface for the Crawler class.
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/**
 * Require the interface to be implemented.
 */
require_once __DIR__ . '/../crawler/CrawlerDbInterface.php';

/**
 * Defines Redis model to be used as DB interface by Crawler class.
 * 
 * Defines one public method in addition to those defined in the interface.
 * Change the constants at the top of the class to change the Redis keys for
 * holding the data.
 * 
 * @see \Crawler
 */
class RedisCrawlerDb implements CrawlerDbInterface {

	/**
	 * [string] Name of the Redis key for storing the urls
	 */
	const URLS_NAME = 'urls';

	/**
	 * [string] Name of the Redis key for storing the 'gems'
	 */
	const GEMS_NAME = 'gems';
	
	/**
	 * [string] Name of the Redis key for storing the number of visits per
	 *   location
	 */
	const COUNTS_NAME = 'counts';

	/**
	 * @var Redis The connection to Redis
	 */
	protected $conn;

	public function __construct() {
		$this->conn = new Redis();
		$this->conn->connect('localhost');
	}

	/* See CrawlerDbInterface */
	public function getUrls($limit) {
		return $this->get(static::URLS_NAME, $limit);
	}

	/* See CrawlerDbInterface */
	public function getUrlsCount() {
		return $this->conn->lLen(static::URLS_NAME);
	}

	/* See CrawlerDbInterface */
	public function removeUrls($limit) {
		return $this->remove(static::URLS_NAME, $limit);
	}

	/* See CrawlerDbInterface */
	public function addUrls($urls) {
		$this->push(static::URLS_NAME, $urls);
		return true;
	}

	/**
	 * Get the location count for location name $name
	 * 
	 * @param string $name The name of the location to get the count of
	 * @return int Number of visits to the location $name
	 */
	public function getCount($name) {
		return $this->conn->hExists(static::COUNTS_NAME, $name)
			? $this->conn->hGet(static::COUNTS_NAME, $name)
			: 0;
	}

	/* See CrawlerDbInterface */
	public function addCount($name) {
		$this->conn->hIncrBy(static::COUNTS_NAME, $name, 1);
		return true;
	}

	/* See CrawlerDbInterface */
	public function addGems(Url $url, $gems, $json_encode = true) {
		$status = $this->conn->hSet(
			static::GEMS_NAME,
			$url->getRaw(),
			$json_encode ? json_encode($gems) : $gems
		);
		switch ($status) {
			case 0:
			case 1:
				return true;
			case false:
				return false;
		}
	}

	/**
	 * Get up to $limit items from Redis list $name.
	 * 
	 * Get up to $limit items from Redis list $name. Get the oldest items.
	 * 
	 * @param string $name The key of the Redis list
	 * @param int $limit The maximum number of items to get
	 * @return string[] The items.
	 */
	protected function get($name, $limit) {
		return $this->conn->lRange($name, -$limit, -1);
	}

	/**
	 * Remove up to $limit items from Redis list $name
	 * 
	 * Remove up to $limit items from Redis list $name. Remove oldest items.
	 * 
	 * @param string $name The key of the Redis list
	 * @param int $limit The maximum number of items to remove
	 * @return true
	 */
	protected function remove($name, $limit) {
		return $this->conn->lTrim($name, 0, -($limit + 1));
	}

	/**
	 * Push any number of values to the Redis list $name
	 * 
	 * @param string $name The Redis list key
	 * @param string[] $values The values to push to the list
	 * @return int|false Returns the new length of the list or false
	 */
	protected function push($name, $values) {
		return $this->conn->lPush($name, ...$values);
	}
}
