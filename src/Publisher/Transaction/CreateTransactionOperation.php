<?php

namespace Orpheus\Publisher\Transaction;

class CreateTransactionOperation extends TransactionOperation {

	protected $data;
	protected $fields;
	
	protected $insertID;
	
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
		// TODO Developer and use an SQLCreateRequest class
		$class = $this->class;
		$queryOptions = $class::extractCreateQuery($this->data);

// 		$sqlAdapter	= $this->getTransactionOperationSet()->getSQLAdapter();
		$sqlAdapter	= $this->getSQLAdapter();
		
		$r = $sqlAdapter->insert($queryOptions);
		
		if( $r ) {
			$this->insertID = $sqlAdapter->lastID($queryOptions['table']);
			
			$class::onSaved($this->data, $this->insertID);
			
			return $this->insertID;
		}
		return 0;
		
// 		SQLAdapter::doInsert($options, static::$DBInstance, static::$IDFIELD);
// 		$LastInsert	= SQLAdapter::doLastID(static::$table, static::$IDFIELD, static::$DBInstance);
		// To do after insertion
// 		static::applyToObject($data, $LastInsert);
// 		static::onSaved($data, $LastInsert);
	}
	
	public function getInsertID() {
		return $this->insertID;
	}
}
