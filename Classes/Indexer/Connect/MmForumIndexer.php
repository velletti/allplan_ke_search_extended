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
 * Fills the index step by step, because we have > 250.000 forum entries
 * On every run the indexer checks the last indexed forum entry by tx_kesearch_index.crdate and gets the next x entries
 * from forum to index
 */
class MmForumIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * Clean up the index before indexing starts (see more annotation details in IndexerInterface)
	 * Delete all records in index, where posts, topics or forums are deleted
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function cleanUpBeforeIndexing()
	{

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');
		$expr = $queryBuilder->expr();

		// Get all posts, which are deleted or hidden (or the belonging forums or topics)
		$queryBuilder
			->getRestrictions()
			// ->removeByType(DeletedRestriction::class)
			// ->removeByType(HiddenRestriction::class)
			->removeAll()
		;
		$queryBuilder
			->select('post.uid')
			->from('tx_mmforum_domain_model_forum_post','post')
			->join(
				'post',
				'tx_mmforum_domain_model_forum_topic',
				'topic',
				$expr->eq('topic.uid', $queryBuilder->quoteIdentifier('post.topic')))
			->join(
				'topic',
				'tx_mmforum_domain_model_forum_forum',
				'forum',
				$expr->eq('topic.forum', $queryBuilder->quoteIdentifier('forum.uid')))
			->where($expr->eq('post.deleted', 1))
			->orWhere($expr->eq('post.hidden', 1))
			->orWhere($expr->eq('topic.deleted', 1))
			->orWhere($expr->eq('topic.hidden', 1))
			->orWhere($expr->eq('forum.deleted', 1))
			->orWhere($expr->eq('forum.hidden', 1))
		;

		try{
			$result = $queryBuilder->execute()->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
			return;
		}

		$uids = [];
		foreach($result as $record){
			$uids[] = $record['uid'];
		}

		unset($queryBuilder);

		// Now delete from index
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');
		$queryBuilder
			->delete('tx_kesearch_index')
			->where($expr->in('orig_uid', $uids))
			->andWhere($expr->in('type', DbUtility::getForumIndexerTypesForSql()))
			->execute()
		;

		print_r([
			'$queryBuilder->getSQL()' => $queryBuilder->getSQL(),
		]);

	}

	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Todo: What to do, if all entries are indexed?
		// Todo: What to do with multiple posts on one topic?
		// Todo cleanup Posts from [quote="DanielP"] and similar

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;

		$schedulerTaskConfig = $indexerRunner->getTaskConfiguration();
		$maxResults = $schedulerTaskConfig->getNrOfIndexRecordsOnOneRun();

		// Forum indexing only step by step
		if(empty($maxResults)){
			throw new Exception('Number of records, which should be indexed on one run, has to be set.');
		}

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');
		$expr = $queryBuilder->expr();

		// Get the posts, which are older than the posts, we have in index already
		$queryBuilder
			->select(
				'forum.uid AS forum_uid',
				'forum.displayed_pid',
				'forum.title AS forum_title',
				'forum.sys_language_uid',
				'forum.deleted AS forum_deleted',
				'forum.hidden AS forum_hidden',
				'topic.subject AS topic_subject',
				'topic.deleted AS topic_deleted',
				'topic.hidden AS topic_hidden',
				'post.uid AS post_uid',
				'post.crdate',
				'post.tstamp',
				'post.topic AS topic_uid',
				'post.text AS post_text',
				'post.deleted AS post_deleted',
				'post.hidden AS post_hidden',
				'users.username'
			)
			->from('tx_mmforum_domain_model_forum_post','post')
			->join(
				'post',
				'tx_mmforum_domain_model_forum_topic',
				'topic',
				$expr->eq('topic.uid', $queryBuilder->quoteIdentifier('post.topic')))
			->join(
				'topic',
				'tx_mmforum_domain_model_forum_forum',
				'forum',
				$expr->eq('topic.forum', $queryBuilder->quoteIdentifier('forum.uid')))
			->join(
				'post',
				'fe_users',
				'users',
				$expr->eq('post.author', $queryBuilder->quoteIdentifier('users.uid')))
			->orderBy('tstamp','DESC')
			->setMaxResults($maxResults)
		;

		// Consider the oldest already indexed forum entry (this condition matches only, if we have entries already)
		if(!empty($oldestCreatedForumIndexTsStamp = DbUtility::getTsStampOfOldestCreatedForumIndexEntry())){
			// Add some seconds to avoid loosing posts, if there is an overlap between two index cycles, because of posts at the same second
			$queryBuilder->where($queryBuilder->expr()->lt('post.tstamp', ($oldestCreatedForumIndexTsStamp + 2)));
		}
print_r($queryBuilder->getSQL());
		$result = $queryBuilder->execute();
		$count = 0;

		if($result){
			while(($record = $result->fetchAssociative())){
print_r($record);
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

		$typeAndFeGroup = DbUtility::getForumIndexerTypeAndFeGroupByForumUid($record['subject']);


		switch((int)$record['sys_language_uid']){

			// DACH
			case 1:
				$pid = 5003;
				$language = -1;
				break;

			// en
			case 0:
				$pid = 5004;
				$language = 0;
				break;

			// other languages
			default :
				$pid = 5005;
				$language = (int)$record['sys_language_uid'];

		}

		// Set the fields
		// $pid = see above...
		$title = FormatUtility::cleanStringForIndex($record['topic_subject']); // title in the result list
		$type = $typeAndFeGroup['type']; // content type (to differ in frontend (css class))
		$targetPid = 'https://connect.allplan.com/index.php?id=' . $record['displayed_pid'] . '&' . implode('&', [
				'tx_mmforum_pi1[forum]=' . intval($record['forum_uid']),
				'tx_mmforum_pi1[topic]=' . intval($record['topic_uid']),
				'tx_mmforum_pi1[action]=show',
				'tx_mmforum_pi1[controller]=Topic',
				'L=' . intval($record['sys_language_uid']),
			]); // target pid for the detail link / external url
		$content = FormatUtility::buildContentForIndex([
			$record['forum_title'],
			$record['topic_subject'],
			$record['post_text'],
			DbUtility::getForumTopicTagsByTopicUid($record['topic_uid']),
			$record['username'],
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
			'orig_uid' => $record['post_uid'],
			// We take the column sortdate to store the original tstamp of the post
			'sortdate' => intval($record['tstamp']),
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