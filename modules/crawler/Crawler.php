<?php
/**
 * Defines the Crawler class
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/** Dependencies */
require_once __DIR__ . '/CrawlerDbInterface.php';
require_once __DIR__ . '/../url/Url.php';

/**
 * Given a CrawlerDbInterface it crawls the web, optionally scraping the
 * found content for 'gems'.
 * 
 * Several callbacks can be passed to the constructor to determine
 *  * which urls to store
 *  * which urls to crawl
 *  * what gems to collect
 *  * how to count the url
 * 
 * See the {@see Crawler::__construct() constructor} for all the ways to specify
 * an instance.
 * 
 * Deze class is gegroeid uit het aanpassen van een crawler door Nick Postema,
 * in opdracht van Nick Postema. E-mail adres bijgevoegd.
 * 
 * @see \CrawlerDbInterface
 * @link mailto:n.postema@ictmaatwerk.com n.postema@ictmaatwerk.com
 */
class Crawler {

	/**
	 * [callable] Default filter for urls to save to storage.
	 * 
	 * Value: {@see Crawler::getDotHtmlUrlFilter()}.
	 * 
	 * See {@see Crawler::$url_filter}.
	 */
	const URL_FILTER_DEFAULT = ['self', 'getDotHtmlUrlFilter'];

	/**
	 * [callable] Default filter returning the location to count from the
	 * visited url
	 * 
	 * Value: {@see Crawler::getDomain()}.
	 * 
	 * See {@see Crawler::$count_filter}.
	 */
	const COUNT_FILTER_DEFAULT = ['self', 'getDomain'];

	/**
	 * The Crawler's interface to storage
	 * 
	 * @var CrawlerDbInterface
	 */
	protected $db;

	/**
	 * The maximum # of urls to crawl
	 * 
	 * @var int
	 */
	protected $target;

	/**
	 * The maximum # of urls to retrieve from storage in one go (batch size)
	 * 
	 * @var int
	 */
	protected $get_url_limit;

	/**
	 * Predicate callback indicating whether to crawl a url or proceed to the
	 * next url
	 * 
	 * See the {@see Crawler::__construct() constructor}'s $process_url_condition
	 * parameter for a specification.
	 * 
	 * @var callable
	 * @used-by Crawler::next()
	 */
	protected $process_url_condition;

	/**
	 * Is applied as filter to the urls to be saved to storage.
	 * 
	 * See the {@see Crawler::__construct() constructor}'s $url_filter parameter
	 * for a specification.
	 * 
	 * @var callable
	 * @used-by Crawler::addUrls()
	 */
	protected $url_filter;

	/**
	 * Is applied as gems filter to the crawled content.
	 * 
	 * See the {@see Crawler::__construct() constructor}'s $gem_filter parameter
	 * for a specification.
	 * 
	 * @var callable
	 * @used-by Crawler::addGems()
	 */
	protected $gem_filter;

	/**
	 * Is applied as count filter to the crawled url.
	 * 
	 * See the {@see Crawler::__construct() constructor}'s $count_filter parameter
	 * for a specification.
	 * 
	 * @var callable
	 * @used-by Crawler::next()
	 */
	protected $count_filter;

	/**
	 * Whether to give output.
	 * 
	 * @var bool
	 */
	protected $give_output;

	/**
	 * The maximum # of urls to keep in storage
	 *
	 * @var int
	 */
	protected $urls_limit;

	/**
	 * Stores the content of the currently visited url
	 *
	 * @var string
	 */
	protected $content_buffer;

	/**
	 * Total number of urls visited by the Crawler
	 *
	 * @var int
	 */
	protected $n_urls_visited = 0;

	/**
	 * @var float Total time crawling (ms)
	 */
	protected $total_time = 0.0;

