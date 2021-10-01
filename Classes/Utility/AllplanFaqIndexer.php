<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use Allplan\AllplanKeSearchExtended\Utility\AllplanFaqIndexerUtility;
use Allplan\AllplanTools\Utility\MailUtility;
use Allplan\NemSolution\Service\FaqWrapper;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
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

class AllplanFaqIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        // ToDo Put tags to Indexer object
        $indexerConfig['tags'] = "#allplanfaq#" ;

        /** @var AllplanFaqIndexerUtility $AllplanFaqIndexerUtility */
        $AllplanFaqIndexerUtility = GeneralUtility::makeInstance("Allplan\\AllplanKeSearchExtended\\Utility\\AllplanFaqIndexerUtility") ;
        // @extensionScannerIgnoreLine
        $AllplanFaqIndexerUtility->init($indexerConfig ,$indexerObject);


        $url = trim( $indexerObject->externalUrl ) ;
        $debug = "Url to XML File in Config: " . ($url) . "\n\n";
        $debug .= "Max Entrys in Config: " . intval($indexerObject->rowcount) . "\n\n";
        if ( $url == "") {
            $url = "http://212.29.3.155/hotline/FAQ_HOTD.nsf/0/05421C80A7EB2CE2C1257480004DDA2E/\$FILE/FAQIDs.xml?OpenElement";
            $debug = "Url to XML File set to: " . ($url) . "\n\n";
        }

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        $connection = $connectionPool->getConnectionForTable('tx_kesearch_index') ;

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_index') ;
        $query = $queryBuilder ->select('uid' , 'sortdate' , 'targetpid', 'tstamp' , 'orig_uid' ) ->from('tx_kesearch_index')
            ->where( $queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter( "supportfa%" , Connection::PARAM_STR)) )
            ->andWhere( $queryBuilder->expr()->gt('sortdate', $queryBuilder->createNamedParameter( 0 , Connection::PARAM_INT)) )
            ->orderBy("sortdate" , "DESC")
            ->addOrderBy("tstamp" , "DESC")
            ->setMaxResults(1)
            ->execute() ;

        $debug .="<hr> SQL : "  .   $queryBuilder->getSQL()  . " \n <br> \n <br>  ";

        $latestIndexRows =    $query->fetchAssociative();


        if( is_array($latestIndexRows )) {
            /* ****************  */
            $lastRun = date( "Y-m-d H:i:s" , ( $latestIndexRows['sortdate']  ) ) ;
            $lastRunTstamp = $latestIndexRows['sortdate']  ;
            $lastRunDay = date( "d" , ( $latestIndexRows['sortdate']  ) ) ;

            $debug .="<hr> Lastest FAQ Entry in DB = UID: "  . $latestIndexRows['uid'] . " | lastRun : ". $lastRun . " sortdate: " . $latestIndexRows['sortdate'] ;
        } else {
            $lastRun = "2014-12-31 00:00:00" ;
            $lastRunTstamp = 1 ;
            $lastRunDay = "0" ;
            $debug .="<hr> Found no FAQ Entry in DB = set LastRun to " . $lastRun  ;

        }
        $xmlFromUrl = $this->getExampleXml() ;
        // For testing  disable  the next command : $this->getJsonFile ... as something like above should come from ws call
        $xmlFromUrl = $this->getJsonFile($url , "urlset" , array('Accept: text/xml, Content-type:text/xml') , FALSE ) ;
        $xmlFromUrlDebug = strip_tags( str_replace( [ "<lastmod>" , "</lastmod>" , "<loc>" , "</loc>" ] , [" LastMod: " , "URL: " , "" , " "] , $xmlFromUrl) ) ;
        MailUtility::debugMail( array("jvelletti@allplan.com" ) , "[FAQ-Indexer] FAQ Indexer Will run with URL: $url ", $xmlFromUrlDebug . " \n\n "   ) ;


        $xml2 = simplexml_load_string ($xmlFromUrl  ) ;

        $debug .= "<hr>xlm2 from string:<br>" . substr( var_export( $xml2 , true ) , 0 , 200 )  . " .... " . strlen( $xml2 ) . " chars .. <hr />" ;
        $error = 0 ;
        $count = 0 ;
        $numIndexed = 0 ;
        $maxIndex =  $indexerObject->rowcount  ;
        $LastModDate = "9999-99-99" ;
        $LastModDay  = "99" ;

        if(  $indexerObject->rowcount < 1 ) {
            $maxIndex = 10000 ;
            $debug .= "Max Entrys set to: " . int( $maxIndex ) . "\n\n";
        }

        if( is_object($xml2)) {
            if( is_object( $xml2->url ) ) {
                $debug .= " ******************************************* put to array if newer than " . $lastRun . " Tstamp : " . $lastRunTstamp . " ****************** ";
                $faq2beIndexed = [] ;
                foreach ($xml2->url as $url) {
                    $notesLastMod = $url->lastmod;

                    if (strlen(trim($url->lastmod)) == 10) {
                        $notesLastMod .= " 23:59:59";
                    }
                    if ($notesLastMod > $lastRun ) {

                        $debug .= "\n <hr>url->loc: " . $url->loc . " : lastmod: " . $notesLastMod ;
                        $faq2beIndexed[]  = $url ;
                    } else {
                        $debug .= " ******************************************* latest already indexed FAQ  ****************** ";
                        $debug .= "\n  \n  <hr>url->loc: " . $url->loc . " : lastmod: " . $url->lastmod  ;
                    //    $faq2beIndexed[]  = $url ;
                        break ;
                    }
                }
                $reversed = array_reverse($faq2beIndexed) ;
                $debug .= " ******************************************* found " . count($reversed) . " ****************** ";
                if ( count($reversed ) > 0 ) {
                    MailUtility::debugMail( array("jvelletti@allplan.com" ) , "[FAQ-Indexer] FAQ Indexer found " . count($reversed) , $debug . " \n\n "   ) ;

                   foreach ( $reversed as $url ) {

                        $debug .= "<hr>url->loc: " . $url->loc . " : lastmod: " . $url->lastmod . "(" . $LastModDate . ") (" . $numIndexed . " / " . $maxIndex . ") ";


                        $numIndexed ++ ;


                        //we are near last to be indexed FAQ .. Keep its lastMode Date
                        if( $numIndexed >= ($maxIndex *.9 ) && $LastModDate == "9999-99-99"  ) {
                            $LastModDate = substr( trim($url->lastmod ) , 0, 10 ) ;
                            $LastModDay = substr($LastModDate , 9 , 2)  ;
                            $debug .= "<hr> Changed Last Mod Date to: " . $LastModDate . " and LastModDay to " . $LastModDay ;
                        }
                        if ( substr( trim($url->lastmod) , 0, 10 )   == $LastModDate   ||  $lastRunDay ==   $LastModDay ) {

                            // if f.e. max Index is configured 100 and the first 90 FAQ are changed on SAME DAY, we will index 190.
                            // if first 200 have the same date, it will continue until date cahnges and indexer will index 100 (configure Number) FAQs more
                            // and to be shure: if we get for all FAQs same lastmod date , this would lead to deadlock .. max should  3 times of config
                            if(  $indexerObject->rowcount > 1 ) {
                                if ( $maxIndex < ( $indexerObject->rowcount * 3 )) {
                                    $debug .= " - LINE: " . __LINE__ . "  (restricted maxIndex ++)" ;
                                    $maxIndex ++ ;
                                }
                            } else {
                                $debug .= " - LINE: " . __LINE__ . "  (unrestricted maxIndex ++) " ;
                                $maxIndex ++ ;
                            }

                        }
                        if( $numIndexed <= $maxIndex ) {
                            if( $AllplanFaqIndexerUtility->indexSingleFAQ( $url->loc , $url->lastmod )) {
                                $debug .= " .. Indexed " ;
                                $count++;
                            } else {
                                $debug .= " ! ERROR ! " ;
                                $error++;
                            }
                        }
                        $debug .= " \n " ;
                    }

                }
            }
        }
        // var_dump( $debug ) ;
        if ( $error > 0 ) {
            $error = true ;
            $introTag = "[FAQ-Indexer-ERROR]" ;
        } else {
            $introTag = "[FAQ-Indexer]" ;
        }

        $details  =  "Allplan FAQ Indexer : got '" . $numIndexed  . "' entries, got " . $error . " Errors and had updated / inserted : '" . $count . "' entries. Crawled: " . $url
        . " and got xlm2 from string: " . substr( var_export( $xml2 , true ) , 0 , 500 )  . " .... Total: " . strlen( $xml2 ) . " chars .." ;

        MailUtility::debugMail( array("jvelletti@allplan.com" , "slorenz@allplan.com" )
            , $introTag . " FAQ Indexer has run on '" . $count . "' objects ", $details . " \n\n " . $debug ) ;



        // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
        $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => $error > 0,
            "event_pid" => $pid ,
            "details" => $details ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => $debug ,

        ) ;

        $this->insertSyslog( $insertFields) ;

        if ( $error ) {
            return false ;
        }
        if( $count > 0 ) {
            return $count ;
        }
        return true ;

    }

    /**
     *  dummy function .. used for local testing
    */
    private function getExampleXml() {
        $return ='<?xml version="1.0" encoding="UTF-8"?>' . "\n" ;
        $return .='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n" ;
        $return .='   <url>' . "\n" ;
        $return .='   <loc>https://connect.allplan.com/de/faqid/20200820142654.html</loc>' . "\n" ;
        $return .='   <lastmod>2017-05-29</lastmod>' . "\n" ;
        $return .='</url>' . "\n" ;
        $return .='</urlset>' ;
        return $return ;
    }




}