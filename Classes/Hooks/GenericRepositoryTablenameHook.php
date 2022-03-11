<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * Doctrine
 */
use Doctrine\DBAL\Exception as DoctrineException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GenericRepositoryTablenameHook
{

	/**
	 * Check if  table name exists by given type
	 * Needed for news, as on connect this table does not exist
	 * @param string $type
	 * @return string
	 * @throws DoctrineException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function getTableName(string $type): string
	{

		switch ($type) {
			case 'page':
				return 'pages';
			case 'news':
				$tableNameToCheck = 'tx_news_domain_model_news';
				break;
			case 'file':
				return 'sys_file';
			default:
				$tableNameToCheck = strip_tags(htmlentities($type));
		}

		// Check if a table exists, that matches the type name
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionForTable($tableNameToCheck);
		$result = $connection->fetchFirstColumn('SHOW TABLES LIKE "' . $tableNameToCheck . '"');
		if(!empty($result)){
			return $tableNameToCheck;
		}
		return '';

	}

}