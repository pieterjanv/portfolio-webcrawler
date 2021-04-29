<?php

define('TARGET', 5); // Maximaal # te scrapen urls
define('GET_URL_LIMIT', 10); // Aantal urls in 1x op te halen uit db
define('SEED', 'https://www.hopt.nl/flessen/82-karmeliet-triple.html');

define('DBNAME', 'price_crawler');
define('SERVERNAME', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');

require_once 'modules/PriceCrawler.php';

$crawler = new PriceCrawler(
	TARGET,
	GET_URL_LIMIT,
	['PriceCrawler', 'isUnvisited'], // process url condition
	['PriceCrawler', 'isInternalUrl'], // url filter
	['PriceCrawler', 'getPriceInfo'], // gems filter
	['PriceCrawler', 'fullUrl'], // count filter
	100000, // max. op te slaan urls
	SEED,
	false, // give output
	DBNAME,
	SERVERNAME,
	USERNAME,
	PASSWORD
);

$crawler->go();
