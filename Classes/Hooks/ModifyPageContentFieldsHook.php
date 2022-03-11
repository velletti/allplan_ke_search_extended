<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Types\Page;

class ModifyPageContentFieldsHook
{

	/**
	 * Modify the page content fields (own columns in tt_content)
	 * @param string $fields
	 * @param Page $page
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyPageContentFields(string &$fields, Page $page)
	{

		// Add the following tt_content fields for indexing
		$fields.= ',pi_flexform';

	}

}