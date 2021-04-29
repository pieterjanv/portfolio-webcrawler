<?php

define('TARGET', 5);
define('GET_URL_LIMIT', 10);
define('COUNT_URL_LIMIT', 10);

define('SERVERNAME', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DBNAME', 'crawler_v1');

require_once 'modules/Test_dbCrawler.php';
require_once 'modules/dbs/MysqlCrawlerDb.php';
require_once 'modules/dbs/RedisCrawlerDb.php';

$mysql = new MysqlCrawlerDb(SERVERNAME, USERNAME, PASSWORD, DBNAME);
$redis = new RedisCrawlerDb();

$mysql_test_crawler = new Test_dbCrawler($mysql, TARGET, GET_URL_LIMIT, 'is_below_COUNT_URL_LIMIT');
$redis_test_crawler = new Test_dbCrawler($redis, TARGET, GET_URL_LIMIT, 'is_below_COUNT_URL_LIMIT');

echo 'Running mysql crawler...' . "\n";
$mysql_test_crawler->go();

echo 'Running redis crawler...' . "\n";
$redis_test_crawler->go();

// callback voor parameter $process_url_condition van Test_dbCrawler constructor
function is_below_COUNT_URL_LIMIT(Url $url, CrawlerDbInterface $db) {
	$domain = $url->getDomain()->getRaw();
	return $db->getCount($domain) < COUNT_URL_LIMIT;
}