	/**
	 * @param CrawlerDbInterface $db The Crawler's interface to storage
	 * @param int $target The maximum # of urls to crawl
	 * 
	 * Default: 100
	 *
	 * @param int $get_url_limit The maximum # of urls to retrieve from storage
	 *   in one go (batch size)
	 *
	 * Default: 10
	 *
	 * @param callable $process_url_condition Predicate callback indicating
	 *   whether to crawl a url or proceed to the next url.
	 *
	 * Default: null
	 * 
	 * Arguments passed:
	 * * [Url] The next url to crawl
	 * * [CrawlerDbInterface] The DB model used by the Crawler
	 *
	 * Must return:
	 * * [bool] Whether to process the url.
	 *
	 * See Crawler::$process_url_condition
	 *
	 * @param callable $url_filter Is applied as filter to the urls to be saved
	 *   to storage
	 *
	 * Default: Crawler::URL_FILTER_DEFAULT
	 *
	 * Arguments passed:
	 *  * [string] A url found in the content
	 *
	 * Must return:
	 *  * [bool] Whether to store the url
	 *
	 * See Crawler::$url_filter
	 *
	 * @param callable $gem_filter Is applied as gems filter to the crawled
	 *   content
	 *
	 * Default: null
	 *
	 * Arguments passed:
	 *  * [string] Content at visited url
	 *
	 * Must return:
	 *  * [mixed] The gems to be saved to the database (JSON encoded)
	 *
	 * See Crawler::$gem_filter
	 *
	 * @param callable $count_filter Is applied as count filter to the crawled
	 *   url
	 *
	 * Default: Crawler::COUNT_FILTER_DEFAULT
	 *
	 * Arguments passed:
	 *  * [Url] The url that has been processed
	 * 
	 * Must return:
	 *  * [string] The location to add the visit to
	 *
	 * See $count_filter
	 *
	 * @param bool $output Whether to give output
	 * 
	 * Default: true
	 *
	 * @param int $urls_limit The maximum # of urls to keep in storage
	 *
	 * Default: 1000
	 *
	 * @see Url
	 * @see Crawler::$process_url_condition
	 * @see Crawler::URL_FILTER_DEFAULT
	 * @see Crawler::$url_filter
	 * @see Crawler::$gem_filter
	 * @see Crawler::COUNT_FILTER_DEFAULT
	 * @see Crawler::$count_filter
	 */
	public function __construct(
		CrawlerDbInterface $db,
		int $target = 100,
		int $get_url_limit = 10,
		callable $process_url_condition = null,
		callable $url_filter = self::URL_FILTER_DEFAULT,
		callable $gem_filter = null,
		callable $count_filter = self::COUNT_FILTER_DEFAULT,
		bool $output = true,
		int $urls_limit = 1000
	) {
		$this->db = $db;
		$this->target = $target;
		$this->get_url_limit = $get_url_limit;
		$this->process_url_condition = $process_url_condition;
		$this->url_filter = $url_filter;
		$this->gem_filter = $gem_filter;
		$this->count_filter = $count_filter;
		$this->give_output = $output;
		$this->urls_limit = $urls_limit;
	}

	/**
	 * Crawl!
	 * 
	 * Crawls until {@see Crawler::$target} is reached or if storage runs out of
	 * urls.
	 */
	public function go() {
		while (true) {
			if (false === $this->next()) {
				break;
			}
		}
	}

	/**
	 * Crawl the next batch of max. Crawler::$get_url_limit urls
	 * 
	 * @return bool Return false if the target has been reached or there are no
	 *   more urls in storage, true if the batch is finished
	 */
	protected function next() {

		$urls = $this->db->getUrls($this->get_url_limit);
		$this->db->removeUrls($this->get_url_limit);

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

			if (!$this->isHtmlContent($url)) {
				continue;
			}

			$this->db->addCount(call_user_func($this->count_filter, $url));

			$this->getContent($url);
			$this->n_urls_visited++;

			if ($this->db->getUrlsCount() <= $this->urls_limit) {
				$this->addUrls($url);
			}

			if($this->gem_filter) {
				$this->addGems($url);
			}
		}

