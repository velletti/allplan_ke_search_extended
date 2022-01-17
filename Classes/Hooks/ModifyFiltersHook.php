<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Filters;

class ModifyFiltersHook
{

	/**
	 * Modifies the search filters
	 * @param array $filters
	 * @param Filters $pObj
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyFilters(array &$filters, Filters $pObj)
	{
		// Not in use at the moment
	}

}