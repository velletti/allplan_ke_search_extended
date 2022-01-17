<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDriverException;
use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\FetchMode;

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
	 * @throws DoctrineDriverException
	 * @throws DoctrineException
	 * @author Jörg Velletti <jvelletti@allplan.com>
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
		/** @var ConnectionPool $connectionPool */
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$connection = $connectionPool->getConnectionForTable($tableNameToCheck);
		$statement = $connection->prepare('SHOW TABLES LIKE "' . $tableNameToCheck . '"');
		$statement->execute();
		if($statement->fetch(FetchMode::ASSOCIATIVE)){
			return $tableNameToCheck;
		}
		return '';

	}

}