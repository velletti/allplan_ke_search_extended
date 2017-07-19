<?php
namespace Allplan\AllplanKeSearchExtended\Utility;
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

class AllplanDocumentationsIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {

        /**
         * @var $db \TYPO3\CMS\Core\Database\DatabaseConnection
         */
        $db = $GLOBALS['TYPO3_DB'];
        // $db->store_lastBuiltQuery = true;

        $fields = '*';
        $table = 'tx_maritelearning_domain_model_download';

        $where = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
        $where.= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

        // echo "Select " . $fields . " FROM " . $table . " WHERE " . $where ;
        //  die;

        $res = $db->exec_SELECTquery($fields,$table,$where);
        $resCount = $db->sql_num_rows($res);

        // echo "ResCount: " . $resCount . "<hr>" ;
        $origData = array() ;

        if($resCount) {
            $resCount = 0 ;

            while(( $record = $db->sql_fetch_assoc($res))) {
                $resCount++ ;
                // Prepare data for the indexer

                $title = $record['title'] ;

                $abstract = $record['title'] ;
                $description = $record['title'] ;

                $content = $title . PHP_EOL . nl2br($abstract) . PHP_EOL . nl2br($description);


                $tags = '#pdf#,#downloads#';
                $sys_language_uid = $record['sys_language_uid'] ;

                /** @var \DateTime $sortdate */
                $sortdate = $record['date']   ;
                $endtime = 0 ;
                $feGroup = '';
                $debugOnly = false;

                $parameters = [
                    'tx_maritelearning_pi1[download]=' . intval( $record['uid'] ),
                    'tx_maritelearning_pi1[action]=single',
                    'tx_maritelearning_pi1[controller]=Download'
                ];

                // https://connect.allplan.com/de/training/dokumente.html?tx_maritelearning_pi1%5Bdownload%5D=2701
                // &tx_maritelearning_pi1%5BdownloadCat%5D=&tx_maritelearning_pi1%5Baction%5D=single&tx_maritelearning_pi1%5Bcontroller%5D=Download&cHash=26cd946f09ee1762121db6d4f03cb9ed


                $origId = $record['l18n_parent'] ;
                $feGroup = $record['fe_group'] ;
                unset($recordOrig) ;
                if( $origId > 0 ) {
                    if( is_array( $origData[$origId] )) {
                        $recordOrig = $origData[$origId] ;
                    } else {

                        $resSingle = $db->exec_SELECTquery($fields,$table,"uid = " . $origId );
                        $recordOrig = $db->sql_fetch_assoc($resSingle) ;
                        $origData[$origId]  = $recordOrig ;
                    }
                    $feGroup = $recordOrig['fe_group'] ;
                }
                // echo "<br>" . $resCount . " LocalizedUid() " . $record['uid']  . " Parent Uid:" . $record['l18n_parent'] . " LNG: " . $sys_language_uid . " orig: " . $recordOrig['uid'];
                // echo " fe Group : " . $feGroup ;

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
                if( $sortdate > 0 )  {
                    $additionalFields['sortdate'] = intval( $sortdate )  ;
                    //  $additionalFields['sortdate'] = $sortdate->getTimestamp() ;
                }
                $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

                $url = "https://" . $_SERVER['SERVER_NAME'] . "/index.php?id=" . $indexerConfig['targetpid'] ."&" .  implode( "&" , $parameters ) ;
                if($sys_language_uid > -1 ) {
                    $url .= "&L=" . $sys_language_uid ;
                }
                $indexerObject->storeInIndex(
                    $pid ,			// folder, where the indexer Data is stored
                    $title,							// title in the result list
                    'documentations',				    // content type Important
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
        // echo "ResCount: " . $resCount . "<hr>" ;
        // die;
        return intval($resCount);
    }
}