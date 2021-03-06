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

class AllplanFaqIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
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
            $debug .="<hr> Lastrun from DB = " . $lastRun . "\n\n";
        }

        $error = 0 ;
        if( is_object($xml2)) {
            $debug .="<hr> xml2 is Object" ;
            if( is_object( $xml2->url ) ) {
                $debug .="<hr> xml2->url is Object" ;
                $i = 0 ;
                if (PHP_SAPI === 'cli') {
                    echo $debug ;
                }
                foreach ($xml2->url as $url) {
                    $debugSub = "<hr>url->loc: " . $url->loc . " : lastmod: " . $url->lastmod . "\n";

                    if( $url->lastmod > $lastRun ) {
                        $i++ ;
                        $urlSingleArray = parse_url( $url->loc ) ;
                        $indexlang = 0  ;
                        switch( substr( $urlSingleArray['path'] , 1,2 )) {
                            case "de":
                                $lang = 1 ;
                                $indexlang = -1 ;
                                $indexerConfig['pid'] = 5025 ;
                                $category = "STRCATEGORY_DE" ;
                                break ;
                            case "it":
                                $lang = 2 ;

                                $indexerConfig['pid'] = 5027 ;
                                $category = "STRCATEGORY_IT" ;
                                break ;
                            case "cz":
                                $lang = 3 ;
                                $indexerConfig['pid'] = 5027 ;
                                $category = "STRCATEGORY_CS" ;
                                break ;
                            case "fr":
                                $lang = 4 ;
                                $indexerConfig['pid'] = 5026 ;
                                $category = "STRCATEGORY_FR" ;
                                break ;
                            case "es":
                                $lang = 18 ;
                                $indexerConfig['pid'] = 5027 ;
                                $category = "STRCATEGORY_ES" ;
                                break ;
                            case "ru":
                                $lang = 14 ;
                                $indexerConfig['pid'] = 5027 ;
                                $category = "STRCATEGORY_EN" ; // RU is not in Response
                                break ;
                            default:
                                $lang = 0 ;
                                $indexerConfig['pid'] = 5027 ;
                                $category = "STRCATEGORY_EN" ;
                                break ;
                        }
                        if (  $indexlang == 0   ) {
                            $indexlang = $lang  ;
                        }
                        $urlSingle = $urlSingleArray['scheme'] . "://" . $urlSingleArray['host'] . "/index.php?" ;
                        $docID = str_replace( ".html" , "" , substr( $urlSingleArray['path'] , strpos( strtolower( $urlSingleArray['path'] ) , "faqid") + 6 ) )  ;
                        $token = hash( "sha256" , $docID  . "JV-" . date("Y-m-d") ) ;
                        $urlSingle .= "&id=5566&L=" . $lang ;
                        $urlSingle .= "&tx_nemsolution_pi1[dokID]=" . $docID;
                        $urlSingle .= "&tx_nemsolution_pi1[action]=show&tx_nemsolution_pi1[ug]=ne&tx_nemsolution_pi1[controller]=Solution&tx_nemsolution_pi1[json]=1&tx_nemsolution_pi1[token]=" . $token;


                        $debugSub .= "<hr>Get FAQ via Curl " . $urlSingle ;


                        // https://connect.allplan.com/index.php?&id=5566&L=1&tx_nemsolution_pi1[docID]=000171ca&tx_nemsolution_pi1[action]=show&tx_nemsolution_pi1[controller]=Solution&tx_nemsolution_pi1[json]=1
                        $singleFaqRaw = $this->getJsonFile( $urlSingle   , "" , array ( "Accept: application/json" , "Content-type: application/json" ) , FALSE , 90) ;
                        $singleFaq = json_decode($singleFaqRaw) ;
                        $debugSub .= "<hr>" . var_export( $singleFaq , true ) ;
                        if( !is_object($singleFaq) ) {
                            $debugSub .= "<hr>" . var_export( $singleFaqRaw , true ) ;
                            $singleFaqRaw = $this->getJsonFile( $urlSingle   , "" , array ( "Accept: application/json" , "Content-type: application/json" ) , TRUE , 90) ;
                            $debugSub .= "<hr>Response with errorCode" . var_export( $singleFaqRaw , true ) ;
                            $error = 1 ;
                        } else {
                            $single['uid']  = $this->convertIdToINT ( $singleFaq->STRDOK_ID , $indexlang ) ;
                            $debugSub .= "<br>ID: " . $single['uid'] ;

                            $single['STRSUBJECT'] =  $singleFaq->STRSUBJECT  ;
                            $single['STRCATEGORY'] =  $singleFaq->$category  ;
                            $single['STRTEXT'] =  $singleFaq->$category . "\n" . $singleFaq->STRTEXT   ;
                            $single['language'] =  $indexlang ;

                            if( is_array( $singleFaq->LSTPROGRAMME )) {
                                foreach ( $singleFaq->LSTPROGRAMME as $tag ) {
                                    $single['tags'] .= ",#" . strtolower( str_replace( " " , "" , $tag ))  . "#" ;
                                }
                            }

                            $single['sortdate'] = mktime( 0 , 0 , 0 , substr(  $singleFaq->STRBEARBEITUNGSSTAND ,3 ,2 ) ,
                                substr( $singleFaq->STRBEARBEITUNGSSTAND , 0 ,2 ) ,  substr( $singleFaq->STRBEARBEITUNGSSTAND ,6 ,4 ) ) ;
                            $single['url'] = $url->loc  ;


                            switch ($singleFaq->STRINTERNET_RELEASE_FOR) {
                                case "Everybody":
                                    $single['type']     = "supportfaq" ;
                                    $single['feGroup']  = '' ;
                                    break;

                                case "Beta Tester":
                                    $single['type']     = "supportfaqbeta" ;
                                    $single['feGroup']  = '38,7,4' ;
                                    break;

                                case "Portal user":
                                    $single['type']     = "supportfaqsp" ;
                                    $single['feGroup']  = '38,7,4,3' ;
                                    break;
                                case "Nemetschek only":
                                    $single['type']     = "supportfaqnem" ;
                                    $single['feGroup']  = '38,7' ;
                                    break;

                                default:
                                    $single['type']     = "supportfaqlocked" ;
                                    $single['feGroup']  = '38' ;
                                    break;
                            }


                            if( $this->putToIndex( $single , $indexerObject , $indexerConfig) ) {
                                $debugSub .= "<hr>" . var_export( $single , true ) ;
                                $count++ ;
                            }
                        }

                        unset($single) ;
                        unset($singleFaq) ;
                    }

                    if (PHP_SAPI === 'cli') {
                        echo $debugSub ;
                    }
                    $debug .= $debugSub ;

                }
            }
        }
        // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
        $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

        $insertFields = array(
            "action"  => 1 ,
            "tablename" => "tx_kesearch_index" ,
            "error" => $error ,
            "event_pid" => $pid ,
            "details" => "Allplan FAQ Indexer got " . $i . " entries and had updated / inserted : " . $count . " entries " ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => $debug ,

        ) ;

        $GLOBALS['TYPO3_DB']->exec_INSERTquery("sys_log" , $insertFields ) ;
        return $count ;
    }
    protected function putToIndex(array $single , \tx_kesearch_indexer $indexerObject , array  $indexerConfig ) {

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
            $single['type'] ,				                    // content type ( useful, if you want to use additionalResultMarker)
            $single['url']                              ,	// uid of the targetpage (see indexer-config in the backend)
            strip_tags ( $single['STRTEXT'] ) , 						                // below the title in the result list
            $indexerConfig['tags'] . $single['tags'] ,						// tags
            '_blank' ,                                      // additional params for the link
            substr( strip_tags( $single['STRTEXT'] ) , 0 , 200 ) ,			// abstract
            $single['language'] ,				    // sys_language_uid
            0 ,						// starttime (not used here)
            0,						// endtime (not used here)
            $single['feGroup'],						// fe_group ('' , '7' , '7,4' , or '7,4,3' )
            false ,					// debug only?
            array( 'sortdate' => $single['sortdate'] , 'orig_uid' => $single['uid'] , 'servername' => $server  )				// additional fields added by hooks
        );

    }
    protected function convertIdToINT( $notes_id , $lang) {
        $row = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows("uid" , "tx_kesearch_allplan_url_ids" ,
                        "notes_id = '" . $notes_id . "' AND sys_language_uid = " . intval($lang) . " AND deleted = 0 "  , '' , "" , "1" ) ;
        if ( count ( $row ) == 0 ) {
            $data = array( "pid" => 0 , "notes_id" => $notes_id , "sys_language_uid" => intval($lang)  ) ;
            $GLOBALS['TYPO3_DB']->exec_INSERTquery("tx_kesearch_allplan_url_ids" , $data ) ;
            $uid = $GLOBALS['TYPO3_DB']->sql_insert_id() ;
        } else {
            $uid = $row[0]['uid'] ;
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