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
	 * @param string $bodytext
	 * @param array $ttContentRow
	 * @param Page $pObj
	 */
	public function modifyContentFromContentElement(string &$bodytext, array &$ttContentRow, Page $pObj)
	{

		$additionalContent = FlexFormUtility::getAdditionalContentFromFlexform($ttContentRow);
		if(!empty($additionalContent)){
			$additionalContent.= '...';
		}

		$bodytext = $additionalContent . $bodytext;

	}

}