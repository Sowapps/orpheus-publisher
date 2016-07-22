<?php

namespace Orpheus\Publisher\Fixture;

/**
 * The Fixture class
 * 
 * This interface is used to register fixtures.
 */
class FixtureRepository {
	
	/**
	 * All the registered fixtures
	 * 
	 * @var array
	 */
	protected static $fixtures	= array();
	
	/**
	 * Register $class having some fixtures to load
	 * 
	 * @param string $class
	 */
	public static function register($class) {
		if( array_key_exists($class, static::$fixtures) ) { continue; }
		static::$fixtures[$class] = null;
	}
	
	/**
	 * List all classes having some fixtures to load
	 * 
	 * @return string[]
	 */
	public static function listAll() {
		$r = array();
		foreach( static::$fixtures as $class => &$state ) {
			if( $state == null ) {
				$state	= class_exists($class, true) && is_subclass_of($class, 'Orpheus\Publisher\Fixture\FixtureInterface');
			}
			if( $state == true ) {
				$r[] = $class;
			}
		}
		return $r;
	}
}
