<?php
namespace Allplan\AllplanKeSearchExtended\Task;

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Andreas Kiefer <kiefer@kennziffer.com>
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



// include indexer class
// require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search') . 'Classes/indexer/class.tx_kesearch_indexer.php');

class AllplanKesearchIndexerTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    /**
     * @var int The time period, after which the rows are deleted
     */
    protected $period ;

    /**
     * @var int language
     */
    protected $language ;

    /**
     * @var string externalUrl
     */
    protected $externalUrl ;

    /**
     * @var int storagePid
     */
    protected $storagePid ;

    /**
     * @var array The index Configs records that should be used for scheduler index
     */
    public $configs ;


	public function execute() {
        if (class_exists(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)) {
            $this->extConf =
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
                    ->get('ke_search');
        } else {
            $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);
        }


		// make indexer instance
        /** @var \Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer  $indexer */
		$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Allplan\\AllplanKeSearchExtended\\Indexer\\AllplanKesearchIndexer');

        $indexer->configs = $this->configs ;
        $indexer->period = $this->period ;
        $indexer->language = $this->language ;
        $indexer->externalUrl = $this->externalUrl ;
        $indexer->storagePid = $this->storagePid ;

        // First Remove the default ke Search registry entrys ( needed as default index will set it again ..
        $indexer->registry->removeAllByNamespace('tx_kesearch');

        // Now write starting timestamp into registry , but use $nameSpace tx_kesearch_<taskUid>
        // this is a helper to delete all records which are older than starting timestamp in registry
        // this also prevents starting the indexer twice
        $nameSpace = 'tx_kesearch_extended' ;
        $registryKey = 'startTimeOfIndexer' . $this->taskUid ;

        if($indexer->registry->get($nameSpace, $registryKey ) === null) {
            $indexer->registry->set($nameSpace, $registryKey, time());
        } else {
            // check lock time
            $lockTime = $indexer->registry->get($nameSpace, $registryKey );

            $compareTime = time() - (60*60*12);

            if ($lockTime < $compareTime || substr( $_SERVER['SERVER_NAME'] , -6 , 6 ) == ".local" ) {
                // lock is older than 12 hours - remove
                $indexer->registry->remove($nameSpace , $registryKey );
                $indexer->registry->set($nameSpace, $registryKey , time());
            } else {
                throw new \RuntimeException(
                    'You can\'t start the indexer twice. Please wait while first indexer process ' . $nameSpace  . " -> " . $registryKey
                    .  ' is currently running : Locktime:' . date("d.m.Y H:i:s" , $lockTime ) . " > " . date("d.m.Y H:i:s" , $compareTime )  ,
                    1493994395218 
                );
                return false ;
            }
        }

		// process
		$indexer->startIndexing(true, $this->extConf, 'CLI');
        // $indexer->registry->removeAllByNamespace($nameSpace);
        $indexer->registry->remove($nameSpace , $registryKey );
		return true;
	}


    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @param array $configs
     */
    public function setConfigs($configs)
    {
        $this->configs = $configs;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param int $period
     */
    public function setPeriod($period)
    {
        $this->period = $period;
    }

    /**
     * @return int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param int $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->externalUrl;
    }

    /**
     * @param string $externalUrl
     */
    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;
    }

    /**
     * @return int
     */
    public function getStoragePid()
    {
        return $this->storagePid;
    }

    /**
     * @param int $storagePid
     */
    public function setStoragePid($storagePid)
    {
        $this->storagePid = $storagePid;
    }



}