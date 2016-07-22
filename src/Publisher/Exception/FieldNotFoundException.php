<?php

namespace Orpheus\Publisher\Exception;

/**
 * The field not found exception class
 * 
 * This exception is thrown when a field is not found in a set.
 */
class FieldNotFoundException extends \Exception {
	
	/**
	 * The field name
	 * 
	 * @var string
	 */
	protected $fieldname;
	
	/**
	 * The source of the exception
	 * 
	 * @var string
	 */
	protected $source;
	
	/**
	 * Constructor
	 * 
	 * @param string $fieldname The name of the missing field.
	 * @param string $source The source of the exception, optional. Default value is null.
	 */
	public function __construct($fieldname, $source=null) {
		parent::__construct('fieldNotFound['.(isset($source) ? $source.'-' : '').$fieldname.']', 1001);
		$this->fieldname	= (string) $fieldname;
		$this->source		= (string) $source;
	}
	
	/**
	 * Get the field name
	 * 
	 * @return string The field name.
	 */
	public function getFieldName() {
		return $this->fieldname;
	}
	
	/**
	 * Get the source
	 * 
	 * @return string The source of the exception.
	 */
	public function getSource() {
		return $this->source;
	}
}
