<?php
/**
 * UnknownKeyException
 */

namespace Orpheus\Publisher\Exception;

use Exception;

/**
 * The unknown key exception class
 * 
 * This exception is thrown when a required key is not found
*/
class UnknownKeyException extends Exception {
	
	/**
	 * The key of success
	 * 
	 * @var string
	 */
	protected string $key;
	
	/**
	 * Constructor
	 * 
	 * @param string $message The message.
	 * @param string $key The unknown key.
	 */
	public function __construct(string $message, string $key) {
		parent::__construct($message, 1002);
		$this->key = $key;
	}
	
	/**
	 * Get the unknown key
	 * 
	 * @return string The key.
	 */
	public function getKey(): string {
		return $this->key;
	}
}
