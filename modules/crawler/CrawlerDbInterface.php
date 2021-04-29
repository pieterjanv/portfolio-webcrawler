<?php
/**
 * Defines CrawlerDbInterface
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/**
 * Dependencies
 */
require_once __DIR__ . '/../url/Url.php';
require_once __DIR__ . '/../Domain.php';

/**
 * The Crawler's interface to storage
 * 
 * @see \Crawler
 */
interface CrawlerDbInterface {

	/**
	 * Get a maximum of $limit urls from storage
	 * 
	 * @param int $limit The maximum # of urls returned
	 * @return string[]|null Array containing the urls
	 */
	public function getUrls($limit);

	/**
	 * Get the number of urls in storage
	 * 
	 * @return int The number of urls in storage
	 */
	public function getUrlsCount();

	/**
	 * Remove the urls that were returned by the last call to getUrls()
	 * 
	 * @param int $limit The maximum number of urls returned by getUrls()
	 * @return bool Whether the removal was successful
	 */
	public function removeUrls($limit);

	/**
	 * Add found urls to storage
	 * 
	 * @param string[] $urls The urls to store
	 * @return bool Whether the adding was successful
	 */
	public function addUrls($urls);

	/**
	 * Add 1 to the number of visits to the location $name
	 * 
	 * The location is as returned by the $count_filter argument passed to
	 * the Crawler constructor.
	 * 
	 * @param string $name The location visited
	 * @return bool Whether the adding was successful
	 */
	public function addCount($name);

	/**
	 * Add the gems to storage
	 * 
	 * Add the gems found at $url to storage. Optionally JSON encode $gems.
	 * 
	 * @param Url $url The url where the gems were found
	 * @param mixed $gems The gems
	 * @param bool $json_encode Optional, default true. Whether the gems are
	 *   JSON encoded before storage.
	 * @return bool Whether adding the gems was successful
	 */
	public function addGems(Url $url, $gems, $json_encode = true);
}
