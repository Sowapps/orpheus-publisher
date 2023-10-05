<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Exception\UserException;

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
	
	/**
	 *
	 * {@inheritDoc}
	 * @see TransactionOperation::validate()
	 */
	public function validate(int &$errors = 0): void {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		
		$newErrors = 0;
		
		try {
			$this->data = $class::checkUserInput($this->data, $this->fields, null, $newErrors);
			$class::onValidEdit($this->data, null, $newErrors);
			$class::onValidCreate($this->data, $newErrors);
		} catch( UserException $exception ) {
			reportError($exception);
			$newErrors++;
		}
		
		$this->setIsValid(!$newErrors);
		$errors += $newErrors;
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
