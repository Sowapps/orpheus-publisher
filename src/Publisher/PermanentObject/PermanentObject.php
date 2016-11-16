<?php
/**
 * The Permanent Object class
 *
 * Permanent objects are persisted in DBMS using SQL Adapter
 *
 * @author Florent Hazard <contact@sowapps.com>
 */

namespace Orpheus\Publisher\PermanentObject;

use Orpheus\Exception\UserException;
use Orpheus\Exception\NotFoundException;
use \Exception;
use Orpheus\Publisher\Transaction\UpdateTransactionOperation;
use Orpheus\Publisher\Transaction\DeleteTransactionOperation;
use Orpheus\Publisher\Exception\FieldNotFoundException;
use Orpheus\SQLAdapter\SQLAdapter;
use Orpheus\SQLRequest\SQLRequest;
use Orpheus\Publisher\Transaction\CreateTransactionOperation;
use Orpheus\Publisher\Exception\InvalidFieldException;
use Orpheus\SQLRequest\SQLSelectRequest;


/**
 * The permanent object class
 * 
 * Manage a permanent object using the SQL Adapter.
 */
abstract class PermanentObject {

	/**
	 * The ID field
	 *
	 * @var string
	 */
	protected static $IDFIELD			= 'id';
	
	/**
	 * Cache of all object instances
	 * 
	 * @var array
	 */
	protected static $instances			= array();
	
	/**
	 * The table
	 * 
	 * @var string
	 */
	protected static $table				= null;
	
	/**
	 * DB instance
	 * 
	 * Use only to get SQL Adapter
	 * 
	 * @var string
	 */
	protected static $DBInstance		= null;
	
	/**
	 * The fields of this object
	 * 
	 * @var array
	 */
	protected static $fields			= array();
	
	/**
	 * The validator
	 * The default one is an array system.
	 * 
	 * @var array
	 */
	protected static $validator			= array();
	
	/**
	 * The domain of this class
	 * Used as default for translations.
	 * 
	 * @var string
	 */
	protected static $domain			= null;
	
	/**
	 * Editable fields
	 *
	 * @var array
	 */
	protected static $editableFields	= null;
	
	/**
	 * Currently modified fields
	 * 
	 * @var array
	 */
	protected $modFields	= array();
	
	/**
	 * The object's data
	 * 
	 * @var array
	 */
	protected $data			= array();
	
	/**
	 * Is this object deleted ?
	 * 
	 * @var boolean
	 */
	protected $isDeleted	= false;
	
	/**
	 * Internal static initialization
	 */
	public static function selfInit() {
		static::$fields = array(static::$IDFIELD);
	}
	
	/**
	 * Get the object as array
	 * 
	 * @param array $array
	 * @return array The resulting array
	 */
	public static function set2Array(array $array) {
		foreach( $array as &$value ) {
			$value = $value->getValue();
		}
		return $array;
	}
	
	/**
	 * Insert this object in the given array using its ID as key
	 * 
	 * @param array $array
	 */
	public function setTo(array &$array) {
		$array[$this->id()]	= $this;
	}
	
	// *** OVERRIDDEN METHODS ***
	
	/**
	 * Constructor
	 * 
	 * @param $data An array of the object's data to construct
	 */
	public function __construct(array $data) {
		foreach( static::$fields as $fieldname ) {
			// We condiser null as a valid value.
			$fieldValue = null;
			if( !array_key_exists($fieldname, $data) ) {
				// Data not found but should be, this object is out of date
// 				$this->reload();// Dont reload here
				// Data not in DB, this class is invalid
// 				if( ENTITY_CLASS_CHECK && !array_key_exists($fieldname, $data) ) {
				if( ENTITY_CLASS_CHECK ) {
					throw new Exception('The class '.static::getClass().' is out of date, the field "'.$fieldname.'" is unknown in database.');
// 				} else {
// 					$this->data[$fieldname]	= null;
				}
			} else {
				$fieldValue	= $data[$fieldname];
// 				$this->data[$fieldname] = $data[$fieldname];
			}
			$this->data[$fieldname] = $this->parseFieldValue($fieldname, $fieldValue);
		}
// 		$this->modFields = array();
		$this->clearModifiedFields();
		if( DEV_VERSION ) {
			$this->checkIntegrity();
		}
	}
	
	/**
	 * Destructor
	 * 
	 * If something was modified, it saves the new data.
	 */
	public function __destruct() {
		if( !empty($this->modFields) ) {
			try {
				$this->save();
			} catch(Exception $e) {
				// Can be destructed outside of the matrix
				log_error($e->getMessage()."<br />\n".$e->getTraceAsString(), 'PermanentObject::__destruct(): Saving');
			}
		}
	}
	
	/**
	 * Magic getter
	 * 
	 * @param string $name Name of the property to get
	 * @return The value of field $name
	 * 
	 * Get the value of field $name.
	 * 'all' returns all fields.
	 */
	public function __get($name) {
		return $this->getValue($name == 'all' ? null : $name);
	}
	
	/**
	 * Magic setter
	 * 
	 * @param $name Name of the property to set
	 * @param $value New value of the property
	 * 
	 * Sets the value of field $name.
	 */
	public function __set($name, $value) {
		$this->setValue($name, $value);
	}
	
