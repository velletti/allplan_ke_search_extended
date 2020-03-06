<?php
namespace Allplan\AllplanKeSearchExtended\Utility;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Jörg velletti (allplan) <jVelletti@allplan.com>
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

class AllplanElearningsIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {


        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_maritelearning_domain_model_lesson')->createQueryBuilder();
        $queryBuilder->select('*')->from('tx_maritelearning_domain_model_lesson');
        $indexerRows = $queryBuilder->execute() ;

        $origData = array() ;

        if($indexerRows) {
            $resCount = 0 ;

            while(( $record = $indexerRows->fetch() )) {
                $resCount++ ;
                // Prepare data for the indexer

                $title = $record['title'] ;

                $abstract = $record['title'] ;
                $description = $record['title'] ;

                $content = $title . PHP_EOL . nl2br($abstract) . PHP_EOL . nl2br($description);


                $tags = '#videos#';
                $sys_language_uid = $record['sys_language_uid'] ;

                /** @var \DateTime $sortdate */
                $sortdate = $record['date']   ;
                $endtime = 0 ;
                $feGroup = '';
                $debugOnly = false;

                $parameters = [
                    'tx_maritelearning_pi1[lesson]=' . intval( $record['uid'] ),
                    'tx_maritelearning_pi1[action]=single',
                    'tx_maritelearning_pi1[controller]=Lesson'
                ];

                // https://connect.local/en/learn/featured/play-a-video.html?tx_maritelearning_pi1%5Blesson%5D=102&
                // &tx_maritelearning_pi1%5Bsys_language_uid%5D=0&tx_maritelearning_pi1%5Baction%5D=single
                //&tx_maritelearning_pi1%5Bcontroller%5D=Lesson&cHash=e4dc73713b0043f46ab2cb6baff4b013

                $origId = $record['l18n_parent'] ;
                $feGroup = $record['fe_group'] ;
                unset($recordOrig) ;
                if( $origId > 0 ) {
                    if( is_array( $origData[$origId] )) {
                        $recordOrig = $origData[$origId] ;
                    } else {
                        $subQuery = $queryBuilder;
                        $expr = $subQuery->expr();
                        $subQuery->where($expr->eq("uid" , $origId )) ;

                        $recordOrig = $subQuery->execute()->fetch() ;
                        $origData[$origId]  = $recordOrig ;

                    }
                    $feGroup = $recordOrig['fe_group'] ;
                }

                $feGroupArray = explode("," , $feGroup ) ;
                if( in_array("1" , $feGroupArray ) || in_array("3" , $feGroupArray ) || in_array("10" , $feGroupArray ) || in_array("11" , $feGroupArray ) || in_array("8" , $feGroupArray ) ) {
                    // if video is available for everybody ( forum member) or normal Customers we will include this in search index.
                    // The Final Access is managed by the extension. So only Intarmal videos or Videos just for students will an fe Groups entry
                    $feGroup = '' ;
                }
                // echo " -> " . $feGroup . " |";


                // The following should be filled (in accordance with the documentation), see also:
                // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
                $additionalFields = array(
                    'orig_uid' => $record['uid']  ,
                    'sortdate' => 0  ,
                    'servername' => $_SERVER['SERVER_NAME']
                );
                if ( substr( $additionalFields['servername'], 0, 6)  == "connect"  ||  substr($additionalFields['servername'] , -13 , 13)  == "psmanaged.com" ) {
                    $additionalFields['servername'] =  "connect.allplan.com"  ;
                }
                if( $sortdate > 0 )  {
                    $additionalFields['sortdate'] = intval( $sortdate )  ;
                   //  $additionalFields['sortdate'] = $sortdate->getTimestamp() ;
                }

                $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

                $url = "https://connect.allplan.com/index.php?id=" . $indexerConfig['targetpid'] . "&" .  implode( "&" , $parameters ) ;
                if($sys_language_uid > -1 ) {
                    $url .= "&L=" . $sys_language_uid ;
                }


                $indexerObject->storeInIndex(
                    $pid ,			// folder, where the indexer Data is stored
                    $title,							// title in the result list
                    'lessons',				    // content type Important
                    $url ,	// uid of the targetpage (see indexer-config in the backend)
                    $content, 						// below the title in the result list
                    $tags,							// tags (not used here)
                    '&' . implode('&', $parameters),						// additional typolink-parameters, e.g. '&tx_jvevents_events[event]=' . $record['uid'];
                    $abstract,						// abstract (not used here)
                    $sys_language_uid,				// sys_language_uid
                    0 ,						// starttime (not used here)
                    $endtime,						// endtime (not used here)
                    $feGroup,						// fe_group (not used here)
                    $debugOnly,						// debug only?
                    $additionalFields				// additional fields added by hooks
                );
            }

        }
        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => 0 ,
            "event_pid" => $pid ,
            "details" => "Allplan Elearning lessons Indexer had updated / inserted " . $resCount . " entrys" ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => var_export($indexerObject , true ) ,

        ) ;

        $this->insertSyslog( $insertFields) ;

        return intval($resCount);
    }
}