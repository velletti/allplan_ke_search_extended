<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Plugins\ResultlistPlugin;
use Tpwd\KeSearch\Plugins\SearchboxPlugin;

class InitialsHook
{

	/**
	 * Change any variable while initializing the plugin
	 * @param ResultlistPlugin|SearchboxPlugin|object $pObj
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function addInitials(&$pObj)
	{
		// Not in use at the moment
	}

}