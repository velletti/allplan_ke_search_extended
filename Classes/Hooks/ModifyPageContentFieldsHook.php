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
	 * @param Page $pObj
	 */
	public function modifyPageContentFields(string &$fields, Page $pObj){

		$fields.= ',pi_flexform';

	}

}