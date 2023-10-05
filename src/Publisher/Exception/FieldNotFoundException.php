<?php
/**
 * FieldNotFoundException
 */

namespace Orpheus\Publisher\Exception;

use RuntimeException;

/**
 * The field not found exception class
 *
 * This exception is thrown when a field is not found in a set.
 */
class FieldNotFoundException extends RuntimeException {
	
	/**
	 * The field name
	 *
	 * @var string
	 */
	protected string $fieldName;
	
	/**
	 * The source of the exception
	 *
	 * @var string|null
	 */
	protected ?string $source;
	
	/**
	 * Constructor
	 *
	 * @param string $fieldName The name of the missing field.
	 * @param string|null $source The source of the exception, optional. Default value is null.
	 */
	public function __construct(string $fieldName, ?string $source = null) {
		parent::__construct('fieldNotFound[' . (isset($source) ? $source . '-' : '') . $fieldName . ']', 1001);
		$this->fieldName = $fieldName;
		$this->source = $source;
	}
	
	/**
	 * Get the field name
	 *
	 * @return string The field name.
	 */
	public function getFieldName(): string {
		return $this->fieldName;
	}
	
	/**
	 * Get the source
	 *
	 * @return string|null The source of the exception.
	 */
	public function getSource(): ?string {
		return $this->source;
	}
}
