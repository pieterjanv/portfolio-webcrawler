<?php
/**
 * The PriceCrawler class is defined
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/** Dependencies */
require_once __DIR__ . '/crawler/Crawler.php';
require_once __DIR__ . '/dbs/PriceCrawlerDb.php';

/**
 * Class voor het crawlen van bierprijzen.
 */
class PriceCrawler extends Crawler {

	/**
	 * [callable] Default url filter
	 * 
	 * Value: {@see PriceCrawler::isInternalUrl()}
	 *
	 * See {@see Crawler::$url_filter}
	 */
	const URL_FILTER_DEFAULT = [__CLASS__, 'isInternalUrl'];

	/**
	 * [callable] Default gem filter
	 * 
	 * Value: {@see PriceCrawler::getPriceInfo()}
	 *
	 * See {@see Crawler::$gem_filter}
	 */
	const GEM_FILTER_DEFAULT = [__CLASS__, 'getPriceInfo'];

	/**
	 * [callable] Default process url condition
	 * 
	 * Value: {@see PriceCrawler::isUnvisited()}
	 *
	 * See {@see Crawler::$process_url_condition}
	 */
	const PROCESS_URL_CONDITION_DEFAULT = [__CLASS__, 'isUnvisited'];

	/**
	 * [callable] Default count filter
	 * 
	 * Value: {@see PriceCrawler::fullUrl()}
	 *
	 * See {@see Crawler::$count_filter}
	 */
	const COUNT_FILTER_DEFAULT = [__CLASS__, 'fullUrl'];

	/**
	 * Construct an instance
	 * 
	 * See {@see Crawler::__construct() Crawler's constructor} for a detailed
	 * description of the parameters.
	 * 
	 * @param int $target
	 * 
	 * Default: 10
	 * 
	 * @param int $get_url_limit
	 *
	 * Default: 10
	 *
	 * @param callable $process_url_condition
	 *
	 * Default: PriceCrawler::PROCESS_URL_CONDITION_DEFAULT
	 *
	 * @param callable $url_filter
	 * 
	 * Default: PriceCrawler::URL_FILTER_DEFAULT
	 * 
	 * @param callable $gem_filter
	 * 
	 * Default: PriceCrawler::GEM_FILTER_DEFAULT
	 * 
	 * @param callable $count_filter
	 * 
	 * Default: Crawler::COUNT_FILTER_DEFAULT
	 * 
	 * @param int $urls_limit
	 * 
	 * Default: 1000
	 *
	 * @see Crawler::__construct()
	 * @see PriceCrawler::URL_FILTER_DEFAULT
	 * @see PriceCrawler::GEM_FILTER_DEFAULT
	 * @see Crawler::COUNT_FILTER_DEFAULT
	 */
	public function __construct(
		int $target = 10,
		int $get_url_limit = 10,
		callable $process_url_condition = self::PROCESS_URL_CONDITION_DEFAULT,
		callable $url_filter = self::URL_FILTER_DEFAULT,
		callable $gem_filter = self::GEM_FILTER_DEFAULT,
		callable $count_filter = self::COUNT_FILTER_DEFAULT,
		int $urls_limit = 100000,
		string $seed = null,
		bool $give_output = false,
		string $dbname = 'price_crawler',
		string $servername = 'localhost',
		string $username = 'root',
		string $password = ''
	) {

		$db = new PriceCrawlerDb($dbname, $servername, $username, $password);

		parent::__construct(
			$db,
			$target,
			$get_url_limit,
			$process_url_condition,
			$url_filter,
			$gem_filter,
			$count_filter,
			$give_output,
			$urls_limit,
		);

		if ($seed) {
			$this->initializeDb($seed);
		}
	}

	/**
	 * Seed the db with starting url
	 */
	protected function initializeDb($seed) {
		$this->db->addUrls([$seed]);
	}

	/**
	 * Filter out non-local urls
	 */
	public static function isInternalUrl($urlRaw, Url $source) {

		if (
			strpos($urlRaw, '.webm') ||
			strpos($urlRaw, '.gif') ||
			strpos($urlRaw, '.mp4') ||
			strpos($urlRaw, '.jpg') || 
			strpos($urlRaw, '.png') || 
			strpos($urlRaw, '.js') || 
			strpos($urlRaw, '.css') || 
			strpos($urlRaw, '.woff') || 
			strpos($urlRaw, '.xml')
		) {
			return false;
		}

		// als de url intern is (beginnend met "/" of relatief)
		if (!preg_match(
			'#^(?:[\w\d]+://([\w\d\.]+)|//([\w\d\.]+)|([\w\d\.]+))#i',
			$urlRaw,
			$matches
		)) {
			return true;
		}

		$domain = isset($matches[3])
			? new Domain($matches[3])
			: (isset($matches[2])
				? new Domain($matches[2])
				: (isset($matches[1])
					? new Domain($matches[1])
					: null
				)
			);

		// als het domein van de url die van de bron is
		if (
			null !== $domain &&
			$source->getDomain()->getTopLevels(2) === $domain->getTopLevels(2)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Scrape page content for price info
	 */
	public static function getPriceInfo($content) {
		preg_match('#<script[^>]+type=[\'"]application/ld\+json[\'"][^>]*?>(.*?)</script>#mi', $content, $matches);
		return $matches[1] ?? null;
	}

	/**
	 * Condition for processing a url
	 */
	public static function isUnvisited(
		Url $url, CrawlerDbInterface $db
	) {
		return 0 === $db->getCount($url->getRaw());
	}

	/**
	 * Count filter for Crawler: return url unfiltered
	 */
	public static function fullUrl(Url $url) {
		return $url->getRaw();
	}
}
