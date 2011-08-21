<?php
//----------
// Init
//----------

require_once( "./init.php" );

//--------------
// API Source
//--------------

require_once( PATH_SOURCES . "/kernel/api.php"                );

//-------------
// API Init
//-------------

$API = API::init();
$API->Display->do_display();
exit();