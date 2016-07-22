<?php

namespace Orpheus\Publisher\Transaction;

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
		$this->data		= $data;
		$this->fields	= $fields;
	}
	
	public function validate(&$errors) {
		$class = $this->class;
// 		$class::checkUserInput($input, $fields, $this, $errCount);
		$newErrors = 0;
		$this->data = $class::checkUserInput($this->data, $this->fields, null, $newErrors);
		
		$class::onValidCreate($this->data, $newErrors);
		
		$errors	+= $newErrors;
		
		$this->setValid();
	}
	
	public function run() {
		// TODO : Use an SQLCreateRequest class
		$class = $this->class;
		$queryOptions = $class::extractCreateQuery($this->data);

		$sqlAdapter	= $this->getSQLAdapter();
		
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
