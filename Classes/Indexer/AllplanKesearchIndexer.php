<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;
use Tpwd\KeSearch\Indexer\IndexerBase;
use Tpwd\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Kiefer (team.inmedias) <andreas.kiefer@inmedias.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * EXTEND Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (team.inmedias) <andreas.kiefer@inmedias.de>
 * @author	Stefan Froemken
 * @author	Christian Bülter (team.inmedias) <christian.buelter@inmedias.de>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class AllplanKesearchIndexer extends IndexerRunner {

    /**
     * @var array The index Configs records that should be used for scheduler index
     */
    public $configs ;



    /**
     * @var int The time period, after which the rows are deleted
     */
    public $period ;

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    var $registry;


    /**
     * @var int language
     */
    public $language ;

    /**
     * @var string externalUrl
     */
    public $externalUrl ;

    /**
     * @var int storagePid
     */
    public $storagePid ;


	/**
	 * this function returns all indexer configurations found in DB
	 * independant of PID
	 */
	public function getConfigurations() {
		if ( is_array($this->configs)) {

            /** @var ConnectionPool $connectionPool */
            $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_indexerconfig')->createQueryBuilder();
            $queryBuilder->select('*')
                ->from('tx_kesearch_indexerconfig') ;

            $expr = $queryBuilder->expr();

            $uids = implode("," , $this->configs) ;
            if( count( $this->configs) > 1 ) {
                $queryBuilder->where(
                    $expr->in('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_STr))
                ) ;
            } else {
                $queryBuilder->where(
                    $expr->eq('uid', $queryBuilder->createNamedParameter($uids, Connection::PARAM_INT))
                ) ;
            }

            return $queryBuilder->execute()->fetchAll();

        } else {
		    return array()  ;
        }
	}
}