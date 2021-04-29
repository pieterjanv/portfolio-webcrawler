<?php
/**
 * The Test_dbCrawler is defined
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/** Dependencies */
require_once __DIR__ . '/crawler/Crawler.php';
require_once __DIR__ . '/moetBeterUrlFilter.php';
require_once __DIR__ . '/getWordpressVersionGemsFilter.php';

/**
 * A class for testing the performance of a db in crawling
 * 
 * This class extends {@see Crawler} to provide an easy way to keep track of 
 * the time used by the DB with which this class is instantiated.
 *
 * The constructor's defaults are different from those of Crawler's constructor.
 */
class Test_dbCrawler extends Crawler {

	/**
	 * [callable] Default url filter
	 * 
	 * Value: {@see moetBeterUrlFilter()}
	 *
	 * See {@see Crawler::$url_filter}
	 */
	const URL_FILTER_DEFAULT = 'moetBeterUrlFilter';

	/**
	 * [callable] Default gem filter
	 * 
	 * Value: {@see getWordpressVersionGemsFilter()}
	 *
	 * See {@see Crawler::$gem_filter}
	 */
	const GEM_FILTER_DEFAULT = 'getWordpressVersionGemsFilter';

	/**
	 * Total number of seconds spent interacting with storage
	 * 
	 * @var float
	 */
	protected $db_total_time = 0;

	/**
	 * Total number of seconds spent loading content
	 * 
	 * @var float
	 */
	protected $load_content_total_time = 0;

	/**
	 * Construct an instance
	 * 
	 * See {@see Crawler::__construct() Crawler's constructor} for a detailed
	 * description of the parameters.
	 * 
	 * Output of Crawler itself is suppressed. Only the output defined by this
	 * class is given.
	 *
	 * @param CrawlerDbInterface $db
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
	 * Default: null
	 *
	 * @param callable $url_filter
	 * 
	 * Default: Test_dbCrawler::URL_FILTER_DEFAULT
	 * 
	 * @param callable $gem_filter
	 * 
	 * Default: Test_dbCrawler::GEM_FILTER_DEFAULT
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
	 * @see Test_dbCrawler::URL_FILTER_DEFAULT
	 * @see Test_dbCrawler::GEM_FILTER_DEFAULT
	 * @see Crawler::COUNT_FILTER_DEFAULT
	 */
	public function __construct(
		CrawlerDbInterface $db,
		int $target = 10,
		int $get_url_limit = 10,
		callable $process_url_condition = null,
		callable $url_filter = self::URL_FILTER_DEFAULT,
		callable $gem_filter = self::GEM_FILTER_DEFAULT,
		callable $count_filter = self::COUNT_FILTER_DEFAULT,
		int $urls_limit = 1000
	) {
		parent::__construct($db, $target, $get_url_limit, $process_url_condition, $url_filter, $gem_filter, $count_filter, false, $urls_limit);
		$this->initializeDb();
	}

	/**
	 * Test the DB by running the crawler
	 * 
	 * @return float Total time spent interacting with the DB.
	 */
	public function go() {
		parent::go();
		echo 'Test finished.' . "\n";
		echo 'Total db time was ' . $this->db_total_time . 's.' . "\n";
		echo 'Total # urls visited is ' . $this->n_urls_visited . '.' . "\n";
		echo 'Total content loading time was ' . $this->load_content_total_time . 's' . "\n";
		return $this->db_total_time;
	}

	/**
	 * Crawl the next batch of urls
	 * 
	 * This method overrides its parent's so that the duration of interactions
	 * can be measured.
	 * 
	 * See {@see Crawler::next()} for a detailed description.
	 */
	protected function next() {

		$urls = self::time(
			$this->db_total_time,
			[$this->db, 'getUrls'],
			$this->get_url_limit
		);

		if (!$urls) {
			$this->output('No more urls.');
			return false;
		}

		foreach ($urls as $url_raw) {

			if ($this->n_urls_visited >= $this->target) {
				$this->output('Target reached');
				return false;
			}

			try {
				$url = new Url($url_raw);
			} catch (InvalidUrlException $exc) {
				$this->output($exc->getMessage());
				continue;
			}

			if (
				$this->process_url_condition &&
				!call_user_func($this->process_url_condition, $url, $this->db)
			) {
				$this->output('Not processing ' . $url->getRaw());
				continue;
			}

			self::time(
				$this->db_total_time,
				[$this->db, 'addCount'],
				call_user_func($this->count_filter, $url)
			);

			self::time($this->load_content_total_time, [$this, 'getContent'], $url);
			$this->n_urls_visited++;

			if ($this->db->getUrlsCount() <= $this->urls_limit) {
				self::time($this->db_total_time, [$this, 'addUrls']);
			}

			if($this->gem_filter) {
				self::time($this->db_total_time, [$this, 'addGems'], $url);
			}
		}

		self::time($this->db_total_time, [$this->db, 'removeUrls'], $this->get_url_limit);

		return true;
	}

	/**
	 * Seed the db with starting url
	 */
	protected function initializeDb() {
		$this->db->addUrls(['https://www.google.nl/search?q=site%3Anl']);
	}

	/**
	 * Measure the time it takes $cb() to complete
	 * 
	 * @param float $total The variable holding the total to update (reference)
	 * @param callable $cb The function whose execution time to measure
	 * @param mixed ...$args ,... The arguments to call $cb() with. Add as many
	 *   as you like.
	 * @return mixed Result returned by $cb()
	 */
	protected static function time(&$total, $cb, ...$args) {
		$start = microtime(true);
		$result = call_user_func($cb, ...$args);
		$total += microtime(true) - $start;
		return $result;
	}
}
