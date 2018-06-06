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

        //$where = 'pid IN (' . $this->getTreeList($indexerConfig['startingpoints_recursive']) . ') ';
        $where = '1=1 ';
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

       //  echo "Select " . $fields . " FROM " . $table . " WHERE " . $where ;
        //  die;

        $res = $db->exec_SELECTquery($fields,$table,$where);
        $resCount = $db->sql_num_rows($res);

        //  echo "ResCount: " . $resCount . "<hr>" ;
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

                $content .=  $this->getFileContent($db , $record['uid'] ) ;

                $tags = '#pdf#,#downloads#';
                $sys_language_uid = $record['sys_language_uid'] ;

                /** @var \DateTime $sortdate */
                $sortdate = $record['date']   ;
                $endtime = 0 ;
                $feGroup = '';
                $debugOnly = false;
                $origId = $record['l18n_parent'] ;

                $parameters = [
                    'tx_maritelearning_pi1[download]=' . intval( $record['l18n_parent']> 0 ? $record['l18n_parent'] : $record['uid'] ),
                    'tx_maritelearning_pi1[action]=single',
                    'tx_maritelearning_pi1[controller]=Download'
                ];


                // var_dump( $parameters) ;
                // echo "<hr>" ;
                // var_dump( record['l18n_parent'] ) ;


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
                if ( $additionalFields['servername'] == "connect-typo3.allplan.com"  ||  substr($additionalFields['servername'] , 6 , 13)  == "ims-firmen.de" ) {
                    $additionalFields['servername'] =  "connect.allplan.com"  ;
                }

                if( $sortdate > 0 )  {
                    $additionalFields['sortdate'] = intval( $sortdate )  ;
                    //  $additionalFields['sortdate'] = $sortdate->getTimestamp() ;
                }
                $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

                $url = "https://" . $additionalFields['servername'] . "/index.php?id=" . $indexerConfig['targetpid'] ."&" .  implode( "&" , $parameters ) ;
                if($sys_language_uid > -1 ) {
                    $url .= "&L=" . $sys_language_uid ;
                }
                $indexerObject->storeInIndex(
                    $pid ,			// folder, where the indexer Data is stored
                    $title,							// title in the result list
                    'documentation',				    // content type Important
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

    public function getFileContent($db , $uid) {

        $fields = "sys_file.uid, sys_file.missing,
sys_file.identifier, sys_file.name , sys_file.sha1, sys_file.creation_date, sys_file.modification_date" ;

        $table = "sys_file
LEFT JOIN sys_file_reference ON ( sys_file_reference.uid_local = sys_file.uid )" ;

        $where = "sys_file.missing = 0 AND 
sys_file_reference.fieldname = \"tx_maritelearning_domain_model_download_download\" and
sys_file_reference.uid_foreign =" ;

        $resSingle = $db->exec_SELECTquery($fields,$table,$where  . $uid );
        $FileRecord = $db->sql_fetch_assoc($resSingle) ;

        $file = PATH_site . "fileadmin" . $FileRecord['identifier'] ;
        $className = 'tx_kesearch_indexer_filetypes_pdf' ;

        // check if class exists
        if (class_exists($className) && file_exists( $file )) {
            // make instance
            $fileObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
            // check if new object has interface implemented
            if ($fileObj instanceof tx_kesearch_indexer_filetypes) {
                // Do the check if a file has already been indexed at this early point in order
                // to skip the time expensive "get content" process which includes calls to external tools
                // fetch the file content directly from the index
                echo " is class  " ;
                $fileContent = $fileObj->getContent( $file );
                // remove line breaks from content in order to identify
                // additional content (which will have trailing linebreaks)
                return  "Filename: " . $FileRecord['name'] .  str_replace("\n", ' ', $fileContent);

            } else {
                return '' ;
            }
        }
    }
}