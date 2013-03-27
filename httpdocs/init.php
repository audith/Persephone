<?php

//---------
// Paths
//---------

# Paths
define( "PATH_ROOT_WEB", str_replace( '\\', '/', dirname( __FILE__ ) ) ); # Compat: For Windows Environments
define( "PATH_ROOT_VHOST", dirname( PATH_ROOT_WEB ) );
define( "PATH_CACHE", PATH_ROOT_VHOST . "/cache" );
define( "PATH_DATA", PATH_ROOT_VHOST . "/data" );
define( "PATH_LOGS", PATH_ROOT_VHOST . "/logs" );
define( "PATH_SOURCES", PATH_ROOT_VHOST . "/sources" );
define( "PATH_LIBS", PATH_ROOT_VHOST . "/libraries" );
define( "PATH_TEMPLATES", PATH_ROOT_VHOST . "/templates" );

/**
 * Include Path
 */
// set_include_path( get_include_path() . PATH_SEPARATOR . PATH_LIBS );
set_include_path( PATH_LIBS . PATH_SEPARATOR . PATH_SOURCES );

//----------
// Prelim
//----------

# Memory managementemail
define( "MEMORY_START", memory_get_usage() );

# Disable Magic Quotes Runtime
@set_magic_quotes_runtime( 0 );

# Set Multi-byte Internal Encoding, Language and RegEx Encoding
mb_internal_encoding( "UTF-8" );
mb_language( "uni" );
mb_regex_encoding( "UTF-8" );

# Safe Mode, RegGlobals and MagicQuotesGPC On?
define( 'SAFE_MODE_ON', ini_get( "safe_mode" ) ? 1 : 0 );
define( 'REGISTER_GLOBALS_ON', ini_get( "register_globals" ) ? 1 : 0 );
define( 'MAGIC_QUOTES_GPC_ON', get_magic_quotes_gpc() ? 1 : 0 );

/**
 * Time now stamp
 */
define( "UNIX_TIME_NOW", time() );

/**
 * DEV MODE
 *
 * Turns the site into 'developers' mode which enables
 * some debugging and other tools. This is NOT recommended
 * as it opens your site up to potential security risks
 */
define( "IN_DEV", 1 );

/**
 * Minimum required PHP version
 */
define( "MIN_PHP_VERSION", "5.3.0" );

/**
 * Time limit
 **/
if ( !SAFE_MODE_ON )
{
	set_time_limit( 0 );
}

/**
 * Ignore User Abort
 */
ignore_user_abort( true );

/**
 * Error reporting
 */
error_reporting( E_ALL );
ini_set( "display_errors", "0" );
ini_set( "error_log", PATH_LOGS . "/php.err" );
ini_set( "log_errors", "1" );
ini_set( "track_errors", "1" );
ini_set( "error_prepend_string", "\n\n" );
ini_set( "error_append_string", "\n\n" );

# Timezone to GMT by default
date_default_timezone_set( "UTC" );

//-----------------------
// Quick version check
//-----------------------

if ( !version_compare( MIN_PHP_VERSION, PHP_VERSION, "<" ) )
{
	throw new Exception( "You must be using PHP " . MIN_PHP_VERSION . " or later. You are currently using " . PHP_VERSION );
}

//------------------
// Autoloader(s)
//------------------

require_once '/Doctrine/Common/ClassLoader.php';
$library_loader = new \Doctrine\Common\ClassLoader( "Zend", PATH_LIBS );
$library_loader->register();
$persephone_loader = new \Doctrine\Common\ClassLoader( 'Persephone', PATH_SOURCES );
$persephone_loader->register();

//-----------------------------------
// Script security: INIT indicator
//-----------------------------------

define( "INIT_DONE", 1 );
