<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Task\IndexerTaskConfiguration;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexerRunner extends KeSearchIndexerRunner
{

	/**
	 * Class variables
	 * =================================================================================================================
	 */

	/**
	 * @var IndexerTaskConfiguration
	 */
	protected IndexerTaskConfiguration $taskConfiguration;

	/**
	 * @return IndexerTaskConfiguration
	 */
	public function getTaskConfiguration(): IndexerTaskConfiguration
	{
		return $this->taskConfiguration;
	}

	/**
	 * @param IndexerTaskConfiguration $taskConfiguration
	 */
	public function setTaskConfiguration(IndexerTaskConfiguration $taskConfiguration): void
	{
		$this->taskConfiguration = $taskConfiguration;
	}

	/**
	 * Functions
	 * =================================================================================================================
	 */

	/**
	 * Initialize the task taskConfiguration object and call the parent constructor
	 * @param IndexerTaskConfiguration $taskConfiguration
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct(IndexerTaskConfiguration $taskConfiguration)
	{
		$this->setTaskConfiguration($taskConfiguration);
		parent::__construct();
	}

	/**
	 * Extends the parent function startIndexing()
	 * @param bool $verbose
	 * @param array $extConf
	 * @param string $mode
	 * @param int $indexingMode
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing($verbose = true, $extConf = array(), $mode = '', $indexingMode = IndexerBase::INDEXING_MODE_FULL): string
	{
		// Parent function (there the CustomIndexerHook will be called)
		return parent::startIndexing($verbose, $extConf, $mode, $indexingMode);
	}

	/**
	 * Overwrites the parent function getConfigurations()
	 * Returns only the indexer configuration, which was set in scheduler task
	 * (in opposite to all configurations returned from ke_search)
	 * @return array
	 * @throws DoctrineDBALDriverException
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function getConfigurations(): array
	{

		$indexerUid = (int)$this->getTaskConfiguration()->getIndexerConfigUid();

		if($indexerUid > 0){

			$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
			$queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_indexerconfig')->createQueryBuilder();
			$queryBuilder
				->select('*')
				->from('tx_kesearch_indexerconfig')
				->where(
					$queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($indexerUid, Connection::PARAM_INT))
				)
			;
			return $queryBuilder->execute()->fetchAllAssociative();

		}

		return [];

	}

}