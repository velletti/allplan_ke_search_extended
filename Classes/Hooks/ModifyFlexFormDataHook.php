<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * TYPO3
 */
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ModifyFlexFormDataHook
{

	/**
	 * Modifies all returned values of the ke_search flexform
	 * @param array $conf
	 * @param ContentObjectRenderer|null $pObj
	 * @param array $piVars
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyFlexFormData(array $conf, ?ContentObjectRenderer $pObj, array $piVars)
	{
		// Not in use at the moment
	}

}