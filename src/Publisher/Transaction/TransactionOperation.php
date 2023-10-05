<?php
/**
 * @author Florent Hazard <contact@sowapps.com>
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\SqlAdapter\AbstractSqlAdapter;

abstract class TransactionOperation {
	
	/**
	 * The class of this operation
	 *
	 * @var class-string<PermanentEntity>
	 */
	protected string $class;
	
	/**
	 * The transaction set
	 *
	 * @var TransactionOperationSet|null $transactionOperationSet
	 */
	protected ?TransactionOperationSet $transactionOperationSet;
	
	/**
	 * The SQL Adapter
	 *
	 * @var AbstractSqlAdapter|null $sqlAdapter
	 */
	protected ?AbstractSqlAdapter $sqlAdapter = null;
	
	/**
	 * If this Operation is valid
	 *
	 * @var boolean
	 */
	protected bool $isValid = false;
	
	/**
	 * Constructor
	 */
	public function __construct(string $class) {
		$this->class = $class;
	}
	
	/**
	 * Run this operation
	 */
	public abstract function run(): mixed;
	
	/**
	 * Run this operation only if valid
	 */
	public function runIfValid(): mixed {
		return $this->isValid ? $this->run() : false;
	}
	
	/**
	 * If this operation is valid
	 */
	public function isValid(): bool {
		return $this->isValid;
	}
	
	/**
	 * Set this operation validity
	 */
	protected function setIsValid(bool $valid): static {
		$this->isValid = $valid;
		
		return $this;
	}
	
	/**
	 * Validate this operation
	 */
	public abstract function validate(int &$errors = 0);
	
	/**
	 * Get the SQL Adapter
	 */
	public function getSqlAdapter(): ?AbstractSqlAdapter {
		return $this->sqlAdapter ?: $this->transactionOperationSet?->getSqlAdapter();
	}
	
	/**
	 * Set the SQL Adapter
	 */
	public function setSqlAdapter(AbstractSqlAdapter $sqlAdapter): static {
		$this->sqlAdapter = $sqlAdapter;
		
		return $this;
	}
	
	/**
	 * Get the TransactionOperationSet
	 */
	public function getTransactionOperationSet(): TransactionOperationSet {
		return $this->transactionOperationSet;
	}
	
	/**
	 * Set the TransactionOperationSet
	 */
	public function setTransactionOperationSet(TransactionOperationSet $transactionOperationSet): static {
		$this->transactionOperationSet = $transactionOperationSet;
		
		return $this;
	}
	
}
