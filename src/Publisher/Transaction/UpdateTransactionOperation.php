<?php
/**
 * UpdateTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Exception\UserException;

/**
 * The UpdateTransactionOperation class
 *
 * Transaction operation to update objects in DBMS
 *
 * @author Florent Hazard <contact@sowapps.com>
 */
class UpdateTransactionOperation extends TransactionOperation {
	
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
	protected array $fields;
	
	/**
	 * The object of this operation
	 *
	 * @var PermanentEntity
	 */
	protected PermanentEntity $entity;
	
	/**
	 * Constructor
	 *
	 * @param string[] $fields
	 */
	public function __construct(string $class, array $data, array $fields, PermanentEntity $entity) {
		parent::__construct($class);
		$this->data = $data;
		$this->fields = $fields;
		$this->entity = $entity;
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
			$this->data = $class::checkUserInput($this->data, $this->fields, $this->entity, $newErrors);
			// Moved to checkUserInput
//			if( !$this->data ) {
//				// No data to update, we can not process update
//				$class::throwException('update.noChange');
//			}
			$class::onValidEdit($this->data, $this->entity, $newErrors);
			$class::onValidUpdate($this->data, $newErrors);
		} catch( UserException $exception ) {
			reportError($exception);
			$newErrors++;
		}
		
		$this->setIsValid(!$newErrors);
		$errors += $newErrors;
	}
	
	public function run(): bool {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		$input = $this->data;
		$class::onEdit($input, null);
		$query = $class::requestUpdate()->fields($input);
		$r = $query->run();
		if( $r ) {
			// Success
			$this->entity->reload();
			$class::onSaved($this->data, $this->entity);
			
			return true;
		}
		
		return false;
	}
	
}