		return true;
	}

	/**
	 * Check the mime type of the content at $url
	 */
	protected function isHtmlContent(Url $url) {
		$headers = $this->getHeaders($url);
		return 1 === preg_match(
			'#[ \t]*content-type:[ \t]*(?:text/html|application/xhtml+xml)#i',
			$headers
		);
	}

	/**
	 * Get the reply to a HEAD request to $url
	 * 
	 * @param Url $url The url to visit
	 */
	protected function getHeaders(Url $url) {
		$ch = curl_init(); // initialize curl handle
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,7);
		curl_setopt($ch, CURLOPT_URL,$url->getRaw()); // set url to request
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 29); // times out after 50s
		curl_setopt($ch, CURLOPT_POST, 0); // set POST method
		return curl_exec($ch); // run the whole process
	}

	/**
	 * Get the content at $url
	 * 
	 * @param Url $url The location of the content to get
	 */
	protected function getContent(Url $url) {
		$ch = curl_init(); // initialize curl handle
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,7);
		curl_setopt($ch, CURLOPT_URL,$url->getRaw()); // set url to post to
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt($ch, CURLOPT_TIMEOUT, 29); // times out after 50s
		curl_setopt($ch, CURLOPT_POST, 0); // set POST method
		$this->content_buffer = curl_exec($ch); // run the whole process
		curl_close($ch);
	}

	/**
	 * Write output
	 * 
	 * @param string $str The string to write
	 */
	protected function output($str) {
		if ($this->give_output) {
			echo $str . "\n";
		}
	}

	/**
	 * Add the urls in the content of the currently visited url to storage
	 * 
	 * Filtered by {@see Crawler::$url_filter}()
	 */
	protected function addUrls(Url $source) {
		$new_urls = array_filter($this->getUrls(),
			function ($new_url) use ($source) {
				return call_user_func($this->url_filter, $new_url, $source);
			}
		);
		$absolute_new_urls = array_map(
			function ($url) use ($source) {
				return self::urlToAbsolute($url, $source);
			},
			$new_urls
		);
		if ($absolute_new_urls && $this->db->addUrls($absolute_new_urls)) {
			$this->output(count($absolute_new_urls) . ' urls added.');
		}
	}

	/**
	 * Get the urls in the content at the currently visited url
	 *
	 * @return string[]
	 */
	protected function getUrls() {
		preg_match_all(
			'/href=[\'"]([^\s()<>\'"]+)[\'"]/i',
			$this->content_buffer,
			$result
		);
		return array_unique($result[1]);
	}

	/**
	 * Add the gems to storage
	 * 
	 * The gems added are those returned by {@see Crawler::$gem_filter}()
	 *
	 * @param Url $url The currently visited url
	 * @return bool|null Returns true if there are no gems to add or if gems
	 *   were added successfully, false if gems were not added successfully.
	 */
	protected function addGems(Url $url) {
		if (!($gems = call_user_func(
			$this->gem_filter, $this->content_buffer)
		)) {
			return true;
		}
		return $this->db->addGems($url, $gems);
	}

	/**
	 * Tests whether $url ends in ".html"
	 * 
	 * @param string $url The string to test
	 * @return bool true if test succeeds, false otherwise
	 */
	protected static function getDotHtmlUrlFilter(string $url) {
		return preg_match('/\.html$/i', $url);
	}

	/**
	 * Gets the domain name of $url
	 * @param Url $url The url to get the domain of
	 * @return string The domain name
	 */
	protected static function getDomain(Url $url) {
		return $url->getDomain()->getRaw();
	}

	private static function urlToAbsolute($url, $source) {

		// strip from # onwards
		$hash_pos = strpos($url, '#');
		$hash_stripped = false !== $hash_pos
			? substr($url, 0, $hash_pos)
			: $url;

		// if url starts with "<protocol>:"
		if (preg_match('#^[\w\d]+:#i', $hash_stripped)) {
			return $hash_stripped;
		}

		// if url starts with "//"
		if (0 === strpos($hash_stripped, '//')) {
			return 'https:' . $hash_stripped;
		}

		$source_domain = $source->getDomain()->getRaw();

		// if url starts with "/"
		if (0 === strpos($hash_stripped, '/')) {
			return 'https://' . $source_domain . $hash_stripped;
		}

		// url is relative
		return $source->getRelativePrefix() . $hash_stripped;
	}
}
