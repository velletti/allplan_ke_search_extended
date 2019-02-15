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

class JvEventsIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
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
        $fields = 'event.uid, event.name , event.teaser, event.description, event.sys_language_uid , event.start_date , event.end_date';
        $fields .= ',org.name as oname, loc.city as city, loc.zip, loc.street_and_nr , loc.name as lname, loc.description as ldesc , org.description as odesc' ;
        $table = 'tx_jvevents_domain_model_event as event ';

        $where = 'event.pid IN (' . $this->getTreeList($indexerConfig['startingpoints_recursive']) . ') ';
        $where.= 'AND ';
        $where.= 'start_date > ' . time();
        $where.= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
        $where.= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

        $table .= "LEFT JOIN tx_jvevents_domain_model_organizer as org ON event.organizer = org.uid 
                LEFT JOIN tx_jvevents_domain_model_location as loc ON event.location = loc.uid " ;

         // echo "Select " . $fields . " FROM " . $table . " WHERE " . $where ;
         // die;

        $res = $db->exec_SELECTquery($fields,$table,$where);
        $resCount = $db->sql_num_rows($res);
        // echo "ResCount: " . $resCount . "<hr>" ;
        // echo $db->debug_lastBuiltQuery . PHP_EOL;

        if($resCount) {
            $resCount = 0 ;
            while(( $record = $db->sql_fetch_assoc($res))) {
                $resCount++ ;
                // Prepare data for the indexer
                $title = $record['name'];
                $abstract = '';
                $abstract = $record['teaser'];
                $description = $record['description'];

                $content = $title . PHP_EOL . nl2br($abstract) . PHP_EOL . nl2br($description);
                $content .=  PHP_EOL . $record['lname']
                    . PHP_EOL . $record['zip'] . " " . $record['city'] . " " . $record['street_and_nr']
                    . PHP_EOL . $record['odesc']  ;
                $content .=  PHP_EOL . $record['oname'] . PHP_EOL . $record['odesc']  ;

                // echo $content ;
                // var_dump( $record ) ;
                // die ;

                    $tags = '';
                $sys_language_uid = $record['sys_language_uid'];
                $sortdate = $record['start_date'];
                $endtime = $record['end_date'];
                if ($endtime < 1) {
                    $endtime = $record['start_date'];
                }
                $feGroup = '';
                $debugOnly = false;

                #$parameters = '&tx_jvevents_events[event]=' . intval($record['uid'] . '&');
                $parameters = [
                    'tx_jvevents_events[event]=' . intval($record['uid']),
                    'tx_jvevents_events[action]=show',
                    'tx_jvevents_events[controller]=Event'
                ];
                $server = $_SERVER['SERVER_NAME'] ;
                if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == "connect" ) {
                    $server = "connect.allplan.com" ;
                }
                if( $server == "www-typo3.allplan.com" ||  $server == "vm5012986.psmanaged.com" ||   $server == "allplan" ||   $server == "www") {
                    $server = "www.allplan.com" ;
                }
                // The following should be filled (in accordance with the documentation), see also:
                // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
                $additionalFields = array(
                    'orig_uid' => $record['uid'] ,
                    'sortdate' => $sortdate ,
                    'servername' => $server
                );

                $indexerObject->storeInIndex(
                    $indexerConfig['pid'],			// folder, where the indexer Data is stored
                    $title,							// title in the result list
                    'jv_events',				    // content type Important
                    $indexerConfig['targetpid'],	// uid of the targetpage (see indexer-config in the backend)
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