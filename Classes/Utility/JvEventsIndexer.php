<?php
namespace Allplan\AllplanKeSearchExtended\Utility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
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

class JvEventsIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {

        /** @var ConnectionPool $connectionPool */

        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_jvevents_domain_model_event') ;
        /** @var ExpressionBuilder $expr */
        $expr = $queryBuilder->expr() ;

        $queryBuilder->from('tx_jvevents_domain_model_event' , 'event')
            ->leftJoin( 'event' , 'tx_jvevents_domain_model_organizer' , "org" , $expr->eq('event' . '.organizer',  'org.uid') )
            ->leftJoin( 'event' , 'tx_jvevents_domain_model_location' , 'loc' , $expr->eq( 'loc.uid', 'event.location') )

            ->select('event.uid','event.name','event.teaser','event.description','event.sys_language_uid','event.start_date','event.end_date')
            ->addSelect("org.name as oname" ,"loc.city as city" , "loc.zip as zip" , " loc.street_and_nr" , "loc.name as lname")
            ->addSelect(" loc.description as ldesc" ,"org.description as odesc" )
            ->where($expr->gt( 'event.start_date', time() ))
            ->andWhere($expr->in( 'event.pid',  $this->getTreeList($indexerConfig['startingpoints_recursive']) ))
        ;

        $res = $queryBuilder->execute()  ;

        if($res) {
            $resCount = 0 ;
            while(( $record = $res->fetch() )) {
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
        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => 0 ,
            "event_pid" => 0 ,
            "details" => "Indexer JvEvents Entries" ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => "Updated "  . $resCount . " Entries "

        ) ;
        $this->insertSyslog( $insertFields) ;
        return intval($resCount);
    }
}