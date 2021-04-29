<?php
/**
 * Defines MysqlCrawlerDb class
 * 
 * A MySql interface for the Crawler class.
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */


/**
 * Require the interface to be implemented.
 */
require_once __DIR__ . '/../crawler/CrawlerDbInterface.php';

/**
 * Defines MySql model to be used as DB interface by Crawler class.
 * 
 * Defines one public method in addition to those defined in the interface.
 * Change the constants at the top of the class to change the MySql table
 * names for holding the data.
 * 
 * @see \Crawler
 */
class MysqlCrawlerDb implements CrawlerDbInterface {

	/**
	 * [string] The name of the table for holding the 'gems'
	 */
	const GEMS_NAME = 'gems';

	/**
	 * [string] The name of the table for holding the urls
	 */
	const URLS_NAME = 'urls';

	/**
	 * [string] The name of the table for holding the number of visits per
	 *   location
	 */
	const COUNTS_NAME = 'counts';

	/**
	 * @var mysqli The connection to MySql.
	 */
	protected $conn;

	/**
	 * @param string $dbname
	 * @param string $servername
	 * @param string $username
	 * @param string $password
	 */
	public function __construct(
		$dbname,
		$servername = 'localhost',
		$username = 'root',
		$password = ''
	) {
		$this->conn = new mysqli($servername, $username, $password, $dbname);
	}

	/* See CrawlerDbInterface */
	public function getUrls($limit) {

		$psql = 'SELECT url FROM ' . static::URLS_NAME . ' ORDER BY id LIMIT ?';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('i', $limit);
		$stmt->execute();
		$stmt->bind_result($url);

		$urls = [];
		while (null !== $stmt->fetch()) {
			$urls[] = $url;
		}

		return $urls;
	}

	/* See CrawlerDbInterface */
	public function getUrlsCount() {
		$sql = 'SELECT COUNT(url) FROM ' . static::URLS_NAME;
		$result = $this->conn->query($sql);
		return $result->fetch_row()[0];
	}

	/* See CrawlerDbInterface */
	public function removeUrls($limit) {
		$psql = 'DELETE FROM ' . static::URLS_NAME . ' ORDER BY id LIMIT ?';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('i', $limit);
		return $stmt->execute();
	}

	/* See CrawlerDbInterface */
	public function addUrls($urls) {
		$sql = 'INSERT INTO ' . static::URLS_NAME . ' (url) VALUES ';
		$sql .= implode(', ', array_map(
			function($str) { return "('$str')"; },
			$urls
		));
		if (!$this->conn->query($sql)) {
			throw new Exception(__CLASS__ . '::addUrls() failed');
			return false;
		}
		return true;
	}

	/**
	 * Get the location count for location name $name
	 * 
	 * @param string $name The name of the location to get the count of
	 * @return int Number of visits to the location $name
	 */
	public function getCount($name) {
		$psql = 'SELECT count FROM ' . static::COUNTS_NAME . ' WHERE name = ? LIMIT 1';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('s', $name_p);
		$name_p = $name;
		$stmt->execute();
		$stmt->bind_result($count);
		return null === $stmt->fetch()
			? 0
			: $count;
	}

	/* See CrawlerDbInterface */
	public function addCount($name) {

		$psql = 'UPDATE ' . static::COUNTS_NAME . ' SET count = count + 1 WHERE name = ?';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('s', $name_p);
		$name_p = $name;
		if(!$stmt->execute()) {
			return false;
		}

		if (!$stmt->affected_rows) {
			return $this->insertCount($name);
		}

		return true;
	}

	/* See CrawlerDbInterface */
	public function addGems(Url $url, $gems, $json_encode = true) {
		$psql = 'INSERT INTO ' . static::GEMS_NAME . ' (url, gems) VALUES (?, ?)';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('ss', $url_p, $gems_p);
		$url_p = $url->getRaw();
		$gems_p = $json_encode ? json_encode($gems) : $gems;
		return $stmt->execute();
	}

	/**
	 * Insert a row in the counts table
	 * 
	 * @param string $name The name of the location that has been encountered
	 *   for the first time
	 * @return bool Return true on success and false on failure
	 */
	protected function insertCount($name) {
		$psql = 'INSERT INTO ' . static::COUNTS_NAME . ' (name, count) VALUES (?, 1)';
		$stmt = $this->conn->prepare($psql);
		$stmt->bind_param('s', $name_p);
		$name_p = $name;
		return $stmt->execute();
	}
}
