<?php
phpinfo();

$a = <<<TEST
a:1:{i:1;a:4:{s:8:"question";s:174:"Which evenings are you available to raid, as Raid Core member? Choose 4-days minimum, vote if you want to join Raid Core, not Reserve. Please consider upcoming Winter season.";s:5:"multi";i:1;s:6:"choice";a:7:{i:1;s:39:"Monday Evening - after 7pm Server time.";i:2;s:40:"Tuesday Evening - after 7pm Server time.";i:3;s:42:"Wednesday Evening - after 7pm Server time.";i:4;s:41:"Thursday Evening - after 7pm Server time.";i:5;s:39:"Friday Evening - after 7pm Server time.";i:6;s:41:"Saturday Evening - after 7pm Server time.";i:7;s:39:"Sunday Evening - after 7pm Server time.";}s:5:"votes";a:7:{i:1;i:12;i:2;i:13;i:3;i:12;i:4;i:18;i:5;i:10;i:6;i:15;i:7;i:15;}}}
TEST;

$a = unserialize($a);

var_dump($a);
exit;










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
