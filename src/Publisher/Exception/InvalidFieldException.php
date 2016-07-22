<?php

namespace Orpheus\Publisher\Exception;

use Orpheus\Exception\UserException;

/**
 * The invalid field exception class
 * 
 * This exception is thrown when we try to validate a form field and it's invalid.
 */
class InvalidFieldException extends UserException {
	
	/**
	 * The message key
	 * 
	 * @var string
	 */
	protected $key;
	
	/**
	 * The type
	 * 
	 * @var string
	 */
	protected $type;
	
	/**
	 * The input field name
	 * 
	 * @var string
	 */
	protected $field;
	
	/**
	 * The value that is not valid
	 * 
	 * @var string
	 */
	protected $value;
	
	/**
	 * The arguments of this check
	 * 
	 * @var array
	 */
	protected $args;
	
	/**
	 * Constructor
	 * 
	 * @param string $key
	 * @param string $field
	 * @param string $value
	 * @param string $type
	 * @param string $domain
	 * @param array $typeArgs
	 */
	public function __construct($key, $field, $value, $type=null, $domain=null, $typeArgs=array()) {
		parent::__construct($key.'_'.$field, $domain);
		$this->key		= $key;
		$this->field	= $field;
		$this->type		= $type;
		$this->value	= $value;
		$this->args		= is_array($typeArgs) ? $typeArgs : (is_object($typeArgs) ? (array) $typeArgs : array($typeArgs));
	}
	
	/**
	 * Get the field
	 * 
	 * @return string
	 */
	public function getField() {
		return $this->field;
	}
	
	/**
	 * Get the type of the field
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Get the field's value that is not valid
	 * @return string
	 */
	public function getValue() {
		
		return $this->value;
	}
	
	/**
	 * Remove args from this exception, this is required for some tests (generating possible errors)
	 */
	public function removeArgs() {
		$this->args	= array();
	}
	
	/**
	 * Get the field's arguments
	 * 
	 * @return array
	 */
	public function getArgs() {
		return $this->args;
	}
	
	/**
	 * Get the user's message
	 * 
	 * @return string The translated message from this exception
	 */
	public function getText() {
		$args	= $this->args;
		$msg	= $this->getMessage();
// 		$msg	= $this->field.'_'.$this->getMessage();
// 		if( !hasTranslation($msg, $this->domain) ) {
// 			if( hasTranslation($this->getMessage().'_field', $this->domain) ) {
// 				$msg	= $this->getMessage().'_field';
// 				$args	= array_merge(array('FIELD'=>t($this->getField(), $this->domain)), $args);
// 			} else
// 			if( hasTranslation($this->getMessage(), $this->domain) ) {
// 				$msg	= $this->getMessage();
// 			}
// 		}
		return t($msg, $this->domain, $args);
	}
	
	/**
	 * Get the key for this field and message
	 * 
	 * @return string
	 */
	public function getKey() {
		return $this->field.'_'.$this->getMessage();
	}
	
	/**
	 * Get the report from this exception
	 * 
	 * @return string
	 */
	public function getReport() {
		return array(static::getText(), $this->field);
	}
	
	/**
	 * Convert an UserException into an InvalidFieldException using other parameters
	 * 
	 * @param UserException $e
	 * @param string $field
	 * @param string $value
	 * @param string $type
	 * @param array $args
	 * @return InvalidFieldException
	 */
	public static function from(UserException $e, $field, $value, $type=null, $args=array()) {
		return new static($e->getMessage(), $field, $value, $type, $e->getDomain(), $args);
	}
}