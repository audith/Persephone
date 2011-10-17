<?php
//----------
// Init
//----------

require_once( "./init.php" );

//--------------
// API Source
//--------------

require_once( PATH_SOURCES . "/kernel/registry.php"                );

//-------------
// API Init
//-------------

$Registry = Registry::init();
$Registry->Display->do_display();
exit();