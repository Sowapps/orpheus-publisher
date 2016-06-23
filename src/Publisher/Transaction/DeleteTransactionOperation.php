<?php

namespace Orpheus\Publisher\Transaction;

use Orpheus\Publisher\PermanentObject\PermanentObject;

class DeleteTransactionOperation extends TransactionOperation {

	protected $object;

	public function __construct($class, PermanentObject $object) {
		parent::__construct($class);
		$this->object	= $object;
	}
	
	public function validate(&$errors) {
		
		$this->setIsValid(!$this->object->isDeleted());
		
	}
	
	public function run() {
		// Testing generating query in this class
		$class = $this->class;
		
		$options	= array(
			'table'		=> $class::getTable(),
			'where'		=> $class::getIDField().'='.$this->object->id(),
			'number'	=> 1,
		);
		
		$sqlAdapter	= $this->getSQLAdapter();
		
		$r = $sqlAdapter->delete($options);
// 		$r = SQLAdapter::doDelete($options, static::$DBInstance, static::$IDFIELD);
		if( $r ) {
			// Success
			$this->object->markAsDeleted();
			return 1;
// 			static::runForDeletion($in);
		}
		return 0;
		
	}
}
