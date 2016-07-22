<?php

namespace Orpheus\Publisher\Exception;

/**
 * The unknown key exception class
 * 
 * This exception is thrown when a required key is not found
*/
class UnknownKeyException extends \Exception {
	
	protected $key;
	
	/**
	 * Constructor
	 * 
	 * @param string $message The message.
	 * @param string $key The unknown key.
	 */
	public function __construct($message, $key) {
		parent::__construct($message, 1002);
		$this->key = (string) $key;
	}
	
	/**
	 * Get the unknown key
	 * 
	 * @return string The key.
	 */
	public function getKey() {
		return $this->key;
	}
}
