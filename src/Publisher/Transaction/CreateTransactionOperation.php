<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Exception\UserException;
use Orpheus\Publisher\Validation\Validation;

/**
 * The CreateTransactionOperation class
 *
 * Transaction operation to create objects into DBMS
 *
 * @author Florent Hazard <contact@sowapps.com>
 */
class CreateTransactionOperation extends TransactionOperation {
	
	/**
	 * The data to insert
	 *
	 * @var array
	 */
	protected array $data;
	
	/**
	 * Fields to restrict creation
	 *
	 * @var string[]
	 */
	protected ?array $fields;
	
	/**
	 * The resulting ID after inserted data
	 *
	 * @var string|null
	 */
	protected ?string $insertId = null;
	
	public function __construct(string $class, array $data, ?array $fields) {
		parent::__construct($class);
		$this->data = $data;
		$this->fields = $fields;
	}
	
	public function validate(): Validation {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		
		$validation = new Validation();
		
		$this->data = $class::validateInput($validation, $this->data, $this->fields);
		if($validation->isValid()) {
			$class::onValidEdit($this->data, null, $validation);
		}
		if($validation->isValid()) {
			$class::onValidCreate($this->data, $validation);
		}
		
		return $validation;
	}
	
	public function run(): string|false {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		$input = $this->data;
		$class::onEdit($input, null);
		$query = $class::requestInsert()->fields($input);
		$result = $query->run();
		
		if( $result ) {
			// Success
			$this->insertId = $query->getLastId();
			$class::onSaved($this->data, $this->insertId);
			
			return $this->insertId;
		}
		
		return false;
	}
	
	/**
	 * Get the last inserted data's id
	 */
	public function getInsertId(): ?string {
		return $this->insertId;
	}
	
}
