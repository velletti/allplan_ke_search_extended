<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * TYPO3
 */

use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * PDO
 */
use PDO;

/**
 * Get the last indexing reports for a special indexer type as opposed to the last indexing report at all from ke_search
 * @see ke_search/Classes/Controller/BackendModuleController.php->getLastIndexingReport()
 */
class LastIndexingReportViewHelper extends AbstractViewHelper
{

	/**
	 * Initialize arguments
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function initializeArguments()
	{
		parent::initializeArguments();
		$this->registerArgument('indexerType', 'string', 'Indexer type', true);
		$this->registerArgument('maxResults', 'string', 'Max results to get from database', false);
	}

	/**
	 * @return array
	 * @author Jörg Velletti <pbenke@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): array
	{
		$indexerType = $this->arguments['indexerType'] ?? false;
		$maxResults = $this->arguments['maxResults'] ?? 1;

		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('sys_log');

		$queryBuilder
			->select('*')
			->from('sys_log')
			->where(
				$queryBuilder->expr()->like(
					'details',
					$queryBuilder->quote('[ke_search]%', PDO::PARAM_STR)
				)
			)
			->orderBy('tstamp', 'DESC')
			->setMaxResults($maxResults);
		if ($indexerType) {
			$queryBuilder->andWhere(
				$queryBuilder->expr()->like(
					'details',
					$queryBuilder->quote('%' . $indexerType . '%',PDO::PARAM_STR)
				)
			);
		}

		// echo PHP_EOL . $queryBuilder->getSQL() . PHP_EOL;

		try{
			return $queryBuilder->execute()->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
			return [];
		}

	}

}