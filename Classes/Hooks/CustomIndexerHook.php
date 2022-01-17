<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


class CustomIndexerHook
{

	/**
	 * Own indexes, writing the data into tx_kesearch_index
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param \Tpwd\KeSearch\Indexer\IndexerRunner $indexerObject reference to the indexer class
	 * @return  string output in the backend after indexing
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function customIndexer(&$indexerConfig, &$indexerObject) {

		// print_r($indexerConfig);

		$content = '';

		switch ($indexerConfig['type']){

			case 'jv_events':
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

}