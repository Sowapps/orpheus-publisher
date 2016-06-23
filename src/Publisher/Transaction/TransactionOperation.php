<?php

namespace Orpheus\Publisher\Transaction;

use Orpheus\SQLAdapter\SQLAdapter;

abstract class TransactionOperation {
	
	protected $class;
	/**
	 * @var TransactionOperationSet $transactionOperationSet
	 */
	protected $transactionOperationSet;

	/**
	 * @var SQLAdapter $sqlAdapter
	 */
	protected $sqlAdapter;
	
	protected $isValid;
	
	public function __construct($class) {
		$this->class	= $class;
	}
	
	public function isValid() {
		return $this->isValid;
	}
	
	protected function setIsValid($valid) {
		$this->isValid	= $valid;
	}
	
	protected function setValid() {
		$this->setIsValid(true);
	}
	
	protected function setInvalid() {
		$this->setIsValid(false);
	}
	
	public abstract function validate(&$errors);
	
	public abstract function run();
	
	public function runIfValid() {
		return $this->isValid ? $this->run() : 0;
// 		return $this->isValid ? $this->run() : null;
	}
	
	public function getSQLAdapter() {
		return $this->sqlAdapter ? $this->sqlAdapter :
			($this->transactionOperationSet ? $this->transactionOperationSet->getSQLAdapter() : null);
	}
	
	public function setSQLAdapter(SQLAdapter $sqlAdapter) {
		$this->sqlAdapter = $sqlAdapter;
		return $this;
	}
	
	public function getTransactionOperationSet() {
		return $this->transactionOperationSet;
	}
	
	public function setTransactionOperationSet(TransactionOperationSet $transactionOperationSet) {
		$this->transactionOperationSet = $transactionOperationSet;
		return $this;
	}
	
}