	/**
	 * Magic isset
	 * 
	 * @param $name Name of the property to check is set
	 * 
	 * Checks if the field $name is set.
	 */
	public function __isset($name) {
        return isset($this->data[$name]);
	}
	
	/**
	 * Magic toString
	 * 
	 * @return The string value of the object.
	 * 
	 * The object's value when casting to string.
	 */
	public function __toString() {
		try {
			return static::getClass().'#'.$this->{static::$IDFIELD};
// 			return '#'.$this->{static::$IDFIELD}.' ('.get_class($this).')';
		} catch( Exception $e ) {
			log_error($e->getMessage()."<br />\n".$e->getTraceAsString(), 'PermanentObject::__toString()', false);
		}
	}
	
	// *** DEV METHODS ***
	
	/**
	 * Get this permanent object's ID
	 * 
	 * @return The id of this object.
	 * 
	 * Get this object ID according to the IDFIELD attribute.
	 */
	public function id() {
		return $this->getValue(static::$IDFIELD);
	}
	
	/**
	 * Get this permanent object's unique ID
	 * 
	 * @return The uid of this object.
	 * 
	 * Get this object ID according to the table and id.
	 */
	public function uid() {
		return $this->getTable().'#'.$this->id();
	}
	
	/**
	 * Update this permanent object from input data array
	 * 
	 * @param	array $input The input data we will check and extract, used by children
	 * @param	string[] $fields The array of fields to check
	 * @param	boolean $noEmptyWarning True to do not report warning for empty data (instead return 0). Default value is true.
	 * @param	&int $errCount Output parameter for the number of occurred errors validating fields.
	 * @param	&int $successCount Output parameter for the number of successes updating fields.
	 * @return	1 in case of success, else 0.
	 * @see runForUpdate()
	 * @overrideit
	 * 
	 * This method require to be overridden but it still be called too by the child classes.
	 * Here $input is not used, it is reserved for child classes.
	 * $data must contain a filled array of new data.
	 * This method update the EDIT event log.
	 * Before saving, runForUpdate() is called to let child classes to run custom instructions.
	 * Parameter $fields is really useful to allow partial modification only (against form hack).
	 */
	public function update($input, $fields, $noEmptyWarning=true, &$errCount=0, &$successCount=0) {
		
		$operation = $this->getUpdateOperation($input, $fields);
		$operation->validate($errCount);
		return $operation->runIfValid();
	}
	
	/**
	 * Get the update operation
	 * 
	 * @param	array $input The input data we will check and extract, used by children
	 * @param	string[] $fields The array of fields to check
	 * @return \Orpheus\Publisher\Transaction\UpdateTransactionOperation
	 */
	public function getUpdateOperation($input, $fields) {
		$operation = new UpdateTransactionOperation(static::getClass(), $input, $fields, $this);
		$operation->setSQLAdapter(static::getSQLAdapter());
		return $operation;
	}
	
	/**
	 * Callback when validating update
	 * 
	 * @param array $input
	 * @param int $newErrors
	 * @return boolean
	 */
	public static function onValidUpdate(&$input, $newErrors) {
		// Don't care about some errors, other fields should be updated.
		$found = 0;
		foreach( $input as $fieldname => $fieldvalue ) {
			if( in_array($fieldname, static::$fields) ) {
				$found++;
			}
		}
		if( $found ) {
			static::fillLogEvent($input, 'edit');
			static::fillLogEvent($input, 'update');
		}
		return $found ? true : false;
	}
	
	
	/**
	 * Extract an update query from this object
	 * 
	 * @param array $input
	 * @param PermanentObject $object
	 * @return array
	 * @uses UpdateTransactionOperation
	 */
	public static function extractUpdateQuery(&$input, PermanentObject $object) {
		static::onEdit($input, $object);
		
		foreach( $input as $fieldname => $fieldvalue ) {
			// If saving object, value is the same, validator should check if value is new
			if( !in_array($fieldname, static::$fields) ) {
				unset($input[$fieldname]);
			}
		}
		
		$options	= array(
			'what'		=> $input,
			'table'		=> static::$table,
			'where'		=> static::getIDField().'='.$object->id(),
			'number'	=> 1,
		);
		
		return $options;
	}
	
	/**
	 * Get the delete operation for this object
	 * 
	 * @return \Orpheus\Publisher\Transaction\DeleteTransactionOperation
	 */
	public function getDeleteOperation() {
// 		return new DeleteTransactionOperation(static::getClass(), $this);
		$operation = new DeleteTransactionOperation(static::getClass(), $this);
		$operation->setSQLAdapter(static::getSQLAdapter());
		return $operation;
	}
	
	/**
	 * Run for Update
	 * 
	 * @param $data the new data
	 * @param $oldData the old data
	 * @see update()
	 * @deprecated
	 * 
	 * This function is called by update() before saving new data.
	 * $data contains only edited data, excluding invalids and not changed ones.
	 * In the base class, this method does nothing.
	 */
	public function runForUpdate($data, $oldData) { }

	/**
	 * Run for Object edit
	 * 
	 * @param array $data the new data
	 * @param PermanentObject $object the old data
	 * @see update()
	 * @see create()
	 *
	 * Replace deprecated runForUpdate()
	 * This function is called by update() and create() before saving new data.
	 * $data contains only edited data, excluding invalids and not changed ones.
	 * In the base class, this method does nothing.
	 */
	public static function onEdit(array &$data, $object) { }
	
