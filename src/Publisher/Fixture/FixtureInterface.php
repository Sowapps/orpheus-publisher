<?php

namespace Orpheus\Publisher\Fixture;

/**
 * The FixtureInterface interface
 * 
 * This interface is used to register fixtures.
 */
interface FixtureInterface {
	
	/**
	 * Load fixtures
	 */
	public static function loadFixtures();
	
}
