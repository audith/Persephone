<?php

# URLs
$CONFIG['url']['ssl_mode']               = FALSE;
$CONFIG['url']['hostname']               = array(
		'http'  => array(
				'full'    => "cms.audith.net",
				'parsed'  => array( 'prefix' => "cms.audith" , 'tld' => "net" )
			),
		'https' => array(
				'full'    => "cms.audith.net",
				'parsed'  => array( 'prefix' => "cms.audith" , 'tld' => "net" )
			),
	);


# SQL

$CONFIG['sql']['debug']                  = 2;
$CONFIG['sql']['pconnect']               = TRUE;
$CONFIG['sql']['protocol']               = "tcp";
$CONFIG['sql']['driver']                 = "mysqli";
$CONFIG['sql']['ssl']                    = FALSE;
$CONFIG['sql']['table_prefix']           = "";
$CONFIG['sql']['host']                   = "localhost";
$CONFIG['sql']['port']                   = 3306;
$CONFIG['sql']['dbname']                 = "audith";
$CONFIG['sql']['user']                   = "root";
$CONFIG['sql']['passwd']                 = "";


# TEMPLATE ENGINE

$CONFIG['display']['cache_lifetime']     = 2592000;                       // = 86400 * 30
$CONFIG['display']['debugging']          = FALSE;


# CACHE ABSTRACTION

$CONFIG['performance']['cache']          = array(
		'_method'             => "memcache",                              // diskcache, memcache, memcached
		'diskcache'           => array(
				'cache_path'           => "",                             // Cache dir for Disk-cache, relative to PATH_ROOT_VHOST, leaving empty defaults to "/cache"
			),
		'memcache'            => array(
				'connection_pool'      => array(
						array( "localhost" , 11211 ),
					),
			),
	);

return $CONFIG;

?>