<?php
/**
 * Defines InvalidUrlException class
 * 
 * @author Pieter Jan <pj.visser@ictmaatwerk.com>
 */

/**
 * @used-by Url::__construct()
 */
class InvalidUrlException extends Exception {

	/**
	 * @param string $message The exception message to throw
	 */
	public function __construct($message) {
		parent::__construct($message);
	}
}
