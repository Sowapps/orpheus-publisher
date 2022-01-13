<?php
/**
 * Loader File for the publisher sources
 */

use Orpheus\Config\Config;
use Orpheus\Config\IniConfig;
use Orpheus\EntityDescriptor\User\AbstractUser;
use Orpheus\Hook\Hook;
use Orpheus\Publisher\PermanentObject\PermanentObject;

if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

defifn('CHECK_MODULE_ACCESS', true);
/** @const bool ENTITY_CLASS_CHECK */
defifn('ENTITY_CLASS_CHECK', true);

// Hooks
define('HOOK_ACCESSDENIED', 'accessDenied');
Hook::create(HOOK_ACCESSDENIED);

/**
 * Hook HOOK_SESSIONSTARTED
 * Previously HOOK_APPREADY but now session is started by route process in Input Controller
 * Previously HOOK_CHECKMODULE but we need session was initialized before checking app things
 * HOOK_CHECKMODULE is called before session is initialized
 */
Hook::register(HOOK_SESSIONSTARTED, function () {
	// RIGHTS is no more used in Orpheus, each page should specifies the access restrictions in routes.yaml
	$GLOBALS['RIGHTS'] = IniConfig::build('rights', true);
	
	if( AbstractUser::isLogged() ) {
		//global $USER;// Do not work in this context.
		$user = AbstractUser::getLoggedUser();
		
		// If login ip is different from current one, protect against cookie stealing
		if( Config::get('deny_multiple_connections', false) && !$user->isLogin(AbstractUser::LOGGED_FORCED) && $user->login_ip !== clientIP() ) {
			$user->logout('loggedFromAnotherComputer');
			return;
		}
	} elseif( isset($_SERVER['PHP_AUTH_USER']) && Config::get('httpauth_enabled') ) {
		AbstractUser::httpAuthenticate();
	}
});

/**
 * Get the id whatever we give to it
 *
 * @param int|string|PermanentObject $id
 * @return int
 * @see PermanentObject::object()
 */
function id(&$id) {
	return $id = intval(is_object($id) ? $id->id() : $id);
}
