<?php

require_once __DIR__ . '/MysqlCrawlerDb.php';

class PriceCrawlerDb extends MysqlCrawlerDb {

	/*
	 * overrides MysqlCrawlerDb::addGems()
	 */
	public function addGems(Url $url, $json, $json_encode = false) {

		if (!($gems = json_decode($json, true))) {
			return null;
		}

		$psql = 'INSERT INTO ' . static::GEMS_NAME;
		$psql .= ' (url, naam, brouwer, prijs, beschikbaarheid, ean)';
		$psql .= ' VALUES (?, ?, ?, ?, ?, ?)';

		if (!($stmt = $this->conn->prepare($psql))) {
			throw new Exception('Prep. addGems() query failed');
		}
		$stmt->bind_param('ssssss',
			$url_p,
			$naam_p,
			$brouwer_p,
			$prijs_p,
			$beschikbaarheid_p,
			$ean_p
		);
		$url_p = $url->getRaw();
		$naam_p = $gems['name'] ?? null;
		$brouwer_p = $gems['brand'] ?? null;
		$prijs_p = $gems['offers']['price'] ?? null;
		$beschikbaarheid_p = $gems['offers']['availability'] ?? null;
		$ean_p = $gems['gtin13'] ?? null;

		return $stmt->execute();
	}
}
