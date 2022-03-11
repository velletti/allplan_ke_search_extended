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
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */
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
class MmForumIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * Todo: Reset to null
	 * For Development
	 * Limit the number of topics to a number for faster development
	 * @var int|null
	 */
	// const NR_OF_TOPICS_TO_INDEX = null;
	const FORUM_INDEXER_NR_OF_TOPICS_TO_INDEX = 500;

	/**
	 * Forum indexer types
	 */
	const FORUM_INDEXER_TYPE_DEFAULT = 'mm_forum';
	const FORUM_INDEXER_TYPE_SP = 'mm_forum_sp';
	const FORUM_INDEXER_TYPE_LOCKED = 'mm_forum_locked';

	/**
	 * Forum indexer storage pids
	 */
	const FORUM_INDEXER_STORAGE_PID_EN = 5004;
	const FORUM_INDEXER_STORAGE_PID_DACH = 5003;
	const FORUM_INDEXER_STORAGE_PID_OTHERS = 5005;


	/**
	 * Clean up the index before indexing starts (see more annotation details in IndexerInterface)
	 * Delete all topics in index, which are deleted or belong to a deleted forum
	 * (all other topics will be updated, so we do not have to care for changed posts inside a topic here)
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function cleanUpBeforeIndexing()
	{
	}

	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Todo Remove nrOfIndexRecordsOnOneRun at all places
		// Todo delete unneeded functions in utilities

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;

		// Get the main topics query builder
		$queryBuilder = DbUtility::getMainQueryBuilderForForumTopics();

		// Could not add HiddenRestrictions in queryBuilder => so add it manually
		$queryBuilder
			->where($queryBuilder->expr()->eq('topic.hidden', 0))
			->andWhere($queryBuilder->expr()->eq('forum.hidden', 0))
		;

		// Limited results for faster development (see class variable above)
		if(!is_null(self::FORUM_INDEXER_NR_OF_TOPICS_TO_INDEX)){
			$queryBuilder->setMaxResults((int)self::FORUM_INDEXER_NR_OF_TOPICS_TO_INDEX);
		}

		// echo $queryBuilder->getSQL() . PHP_EOL;

		$result = $queryBuilder->execute();
		$count = 0;

		if($result){
			while(($record = $result->fetchAssociative())){

				// Add the data of the posts
				DbUtility::addForumPostDataToTopic($record);

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

		$typeAndFeGroup = DbUtility::getForumIndexerTypeAndFeGroupByForumUid($record['forum_uid']);
		switch((int)$record['sys_language_uid']){
			// EN
			case 0:
				$language = 0;
				break;
			// DACH
			case 1:
				$language = -1;
				break;
			// OTHER languages
			default:
				$language = (int)$record['sys_language_uid'];
		}

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