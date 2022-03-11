<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\FlexFormUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Types\Page;

class ModifyContentFromContentElementHook
{

	/**
	 * Modifies the content from a content element
	 * (used in the standard page indexer from ke_search)
	 * @param string $bodytext
	 * @param array $ttContentRow
	 * @param Page $page
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyContentFromContentElement(string &$bodytext, array &$ttContentRow, Page $page)
	{

		$additionalContent = FlexFormUtility::getAdditionalContentFromFlexform($ttContentRow);
		if(!empty($additionalContent)){
			$additionalContent.= '...';
		}

		$bodytext = $additionalContent . $bodytext;

	}

}