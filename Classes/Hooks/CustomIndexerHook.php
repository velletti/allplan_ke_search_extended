<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanContentserveIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanDocumentationsIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanElearningsIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanFaqIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanHelpIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanShopIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\ForumIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\JvEventsIndexer;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * GeneralUtility
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomIndexerHook
{

	/**
	 * Own indexes, writing the data into tx_kesearch_index
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return string output in the backend after indexing
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function customIndexer(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): string
	{

		$content = '';

		switch ($indexerConfig['type']){

			case 'jv_events':
				$eventIndexer = GeneralUtility::makeInstance(JvEventsIndexer::class);
				$resCount = $eventIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' events where indexed.</p>';
				break;

			case 'allplanforum':
				$forumIndexer = GeneralUtility::makeInstance(ForumIndexer::class);
				$resCount = $forumIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' forum Entries where indexed.</p>';
				break;

			case 'onlinehelp':
				$helpIndexer = GeneralUtility::makeInstance(AllplanHelpIndexer::class);
				$resCount = $helpIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Allplan help entries where indexed.</p>';
				break;

			// Todo: Check first, if we are on Connect
			case 'supportfaq':
				$faqIndexer = GeneralUtility::makeInstance(AllplanFaqIndexer::class);
				$resCount = $faqIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' FAQ entries where indexed.</p>';
				break;

			case 'shop':
				$shopIndexer = GeneralUtility::makeInstance(AllplanShopIndexer::class);
				$resCount = $shopIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Shop entries where indexed.</p>';
				break;

			// Todo check spelling
			case 'lessions':
				$elearningIndexer = GeneralUtility::makeInstance(AllplanElearningsIndexer::class);
				$resCount = $elearningIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Elearning Video entries where indexed.</p>';
				break;

			case 'documentation':
				$elearningIndexer = GeneralUtility::makeInstance(AllplanDocumentationsIndexer::class);
				$resCount = $elearningIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Elearning Documentation entries where indexed.</p>';
				break;

			case 'contentserve':
				$contentIndexer = GeneralUtility::makeInstance(AllplanContentserveIndexer::class);
				$resCount = $contentIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Contentserve entries where indexed.</p>';
				break;
		}

		return $content;

	}

}