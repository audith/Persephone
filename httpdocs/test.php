<?php
//$a = "_SERVER"; var_dump($$a); var_dump($_SERVER); exit;
class A
{
	function __construct ()
	{
		$a = "_SERVER"; $x = $$a; var_dump($x); var_dump($_SERVER); exit;
	}
}

new A();


/*
$a = 'a:6:{i:0;a:4:{s:4:"file";s:14:"/jquery-ui.css";s:6:"params";s:0:"";s:4:"type";s:3:"css";s:5:"scope";s:6:"global";}i:1;a:4:{s:4:"file";s:10:"/style.css";s:6:"params";s:0:"";s:4:"type";s:3:"css";s:5:"scope";s:5:"local";}i:2;a:4:{s:4:"file";s:10:"/jquery.js";s:6:"params";s:0:"";s:4:"type";s:2:"js";s:5:"scope";s:6:"global";}i:3;a:4:{s:4:"file";s:13:"/jquery-ui.js";s:6:"params";s:0:"";s:4:"type";s:2:"js";s:5:"scope";s:6:"global";}i:4;a:4:{s:4:"file";s:10:"/global.js";s:6:"params";s:0:"";s:4:"type";s:2:"js";s:5:"scope";s:6:"global";}i:5;a:4:{s:4:"file";s:10:"/global.js";s:6:"params";s:0:"";s:4:"type";s:2:"js";s:5:"scope";s:5:"local";}}';
print_r(unserialize($a));

echo serialize(
	array(
		array( 'file' => "/jquery-ui.css", 'params' => "" , 'type' => "css"  , 'scope' => "global" ),
		array( 'file' => "/style.css", 'params' => "" , 'type' => "css"  , 'scope' => "local" ),
		array( 'file' => "/jquery.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
		array( 'file' => "/jquery-ui.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
		array( 'file' => "/global.js", 'params' => "" , 'type' => "js"  , 'scope' => "global" ),
		array( 'file' => "/global.js", 'params' => "" , 'type' => "js"  , 'scope' => "local" ),
	)
);
*/

?>