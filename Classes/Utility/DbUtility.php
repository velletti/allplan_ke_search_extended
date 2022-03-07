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
	 * @param string $description
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function saveIndexerResultInSysLog(string $title, string $description)
	{
		if(empty($title)){
			throw new Exception('Write indexer result to sys_log: No title given');
		}
		if(empty($description)){
			throw new Exception('Write indexer result to sys_log: No description given');
		}
		$record = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => $title,
			'tstamp' => time(),
			'type' => 1,
			'message' => $description,
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
	 * Get the newest tstamp from table tx_kesearch_index, where we have a forum entry
	 * ≙ the date of latest indexed forum entry
	 * @return int|null
	 * @throws DoctrineDBALDriverException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getNewestForumIndexTsStamp(): ?int
	{
		$possibleIndexerTypes = IndexerUtility::getPossibleIndexerTypesForForum();

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

		$newestRecord = $queryBuilder
			->select('sortdate')
			// Todo change to correct table
			###################->from('tx_kesearch_index')
			->from('tx_kesearch_index_ORIG')
			->where($queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter('allplanforu%')))
			->setMaxResults(1)
			->orderBy('sortdate','DESC')
			->execute()
			->fetchAssociative()
		;

		if(empty($newestRecord)){
			return null;
		}

		return (int)$newestRecord['sortdate'];

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

}