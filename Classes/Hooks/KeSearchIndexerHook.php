<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class KeSearchIndexerHook extends BaseKeSearchIndexerHook{

    /**
     * Adds indexers to the TCA of the indexer-configuration.
     * (You can select these ones in the backend by creating a new record "indexer-configuration")
     * @param array $params
     * @param mixed $pObj
     */
    public function registerIndexerConfiguration(&$params, $pObj) {
        $iconPath = 'EXT:allplan_ke_search_extended/Resources/Public/Icons/' ;
        // Events (jv_events)
        // =================================================================================================================================
        $newArray = [
            'Events (jv_events)',
            'jv_events',
            $iconPath . 'indexer-jv_events.gif'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

        // marit lessons
        // =================================================================================================================================
        $newArray = [
            'Allplan Lessions (marit_elearning)',
            'lessions',
            $iconPath . 'indexer-video-lesson.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

        // marit lessons
        // =================================================================================================================================
        $newArray = [
            'Allplan Training Dokumentation (marit_elearning)',
            'documentation',
            $iconPath . 'indexer-pdf-lesson.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);
        // contentserve downloads ..
        // =================================================================================================================================
        $newArray = [
            'Allplan ContentServe downloads',
            'contentserve',
            $iconPath . 'indexer-content-downloads.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);


        // Forum
        // =================================================================================================================================
        $newArray = [
            'Allplan Forum ',
            'allplanforum',
            $iconPath . 'indexer-forum.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

        // Online Help
        // =================================================================================================================================
        $newArray = [
            'Allplan Online Help',
            'onlinehelp',
            $iconPath . 'indexer-allplan-help.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

        // Online Help
        // =================================================================================================================================
        $newArray = [
            'Allplan Support FAQs',
            'supportfaq',
            $iconPath . 'indexer-allplan-faq.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

        // shop.allplan.com
        // =================================================================================================================================
        $newArray = [
            'Allplan Shop',
            'shop',
            $iconPath . 'indexer-allplan-shop.png'
        ];
        $params['items'][] = $newArray;
        unset($newArray);

       // print_r($GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']);

    }


    /**
     * Own indexes, writing the data into tx_kesearch_index
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \Tpwd\KeSearch\Indexer\IndexerRunner $indexerObject reference to the indexer class
     * @return  string output in the backend after indexing
     */
    public function customIndexer(&$indexerConfig, &$indexerObject) {

        // print_r($indexerConfig);

        $content = '';

        // Events (jv_events)
        // =================================================================================================================================

        switch ($indexerConfig['type']) {

            case 'jv_events' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanHelpIndexer $eventIndexer */
                $eventIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\JvEventsIndexer");

                $resCount = $eventIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' events where indexed.</p>';
                break;

            case 'allplanforum' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanHelpIndexer $forumIndexer */
                $forumIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\ForumIndexer");
                $resCount = $forumIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Forum Entries where indexed.</p>';
                break;

            case 'onlinehelp' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanHelpIndexer $helpIndexer */
                $helpIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanHelpIndexer");

                $resCount = $helpIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' help entries where indexed.</p>';
                break;
            case 'supportfaq' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanFaqIndexer $faqIndexer */
                $faqIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanFaqIndexer");

                $resCount = $faqIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' FAQ entries where indexed.</p>';
                break;
            case 'shop' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanShopIndexer $shopIndexer */
                $shopIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanShopIndexer");

                $resCount = $shopIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Shop entries where indexed.</p>';
                break;
            case 'lessions' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanShopIndexer $elearningIndexer */
                $elearningIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanElearningsIndexer");

                $resCount = $elearningIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Elearning Video entries where indexed.</p>';
                break;

            case 'documentation' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanShopIndexer $elearningIndexer */
                $elearningIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanDocumentationsIndexer");

                $resCount = $elearningIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Elearning Documentation entries where indexed.</p>';
                break;
            case 'contentserve' :
                /** @var \Allplan\AllplanKeSearchExtended\Utility\AllplanShopIndexer $elearningIndexer */
                $contentIndexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanContentserveIndexer");

                $resCount = $contentIndexer->main($indexerConfig, $indexerObject);
                $content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Contentserve  entries where indexed.</p>';
                break;
        }


        return $content;

    }

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