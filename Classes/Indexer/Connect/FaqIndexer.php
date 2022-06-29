<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerBase;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerInterface;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FaqUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */

use Allplan\Library\Salesforce\Model\Knowledgebase;
use Allplan\Library\Salesforce\Service\KnowledgeBases;
use Allplan\Library\Salesforce\Utility\DateUtility;
use Allplan\NemMysupport\Utility\GetConfig;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Php
 */
use Exception;


/**
 * Indexer for forum (EXT:mm_forum)
 */
class FaqIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * For Development
	 * Limit the number of topics to a number for faster development
	 * @var int|null
	 */
	const FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX = 5 ;

	/**
	 * Forum indexer types
	 */
	const FAQ_INDEXER_TYPE_DEFAULT = 'supportfaq';


    const FAQ_DEFAULT_TAG = "#allplanfaq#" ;



	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Todo delete unneeded functions in utilities

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;

		$latest = DbUtility::getLatestSortdateByIndexerType( self::FAQ_INDEXER_TYPE_DEFAULT . "%" ) ;

        $knowledgeBases = new KnowledgeBases( GetConfig::read() ) ;


		$result = $knowledgeBases->getKnowledgeBasesModifiedAfterDate( DateUtility::convertTimestampToSalesforceDate($latest) , self::FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX ) ;
		$count = 0;
		if($result){
            /** @var Knowledgebase  $recordObj */
            foreach ($result as $recordObj ){

                $record = FaqUtility::getRecordAsArray($recordObj , self::FAQ_DEFAULT_TAG ) ;

                var_dump($record);
                die;

				// Write record to index
				if($this->storeInKeSearchIndex($record, $indexerRunner, $indexerConfig)){
					$count++;
				}

			}
		}

		// Write to sys_log
		DbUtility::saveIndexerResultInSysLog(
			'Indexer: Forum (EXT:mm_forum)',
			$count
		);

		return $count;

	}

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		// Set the fields
		$pid = IndexerUtility::getStoragePid($indexerRunner, $indexerConfig, (int)$record['sys_language_uid']); // storage pid, where the indexed data should be stored
		$title = FormatUtility::cleanStringForIndex($record['topic_subject']); // title in the result list
		$type = $typeAndFeGroup['type']; // content type (to differ in frontend (css class))
		$targetPid = 'https://connect.allplan.com/index.php?id=' . $record['forum_displayed_pid'] . '&' . implode('&', [
				'tx_mmforum_pi1[forum]=' . intval($record['forum_uid']),
				'tx_mmforum_pi1[topic]=' . intval($record['topic_uid']),
				'tx_mmforum_pi1[action]=show',
				'tx_mmforum_pi1[controller]=Topic',
				'L=' . intval($record['sys_language_uid']),
			]); // target pid for the detail link / external url
		$content = FormatUtility::buildContentForIndex([
			$record['forum_title'],
			$record['topic_subject'],
			$record['post_contents'],
			DbUtility::getForumTopicTagsByTopicUid($record['topic_uid'])
		]);
		$tags = '#forum#'; // tags
		$params = ''; // additional parameters for the link in frontend
		$abstract = ''; // not used here
		// $language = see above...
		$startTime = 0;
		$endTime = 0;
		$feGroup = $typeAndFeGroup['fe_group'];
		$debugOnly = false;
		$additionalFields = [
			'orig_uid' => $record['topic_uid'],
			// We take the column sortdate to store the original tstamp of the post
			'sortdate' => intval($record['post_last_post_tstamp']),
			'tx_allplan_ke_search_extended_server_name' => EnvironmentUtility::getServerName(),
		];

		// Call the function from ke_search
		return $indexerRunner->storeInIndex(
			$pid,
			$title,
			$type,
			$targetPid,
			$content,
			$tags,
			$params,
			$abstract,
			$language,
			$startTime,
			$endTime,
			$feGroup,
			$debugOnly,
			$additionalFields
		);

	}

}