<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Publisher;

use Orpheus\Config\Config;
use Orpheus\Config\IniConfig;
use Orpheus\Core\AbstractOrpheusLibrary;
use Orpheus\EntityDescriptor\User\AbstractUser;

class OrpheusPublisherLibrary extends AbstractOrpheusLibrary {
	
	public function configure(): void {
		defifn('CHECK_MODULE_ACCESS', true);
		/** @const bool ENTITY_CLASS_CHECK */
		defifn('ENTITY_CLASS_CHECK', true);
	}
	
	public function start(): void {
		// RIGHTS is only used for operation permissions. For access permissions, each page should specify the access restrictions in routes.yaml
		$GLOBALS['RIGHTS'] = IniConfig::build('rights', true);
		
		if( isset($_SERVER['PHP_AUTH_USER']) && Config::get('httpauth_enabled') ) {
			AbstractUser::httpAuthenticate();
		}
	}
	
}
