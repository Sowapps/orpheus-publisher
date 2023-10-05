<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher\Fixture;

/**
 * The FixtureRepository class
 * 
 * This interface is used to register fixtures.
 */
class FixtureRepository {
	
	/**
	 * All the registered fixtures
	 * 
	 * @var array
	 */
	protected static array $fixtures = [];
	
	/**
	 * Register $class having some fixtures to load
	 */
	public static function register(string $class): void {
		if( array_key_exists($class, static::$fixtures) ) {
			return;
		}
		static::$fixtures[$class] = null;
	}
	
	/**
	 * List all classes having some fixtures to load
	 * 
	 * @return string[]
	 */
	public static function listAll(): array {
		$repositories = array();
		foreach( static::$fixtures as $class => &$state ) {
			if( $state == null ) {
				$state = class_exists($class) && is_subclass_of($class, 'Orpheus\Publisher\Fixture\FixtureInterface');
			}
			if( $state ) {
				$repositories[] = $class;
			}
		}
		return $repositories;
	}
	
}
