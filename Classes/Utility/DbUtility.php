<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PDO
 */
use PDO;

/**
 * Php
 */
use Exception;

class DbUtility
{

	/**
	 * Common db utilities
	 * =================================================================================================================
	 */

	/**
	 * Writes into table sys_log especially for indexer entries
	 * @see https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/ApiOverview/SystemLog/Index.html
	 * @param string $title
	 * @param int|string $numberOfRecords
	 * @param string|null $details
	 * @param string|null $logData
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function saveIndexerResultInSysLog(string $title, $numberOfRecords , string $details= '' , $logData = '' )
	{
		if(empty($title)){
			throw new Exception('Write indexer result to sys_log: No title given');
		}
		if(!is_integer($numberOfRecords) && $numberOfRecords == ''){
			throw new Exception('Write indexer result to sys_log: No number of records given' . $details );
		}
		$record = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => $title . " " . $details,
			'tstamp' => time(),
			'type' => 1,
			'message' => 'Inserted / updated ' . $numberOfRecords . ' entries' . $logData ,
			'log_data' => $logData ,
		];
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('sys_log')->createQueryBuilder();
		$queryBuilder->insert('sys_log')->values($record)->execute();
	}

	/**
	 * Returns a tree list of pages starting with the $startPageUid
	 * @param int $startPageUid
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getTreeList(int $startPageUid): string
	{
		$depth = 10;
		$queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
		return $queryGenerator->getTreeList($startPageUid, $depth, 0, 1);
	}

	/**
	 * Gets all records from a given table
	 * @param string $table
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getAllRecordsFromTable(string $table): ?array
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable($table)->createQueryBuilder();

		try{
			$records = $queryBuilder
				->select('*')
				->from($table)
				->execute()
				->fetchAllAssociative()
			;
		}catch(DoctrineDBALDriverException $e){
			return null;
		}

		if (!count($records) > 0){
			return null;
		}

		return $records;

	}

	/**
	 * Get the sys_file.uid by a given tx_maritelearning_domain_model_download.uid
	 * @param string|int $uid
	 * @return int|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getSysFileUidByMaritElearningDocumentUid($uid): ?int
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('sys_file')->createQueryBuilder();
		$expr = $queryBuilder->expr();

		$queryBuilder
			->select('sf.uid')
			->from('sys_file','sf')
			->leftJoin('sf','sys_file_reference','sfr','sf.uid = sfr.uid_local')
			->where($expr->eq('sf.missing',0))
			->andWhere($expr->eq('sfr.fieldname', $queryBuilder->createNamedParameter('tx_maritelearning_domain_model_download_download')))
			->andWhere($expr->eq('sfr.uid_foreign', $queryBuilder->createNamedParameter(intval($uid),PDO::PARAM_INT))
		);

		try{
			$sysFileUid = $queryBuilder->execute()->fetchOne();
		}catch(DoctrineDBALDriverException $e){
			return null;
		}

		if(empty($sysFileUid)){
			return null;
		}

		return (int)$sysFileUid;

	}

	/**
	 * Get the indexer type by a given indexer config uid (table tx_kesearch_indexerconfig)
	 * @param int|string $uid
	 * @return mixed|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getIndexerTypeByIndexerConfigUid($uid)
	{
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_indexerconfig');

		try{
			$indexerConfig = $queryBuilder
				->select('type')
				->from('tx_kesearch_indexerconfig')
				->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(intval($uid),PDO::PARAM_INT)))
				->execute()
				->fetchAssociative()
			;
		}catch(DoctrineDBALDriverException $e){
			return null;
		}

		if(empty($indexerConfig)){
			return null;
		}

		return $indexerConfig['type'];

	}

	/**
	 * Get the latest tstamp by a given indexer type
	 * @param string $indexerType
	 * @return mixed|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getLatestTstampByIndexerType(string $indexerType)
	{
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

		try{
			$result = $queryBuilder
				->select('tstamp')
				->from('tx_kesearch_index')
				->where($queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($indexerType)))
				->from('tx_kesearch_index')
				->orderBy('tstamp','DESC')
				->execute()->fetchOne()
			;
		}catch(DoctrineDBALDriverException $e){
			return null;
		}

		if(empty($result)){
			return null;
		}

		return $result;

	}

    /**
     * Get the latest tstamp by a given indexer type
     * @param string $indexerType
     * @return mixed|null
     * @author Peter Benke <pbenke@allplan.com>
     */
    public static function getLatestSortdateByIndexerType(string $indexerType)
    {
        $connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

        try{
            $result = $queryBuilder
                ->select('sortdate' )
                ->from('tx_kesearch_index')
                ->where($queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter($indexerType)))
                ->from('tx_kesearch_index')
                ->orderBy('sortdate','DESC')
                ->execute()->fetchOne()
            ;
        }catch(DoctrineDBALDriverException $e){
            return null;
        }

