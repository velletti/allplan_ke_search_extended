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

class DbUtility
{

	/**
	 * Writes into table sys_log
	 * @param array $record
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function writeToSyslog(array $record)
	{
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
	 */
	public static function getRawRecord(string $table, string $where = '', string $fields = '*')
	{
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable($table);
		$queryBuilder->getRestrictions()->removeAll();

		$row = $queryBuilder
			// ... => see: https://stackoverflow.com/questions/41124015/what-is-the-meaning-of-three-dots-in-php
			->select(...GeneralUtility::trimExplode(',', $fields, true))
			->from($table)
			->where($where)
			->execute()
			->fetch();

		return $row ?: false;
	}

}