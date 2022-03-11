<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Pluginbase;

class AdditionalResultMarkerHook
{

	/**
	 * Hook for additional markers in result row
	 * @param array $tempMarkerArray
	 * @param array $row
	 * @param Pluginbase $pluginBase
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function additionalResultMarker(array &$tempMarkerArray, array $row, Pluginbase $pluginBase)
	{

		// Not in use at the moment

	}

}