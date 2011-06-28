<?php

# URLs
$CONFIG['url']['ssl_mode']               = false;
$CONFIG['url']['hostname']               = array(
		'http'  => "shehi.dyndns.org",
		'https' => "shehi.dyndns.org",
	);


# SQL

$CONFIG['sql']['debug']                  = 2;
$CONFIG['sql']['pconnect']               = true;
$CONFIG['sql']['protocol']               = "tcp";
$CONFIG['sql']['driver']                 = "mysqli";
$CONFIG['sql']['ssl']                    = false;
$CONFIG['sql']['table_prefix']           = "";
$CONFIG['sql']['host']                   = "localhost";
$CONFIG['sql']['port']                   = 3306;
$CONFIG['sql']['dbname']                 = "audith";
$CONFIG['sql']['user']                   = "root";
$CONFIG['sql']['passwd']                 = "";


# TEMPLATE ENGINE

$CONFIG['display']['cache_lifetime']     = 2592000;                       // = 86400 * 30
$CONFIG['display']['debugging']          = false;


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