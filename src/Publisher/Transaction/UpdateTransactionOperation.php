<?php
/**
 * UpdateTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Publisher\Validation\Validation;

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
	
	public function validate(): Validation {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		$validation = new Validation();
		
		$this->data = $class::validateInput($validation, $this->data, $this->fields, $this->entity);
			// Moved to checkUserInput
//			if( !$this->data ) {
//				// No data to update, we can not process update
//				$class::throwException('update.noChange');
//			}
		if( $validation->isValid() ) {
			$class::onValidEdit($this->data, $this->entity, $validation);
		}
		if( $validation->isValid() ) {
			$class::onValidUpdate($this->data, $validation);
		}
		
		return $validation;
	}
	
	public function run(): bool {
		/** @var class-string<PermanentEntity> $class */
		$class = $this->class;
		$input = $this->data;
		$class::onEdit($input, null);
		$query = $class::requestUpdate()
			->fields($input)
			->where('id', '=', $this->entity->id());
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
