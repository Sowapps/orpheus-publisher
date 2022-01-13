<?php
/**
 * CreateTransactionOperation
 */

namespace Orpheus\Publisher\Transaction;

use Orpheus\Publisher\PermanentObject\PermanentObject;

/**
 * The CreateTransactionOperation class
 *
 * Transaction operation to create objects into DBMS
 *
 * @author Florent Hazard <contact@sowapps.com>
 */
class CreateTransactionOperation extends TransactionOperation {
	
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
	 * The resulting ID after inserted data
	 *
	 * @var string
	 */
	protected $insertID;
	
	/**
	 * Constructor
	 *
	 * @param string $class
	 * @param array $data
	 * @param string[] $fields
	 */
	public function __construct($class, array $data, $fields) {
		parent::__construct($class);
		$this->data = $data;
		$this->fields = $fields;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @param array $errors
	 * @see TransactionOperation::validate()
	 */
	public function validate(&$errors = 0) {
		/** @var PermanentObject $class */
		$class = $this->class;
		
		$newErrors = 0;
		$this->data = $class::checkUserInput($this->data, $this->fields, null, $newErrors);
		
		$class::onValidCreate($this->data, $newErrors);
		
		$errors += $newErrors;
		
		$this->setValid();
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see TransactionOperation::run()
	 */
	public function run() {
		// TODO : Use a SqlCreateRequest class
		/** @var PermanentObject $class */
		$class = $this->class;
		$queryOptions = $class::extractCreateQuery($this->data);
		
		$sqlAdapter = $this->getSqlAdapter();
		
		$r = $sqlAdapter->insert($queryOptions);
		
		if( $r ) {
			$this->insertID = $sqlAdapter->lastID($queryOptions['table']);
			
			$class::onSaved($this->data, $this->insertID);
			
			return $this->insertID;
		}
		return 0;
	}
	
	/**
	 * Get the last inserted data's id
	 *
	 * @return string
	 */
	public function getInsertID() {
		return $this->insertID;
	}
}
