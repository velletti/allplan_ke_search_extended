<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;
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


// include original indexer class
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search') . 'Classes/indexer/class.tx_kesearch_indexer.php');

/**
 * EXTEND Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (team.inmedias) <andreas.kiefer@inmedias.de>
 * @author	Stefan Froemken
 * @author	Christian Bülter (team.inmedias) <christian.buelter@inmedias.de>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class AllplanKesearchIndexer extends \tx_kesearch_indexer {

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
	 * this function returns all indexer configurations found in DB
	 * independant of PID
	 */
	public function getConfigurations() {
		if ( is_array($this->configs)) {
            $where = ' hidden=0 AND deleted=0 ';
            $uids = implode("," , $this->configs) ;
		    if( count( $this->configs) > 1 ) {
                $where .= ' and uid in(' .$uids . ")" ;
            } else {
                $where .= ' and uid = ' .$uids  ;
            }

            $indexerRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("*", 'tx_kesearch_indexerconfig' , $where );
            return $indexerRows ;
        } else {
		    return array()  ;
        }
	}
}