        if(empty($result)){
            return null;
        }

        return $result;

    }


	/**
	 * Utilities for Forum indexer
	 * =================================================================================================================
	 */

	/**
	 * Get the various forum indexer types as a comma separated list, wrapped in ' for sql queries
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumIndexerTypesForSql(): string
	{
		$mmForumIndexer = IndexerUtility::getForumIndexerInstance();
		return "'" . $mmForumIndexer::FORUM_INDEXER_TYPE_DEFAULT . "','". $mmForumIndexer::FORUM_INDEXER_TYPE_SP . "','" . $mmForumIndexer::FORUM_INDEXER_TYPE_LOCKED . "'";
	}

	/**
	 * Get the various forum indexer types as a comma separated list, wrapped in ' for sql queries
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumIndexerStoragePidsForSql(): string
	{
		$mmForumIndexer = IndexerUtility::getForumIndexerInstance();
		return "'" . $mmForumIndexer::FORUM_INDEXER_STORAGE_PID_EN . "','". $mmForumIndexer::FORUM_INDEXER_STORAGE_PID_DACH . "','" . $mmForumIndexer::FORUM_INDEXER_STORAGE_PID_OTHERS . "'";
	}

    /**
     * Get the various forum indexer types as a comma separated list, wrapped in ' for sql queries
     * @return string
     * @author Peter Benke <pbenke@allplan.com>
     */
    public static function getFaqIndexerTypesForSql(): string
    {

        return "'" . FaqUtility::FAQ_INDEXER_TYPE_DEFAULT  . "','". FaqUtility::FAQ_INDEXER_TYPE_SP . "','" .FaqUtility::FAQ_INDEXER_TYPE_BETA .  "','" . FaqUtility::FAQ_INDEXER_TYPE_NEM .  "','" . FaqUtility::FAQ_INDEXER_TYPE_LOCKED  .   "' "  ;
    }

    /**
     * Get the various forum indexer types as a comma separated list, wrapped in ' for sql queries
     * @return string
     * @author Peter Benke <pbenke@allplan.com>
     * @author Jörg Velletti <jvelletti@allplan.com>
     */
    public static function getFaqIndexerStoragePidsForSql(): string
    {
        return "'" .  FaqUtility::FAQ_INDEXER_STORAGE_PID_DACH . "','".  FaqUtility::FAQ_INDEXER_STORAGE_PID_EN . "','" .  FaqUtility::FAQ_INDEXER_STORAGE_PID_FR . "'";
    }


    /**
     * Get the latest tstamp by a given indexer type
     * @param string $indexerType
     * @return mixed|null
     * @author Peter Benke <pbenke@allplan.com>
     */
    public static function getLatestSortdateAndOrigUidByIndexerType(string $indexerType)
    {
        $connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

        try{
            $result = $queryBuilder
                ->select('sortdate' , 'orig_uid')
                ->from('tx_kesearch_index')
                ->where($queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter($indexerType)))
                ->from('tx_kesearch_index')
                ->orderBy('sortdate','DESC')
                ->addOrderBy( 'orig_uid' , 'DESC' )
                ->execute()->fetchAssociative()
            ;
        }catch(DoctrineDBALDriverException $e){
            return null;
        }

        if(empty($result)){
            return null;
        }

        return $result;

    }


	/**
	 * Get the type and the fe_group for a forum index entry by a given forum uid
	 * (tx_kesearch_index.type, tx_kesearch_index.fe_group)
	 * Returns an array like:
	 * [
	 *     'type' => ...,
	 *     'fe_group' => ...,
	 * ]
	 * @param int|string $forumUid
	 * @return array
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumIndexerTypeAndFeGroupByForumUid($forumUid): array
	{

		$mmForumIndexer = IndexerUtility::getForumIndexerInstance();
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_access');

		// Get the various access levels for the given forum
		$queryBuilder
			->select('login_level', 'affected_group')
			->from('tx_mmforum_domain_model_forum_access')
			->where($queryBuilder->expr()->eq('operation', $queryBuilder->createNamedParameter('read')))
			->andWhere($queryBuilder->expr()->eq('forum', $queryBuilder->createNamedParameter(intval($forumUid),PDO::PARAM_INT)))
			->orderBy('affected_group','DESC')
		;

		try{
			$result = $queryBuilder->execute()->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
			$result = null;
		}

		/**
		 * Login levels:
		 * @see /typo3conf/ext/mm_forum/Classes/Domain/Model/Forum/Access.php
		 * We do not use this class here to avoid possible problems on www, where we do not have the extension mm_forum
		 * 0: Everyone
		 * 1: Any user, which is logged in
		 * 2: A specific user group
		 *
		 * We want to show all entries in index except these, which are visible only for Allplan employees or similar
		 * => so fe_group will only be filled, if type is FORUM_INDEXER_TYPE_LOCKED and fe_groups are set (except 1,3)
		 */

		// default
		$type = $mmForumIndexer::FORUM_INDEXER_TYPE_LOCKED;
		$feGroups = [];
		$feGroup = '';

		foreach($result as $access){

			// Any user and any logged-in user
			if(in_array($access['login_level'], [0,1])){

				$type = $mmForumIndexer::FORUM_INDEXER_TYPE_DEFAULT;

			}else{

				// fe_user group 'ForumUser'
				if($access['affected_group'] == 1){
					$type = $mmForumIndexer::FORUM_INDEXER_TYPE_DEFAULT;
				}

				// fe_user group 'SP user'
				if($access['affected_group'] == 3){
					$type = $mmForumIndexer::FORUM_INDEXER_TYPE_SP;
				}

				$feGroups[] = $access['affected_group'];

			}
		}

		// Build a comma separated list of fe_user groups, if we have any
		if($type == $mmForumIndexer::FORUM_INDEXER_TYPE_LOCKED && count($feGroups) > 0){
			$feGroup = implode(',' , $feGroups);
		}

		return [
			'type' => $type,
			'fe_group' => $feGroup,
		];

	}

	/**
	 * Get the tags added to a topic as a space separated string
	 * @param int|string $topicUid
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumTopicTagsByTopicUid($topicUid): string
	{
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_tag');
		$result = $queryBuilder
			->select('t.name')
			->from('tx_mmforum_domain_model_forum_tag_topic','mm')
			->join(
				'mm',
				'tx_mmforum_domain_model_forum_tag',
				't',
				$queryBuilder->expr()->eq('t.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign')))
			->where($queryBuilder->expr()->eq('mm.uid_local', (int)$topicUid))
			->execute()
		;

		$topicTags = '';
		foreach($result as $tagRecord){
			$topicTags .= ' ' . $tagRecord['name'];
		}

		return $topicTags;

	}

	/**
	 * Get all forum posts by a given topic uid
	 * @param int|string $topicUid
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumPostsForTopic($topicUid): ?array
	{
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');
		$queryBuilder
			->select(
				'crdate',
				'tstamp',
				'text'
			)
			->from('tx_mmforum_domain_model_forum_post')
			->where($queryBuilder->expr()->eq('topic', $queryBuilder->createNamedParameter((int)$topicUid,PDO::PARAM_INT)))
			->orderBy('tstamp','DESC')
		;

		try{
			$posts = $queryBuilder->execute()->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
			return null;
		}

		if(empty($posts)){
			return null;
		}

		return $posts;
	}

	/**
	 * Adds the contents from all posts and the tstamp of the latest post to the topic as follows:
	 * - 'post_contents'
	 * - 'post_last_post_tstamp'
	 * @param array|null $topic
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function addForumPostDataToTopic(?array &$topic)
	{

		if(empty($topic)){
			return;
		}

		$posts = self::getForumPostsForTopic($topic['topic_uid']);
		if(empty($posts)){
			return;
		}

		$contents = [];
		foreach($posts as $post){
			$contents[] = preg_replace('#\s+#', ' ', $post['text']); // Multiple spaces, tabs and the rest of linebreaks => to spaces
		}

		$topic['post_contents'] = implode(PHP_EOL, $contents);
		$topic['post_last_post_tstamp'] = $posts[0]['tstamp'];

	}

	/**
	 * Get the main query builder to get the topics (joined with forum)
	 * @return QueryBuilder
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getMainQueryBuilderForForumTopics(): QueryBuilder
	{

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');
		$expr = $queryBuilder->expr();

		$queryBuilder
			->select(
				'forum.uid AS forum_uid',
				'forum.displayed_pid AS forum_displayed_pid',
				'forum.title AS forum_title',
				'forum.sys_language_uid',
				'forum.hidden AS forum_hidden',
				'forum.deleted AS forum_deleted',
				'topic.uid AS topic_uid',
				'topic.subject AS topic_subject',
				'topic.hidden AS topic_hidden',
				'topic.deleted AS topic_deleted',
			)
			->from('tx_mmforum_domain_model_forum_topic','topic')
			->join(
				'topic',
				'tx_mmforum_domain_model_forum_forum',
				'forum',
				$expr->eq('topic.forum', $queryBuilder->quoteIdentifier('forum.uid')))
			->where($expr->eq('topic.hidden', 0)) // could not add HiddenRestriction in queryBuilder...
			->andWhere($expr->eq('forum.hidden', 0))
			->orderBy('topic.uid','DESC')
		;

		return $queryBuilder;

	}

}