	/**
	 * Callback when validating input
	 * 
	 * @param array $input
	 * @param array $fields
	 * @param PermanentObject $object
	 */
	public static function onValidateInput(array &$input, &$fields, $object) { }
	
	/**
	 * Callbakc when object was saved
	 * 
	 * @param array $data
	 * @param int|PermanentObject $object
	 */
	public static function onSaved(array $data, $object) { }
	
	/**
	 * Is this object called onSaved ?
	 * It prevents recursive calls
	 * 
	 * @var boolean
	 */
	protected $onSavedInProgress = false;
	
	/**
	 * Save this permanent object
	 * 
	 * @return boolean True in case of success
	 * 
	 * If some fields was modified, it saves these fields using the SQL Adapter.
	 */
	public function save() {
		if( empty($this->modFields) || $this->isDeleted() ) {
			return false;
		}

		$data = array_filterbykeys($this->all, $this->modFields);
		if( !$data ) {
			throw new Exception('No updated data found but there is modified fields, unable to update');
		}
		$operation = $this->getUpdateOperation($data, $this->modFields);
		// Do not validate, new data are invalid due to the fact the new data are already in object
// 		$operation->validate();
		$r = $operation->run();
		$this->modFields	= array();
		if( !$this->onSavedInProgress ) {
			// Protect script against saving loops
			$this->onSavedInProgress = true;
			static::onSaved($data, $this);
			$this->onSavedInProgress = false;
		}
		return $r;
	}
	
	/**
	 * Check object integrity & validity
	 */
	public function checkIntegrity() { }
	
	/**
	 * What do you think it does ?
	 * 
	 * @return int
	 */
	public function remove() {
		if( $this->isDeleted() ) { return; }
		$operation = $this->getDeleteOperation();
// 		$errors = 0;
		$operation->validate($errors);
		return $operation->runIfValid();
// 		return static::delete($this->id());
	}
	
	/**
	 * Free the object (remove)
	 * 
	 * @return boolean
	 * @see remove()
	 */
	public function free() {
		if( $this->remove() ) {
			$this->data			= null;
			$this->modFields	= null;
			return true;
		}
		return false;
	}
	
	/**
	 * Reload fields from database
	 * 
	 * @param string $field The field to reload, default is null (all fields).
	 * @return boolean True if done
	 * 
	 * Update the current object's fields from database.
	 * If $field is not set, it reloads only one field else all fields.
	 * Also it removes the reloaded fields from the modified ones list.
	 */
	public function reload($field=null) {
		$IDFIELD = static::getIDField();
		$options = array('where' => $IDFIELD.'='.$this->$IDFIELD, 'output' => SQLAdapter::ARR_FIRST);
		if( $field ) {
			if( !in_array($field, static::$fields) ) {
				throw new FieldNotFoundException($field, static::getClass());
			}
			$i = array_search($this->modFields);
			if( $i !== false ) {
				unset($this->modFields[$i]);
			}
			$options['what'] = $field;
		} else {
			$this->modFields = array();
		}
		try {
			$data = static::get($options);
		} catch( SQLException $e ) {
			$data = null;
		}
		if( empty($data) ) {
			$this->markAsDeleted();
			return false;
		}
		if( !is_null($field) ) {
			$this->data[$field] = $data[$field];
		} else {
			$this->data = $data;
		}
		return true;
	}
	
	/**
	 * Mark the field as modified
	 * 
	 * @param $field The field to mark as modified.
	 * 
	 * Adds the $field to the modified fields array.
	 */
	protected function addModFields($field) {
		if( !in_array($field, $this->modFields) ) {
			$this->modFields[] = $field;
		}
	}
	
	/**
	 * List all modified fields
	 * 
	 * @return string[]
	 */
	protected function listModifiedFields() {
		return $this->modFields;
	}
	
	/**
	 * Clear modified fields
	 */
	protected function clearModifiedFields() {
		$this->modFields = array();
	}
	
	/**
	 * Check if this object is deleted
	 * 
	 * @return boolean True if this object is deleted
	 * 
	 * Checks if this object is known as deleted.
	 */
	public function isDeleted() {
		return $this->isDeleted;
	}
	
	/**
	 * Check if this object is valid
	 * 
	 * @return boolean True if this object is valid
	 * 
	 * Check if this object is not deleted.
	 * May be used for others cases.
	 */
	public function isValid() {
		return !$this->isDeleted();
	}
	
	/**
	 * Mark this object as deleted
	 * 
	 * @see isDeleted()
	 * @warning Be sure what you are doing before calling this function (never out of this class' context).
	 * 
	 * Mark this object as deleted
	 */
	public function markAsDeleted() {
		$this->isDeleted = true;
	}
	
	/**
	 * Get one value or all values
	 * 
	 * @param $key Name of the field to get.
	 * @return mixed
	 * 
	 * Get the value of field $key or all data values if $key is null.
	 */
	public function getValue($key=null) {
		if( empty($key) ) {
			return $this->data;
		}
		if( !array_key_exists($key, $this->data) ) {
// 			log_debug('Key "'.$key.'" not found in array : '.print_r($this->data, 1));
			throw new FieldNotFoundException($key, static::getClass());
		}
		return $this->data[$key];
	}
	
