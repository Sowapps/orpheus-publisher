<?php
/**
 * TransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\SQLAdapter\SQLAdapter;

abstract class TransactionOperation {
	
	/**
	 * The class of this operation
	 * 
	 * @var string
	 */
	protected $class;
	
	/**
	 * The transaction set
	 * 
	 * @var TransactionOperationSet $transactionOperationSet
	 */
	protected $transactionOperationSet;

	/**
	 * The SQL Adapter
	 * 
	 * @var SQLAdapter $sqlAdapter
	 */
	protected $sqlAdapter;
	
	/**
	 * If this Operation is valid
	 * 
	 * @var boolean
	 */
	protected $isValid;
	
	/**
	 * Constructor
	 * 
	 * @param string $class
	 */
	public function __construct($class) {
		$this->class = $class;
	}
	
	/**
	 * If this operation is valid
	 * 
	 * @return boolean
	 */
	public function isValid() {
		return $this->isValid;
	}
	
	/**
	 * Set this operation validity
	 * 
	 * @param boolean $valid
	 * @return \Orpheus\Publisher\Transaction\TransactionOperation
	 */
	protected function setIsValid($valid) {
		$this->isValid = $valid;
		return $this;
	}
	
	/**
	 * Set this operation as valid
	 * 
	 * @return \Orpheus\Publisher\Transaction\TransactionOperation
	 */
	protected function setValid() {
		$this->setIsValid(true);
		return $this;
	}
	
	/**
	 * Set this operation as invalid
	 * 
	 * @return \Orpheus\Publisher\Transaction\TransactionOperation
	 */
	protected function setInvalid() {
		$this->setIsValid(false);
		return $this;
	}
	
	/**
	 * Validate this operation
	 * 
	 * @param array $errors
	 */
	public abstract function validate(&$errors=0);
	
	/**
	 * Run this operation
	 */
	public abstract function run();
	
	/**
	 * Run this operation only if valid
	 * 
	 * @return mixed
	 */
	public function runIfValid() {
		return $this->isValid ? $this->run() : 0;
// 		return $this->isValid ? $this->run() : null;
	}
	
	/**
	 * Get the SQL Adapter
	 * 
	 * @return \Orpheus\SQLAdapter\SQLAdapter|NULL
	 */
	public function getSQLAdapter() {
		return $this->sqlAdapter ? $this->sqlAdapter :
			($this->transactionOperationSet ? $this->transactionOperationSet->getSQLAdapter() : null);
	}
	
	/**
	 * Set the SQL Adapter
	 * 
	 * @param SQLAdapter $sqlAdapter
	 * @return \Orpheus\Publisher\Transaction\TransactionOperation
	 */
	public function setSQLAdapter(SQLAdapter $sqlAdapter) {
		$this->sqlAdapter = $sqlAdapter;
		return $this;
	}
	
	/**
	 * Get the TransactionOperationSet
	 * 
	 * @return \Orpheus\Publisher\Transaction\TransactionOperationSet
	 */
	public function getTransactionOperationSet() {
		return $this->transactionOperationSet;
	}
	
	/**
	 * Set the TransactionOperationSet
	 * 
	 * @param TransactionOperationSet $transactionOperationSet
	 * @return \Orpheus\Publisher\Transaction\TransactionOperation
	 */
	public function setTransactionOperationSet(TransactionOperationSet $transactionOperationSet) {
		$this->transactionOperationSet = $transactionOperationSet;
		return $this;
	}
	
}
