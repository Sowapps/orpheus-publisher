<?php
/**
 * DeleteTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Publisher\Validation\Validation;

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
	
	public function validate(): Validation {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		
		$validation = new Validation();
		
		if( $this->object->isDeleted() ) {
			$validation->addError('alreadyDeleted', $class::getDomain());
		}
		
		return $validation;
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
