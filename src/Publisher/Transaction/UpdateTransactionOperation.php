<?php

namespace Orpheus\Publisher\Transaction;

use Orpheus\Publisher\PermanentObject\PermanentObject;

class UpdateTransactionOperation extends TransactionOperation {

	protected $data;
	protected $fields;
	protected $object;

	public function __construct($class, array $data, $fields, PermanentObject $object) {
		parent::__construct($class);
		$this->data		= $data;
		$this->fields	= $fields;
		$this->object	= $object;
		
// 		debug('UpdateTransactionOperation - $this->data', $this->data);
	}
	
	public function validate(&$errors=0) {
		$class = $this->class;
		$newErrors = 0;
		
// 		debug('validate() - $this->data before check', $this->data);
		$this->data = $class::checkUserInput($this->data, $this->fields, $this->object, $newErrors);
// 		debug('validate() - $this->data after check ['.$newErrors.']', $this->data);
	
		$this->setIsValid($class::onValidUpdate($this->data, $newErrors));
		
		$errors	+= $newErrors;
	}
	
	public function run() {
		// TODO Developer and use an SQLUpdateRequest class
		$class = $this->class;
		$queryOptions = $class::extractUpdateQuery($this->data, $this->object);

		$sqlAdapter	= $this->getSQLAdapter();
	
		$r = $sqlAdapter->update($queryOptions);
		if( $r ) {
			// Success
			$this->object->reload();
			$class::onSaved($this->data, $this);
			return 1;
// 			static::runForDeletion($in);
		}
		return 0;
	
// 		static::onSaved(array_filterbykeys($this->all, $modFields), $this);
// 		$class::onSaved($this->data, $this->insertID);
	}
}
