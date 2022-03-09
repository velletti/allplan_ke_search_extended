<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\Connect\MmForumIndexerTypes;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
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
	 * Writes into table sys_log especially for indexer entries
	 * @see https://docs.typo3.org/m/typo3/reference-coreapi/10.4/en-us/ApiOverview/SystemLog/Index.html
	 * @param string $title
	 * @param int|string $numberOfRecords
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function saveIndexerResultInSysLog(string $title, $numberOfRecords)
	{
		if(empty($title)){
			throw new Exception('Write indexer result to sys_log: No title given');
		}
		if($numberOfRecords == ''){
			throw new Exception('Write indexer result to sys_log: No number of records given');
		}
		$record = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => $title,
			'tstamp' => time(),
			'type' => 1,
			'message' => 'Inserted / updated ' . $numberOfRecords . ' entries',
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
	 * Get raw record from database
	 * @param string $table
	 * @param string $where
	 * @param string $fields
	 * @return array|false
	 * @throws DoctrineDBALDriverException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getRawRecord(string $table, string $where = '', string $fields = '*')
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable($table);
		$queryBuilder->getRestrictions()->removeAll();

		// Todo: change fetch() to ...? (Only called from Shop indexer)

		$row = $queryBuilder
			// ... => see: https://stackoverflow.com/questions/41124015/what-is-the-meaning-of-three-dots-in-php
			->select(...GeneralUtility::trimExplode(',', $fields, true))
			->from($table)
			->where($where)
			->execute()
			->fetch();

		return $row ?: false;
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
	 * Get the timestamp of the oldest forum index entry in table 'tx_kesearch_index'
	 * This is the original timestamp of 'tx_mmforum_domain_model_forum_post' record
	 * for this purpose we use the column tx_kesearch_index.sortdate
	 * @return int|null
	 * @throws DoctrineDBALDriverException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getTsStampOfOldestCreatedForumIndexEntry(): ?int
	{

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);

		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');
		$oldestRecord = $queryBuilder
			->select('sortdate')
			->from('tx_kesearch_index')
			->where(
				$queryBuilder->expr()->in(
					'type',
					self::getForumIndexerTypesForSql()
				)
			)
			->setMaxResults(1)
			->orderBy('sortdate','ASC')
			->execute()
			->fetchAssociative()
		;

		if(empty($oldestRecord)){
			return null;
		}

		return (int)$oldestRecord['sortdate'];

	}

	/**
	 * Get the various forum indexer types as a comma separated list, wrapped in ' for sql queries
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getForumIndexerTypesForSql(): string
	{
		$mmForumIndexerTypes = GeneralUtility::makeInstance(MmForumIndexerTypes::class);
		return "'" . $mmForumIndexerTypes::FORUM_INDEXER_TYPE_DEFAULT . "','". $mmForumIndexerTypes::FORUM_INDEXER_TYPE_SP . "','" . $mmForumIndexerTypes::FORUM_INDEXER_TYPE_LOCKED . "'";
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

		$mmForumIndexerTypes = GeneralUtility::makeInstance(MmForumIndexerTypes::class);
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
		$type = $mmForumIndexerTypes::FORUM_INDEXER_TYPE_LOCKED;
		$feGroups = [];
		$feGroup = '';

		foreach($result as $access){

			// Any user and any logged-in user
			if(in_array($access['login_level'], [0,1])){

				$type = $mmForumIndexerTypes::FORUM_INDEXER_TYPE_DEFAULT;

			}else{

				// fe_user group 'ForumUser'
				if($access['affected_group'] == 1){
					$type = $mmForumIndexerTypes::FORUM_INDEXER_TYPE_DEFAULT;
				}

				// fe_user group 'SP user'
				if($access['affected_group'] == 3){
					$type = $mmForumIndexerTypes::FORUM_INDEXER_TYPE_SP;
				}

				$feGroups[] = $access['affected_group'];

			}
		}

		// Build a comma separated list of fe_user groups, if we have any
		if($type == $mmForumIndexerTypes::FORUM_INDEXER_TYPE_LOCKED && count($feGroups) > 0){
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

}