<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerRunner;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Todo: Delete this file

class AllplanKesearchIndexer extends IndexerRunner
{

	/**
	 * @var array The index config records, that should be used for scheduler index
	 */
	public array $configs;

	/**
	 * @var int The time period, after which the rows are deleted
	 */
	public int $period ;

	/**
	 * @var Registry
	 */
	var Registry $registry;

	/**
	 * @var array
	 */
	public array $language;

	/**
	 * @var string
	 */
	public string $externalUrl;

	/**
	 * @var int
	 */
	public int $storagePid;


	/**
	 * Overwrites the parent function getConfigurations()
	 * Returns all indexer configurations found in DB independent of pid
	 *
	 * @return array
	 *  @throws DoctrineDBALDriverException
	 */
	public function getConfigurations(): array
	{

		# print_r(['$this->configs' => $this->configs]);

		if(is_array($this->configs)){

			$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
			$queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_indexerconfig')->createQueryBuilder();
			$queryBuilder
				->select('*')
				->from('tx_kesearch_indexerconfig')
			;

			$expr = $queryBuilder->expr();
			$uids = implode(',', $this->configs);
			$queryBuilder->where(
				$expr->eq('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT))
			);
			$result = $queryBuilder->execute()->fetchAllAssociative();
print_r($result);
			return $result;

			/*
			if(count($this->configs) > 1){
				$queryBuilder->where(
					$expr->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_STR))
				);
			} else {
				$queryBuilder->where(
					$expr->eq('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT))
				);
			}
			*/
			return $queryBuilder->execute()->fetchAll();

		} else {
			return [];
		}
	}

}