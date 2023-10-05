<?php
/**
 * DeleteTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;

/**
 * The DeleteTransactionOperation class
 *
 * Transaction operation to delete objects from DBMS
 *
 * @author Florent Hazard <contact@sowapps.com>
 */
class DeleteTransactionOperation extends TransactionOperation {
	
	/**
	 * The object of this operation
	 *
	 * @var PermanentEntity
	 */
	protected PermanentEntity $object;
	
	/**
	 * Constructor
	 */
	public function __construct(string $class, PermanentEntity $object) {
		parent::__construct($class);
		$this->object = $object;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @param array $errors
	 * @see TransactionOperation::validate()
	 */
	public function validate(int &$errors = 0): void {
		$newErrors = 0;
		if( $this->object->isDeleted() ) {
			$newErrors++;
		}
		$this->setIsValid(!$newErrors);
		$errors += $newErrors;
	}
	
	public function run(): bool {
		/** @var PermanentEntity $class */
		$class = $this->class;
		
		$options = [
			'table'  => $class::getTable(),
			'where'  => $class::getIDField() . '=' . $this->object->id(),
			'number' => 1,
		];
		
		$sqlAdapter = $this->getSqlAdapter();
		
		$r = $sqlAdapter->delete($options);
		if( $r ) {
			// Success
			$this->object->markAsDeleted();
			return true;
		}
		return false;
		
	}
}
