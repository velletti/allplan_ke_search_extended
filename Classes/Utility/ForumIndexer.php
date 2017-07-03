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

class ForumIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
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

        // Get the data from tx_jvevents_domain_model_event


        // ToDo  Enhence the Query to respect Access Rights, get language from Forum and much more !!!
        $fields = 'p.uid, u.username, p.text, t.subject , f.displayed_pid , f.title , p.topic , f.sys_language_uid , p.crdate , p.tstamp , f.uid as forumUid  ';

        $table = 'tx_mmforum_domain_model_forum_post as p';
        $table .= ' LEFT JOIN tx_mmforum_domain_model_forum_topic as t on (t.uid = p.topic)';
        $table .= ' LEFT JOIN tx_mmforum_domain_model_forum_forum as f on (t.forum = f.uid)';

        $table .= ' LEFT JOIN fe_users as u on (p.author = u.uid)';

        $where = ' p.pid=67 and  p.deleted=0 and  t.deleted=0 and  f.deleted=0';
  //      $where .= " AND a.operation = 'read'   " ;
  //      $where .= " AND  ( a.login_level = 0   OR  a.login_level = 1   OR  ( a.login_level = 2  and a.affected_group = 1 ) OR  ( a.login_level = 2  and a.affected_group = 3 ) ) " ;

        $debug = "[KE search Indexer] Indexer Forum Entries starts " . PHP_EOL ;


        if( $indexerObject->period > 365 ) {
            $lastRun = time() - ( 60 * 60 * 24 * ( $indexerObject->period  ))  ;
        }
        $lastRunRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw( "tx_kesearch_index" , "`type` like 'allplanforu%' ORDER BY starttime DESC") ;


        if( is_array($lastRunRow )) {
            $debug .= "Last Forumsentry in Index: index UID: " . $lastRunRow['uid'] . " POST uid: " .  $lastRunRow['orig_uid']  . PHP_EOL . " Sortdate: " . date ( "d.m.Y H:i" , $lastRunRow['sortdate']  ) . PHP_EOL . PHP_EOL;
            $lastRun = $lastRunRow['sortdate']  ;
        }
        if ( intval( $lastRun ) < 100000  ) {
            $lastRun = mktime( 0 ,0 ,0 , date("m") , date("d") , date("Y") -10 ) ;
        }

        $where .= " AND ( p.tstamp > " . $lastRun . " OR p.crdate > " . $lastRun . " ) ";

        $debug .= "SELECT " . $fields . PHP_EOL . " FROM " . $table . " WHERE " . $where  . PHP_EOL . PHP_EOL ;


        $res = $db->exec_SELECTquery($fields,$table,$where , '' , 'p.tstamp ASC'  );
        $resCount = $db->sql_num_rows($res);
        $debug .= "SELECT will have " . $resCount . " hits !  ( only posts after " .  date( "d.m.Y H:i" , $lastRun ) . " ) ". PHP_EOL  ;

        if( $db->store_lastBuiltQuery === true )  {
            echo $db->debug_lastBuiltQuery . PHP_EOL;
            echo "<hr>" ;
            var_dump($lastRunRow) ;
            die;
        }


        if($resCount) {
            $count = 0 ;
            while(($record = $db->sql_fetch_assoc($res))){

                // Prepare data for the indexer
                $title = $record['subject'];
                $abstract = '';

                // name des Forums, Betreff des Topics und dann der Text ...
                $content = $record['title'] . PHP_EOL . $title . PHP_EOL . $record['text'] ;

                if( $record['username'] ) {
                    $content .=  PHP_EOL . "User:" .$record['username'];
                }

                $attachmentRows = $db->exec_SELECTquery('filename ' , "post = " . $record['uid']  );

                while(($attachmentRow = $db->sql_fetch_assoc($attachmentRows))) {
                    $content .=  PHP_EOL . "File:" . $attachmentRow['filename']  ;
                }


                $tags = '#forum#';

                switch ($record['sys_language_uid']) {
                    case 1:
                        $sys_language_uid = -1 ;
                        $pid = 5003 ;
                        break ;
                    case 0:
                        $sys_language_uid = 0 ;
                        $pid = 5004 ;
                        break ;
                    default :
                        $sys_language_uid = $record['sys_language_uid'] ;
                        $pid = 5005 ;
                        break ;
                }

                $starttime = 0;
                $endtime = 0;
                $debugOnly = false;

                // The following should be filled (in accordance with the documentation), see also:
                // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/

                $additionalFields = array(
                    'orig_uid' => $record['uid'] ,
                    'sortdate' => $record['tstamp'] ,
                    'servername' => $_SERVER['SERVER_NAME']
                );
                if ( $additionalFields['sortdate'] == 0  ) {
                    $additionalFields['sortdate'] = $record['crdate'] ;
                }
                if ( $additionalFields['servername'] == "connect-typo3.allplan.com"  ) {
                    $additionalFields['servername'] =  "connect.allplan.com"  ;
                }


                // ToDo  Adjust Target URL, must be an external URL
                $url =  "https://connect.allplan.com/index.php?id=" . $record['displayed_pid'] . "&L=" . $record['sys_language_uid'] ;
                $url .= "&tx_mmforum_pi1[controller]=Topic&tx_mmforum_pi1[action]=show&tx_mmforum_pi1[topic]=" . $record['topic'] . "&tx_mmforum_pi1[forum]="  . $record['forumUid'] ;

                // get FE Groups and decide if we store show this public or allow access only for specific fe_groups

                $type = "allplanforumlocked" ;
                $feGroup = '' ;
                $accessData = $db->exec_SELECTquery('login_level , affected_group' ,'tx_mmforum_domain_model_forum_access' ,
                    "operation = 'read' AND forum = " . $record['forumUid'] , '' , 'affected_group DESC ' );
                $feGroupsArray = array() ;

                while(($access = $db->sql_fetch_assoc($accessData))) {
                    if ($access['login_level'] == 0 ||  $access['login_level'] == 1  ) {
                        $type = "allplanforum" ;
                    } else {
                        if ( $access['affected_group'] == 3 ) {
                            $type = "allplanforumsp" ;
                        }
                        if ( $access['affected_group'] == 1 ) {
                            $type = "allplanforum" ;
                        }
                        $feGroupsArray[] =  $access['affected_group'] ;
                    }
                }
                if ( $type == "allplanforumlocked"  && count( $feGroupsArray)> 0) {
                    $feGroup = implode("," , $feGroupsArray ) ;
                }

                $indexerObject->storeInIndex(
                    $pid  ,			// folder, where the indexer is stored (not where the data records are stored!)
                    $record['subject'],							// title in the result list
                    $type ,				        // content type
                     $url ,	// uid of the targetpage (see indexer-config in the backend)
                    $content, 						// below the title in the result list
                    $tags,							// tags (not used here)
                    '' ,						// additional typolink-parameters, e.g. '&tx_jvevents_events[event]=' . $record['uid'];
                    $abstract,						// abstract (not used here)
                    $sys_language_uid,				// sys_language_uid
                    $starttime,						// starttime (not used here)
                    $endtime,						// endtime (not used here)
                    $feGroup,						// fe_group
                    $debugOnly,						// debug only?
                    $additionalFields				// additional fields added by hooks
                );

                $count++ ;
                if ( $count > 999 ) {
                    $this->logToSystem( $debug ) ;
                    return intval($count);
                }
            }

        }
        $this->logToSystem( $debug ) ;
        return intval($count);
    }
    private function logToSystem( $text ) {
        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => 0 ,
            "event_pid" => 0 ,
            "details" => $text  ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => "Indexer Forum Entries " ,
        ) ;

        $GLOBALS['TYPO3_DB']->exec_INSERTquery("sys_log" , $insertFields ) ;
    }

}