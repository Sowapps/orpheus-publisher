<?php
/**
 * UpdateTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\Publisher\PermanentObject\PermanentObject;

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
	protected $data;
	
	/**
	 * Fields to restrict creation
	 *
	 * @var string[]
	 */
	protected $fields;
	
	/**
	 * The object of this operation
	 *
	 * @var PermanentObject
	 */
	protected $object;
	
	/**
	 * Constructor
	 *
	 * @param string $class
	 * @param array $data
	 * @param string[] $fields
	 * @param PermanentObject $object
	 */
	public function __construct($class, array $data, $fields, PermanentObject $object) {
		parent::__construct($class);
		$this->data = $data;
		$this->fields = $fields;
		$this->object = $object;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @param array $errors
	 * @see \Orpheus\Publisher\Transaction\TransactionOperation::validate()
	 */
	public function validate(&$errors = 0) {
		$class = $this->class;
		$newErrors = 0;
		
		$this->data = $class::checkUserInput($this->data, $this->fields, $this->object, $newErrors);
		
		$this->setIsValid($class::onValidUpdate($this->data, $newErrors));
		
		$errors += $newErrors;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Orpheus\Publisher\Transaction\TransactionOperation::run()
	 */
	public function run() {
		// TODO : Use a SQLUpdateRequest class
		$class = $this->class;
		$queryOptions = $class::extractUpdateQuery($this->data, $this->object);
		
		$sqlAdapter = $this->getSQLAdapter();
		
		$r = $sqlAdapter->update($queryOptions);
		if( $r ) {
			// Success
			$this->object->reload();
			$class::onSaved($this->data, $this->object);
			return 1;
		}
		return 0;
	}
}
