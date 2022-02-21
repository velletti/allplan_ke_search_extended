<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;

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
 * Php
 */
use Exception;

class IndexerUtility
{

	/**
	 * Gets the storage pid, where the index record should be stored
	 * If the pid was defined in scheduler task, it will be preferred, otherwise the pid from indexer configuration
	 * will be taken
	 * @param IndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getStoragePid(IndexerRunner $indexerRunner, array $indexerConfig): string
	{

		$pid = $indexerConfig['pid'];
		$taskConfiguration = $indexerRunner->getTaskConfiguration();

		if(!empty($taskConfiguration->getStoragePid())){
			$pid = $taskConfiguration->getStoragePid();
		}

		return (string)$pid;

	}

}