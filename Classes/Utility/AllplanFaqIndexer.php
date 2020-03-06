<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use TYPO3\CMS\Core\Database\Connection;
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

class AllplanFaqIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        // rendered by an agent every 4 hours
        // http:// IP of the news Server see doku/hotline/FAQ_HOTD.nsf/0/05421C80A7EB2CE2C1257480004DDA2E/\$File/FAQIDs.xml?OpenElement
        // http://212.29.3.155/hotline/FAQ_HOTD.nsf/0/05421C80A7EB2CE2C1257480004DDA2E/\$File/FAQIDs.xml?OpenElement

        $url = $indexerObject->externalUrl  ;

        $debug = "url: " . ($url) ;

        // ToDo Put tags to Indexer object
        $indexerConfig['tags'] = "#allplanfaq#" ;

        // For testing  disable  the next command ... something like this should come from next ws call
        $xmlFromUrl = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        <url>
            <loc>https://connect.allplan.com/de/faqid/000171ca.html</loc>
            <lastmod>2017-05-29</lastmod>
       </url>
        </urlset>
        ' ;

        $xmlFromUrl = $this->getJsonFile($url , "urlset" , array('Accept: text/xml, Content-type:text/xml') , FALSE ) ;

        $xml2 = simplexml_load_string ($xmlFromUrl  ) ;

        $debug .= "<hr>xlm2 from string:<br>" . substr( var_export( $xml2 , true ) , 0 , 200 )  . " .... " . strlen( $xml2 ) . " chars .. <hr />" ;

        $count = 0 ;
        $lastRunRow = $this->getRecordRaw( "tx_kesearch_index" , "`type` = 'allplanfaq' ORDER BY sortdate DESC ") ;
        $lastRun = "2014-12-12" ;
        if( $indexerObject->period > 365 ) {
            $lastRun = date( "Y-m-d" , time() - ( 60 * 60 * 24 * ( $indexerObject->period -1 )) ) ;
            $debug .="<hr> Lastrun from Indexer config Field Period  = " . $lastRun;

        }

        if( is_array($lastRunRow )) {
            $lastRun = date( "Y-m-d" , $lastRunRow['sortdate'] ) ;
            $debug .="<hr> Lastrun from DB = " . $lastRun;
        }


        if( is_object($xml2)) {
            $debug .="<hr> xml2 is Object" ;
            if( is_object( $xml2->url ) ) {
                $debug .="<hr> xml2->url is Array" ;
                $i = 0 ;
                foreach ($xml2->url as $url) {
                    $debug .= "<hr>url loc: " . $url->loc . " : lastmod: " . $url->lastmod ;

                    if( $url->lastmod > $lastRun ) {
                        $i++ ;
                        $urlSingleArray = parse_url( $url->loc ) ;
                        $indexlang = 0  ;
                        switch( substr( $urlSingleArray['path'] , 1,2 )) {
                            case "de":
                                $lang = 1 ;
                                $indexlang = -1 ;
                                $indexerConfig['pid'] = 5025 ;
                                break ;
                            case "it":
                                $lang = 2 ;

                                $indexerConfig['pid'] = 5027 ;
                                break ;
                            case "cz":
                                $lang = 3 ;
                                $indexerConfig['pid'] = 5027 ;
                                break ;
                            case "fr":
                                $lang = 4 ;
                                $indexerConfig['pid'] = 5026 ;
                                break ;
                            case "es":
                                $lang = 18 ;
                                $indexerConfig['pid'] = 5027 ;
                                break ;
                            case "ru":
                                $lang = 14 ;
                                $indexerConfig['pid'] = 5027 ;
                                break ;
                            default:
                                $lang = 0 ;
                                $indexerConfig['pid'] = 5027 ;
                                break ;
                        }
                        if (  $indexlang == 0   ) {
                            $indexlang = $lang  ;
                        }
               //         $urlSingleArray['host'] = "connectv9.allplan.com.ddev.local" ;
                        $urlSingle = $urlSingleArray['scheme'] . "://" . $urlSingleArray['host'] . "/index.php?" ;
                        $urlSingle .= "&id=5566&L=" . $lang ;
                        $urlSingle .= "&tx_nemsolution_pi1[dokID]=" . str_replace( ".html" , "" , substr( $urlSingleArray['path'] , strpos( strtolower( $urlSingleArray['path'] ) , "faqid") + 6 ) ) ;
                        $urlSingle .= "&tx_nemsolution_pi1[action]=show&tx_nemsolution_pi1[controller]=Solution&tx_nemsolution_pi1[json]=1" ;


                        $debug .= "<hr>url loc: " . $urlSingle ;


                        // https://connect.allplan.com/index.php?&id=5566&L=1&tx_nemsolution_pi1[docID]=000171ca&tx_nemsolution_pi1[action]=show&tx_nemsolution_pi1[controller]=Solution&tx_nemsolution_pi1[json]=1
                        $singleFaqRaw = $this->getJsonFile( $urlSingle   , "" , array ( "Accept: application/json" , "Content-type:application/json" ) , FALSE ) ;

                        $singleFaq = json_decode($singleFaqRaw) ;
            //            $debug .= "<hr>Decoded Json: " . var_export( $singleFaq , true ) ;
                        if( is_object($singleFaq) ) {
                            $single['uid']  = $this->convertIdToINT ( $singleFaq->STRDOK_ID , $indexlang ) ;
                            $debug .= "<br>ID: " . $single['uid'] ;

                            $single['STRSUBJECT'] =  $singleFaq->STRSUBJECT  ;
                            $single['STRTEXT'] =  $singleFaq->STRTEXT  ;
                            $single['language'] =  $indexlang ;

                            if( is_array( $singleFaq->LSTPROGRAMME )) {
                                foreach ( $singleFaq->LSTPROGRAMME as $tag ) {
                                    $single['tags'] .= ",#" . strtolower( str_replace( " " , "" , $tag ))  . "#" ;
                                }
                            }

                            $single['sortdate'] = mktime( 0 , 0 , 0 , substr(  $singleFaq->STRBEARBEITUNGSSTAND ,3 ,2 ) ,
                                substr( $singleFaq->STRBEARBEITUNGSSTAND , 0 ,2 ) ,  substr( $singleFaq->STRBEARBEITUNGSSTAND ,6 ,4 ) ) ;
                            $single['url'] = $url->loc  ;

                            if( $this->putToIndex( $single , $indexerObject , $indexerConfig) ) {
                                $debug .= "<hr>Single= " . var_export( $single , true ) ;
                                $count++ ;
                            }
                        } else {
                            $debug .= "<hr>Error in RAW Json:"  ;

                            switch(json_last_error()) {
                                case JSON_ERROR_DEPTH:
                                    $debug .= ' - Maximale Stacktiefe überschritten';
                                    break;
                                case JSON_ERROR_STATE_MISMATCH:
                                    $debug .= ' - Unterlauf oder Nichtübereinstimmung der Modi';
                                    break;
                                case JSON_ERROR_CTRL_CHAR:
                                    $debug .= ' - Unerwartetes Steuerzeichen gefunden';
                                    break;
                                case JSON_ERROR_SYNTAX:
                                    $debug .= ' - Syntaxfehler, ungültiges JSON';
                                    break;
                                case JSON_ERROR_UTF8:
                                    $debug .= ' - Missgestaltete UTF-8 Zeichen, möglicherweise fehlerhaft kodiert';
                                    break;
                                default:
                                    $debug .= ' - Unbekannter Fehler';
                                    break;
                            }
                            $debug .= "<hr>RAW Json:" . htmlentities( $singleFaqRaw  ) ;
                        }

                        unset($single) ;
                        unset($singleFaq) ;
                    }
                }
            }
        }
        // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
        $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => 0 ,
            "event_pid" => $pid ,
            "details" => "Allplan FAQ Indexer had updated / inserted " . $count . " entrys" ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => $debug ,

        ) ;

        $this->insertSyslog( $insertFields) ;


        return $count ;
    }
    protected function putToIndex(array $single , AllplanKesearchIndexer $indexerObject , array  $indexerConfig ) {

        // Prepare data for the indexer
        $content = $single['title'] . PHP_EOL . nl2br($single['text']) ;


        // take storage PID form indexexer Configuration ... Hard Coded by Language !!!
        $pid =  $indexerConfig['pid'] ;

        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "www-typo3.allplan.com" ||  $server == "vm5012986.psmanaged.com" ||   $server == "allplan" ||   $server == "www") {
            $server = "www.allplan.com" ;
        } else {
            $server = "connect.allplan.com" ;
        }
        return $indexerObject->storeInIndex(
            $pid ,			                // folder, where the indexer data should be stored (not where the data records are stored!)
            $single['STRSUBJECT'] ,							    // title in the result list
            'supportfaq',				                    // content type ( useful, if you want to use additionalResultMarker)
            $single['url']                              ,	// uid of the targetpage (see indexer-config in the backend)
            strip_tags ( $single['STRTEXT'] ) , 						                // below the title in the result list
            $indexerConfig['tags'] . $single['tags'] ,						// tags
            '_blank' ,                                      // additional params for the link
            substr( strip_tags( $single['STRTEXT'] ) , 0 , 200 ) ,			// abstract
            $single['language'] ,				    // sys_language_uid
            0 ,						// starttime (not used here)
            0,						// endtime (not used here)
            '',						// fe_group (not used here)
            false ,					// debug only?
            array( 'sortdate' => $single['sortdate'] , 'orig_uid' => $single['uid'] , 'servername' => $server  )				// additional fields added by hooks
        );

    }
    protected function convertIdToINT( $notes_id , $lang) {

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_allplan_url_ids')->createQueryBuilder();
        $queryBuilder->select('uid')
            ->from('tx_kesearch_allplan_url_ids') ;

        $expr = $queryBuilder->expr();
        $queryBuilder->where(
            $expr->eq('notes_id', $queryBuilder->createNamedParameter($notes_id, Connection::PARAM_STR))
        )->andWhere(
            $expr->eq('sys_language_uid', $queryBuilder->createNamedParameter(intval($lang), Connection::PARAM_INT))
        )->setMaxResults(1) ;


        $row = $queryBuilder->execute()->fetch();

        if ( $row && count ( $row ) == 0 ) {
            $uid = $row['uid'] ;
        } else {

            $data = array( "pid" => 0 , "notes_id" => $notes_id , "sys_language_uid" => intval($lang)  ) ;
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_allplan_url_ids')->createQueryBuilder();
            /** @var \TYPO3\CMS\Core\Database\Connection $connection */
            $connection = $connectionPool->getConnectionForTable('tx_kesearch_allplan_url_ids') ;

            $queryBuilder->insert("tx_kesearch_allplan_url_ids")->values( $data)->execute() ;

            $uid = $connection->lastInsertId('tx_kesearch_allplan_url_ids') ;

        }
        if ( $uid == 0 ) {
          //  var_dump($notes_id) ;
            //  var_dump($lang ) ;
            //  var_dump($data ) ;
            //  var_dump($row) ;
            //  die ;
        }
        return $uid  ;
    }


}