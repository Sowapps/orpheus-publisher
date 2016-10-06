<?php
/**
 * Loader File for the publisher sources
 */

use Orpheus\Config\IniConfig;
use Orpheus\Config\Config;
use Orpheus\Hook\Hook;
use Orpheus\Publisher\PermanentObject\PermanentObject;


if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

defifn('CHECK_MODULE_ACCESS',	true);
// defifn('USER_CLASS',			'User');
// global $USER_CLASS;
// $USER_CLASS = USER_CLASS;

// Hooks
define('HOOK_ACCESSDENIED', 	'accessDenied');
Hook::create(HOOK_ACCESSDENIED);

/**
 * Hook HOOK_SESSIONSTARTED
 * Previously HOOK_APPREADY but now session is started by route process in Input Controller
 * Previously HOOK_CHECKMODULE but we need session was initialized before checking app things
 * HOOK_CHECKMODULE is called before session is initialized
 */
Hook::register(HOOK_SESSIONSTARTED, function () {
// 	debug('Publisher HOOK_APPREADY => '.HOOK_APPREADY);
// 	global $USER_CLASS;
	$GLOBALS['ACCESS'] = IniConfig::build('access', true);
	$GLOBALS['RIGHTS'] = IniConfig::build('rights', true);
	
	if( User::isLogged() ) {
		//global $USER;// Do not work in this context.
		/* @var User $USER */
		$USER = $GLOBALS['USER'] = &$_SESSION['USER'];
		if( !$USER->reload() ) {
			// User does not exist anymore
			$USER->logout();
		}
		$USER->onConnected();
		
		// If login ip is different from current one, protect against cookie stealing
		if( Config::get('deny_multiple_connections', false) && !$USER->isLogin(AbstractUser::LOGGED_FORCED) && $USER->login_ip != $_SERVER['REMOTE_ADDR'] ) {
			$USER->logout('loggedFromAnotherComputer');
			return;
		}
	} else
	if( isset($_SERVER['PHP_AUTH_USER']) && Config::get('httpauth_enabled') ) {
		User::httpAuthenticate();
	}
});

/** Hook 'runModule'
 */
/*
Hook::register(HOOK_RUNMODULE, function () {
// 	global $USER_CLASS, $Module;
	global $Module;
	
// 	debug('Publisher HOOK_RUNMODULE $USER', User::getLoggedUser());
// 	debug('$Module', $Module);
	// If user can not access to this module, we redirect him to default but if default is forbidden, we can not redirect indefinitely.
	// User should always access to default, even if it redirects him to another module.
	if( !User::canAccess($Module) && DEFAULTROUTE != $Module ) {
		$module	= $Module;
		// If the trigger returns null, 0, '' or false (false equality), it redirects the user if the module has not changed during trigger process
		// If the trigger returns true, 1 or a value, it cancels the redirects
		// This allows the dev to override the authentication, but it allows to use another limitation, like in page authentication or error message
		if( CHECK_MODULE_ACCESS && !Hook::trigger(HOOK_ACCESSDENIED, false, false) && $module===$Module ) {
			redirectTo(u(defined('ACCESSDENIEDMOD') ? ACCESSDENIEDMOD : DEFAULTROUTE));
		}
	}
});
*/

/**
 * Get the id whatever we give to it
 * 
 * @param int|string|PermanentObject $id
 * @return int
 * @see \Orpheus\Publisher\PermanentObject\PermanentObject::object()
 */
function id(&$id) {
	return $id = intval(is_object($id) ? $id->id() : $id);
}
