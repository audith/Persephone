<?php
phpinfo();

$tmp = array(
	// array( 'file' => "/highslide.css", 'params' => "" , 'type' => "css" , 'scope' => "global" ),
	array( 'file' => "/jquery-ui.css", 'params' => "" , 'type' => "css"  , 'scope' => "global" ),
	// array( 'file' => "/jquery-tablesorter-blue.css", 'params' => "" , 'type' => "css"  , 'scope' => "local" ),
	array( 'file' => "/style.css", 'params' => "" , 'type' => "css"  , 'scope' => "local" ),

	array( 'file' => "/jquery.js", 'params' => "" , 'type' => "js" , 'scope' => "global" ),
	array( 'file' => "/jquery-ui.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/jquery-ui-i18n.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/jquery.livequery.js", 'params' => "" , 'type' => "js" , 'scope' => "global" ),
	// array( 'file' => "/jquery.metadata.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	array( 'file' => "/jquery.cookie.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/jquery.tablesorter.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/jquery.tablesorter.pager.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/tiny_mce/jquery.tinymce.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	// array( 'file' => "/highslide.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	array( 'file' => "/global.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
	array( 'file' => "/global.js", 'params' => "" , 'type' => "js"  , 'scope' => "local" ),
);

print serialize($tmp);

/*
$im = new Imagick();
$im->newPseudoImage(1000, 1000, "magick:rose");
$im->setImageFormat("png");
$im->roundCorners(12,8);
$type=$im->getFormat();
header("Content-type: $type");
echo $im->getimageblob();
*/

?>