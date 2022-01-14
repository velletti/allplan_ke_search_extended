<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;

/**
 * TYPO3Fluid
 */

use Tpwd\KeSearch\Lib\Db;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class KeSearchUnlockViewHelper
 * @package Allplan\AllplanKeSearchExtended\ViewHelpers
 */
class KeSearchReportViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Constructor
     *
     * @api
     */
    public function initializeArguments()
    {
        $this->registerArgument('amount', 'int', 'number of entrys to fetch' , false , 3 );
        $this->registerArgument('search', 'string', 'additional Search condition' , false  );
    }

	/**
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render()
	{

        $amount = $this->arguments['amount'] ? $this->arguments['amount']  : 3 ;
        $search = $this->arguments['search'] ? $this->arguments['search']  : false ;

        $queryBuilder = Db::getQueryBuilder('sys_log');
        $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->like(
                    'details',
                    $queryBuilder->quote('[ke_search]%', \PDO::PARAM_STR)
                )
            )
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults($amount) ;
        if ($search) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->like(
                    'details',
                    $queryBuilder->quote($search , \PDO::PARAM_STR)
                )
            ) ;
        }
        return  $queryBuilder
            ->execute()
            ->fetchAllAssociative() ;

	}

}