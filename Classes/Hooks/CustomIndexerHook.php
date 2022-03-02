<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
# Todo: Sort
use Allplan\AllplanKeSearchExtended\Indexer\Www\JvEventsIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\Miscellaneous\AllplanOnlineHelpIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\Connect\MaritElearningDocumentsIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\Connect\MaritElearningLessonsIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;

use Allplan\AllplanKeSearchExtended\Indexer\ContentServeIndexer;

use Allplan\AllplanKeSearchExtended\Indexer\AllplanFaqIndexer;

use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanShopIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\ForumIndexer;


/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * GeneralUtility
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Php
 */
use Exception;

class CustomIndexerHook
{

	/**
	 * Own indexes, writing the data into tx_kesearch_index
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param IndexerRunner $indexerRunner reference to the indexer runner
	 * @return string output in the backend after indexing
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function customIndexer(array &$indexerConfig, IndexerRunner &$indexerRunner): string
	{

		$content = '';

		switch ($indexerConfig['type']){

			/**
			 * Www
			 * =========================================================================================================
			 */
			case 'jv_events':
				$jvEventsIndexer = GeneralUtility::makeInstance(JvEventsIndexer::class, $indexerRunner);
				$resultCount = $jvEventsIndexer->startIndexing();
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resultCount . ' events (EXT:jv_events) where indexed.</p>';
				break;


			/**
			 * Connect
			 * =============================================================================================================
			 */
			case 'marit_elearning_lessons':
				$maritElearningLessonsIndexer = GeneralUtility::makeInstance(MaritElearningLessonsIndexer::class, $indexerRunner);
				$resultCount = $maritElearningLessonsIndexer->startIndexing();
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resultCount . ' Elearning lessons (videos) (EXT:marit_elearning) where indexed.</p>';
				break;

			case 'marit_elearning_documents':
				$maritElearningLessonsIndexer = GeneralUtility::makeInstance(MaritElearningDocumentsIndexer::class, $indexerRunner);
				$resultCount = $maritElearningLessonsIndexer->startIndexing();
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resultCount . ' Elearning documents (EXT:marit_elearning) where indexed.</p>';
				break;

			/**
			 * Miscellaneous
			 * =========================================================================================================
			 */
			case 'allplan_online_help':
				$allplanOnlineHelpIndexer = GeneralUtility::makeInstance(AllplanOnlineHelpIndexer::class, $indexerRunner);
				$resultCount = $allplanOnlineHelpIndexer->startIndexing();
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resultCount . ' Allplan Online Help entries where indexed.</p>';
				break;


			/*
			// Todo: spelling
			case 'allplanforum':
				$forumIndexer = GeneralUtility::makeInstance(ForumIndexer::class);
				$resCount = $forumIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' forum entries where indexed.</p>';
				break;
			*/

/*
			// Todo: spelling
			// Todo: Check first, if we are on Connect
			case 'supportfaq':
				$faqIndexer = GeneralUtility::makeInstance(AllplanFaqIndexer::class);
				$resCount = $faqIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' FAQ entries where indexed.</p>';
				break;

			// Todo: spelling
			case 'shop':
				$shopIndexer = GeneralUtility::makeInstance(AllplanShopIndexer::class);
				$resCount = $shopIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Shop entries where indexed.</p>';
				break;


			// Todo
			case 'documentation':
				$elearningIndexer = GeneralUtility::makeInstance(AllplanDocumentationsIndexer::class);
				$resCount = $elearningIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Elearning documentation entries where indexed.</p>';
				break;

			// Todo: spelling
			case 'contentserve':
				$contentIndexer = GeneralUtility::makeInstance(ContentServeIndexer::class);
				$resCount = $contentIndexer->main($indexerConfig, $indexerObject);
				$content = '<p><strong>Indexer "' . $indexerConfig['title'] . '"</strong>:<br>' . $resCount . ' Contentserve entries where indexed.</p>';
				break;
*/
		}

		return $content;

	}

}