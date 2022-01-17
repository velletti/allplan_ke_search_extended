<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class KeSearchIndexerHook extends BaseKeSearchIndexerHook{






    /**
     * Modifies the page content fields
     * @param $fields
     * @param $pObj
     */
    public function modifyPageContentFields(&$fields, $pObj){

        // Add pi_flexform for the allplan content elements
        $fields.= ',pi_flexform';

    }

    /**
     * Modifies the content from a content element
     * @param $bodytext
     * @param $ttContentRow
     * @param $pObj
     */
    public function modifyContentFromContentElement(&$bodytext, &$ttContentRow, $pObj){

        $additionalContent = $this->getAdditionalContentFromFlexform($ttContentRow);
        if(!empty($additionalContent)){
            $additionalContent.='...';
        }

        $bodytext = $additionalContent . $bodytext;

    }

}