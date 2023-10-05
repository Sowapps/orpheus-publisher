<?php
/**
 * TransactionOperationSet
 */

namespace Orpheus\Publisher\Transaction;

use ArrayIterator;
use IteratorAggregate;
use Orpheus\SqlAdapter\AbstractSqlAdapter;
use Traversable;

/**
 * The Transaction Object Set class
 *
 * This class is about a transaction with multiple operation for an adapter
 *
 * @author Florent Hazard <contact@sowapps.com>
 *
 */
class TransactionOperationSet implements IteratorAggregate {
	
	/**
	 * List of operation in this set
	 *
	 * @var TransactionOperation[] $operations
	 */
	protected array $operations = [];
	
	/**
	 * The SQL Adapter to use
	 *
	 * @var AbstractSqlAdapter $sqlAdapter
	 */
	protected AbstractSqlAdapter $sqlAdapter;
	
	/**
	 * Constructor
	 */
	public function __construct(AbstractSqlAdapter $sqlAdapter) {
		$this->sqlAdapter = $sqlAdapter;
	}
	
	/**
	 * Add an operation to this set
	 */
	public function add(TransactionOperation $operation): void {
		$this->operations[] = $operation;
	}
	
	/**
	 * Get the SQL Adapter
	 */
	public function getSqlAdapter(): AbstractSqlAdapter {
		return $this->sqlAdapter;
	}
	
	/**
	 * Try to apply operations
	 */
	public function save(): void {
		if( !$this->operations ) {
			return;
		}
		// Validate all operations before saving it
		$this->validateOperations();
		// Then operations are valid, so we save it
		$this->runOperations();
	}
	
	/**
	 * Validate operations, before applying
	 */
	protected function validateOperations(): void {
		$errors = 0;
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->validate($errors);
		}
	}
	
	/**
	 * Run operation, these will be applied into DBMS
	 */
	protected function runOperations(): void {
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->runIfValid();
		}
	}
	
	public function getIterator(): Traversable {
		return new ArrayIterator($this->operations);
	}
	
}
