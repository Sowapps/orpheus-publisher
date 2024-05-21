<?php
/**
 * InvalidFieldException
 */

namespace Orpheus\Publisher\Exception;

use Orpheus\Exception\UserException;

/**
 * The invalid field exception class
 *
 * This exception is thrown when we try to validate a form field, and it's invalid.
 */
class InvalidFieldException extends UserException {
	
	/**
	 * The message key
	 *
	 * @var string
	 */
	protected string $key;
	
	/**
	 * The type
	 *
	 * @var string|null
	 */
	protected ?string $type;
	
	/**
	 * The input field name
	 *
	 * @var string
	 */
	protected string $field;
	
	/**
	 * The value that is not valid
	 *
	 * @var string|null
	 */
	protected ?string $value;
	
	/**
	 * The arguments of this check
	 *
	 * @var array
	 */
	protected array $args;
	
	/**
	 * Constructor
	 *
	 * @param string|null $domain
	 * @param array|object|scalar $typeArgs
	 */
	public function __construct(string $key, string $field, ?string $value, ?string $type = null, string $domain = null, mixed $typeArgs = []) {
		parent::__construct($field . '_' . $key, $domain);
		$this->key = $key;
		$this->field = $field;
		$this->type = $type;
		$this->value = $value;
		$this->args = is_array($typeArgs) ? $typeArgs : (is_object($typeArgs) ? (array) $typeArgs : [$typeArgs]);
	}
	
	public function getExtraData(): array {
		return [
			'key' => $this->getKey(),
			'field' => $this->getField(),
			'type' => $this->getType(),
			'value' => $this->getValue(),
			'args' => $this->getArgs(),
		];
	}
	
	/**
	 * Get the field
	 */
	public function getField(): string {
		return $this->field;
	}
	
	/**
	 * Get the type of the field
	 */
	public function getType(): ?string {
		return $this->type;
	}
	
	/**
	 * Get the field's value that is not valid
	 */
	public function getValue(): string {
		
		return $this->value;
	}
	
	/**
	 * Remove args from this exception, this is required for some tests (generating possible errors)
	 */
	public function removeArgs(): void {
		$this->args = [];
	}
	
	/**
	 * Get the field's arguments
	 */
	public function getArgs(): array {
		return $this->args;
	}
	
	/**
	 * Get the key for this field and message
	 */
	public function getKey(): string {
		return $this->key;
	}
	
	/**
	 * Get the user's message
	 *
	 * @return string The translated message from this exception
	 */
	public function getText(): string {
		$args = $this->args;
		$msg = $this->getMessage();
		
		return t($msg, $this->domain, $args);
	}
	
	/**
	 * Convert an UserException into an InvalidFieldException using other parameters
	 */
	public static function from(UserException $e, string $field, ?string $value, ?string $type = null, array $args = []): InvalidFieldException {
		return new static($e->getMessage(), $field, $value, $type, $e->getDomain(), $args);
	}
}
