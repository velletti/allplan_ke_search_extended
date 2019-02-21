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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;


class ForumIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {

        $debug = "[KE search Indexer] Indexer Forum Entries starts " . PHP_EOL ;

        /**
         * @var ConnectionPool $connectionPool
         */
        $connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index');

        $lastRunQuery = $queryBuilder->select( 'sortdate' )->from('tx_kesearch_index' )->where( $queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter( 'allplanforu%' )) )->setMaxResults(1)->orderBy('sortdate' , 'DESC') ;

        $debug .= "Query LastRun : " . $lastRunQuery->getSQL() ;

        $lastRunRow = $lastRunQuery->execute()->fetch() ;
        $debug .= "Result: " . var_export( $lastRunRow , true ) ;
        if( $indexerObject->period > 365 ) {
            $lastRun = time() - ( 60 * 60 * 24 * ( $indexerObject->period  ))  ;
        }

        if( is_array($lastRunRow )) {
            $debug .= "Last Forumsentry in Index: index UID: " . $lastRunRow['uid'] . " POST uid: " .  $lastRunRow['orig_uid']  . PHP_EOL . " Sortdate: " . date ( "d.m.Y H:i" , $lastRunRow['sortdate']  ) . PHP_EOL . PHP_EOL;
            $lastRun = $lastRunRow['sortdate']  ;
        }
        if ( intval( $lastRun ) < 100000  ) {
            $lastRun = mktime( 0 ,0 ,0 , date("m") , date("d") , date("Y") -10 ) ;
        }
        // remove 2 Seconds : We can write > Condition instead of  >= AND we will get also posts if they are writnen in the same second.
        $lastRun = $lastRun - 2 ;

        $debug .= "We will set LastRun to : " . date( "d.m.Y H:i:s" , $lastRun) ;




        // ************************** now build the query to get 9999 posts with needed infos *********************************

        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_post');

        $resultQuery = $queryBuilder
            ->select( 'p.uid' , 'u.username' , 'p.text' ,'t.subject' , 'f.displayed_pid' , 'f.title' , 'p.topic' , 'f.sys_language_uid' , 'p.crdate' , 'p.tstamp' , 'f.uid AS forumUid' )
            ->from('tx_mmforum_domain_model_forum_post' , 'p')
            ->join(
                'p',
                'tx_mmforum_domain_model_forum_topic',
                't',
                $queryBuilder->expr()->eq('t.uid', $queryBuilder->quoteIdentifier('p.topic'))
            )->join(
                't',
                'tx_mmforum_domain_model_forum_forum',
                'f',
                $queryBuilder->expr()->eq('t.forum', $queryBuilder->quoteIdentifier('f.uid'))
            )->join(
                'p',
                'fe_users',
                'u',
                $queryBuilder->expr()->eq('p.author', $queryBuilder->quoteIdentifier('u.uid'))
            )


            ->andWhere(
                $queryBuilder->expr()->eq('p.pid', 67 )
            )->andWhere(
                $queryBuilder->expr()->eq('p.deleted', 0 )
            )->andWhere(
                $queryBuilder->expr()->eq('t.deleted', 0 )
            )->andWhere(
                $queryBuilder->expr()->eq('f.deleted', 0 )
            )->setMaxResults(9999) ;

        // "sortdate" is needed later on .. so we put it to a Variable
        $sortDate = "p.crdate" ;
        $result =  $resultQuery->andWhere( $queryBuilder->expr()->gt('p.crdate', $lastRun   ))
            ->orderBy( $sortDate , 'ASC')
            ->execute() ;


        if( ! $result->fetch() ) {
            // all NEW entries since last run are indexed. so wie need to index modified entries ...
            $sortDate = 'p.tstamp' ;
            $result =  $resultQuery->andWhere( $queryBuilder->expr()->gt('p.tstamp', $lastRun   ))
                ->orderBy( $sortDate, 'ASC')
                ->execute() ;
        }

        $debug2 = '' ;
        $count = 0 ;
        $tagsFound = 0 ;
        while( $record = $result->fetch()  ) {

            // Prepare data for the indexer
            $title = $record['subject'];
            $abstract = '';
            if( $debug2 == '' ) {
                $debug2 = 'Indexing the following posts: ' . $record['uid'] . " - ";
            }

            // name des Forums, Betreff des Topics und dann der Text ...
            $content = $record['title'] . PHP_EOL . $title . PHP_EOL . $record['text'] . PHP_EOL ;

            $content .=  PHP_EOL . "Topic:" .$record['topic'];
            if( $record['username'] ) {
                $content .=  PHP_EOL . "User:" .$record['username'];
            }

            $tagqueryBuilder = $connectionPool->getQueryBuilderForTable('tx_mmforum_domain_model_forum_tag_topic');

            $tagQuery = $tagqueryBuilder
                ->select( 't.name as name' )
                ->from('tx_mmforum_domain_model_forum_tag_topic' , 'mm')
                ->join(
                    'mm',
                    'tx_mmforum_domain_model_forum_tag',
                    't',
                    $tagqueryBuilder->expr()->eq('t.uid', $tagqueryBuilder->quoteIdentifier('mm.uid_foreign')))
                ->where( $tagqueryBuilder->expr()->eq('mm.uid_local', $record['topic'] ))->execute() ;

            $content .=  PHP_EOL . "Tag: "   ;
            while( $tagRow = $tagQuery->fetch() ) {
                $content .=   " " . $tagRow['name']  ;
                $tagsFound ++ ;
            }


            // $attachmentRows = $db->exec_SELECTquery('filename ' , 'tx_mmforum_domain_model_forum_attachment' , "post = " . $record['uid']  );

            $attachmentRows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mmforum_domain_model_forum_attachment')
            ->select(
                ['filename' ],
                'tx_mmforum_domain_model_forum_attachment',
                ['post' =>  $record['uid'] ]
            );

            while( $attachmentRow = $attachmentRows->fetch() ) {
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
                'sortdate' => $record[$sortDate] ,
                'servername' => $_SERVER['SERVER_NAME']
            );

            if ( substr( $additionalFields['servername'],0 , 6 ) == "connect"  ||  substr($additionalFields['servername'] , -13 , 13)  == "psmanaged.com" ) {
                $additionalFields['servername'] =  "connect.allplan.com"  ;
            }


            // ToDo  Adjust Target URL, must be an external URL
            $url =  "https://connect.allplan.com/index.php?id=" . $record['displayed_pid'] . "&L=" . $record['sys_language_uid'] ;
            $url .= "&tx_mmforum_pi1[controller]=Topic&tx_mmforum_pi1[action]=show&tx_mmforum_pi1[topic]=" . $record['topic'] . "&tx_mmforum_pi1[forum]="  . $record['forumUid'] ;

            // get FE Groups and decide if we store show this public or allow access only for specific fe_groups

            $type = "allplanforumlocked" ;
            $feGroup = '' ;

            $accessData = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mmforum_domain_model_forum_attachment')
            ->select(
                ['login_level' , 'affected_group'],
                'tx_mmforum_domain_model_forum_access',
                ['operation' =>  'read' , 'forum' =>  $record['forumUid']],
                [],
                ['affected_group' => 'DESC ']
            );


            $feGroupsArray = array() ;

            while( $access = $accessData->fetch() ) {
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
                $debug2 .= ' ' . $record['uid'] . " ! ";
                $this->logToSystem( $debug2  ) ;
                break;
            }
        }

        $debug .= ' ' . $record['uid'] . " ! ";

        $this->logToSystem( $debug . " | Tags found: " . $tagsFound ) ;
        return intval($count);
    }
    private function logToSystem( $text ) {


        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_log')
            ->insert(
                'sys_log',
                [   "action"  => 1 ,
                    "tablename" => "tx_kesearch_index" ,
                    "error" => 0 ,
                    "event_pid" => 0 ,
                    "details" => $text  ,
                    "tstamp" => time() ,
                    "type" => 1 ,
                    "message" => "Indexer Forum Entries "
                ]
            );

    }

}