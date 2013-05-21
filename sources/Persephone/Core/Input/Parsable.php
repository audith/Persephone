<?php
namespace Persephone\Core\Input;

/**
 * Parsable - interface to make sure a class is parse()'able
 *
 * @package  Audith CMS codename Persephone
 * @author   Shahriyar Imanov <shehi@imanov.name>
 * @author   Oleku Konko <https://github.com/olekukonko/>
 * @version  1.0
 */
interface Parsable
{
	/**
	 * @param       mixed           $key        Index/offset etc to parse
	 * @param       mixed           $mixed      Value to parse
	 *
	 * @return      mixed|null
	 */
	public function parse ( $key, $mixed );
}