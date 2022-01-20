<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Db;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * PDO
 */
use PDO;

/**
 * Class KeSearchUnlockViewHelper
 * @package Allplan\AllplanKeSearchExtended\ViewHelpers
 */
class KeSearchReportViewHelper extends AbstractViewHelper
{
	/**
	 * @var bool
	 */
	protected bool $escapeOutput = false;

	/**
	 * Needed as child node's output can return a DateTime object which can't be escaped
	 *
	 * @var bool
	 */
	protected bool $escapeChildren = false;

	/**
	 * Constructor
	 *
	 * @api
	 */
	public function initializeArguments()
	{
		$this->registerArgument('amount','int','number of entrys to fetch',false, 3);
		$this->registerArgument('search','string','additional Search condition',false);
	}

	/**
	 * @return array
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): array
	{

		$amount = $this->arguments['amount'] ?? 3;
		$search = $this->arguments['search'] ?? false;

		$queryBuilder = Db::getQueryBuilder('sys_log');
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
			->setMaxResults($amount);
		if ($search) {
			$queryBuilder->andWhere(
				$queryBuilder->expr()->like(
					'details',
					$queryBuilder->quote($search,PDO::PARAM_STR)
				)
			) ;
		}

		try{
			return $queryBuilder->execute()->fetchAllAssociative();
		}catch(DoctrineDBALDriverException $e){
			return [];
		}

	}

}