<?php
/**
 * TransactionOperationSet
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\SqlAdapter\SqlAdapter;

/**
 * The Transaction Object Set class
 *
 * This class is about a transaction with multiple operation for an adapter
 *
 * @author Florent Hazard <contact@sowapps.com>
 *
 */
class TransactionOperationSet implements \IteratorAggregate {
	
	/**
	 * List of operation in this set
	 *
	 * @var TransactionOperation[] $operations
	 */
	protected $operations = [];
	
	/**
	 * The SQL Adapter to use
	 *
	 * @var SqlAdapter $sqlAdapter
	 */
	protected $sqlAdapter;
	
	/**
	 * Constructor
	 *
	 * @param SqlAdapter $sqlAdapter
	 */
	public function __construct(SqlAdapter $sqlAdapter) {
		$this->sqlAdapter = $sqlAdapter;
	}
	
	/**
	 * Add an operation to this set
	 *
	 * @param TransactionOperation $operation
	 */
	public function add(TransactionOperation $operation) {
		$this->operations[] = $operation;
	}
	
	/**
	 * Get the SQL Adapter
	 *
	 * @return \Orpheus\SqlAdapter\SqlAdapter
	 */
	public function getSqlAdapter() {
		return $this->sqlAdapter;
	}
	
	/**
	 * Try to apply operations
	 */
	public function save() {
		if( !$this->operations ) {
			return;
		}
		// Validate all operations before saving it
		$this->validateOperations();
		// Then operations are valids, so we save it
		$this->runOperations();
	}
	
	/**
	 * Validate operations, before applying
	 */
	protected function validateOperations() {
		$errors = 0;
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->validate($errors);
		}
	}
	
	/**
	 * Run operation, these will be applied into DBMS
	 */
	protected function runOperations() {
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->runIfValid();
		}
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see IteratorAggregate::getIterator()
	 */
	public function getIterator() {
		return new \ArrayIterator($this->operations);
	}
	
}
