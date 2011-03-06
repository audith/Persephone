<?
if ( ! defined( "INIT_DONE" ) )
{
	print "Improper access! Exiting now...";
	exit();
}

/**
 * MODULES > Data Fields : Alphanum parser
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @version  1.0
**/
class Alphanum extends Data_Fields
{
	public function __construct ()
	{
		$typedef_map = array(
				"string"  =>  array(
						"rendering"  =>  array(
								"input"     =>  array(
										"maxlength"    =>  null
									),
								"dropdown"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									),
								"multiple"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									),
								"textarea"  =>  array(
										"maxlength"    =>  null
									)
							)
					),
				"num"     =>  array(
						"rendering"  =>  array(
								"input"     =>  array(
										"maxlength"    =>  null
									),
								"dropdown"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									),
								"multiple"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									)
							)
					),
				"unum"    =>  array(
						"rendering"  =>  array(
								"input"     =>  array(
										"maxlength"    =>  null
									),
								"dropdown"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									),
								"multiple"  =>  array(
										"extras"       =>  array( "set_of_keys" )
									)
							)
					)
			);
	}
	
	
}

?>