	/**
	 * Set the value of a field
	 * 
	 * @param string $key Name of the field to set
	 * @param mixed $value New value of the field
	 * @return PermanentObject
	 * 
	 * Set the field $key with the new $value.
	 */
	public function setValue($key, $value) {
		if( !isset($key) ) {//$value
			throw new Exception("nullKey");
		} else
		if( !in_array($key, static::$fields) ) {
			throw new FieldNotFoundException($key, static::getClass());
		} else
		if( $key === static::$IDFIELD ) {
			throw new Exception("idNotEditable");
		} else
// 		if( empty($this->data[$key]) || $value !== $this->data[$key] ) {
		// If new value is different
		if( $value !== $this->data[$key] ) {
			$this->addModFields($key);
			$this->data[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Verify equality with another object
	 * 
	 * @param object $o The object to compare.
	 * @return boolean True if this object represents the same data, else False.
	 * 
	 * Compare the class and the ID field value of the 2 objects.
	 */
	public function equals($o) {
		return (get_class($this)==get_class($o) && $this->id()==$o->id());
	}
	
	/**
	 * Log an event
	 * 
	 * @param string $event The event to log in this object
	 * @param int $time A specified time to use for logging event
	 * @param string $ipAdd A specified IP Adress to use for logging event
	 * @see getLogEvent()
	 * 
	 * Log an event to this object's data.
	 */
	public function logEvent($event, $time=null, $ipAdd=null) {
		$log = static::getLogEvent($event, $time, $ipAdd);
		if( in_array($event.'_time', static::$fields) ) {
			$this->setValue($event.'_time', $log[$event.'_time']);
		} else
		if( in_array($event.'_date', static::$fields) ) {
			$this->setValue($event.'_date', sqlDatetime($log[$event.'_time']));
		} else {
			return;
		}
		if( in_array($event.'_agent', static::$fields) && isset($_SERVER['HTTP_USER_AGENT']) ) {
			$this->setValue($event.'_agent', $_SERVER['HTTP_USER_AGENT']);
		}
		if( in_array($event.'_referer', static::$fields) && isset($_SERVER['HTTP_REFERER']) ) {
			$this->setValue($event.'_referer', $_SERVER['HTTP_REFERER']);
		}
		try {
			$this->setValue($event.'_ip', $log[$event.'_ip']);
		} catch(FieldNotFoundException $e) {}
	}
	
	/**
	 * Add an $event log in this $array
	 * 
	 * @param array $array
	 * @param string $event
	 */
	public static function fillLogEvent(&$array, $event) {
		// All event fields will be filled, if value is not available, we set to null
		if( in_array($event.'_time', static::$fields) ) {
			$array[$event.'_time'] = time();
		} else
		if( in_array($event.'_date', static::$fields) ) {
			$array[$event.'_date'] = sqlDatetime();
		} else {
			// Date or time is mandatory
			return;
		}
		if( in_array($event.'_ip', static::$fields) ) {
			$array[$event.'_ip'] = clientIP();
// 			$array[$event.'_ip'] = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
		}
		if( in_array($event.'_agent', static::$fields) ) {
			$array[$event.'_agent'] = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
		}
		if( in_array($event.'_referer', static::$fields) ) {
			$array[$event.'_referer'] = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
		}
	}
	
	/**
	 * Get the log of an event
	 * 
	 * @param string $event The event to log in this object
	 * @param int $time A specified time to use for logging event
	 * @param string $ipAdd A specified IP Adress to use for logging event
	 * @deprecated
	 * @see logEvent()
	 * 
	 * Build a new log event for $event for this time and the user IP address.
	 */
	public static function getLogEvent($event, $time=null, $ipAdd=null) {
		return array(
			$event.'_time'	=> isset($time) ? $time : time(),
			$event.'_date'	=> isset($time) ? sqlDatetime($time) : sqlDatetime(),
			$event.'_ip'	=> isset($ipAdd) ? $ipAdd : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1' ),
		);
	}
	
	// *** STATIC METHODS ***
	
	/**
	 * Get the object whatever we give to it
	 * 
	 * @param PermanentObject|int $obj
	 * @return \Orpheus\Publisher\PermanentObject\PermanentObject
	 * @see id()
	 */
	public static function object(&$obj) {
		return $obj = is_id($obj) ? static::load($obj) : $obj;
	}
	
	/**
	 * Test if $fieldname is editable
	 * 
	 * @param string $fieldname
	 * @return boolean
	 */
	public static function isFieldEditable($fieldname) {
		if( $fieldname == static::$IDFIELD ) { return false; }
		if( !is_null(static::$editableFields) ) { return in_array($fieldname, static::$editableFields); }
		if( method_exists(static::$validator, 'isFieldEditable') ) { return in_array($fieldname, static::$editableFields); }
		return in_array($fieldname, static::$fields);
	}
	
	/**
	 * Get some permanent objects
	 * 
	 * @param array $options The options used to get the permanents object
	 * @return SQLSelectRequest|static|static[] An array of array containing object's data
	 * @see SQLAdapter
	 * 
	 * Get an objects' list using this class' table.
	 * Take care that output=SQLAdapter::ARR_OBJECTS and number=1 is different from output=SQLAdapter::OBJECT
	 * 
	 */
	public static function get($options=NULL) {
		if( $options === NULL ) {
			return SQLRequest::select(static::getSQLAdapter(), static::$IDFIELD, static::getClass())->from(static::$table)->asObjectList();
		}
		if( $options instanceof SQLSelectRequest ) {
			$options->setSQLAdapter(static::getSQLAdapter());
			$options->setIDField(static::$IDFIELD);
			$options->from(static::$table);
			return $options->run();
		}
		if( is_string($options) ) {
			$options = array();
			$args = func_get_args();
			foreach( array('where', 'orderby') as $i => $key ) {
				if( !isset($args[$i]) ) { break; }
				$options[$key] = $args[$i];
			}
		}
		$options['table'] = static::$table;
		// May be incompatible with old revisions (< R398)
		if( !isset($options['output']) ) {
			$options['output'] = SQLAdapter::ARR_OBJECTS;
		}
		//This method intercepts outputs of array of objects.
		$onlyOne = $objects = 0;
		if( in_array($options['output'], array(SQLAdapter::ARR_OBJECTS, SQLAdapter::OBJECT)) ) {
			if( $options['output'] == SQLAdapter::OBJECT ) {
				$options['number'] = 1;
				$onlyOne = 1;
			}
			$options['output'] = SQLAdapter::ARR_ASSOC;
			$objects = 1;
		}
		$sqlAdapter = static::getSQLAdapter();
		$r = $sqlAdapter->select($options);
		if( empty($r) && in_array($options['output'], array(SQLAdapter::ARR_ASSOC, SQLAdapter::ARR_OBJECTS, SQLAdapter::ARR_FIRST)) ) {
			return $onlyOne && $objects ? null : array();
		}
		if( !empty($r) && $objects ) {
			if( $onlyOne ) {
				$r = static::load($r[0]);
			} else {
				foreach( $r as &$rdata ) {
					$rdata = static::load($rdata);
				}
			}
		}
		return $r;
	}
	
	/**
	 * Load a permanent object
	 * 
	 * @param	mixed|mixed[] $in The object ID to load or a valid array of the object's data
	 * @param	boolean $nullable True to silent errors row and return null
	 * @param	boolean $usingCache True to cache load and set cache, false to not cache
	 * @return	PermanentObject The object
	 * @see static::get()
	 * 
	 * Loads the object with the ID $id or the array data.
	 * The return value is always a static object (no null, no array, no other object).
	 */
	public static function load($in, $nullable=true, $usingCache=true) {
		if( empty($in) ) {
			if( $nullable ) { return null; }
			static::throwNotFound('invalidParameter_load');
		}
		// Try to load an object from this class
		if( is_object($in) && $in instanceof static ) {
			return $in;
		}
		$IDFIELD	= static::$IDFIELD;
		// If $in is an array, we trust him, as data of the object.
		if( is_array($in) ) {
			$id		= $in[$IDFIELD];
			$data	= $in;
		} else {
			$id		= $in;
		}
		if( !is_ID($id) ) {
			static::throwException('invalidID');
		}
		// Loading cached
		if( $usingCache && isset(static::$instances[static::getClass()][$id]) ) {
			return static::$instances[static::getClass()][$id];
		}
		// If we don't get the data, we request them.
		if( empty($data) ) {
			// Getting data
			$obj = static::get(array(
				'where'	=> $IDFIELD.'='.$id,
				'output'=> SQLAdapter::OBJECT,
			));
			// Ho no, we don't have the data, we can't load the object !
			if( empty($obj) ) {
				if( $nullable ) { return null; }
				static::throwNotFound();
			}
		} else {
			$obj = new static($data);
		}
		// Caching object
		return $usingCache ? $obj->checkCache() : $obj;
	}
	
	/**
	 * Cache an array of objects
	 * 
	 * @param array $objects
	 * @return array
	 */
	public static function cacheObjects(array &$objects) {
		foreach( $objects as &$obj ) {
			$obj = $obj->checkCache();
		}
		return $objects;
	}
	
	/**
	 * Check if this object is cached and cache it
	 * 
	 * @return \Orpheus\Publisher\PermanentObject\PermanentObject
	 */
	protected function checkCache() {
		if( isset(static::$instances[static::getClass()][$this->id()]) ) { return static::$instances[static::getClass()][$this->id()]; }
		static::$instances[static::getClass()][$this->id()]	= $this;
		return $this;
	}
	
	/**
	 * Delete a permanent object
	 * 
	 * @param int $in The object ID to delete or the delete array.
	 * @return int The number of deleted rows.
	 * @deprecated
	 * 
	 * Delete the object with the ID $id or according to the input array.
	 * It calls runForDeletion() only in case of $in is an ID.
	 * 
	 * The cached object is mark as deleted.
	 * Warning ! If several class instantiate the same db row, it only marks the one of the current class, others won't be marked as deleted, this can cause issues !
	 * We advise you to use only one class of one item row or to use it read-only.
	 */
	public static function delete($in) {
		throw new Exception("Deprecated, please use remove()");
	}
	
	/**
	 * Remove deleted instances from cache
	 */
	public static function clearDeletedInstances() {
		if( !isset(static::$instances[static::getClass()]) ) { return; }
		$instances	= &static::$instances[static::getClass()];
		foreach( $instances as $id => $obj ) {
			if( $obj->isDeleted() ) {
				unset($instances[$id]);
			}
		}
	}
	
	/**
	 * Remove deleted instances
	 */
	public static function clearInstances() {
		return static::clearDeletedInstances();
	}
	
	/**
	 * Remove all instances
	 */
	public static function clearAllInstances() {
		if( !isset(static::$instances[static::getClass()]) ) { return; }
		unset(static::$instances[static::getClass()]);
	}
	
	/**
	 * Escape identifier through instance
	 * 
	 * @param	string $identifier The identifier to escape. Default is table name.
	 * @return	string The escaped identifier
	 * @see SQLAdapter::escapeIdentifier()
	 * @see static::ei()
	 */
	public static function escapeIdentifier($identifier=null) {
		$sqlAdapter = static::getSQLAdapter();
		return $sqlAdapter->escapeIdentifier($identifier ? $identifier : static::$table);
// 		return SQLAdapter::doEscapeIdentifier($identifier ? $identifier : static::$table, static::$DBInstance);
	}
	
	/**
	 * Escape identifier through instance
	 * 
	 * @param	string $identifier The identifier to escape. Default is table name.
	 * @return	string The escaped identifier
	 * @see static::escapeIdentifier()
	 */
	public static function ei($identifier=null) {
		return static::escapeIdentifier($identifier);
	}

	/**
	 * Parse the value from SQL scalar to PHP type
	 *
	 * @param string $name The field name to parse
	 * @param string $value The field value to parse
	 * @return string The parse $value
	 * @see PermanentObject::formatFieldValue()
	 */
	protected static function parseFieldValue($name, $value) {
		return $value;
	}
	
	/**
	 * Format the value
	 * 
	 * @param string $name The field name to format
	 * @param mixed $value The field value to format
	 * @return string The formatted $Value
	 * @deprecated Prefer to use parseFieldValue(), Adapter should format the data
	 * @see PermanentObject::formatValue()
	 */
	protected static function formatFieldValue($name, $value) {
		return $value;
	}
	
	/**
	 * Escape value through instance
	 * 
	 * @param scalar $value The value to format
	 * @return string The formatted $Value
	 * @see SQLAdapter::formatValue()
	 */
	public static function formatValue($value) {
		$sqlAdapter = static::getSQLAdapter();
		return $sqlAdapter->formatValue($value);
// 		return SQLAdapter::doFormatValue($value, static::$DBInstance);
	}
	
	/**
	 * Escape value through instance
	 * 
	 * @param scalar $value The value to format
	 * @return string The formatted $Value
	 * @see PermanentObject::formatValue()
	 */
	public static function fv($value) {
		return static::formatValue($value);
	}
	
	/**
	 * Escape values through instance and return as list string
	 * 
	 * @param array $list The list of values
	 * @return string The formatted list string
	 * @see PermanentObject::formatValue()
	 * 
	 * @todo Change to use formatFieldValue($name, $value) ?
	 */
	public static function formatValueList(array $list) {
		$sqlAdapter = static::getSQLAdapter();
		return $sqlAdapter->formatValueList($list);
	}
	
	/**
	 * Create a new permanent object
	 * 
	 * @param	array $input The input data we will check, extract and create the new object.
	 * @param	array $fields The array of fields to check. Default value is null.
	 * @param	int $errCount Output parameter to get the number of found errors. Default value is 0
	 * @return	int The ID of the new permanent object.
	 * @see		testUserInput()
	 * @see		createAndGet()
	 * 
	 * Create a new permanent object from ths input data.
	 * To create an object, we expect that it is valid, else we throw an exception.
	 */
	public static function create($input=array(), $fields=null, &$errCount=0) {
		$operation = static::getCreateOperation($input, $fields);
		$operation->validate($errCount);
		return $operation->runIfValid();
	}
	
	/**
	 * Get the create operation
	 * 
	 * @param	array $input The input data we will check and extract, used by children
	 * @param	string[] $fields The array of fields to check
	 * @return \Orpheus\Publisher\Transaction\CreateTransactionOperation
	 */
	public static function getCreateOperation($input, $fields) {
// 		return new CreateTransactionOperation(static::getClass(), $input, $fields);
		$operation = new CreateTransactionOperation(static::getClass(), $input, $fields);
		$operation->setSQLAdapter(static::getSQLAdapter());
		return $operation;
	}
	
	/**
	 * Callback when validating create
	 * 
	 * @param array $input
	 * @param int $newErrors
	 * @return boolean
	 */
	public static function onValidCreate(&$input, $newErrors) {
		if( $newErrors ) {
			static::throwException('errorCreateChecking');
		}
		static::fillLogEvent($input, 'create');
		static::fillLogEvent($input, 'edit');
// 		$input = static::getLogEvent('create') + static::getLogEvent('edit') + $input;
		return true;
	}
	
	/**
	 * Extract a create query from this class
	 * 
	 * @param array $input
	 * @return array
	 */
	public static function extractCreateQuery(&$input) {
		// To do on Edit
		static::onEdit($input, null);
		
		foreach( $input as $fieldname => $fieldvalue ) {
			if( !in_array($fieldname, static::$fields) ) {
				unset($input[$fieldname]);
			}
		}
		
		$options	= array(
			'table'	=> static::$table,
			'what'	=> $input,
		);
		return $options;
	}
	
	/**
	 * Create a new permanent object
	 * 
	 * @param	array $input The input data we will check, extract and create the new object.
	 * @param	array|null $fields The array of fields to check. Default value is null.
	 * @param	int $errCount Output parameter to get the number of found errors. Default value is 0
	 * @return	PermanentObject The new permanent object
	 * @see testUserInput()
	 * @see create()
	 *
	 * Create a new permanent object from ths input data.
	 * To create an object, we expect that it is valid, else we throw an exception.
	 */
	public static function createAndGet($input=array(), $fields=null, &$errCount=0) {
		return static::load(static::create($input, $fields, $errCount));
	}
	
	/**
	 * Complete missing fields
	 * 
	 * @param array $data The data array to complete.
	 * @return array The completed data array.
	 * 
	 * Complete an array of data of an object of this class by setting missing fields with empty string.
	 */
	public static function completeFields($data) {
		foreach( static::$fields as $fieldname ) {
			if( !isset($data[$fieldname]) ) {
				$data[$fieldname] = '';
			}
		}
		return $data;
	}
	
	/**
	 * Get known fields
	 * 
	 * @return array
	 */
	public static function getFields() {
		return static::$fields;
	}
	
	/**
	 * Get the name of this class
	 * 
	 * @return string The name of this class.
	 */
	public static function getClass() {
		return get_called_class();
	}
	
	/**
	 * Get the table of this class
	 * 
	 * @return The table of this class.
	 */
	public static function getTable() {
		return static::$table;
	}
	
	/**
	 * Get the ID field name of this class
	 * 
	 * @return The ID field of this class.
	 */
	public static function getIDField() {
		return static::$IDFIELD;
	}
	
	/**
	 * Get the domain of this class
	 * 
	 * @return The domain of this class.
	 * 
	 * Get the domain of this class, can be guessed from $table or specified in $domain.
	 */
	public static function getDomain() {
		return static::$domain !== NULL ? static::$domain : static::$table;
	}
	
	/**
	 * Get the validator of this class
	 * 
	 * @return The validator of this class.
	 * 
	 * Get the validator of this class.
	 */
	public static function getValidator() {
		return static::$validator;
	}
	
	/**
	 * Run for object
	 * 
	 * @param $data The new data to process.
	 * @see create()
	 * 
	 * This function is called by create() after checking new data and before inserting them.
	 * In the base class, this method does nothing.
	 */
	public static function runForObject(&$data) { }
	
	/**
	 * Apply for new object
	 * 
	 * @param $data The new data to process.
	 * @param $id The ID of the new object.
	 * @see create()
	 * 
	 * This function is called by create() after inserting new data.
	 * In the base class, this method does nothing.
	 */
	public static function applyToObject(&$data, $id) { }
	
	// 		** VALIDATION METHODS **
	
	/**
	 * Check user input
	 * 
	 * @param array $input The user input data to check.
	 * @param string[] $fields The array of fields to check. Default value is null.
	 * @param PermanentObject $ref The referenced object (update only). Default value is null.
	 * @param int $errCount The resulting error count, as pointer. Output parameter.
	 * @return The valid data.
	 * 
	 * Check if the class could generate a valid object from $input.
	 * The method could modify the user input to fix them but it must return the data.
	 * The data are passed through the validator, for different cases:
	 * - If empty, this function return an empty array.
	 * - If an array, it uses an field => checkMethod association.
	 */
	public static function checkUserInput($input, $fields=null, $ref=null, &$errCount=0) {
		if( !isset($errCount) ) {
			$errCount = 0;
		}
		// Allow reversed parameters 2 & 3 - Declared as useless
// 		if( !is_array($fields) && !is_object($ref) ) {
// 			$tmp = $fields; $fields = $ref; $ref = $tmp; unset($tmp);
// 		}
// 		if( is_null($ref) && is_object($ref) ) {
// 			$ref = $fields;
// 			$fields = null;
// 		}
		if( is_array(static::$validator) ) {
			if( $fields===NULL ) {
				$fields	= static::$editableFields;
			}
			if( empty($fields) ) { return array(); }
			$data = array();
			foreach( $fields as $field ) {
				// If editing the id field
				if( $field == static::$IDFIELD ) { continue; }
				$value = $notset = null;
				try {
					try {
						// Field to validate
						if( !empty(static::$validator[$field]) ) {
							$checkMeth	= static::$validator[$field];
							// If not defined, we just get the value without check
							$value	= static::$checkMeth($input, $ref);
	
						// Field to NOT validate
						} else if( array_key_exists($field, $input) ) {
							$value	= $input[$field];
						} else {
							$notset	= 1;
						}
						if( !isset($notset) &&
							( $ref===NULL || $value != $ref->$field) &&
							( $fields===NULL || in_array($field, $fields))
						) {
							$data[$field]	= $value;
						}

					} catch( UserException $e ) {
						if( $value===NULL && isset($input[$field]) ) {
							$value	= $input[$field];
						}
						throw InvalidFieldException::from($e, $field, $value);
					}
					
				} catch( InvalidFieldException $e ) {
					$errCount++;
					reportError($e, static::getDomain());
				}
			}
			return $data;
		
		} else if( is_object(static::$validator) ) {
			if( method_exists(static::$validator, 'validate') ) {
				return static::$validator->validate($input, $fields, $ref, $errCount);
			}
		}
		return array();
	}
	
	/**
	 * Check for object
	 * 
	 * @param $data The new data to process.
	 * @param $ref The referenced object (update only). Default value is null.
	 * @see create()
	 * @see update()
	 * 
	 * This function is called by create() after checking user input data and before running for them.
	 * In the base class, this method does nothing.
	 */
	public static function checkForObject($data, $ref=null) { }
	
	/**
	 * Test user input
	 * 
	 * @param $input The new data to process.
	 * @param $fields The array of fields to check. Default value is null.
	 * @param $ref The referenced object (update only). Default value is null.
	 * @param $errCount The resulting error count, as pointer. Output parameter.
	 * @see create()
	 * @see checkUserInput()
	 * 
	 * Does a checkUserInput() and a checkForObject()
	 */
	public static function testUserInput($input, $fields=null, $ref=null, &$errCount=0) {
		$data = static::checkUserInput($input, $fields, $ref, $errCount);
		if( $errCount ) { return false; }
		try {
			static::checkForObject($data, $ref);
		} catch( UserException $e ) {
			$errCount++;
			reportError($e, static::getDomain());
			return false;
		}
		return true;
	}
	
	/**
	 * Store data about known classes
	 * 
	 * @var array
	 */
	protected static $knownClassData	= array();
	
	/**
	 * Get all gathered data about this class
	 * 
	 * @param array $classData
	 * @return array
	 */
	public static function getClassData(&$classData=null) {
		$class = static::getClass();
		if( !isset(static::$knownClassData[$class]) ) {
			static::$knownClassData[$class]	= (object) array(
				'sqlAdapter' => null,
			);
		}
		$classData = static::$knownClassData[$class];
		return $classData;
	}
	
	/**
	 * Get the SQL Adapter of this class
	 * 
	 * @return SQLAdapter
	 */
	public static function getSQLAdapter() {
		$classData = null;
		static::getClassData($classData);
		if( !$classData->sqlAdapter ) {
			$classData->sqlAdapter = SQLAdapter::getInstance(static::$DBInstance);
		}
		// This after $knownClassData classData
		
		return $classData->sqlAdapter;
	}
	
	/**
	 * Initialize the class
	 * 
	 * @param string $isFinal
	 * 
	 * Call this function only once after declaring it
	 */
	public static function init($isFinal=true) {
		
		$parent = get_parent_class(get_called_class());
		if( empty($parent) ) { return; }
		
		static::$fields = array_unique(array_merge(static::$fields, $parent::$fields));
		// Deprecated, no more defining editable fields, rely on form and EntityDescriptor
		if( !$parent::$editableFields ) {
			static::$editableFields = !static::$editableFields ? $parent::$editableFields : array_unique(array_merge(static::$editableFields, $parent::$editableFields));
		}
		// Deprecated, should use EntityDescriptor as validator
		if( is_array(static::$validator) && is_array($parent::$validator) ) {
			static::$validator = array_unique(array_merge(static::$validator, $parent::$validator));
		}
		if( !static::$domain ) {
			static::$domain = static::$table;
		}
	}
	
	/**
	 * Throw an UserException
	 * 
	 * @param string $message the text message, may be a translation string
	 * @see UserException
	 * 
	 * Throws an UserException with the current domain.
	 */
	public static function throwException($message) {
		throw new UserException($message, static::getDomain());
	}

	/**
	 * Throw a NotFoundException
	 *
	 * @param string $message the text message, may be a translation string
	 * @see UserException
	 *
	 * Throws an NotFoundException with the current domain.
	 */
	public static function throwNotFound($message=null) {
		throw new NotFoundException($message, static::getDomain());
	}
	
	/**
	 * Translate text according to the object domain
	 * 
	 * @param string $text The text to translate
 	 * @param array|string $values The values array to replace in text. Could be used as second parameter.
	 * @return string The translated text
	 * @see t()
	 * 
	 * Translate text according to the object domain
	 */
	public static function text($text, $values=array()) {
		return t($text, static::getDomain(), is_array($values) ? $values : array_slice(func_get_args(), 1));
	}
	
	/**
	 * Translate text according to the object domain
	 * 
	 * @param sting $text The text to translate
 	 * @param array|string $values The values array to replace in text. Could be used as second parameter.
	 * @see t()
	 * 
	 * Translate text according to the object domain
	 */
	public static function _text($text, $values=array()) {
		_t($text, static::getDomain(), is_array($values) ? $values : array_slice(func_get_args(), 1));
	}
	
	/**
	 * Report an UserException
	 * 
	 * @param $e the UserException
	 * @see UserException
	 * 
	 * Throws an UserException with the current domain.
	 */
	public static function reportException(UserException $e) {
		reportError($e);
	}
}
PermanentObject::selfInit();