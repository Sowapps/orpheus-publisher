<?php
/**
 * @author Florent Hazard <contact@sowapps.com>
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\EntityDescriptor\Entity\PermanentEntity;
use Orpheus\Publisher\Validation\Validation;
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
	 * Validate this operation
	 */
	public abstract function validate(): Validation;
	
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
