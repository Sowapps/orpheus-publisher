<?php
/**
 * DeleteTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\Publisher\PermanentObject\PermanentObject;

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
	 * @var PermanentObject
	 */
	protected $object;
	
	/**
	 * Constructor
	 *
	 * @param string $class
	 * @param PermanentObject $object
	 */
	public function __construct($class, PermanentObject $object) {
		parent::__construct($class);
		$this->object = $object;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @param array $errors
	 * @see \Orpheus\Publisher\Transaction\TransactionOperation::validate()
	 */
	public function validate(&$errors = 0) {
		$this->setIsValid(!$this->object->isDeleted());
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Orpheus\Publisher\Transaction\TransactionOperation::run()
	 */
	public function run() {
		// Testing generating query in this class
		$class = $this->class;
		
		$options = [
			'table'  => $class::getTable(),
			'where'  => $class::getIDField() . '=' . $this->object->id(),
			'number' => 1,
		];
		
		$sqlAdapter = $this->getSQLAdapter();
		
		$r = $sqlAdapter->delete($options);
		// 		$r = SQLAdapter::doDelete($options, static::$DBInstance, static::$IDFIELD);
		if( $r ) {
			// Success
			$this->object->markAsDeleted();
			return 1;
			// 			static::runForDeletion($in);
		}
		return 0;
		
	}
}
