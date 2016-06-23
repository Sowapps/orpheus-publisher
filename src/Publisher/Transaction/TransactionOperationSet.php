<?php

namespace Orpheus\Publisher\Transaction;

use Orpheus\SQLAdapter\SQLAdapter;
use Orpheus\Publisher\PermanentObject\PermanentObject;

/** The Transaction Object Set class

	This class is about a transaction with multiple operation for an adapter
 */
class TransactionOperationSet implements \IteratorAggregate {

	/**
	 * @var TransactionOperation[] $operations
	 */
	protected $operations	= array();
	/**
	 * @var SQLAdapter $sqlAdapter
	 */
	protected $sqlAdapter;
	
	public function __construct(SQLAdapter $sqlAdapter) {
		$this->sqlAdapter	= $sqlAdapter;
	}
	
	public function add(PermanentObject $operation) {
		$this->operations[] = $operation;
	}
	
	public function getSQLAdapter() {
		return $this->sqlAdapter;
	}
	
	public function save() {
		if( !$this->operations ) {
			return;
		}
		// Validate all operations before saving it
		$this->validateOperations();
		// Then operations are valids, so we save it
		$this->runOperations();
	}
	
	protected function validateOperations() {
		$errors	= 0;
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->validate($errors);
		}
	}
	
	protected function runOperations() {
		foreach( $this->operations as $operation ) {
			$operation->setTransactionOperationSet($this);
			$operation->runIfValid();
		}
	}
	
	public function getIterator() {
		return new \ArrayIterator($this->operations);
	}
	
}
