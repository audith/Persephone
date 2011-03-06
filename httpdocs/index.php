<?php
//----------
// Init
//----------

require_once( "./init.php" );

//------------
// Sources
//------------

require_once( PATH_SOURCES . "/kernel/api.php"                );
require_once( PATH_SOURCES . "/kernel/db.php"                 );
require_once( PATH_SOURCES . "/kernel/input.php"              );
require_once( PATH_SOURCES . "/kernel/cache.php"              );
require_once( PATH_SOURCES . "/kernel/session.php"            );
require_once( PATH_SOURCES . "/kernel/modules.php"            );
require_once( PATH_SOURCES . "/kernel/display.php"            );
require_once( PATH_SOURCES . "/kernel/ips_converge.php"       );

//---------------
// Continue...
//---------------

$API = API::init();
$API->Display->do_display();
exit();