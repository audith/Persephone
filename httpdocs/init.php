<?php

# Memory managementemail
if ( function_exists( "memory_get_usage" ) )
{
	define( "MEMORY_START", memory_get_usage() );
}

# Disable Magic Quotes Runtime
@set_magic_quotes_runtime(0);

# Set Multi-byte Internal Encoding - VERY IMPORTANT!!!
if ( function_exists( "mb_internal_encoding" ) )
{
	mb_internal_encoding( "UTF-8" );
}

# Safe Mode, RegGlobals and MagicQuotesGPC On?
define( 'SAFE_MODE_ON'         , ini_get( "safe_mode" )        ? 1 : 0 );
define( 'REGISTER_GLOBALS_ON'  , ini_get( "register_globals" ) ? 1 : 0 );
define( 'MAGIC_QUOTES_GPC_ON'  , get_magic_quotes_gpc()        ? 1 : 0 );

# Paths
define( "PATH_ROOT_WEB"     , str_replace( "\\", "\/", getenv( "DOCUMENT_ROOT" ) )   );   # Compat: For Windows Environments
define( "PATH_ROOT_VHOST"   , dirname( PATH_ROOT_WEB )                    );
define( "PATH_CACHE"        , PATH_ROOT_VHOST . "/cache"                  );
define( "PATH_DATA"         , PATH_ROOT_VHOST . "/data"                   );
define( "PATH_LOGS"         , PATH_ROOT_VHOST . "/logs"                   );
define( "PATH_SOURCES"      , PATH_ROOT_VHOST . "/sources"                );
define( "PATH_LIBS"         , PATH_ROOT_VHOST . "/libraries"              );
define( "PATH_TEMPLATES"    , PATH_ROOT_VHOST . "/templates"              );

/**
 * DEV MODE
 *
 * Turns the site into 'developers' mode which enables
 * some debugging and other tools. This is NOT recommended
 * as it opens your site up to potential security risks
 */
define( "IN_DEV" , 1 );

/**
 * SQL DEBUG MODE
 *
 * Turns on SQL debugging mode. This is NOT recommended
 * as it opens your board up to potential security risks
 */
define( "SQL_DEBUG_MODE" , 0 );

/**
 * MEMORY DEBUG MODE
 *
 * Turns on MEMORY debugging mode. This is NOT recommended
 * as it opens your board up to potential security risks
 */
define( "MEMORY_DEBUG_MODE" , 0 );

/**
 * Write to debug file?
 * Enter relative / full path into the constant below
 * Remove contents to turn off debugging.
 * WARNING: If you are passing passwords and such via XML_RPC
 * AND wish to debug, ensure that the debug file ends with .php
 * to prevent it loading as plain text via HTTP which would show
 * the entire contents of the file.
 */
define( "XML_RPC_DEBUG_FILE" , PATH_LOGS . "/xml_rpc_debug.log" );
define( "XML_RPC_DEBUG_ON"   , TRUE                             );

/**
 * Time now stamp
 */
define( "UNIX_TIME_NOW"      , time()                           );

/**
 * Minimum required PHP version
 */
define( "MIN_PHP_VERSION"    , "5.1.0"                          );

/**
 * Quick version control
 */
if ( ! version_compare( MIN_PHP_VERSION, PHP_VERSION, "<=" ) )
{
	throw new Exception( "You must be using PHP " . MIN_PHP_VERSION . " or later. You are currently using " . PHP_VERSION );
	exit();
}

/**
 * Time limit
 **/
if ( ! SAFE_MODE_ON )
{
	set_time_limit(0);
}

/**
 * Ignore User Abort
 */
ignore_user_abort( true );

/**
 * Error reporting
 */
error_reporting( E_ALL );
/*
 if ( IN_DEV )
 {
 if ( version_compare( PHP_VERSION, "5.2.4", "<" ) )
 {
 ini_set( "display_errors" , "1" );
 }
 else
 {
 ini_set( "display_errors" , "stdout" );
 }
 }
 else
 {
 ini_set( "display_errors" , "0"      );
 }
 */
ini_set( "display_errors"        , "0"                              );
ini_set( "error_log"             , PATH_LOGS . "/php_errors.log"    );
ini_set( "log_errors"            , "1"                              );
ini_set( "track_errors"          , "1"                              );
ini_set( "error_prepend_string"  , "\n\n"                           );
ini_set( "error_append_string"   , "\n\n"                           );

/**
 * Include Path
 */
// set_include_path( get_include_path() . PATH_SEPARATOR . PATH_LIBS );
set_include_path( PATH_LIBS );

# PHP Timezone (PHP 5 >= 5.1.0)
if ( function_exists( 'date_default_timezone_set' ) )
{
	date_default_timezone_set( "UTC" );
}

# Script security: INIT indicator
define( "INIT_DONE", 1 );

?>