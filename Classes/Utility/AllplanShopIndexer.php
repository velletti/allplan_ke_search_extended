<?php
namespace Allplan\AllplanKeSearchExtended\Utility;
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;

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

class AllplanShopIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        //
        // http://shop.allplan.com/export/sitemap1.xml
        // or
        // http://shop.allplan.com/export/sitemap2.xml and so on ...

        $url = $indexerObject->externalUrl  ;
        $debug = "url: " . ($url) ;
        // ToDo Put tags to Indexer object
        $indexerConfig['tags'] = "#shop#" ;

        // For testing  disable the next command getJsonFile()  .. ... something like this should come from that call
        $xmlFromUrl = '<?xml version="1.0" encoding="UTF-8"?>
    <urlset>
    <url>
        <loc>http://shop.allplan.com/Web-Applikationen/AX3000-Energie-Wohngebaeude-Bedarf.html</loc>
        <priority>1.0</priority>
        <lastmod>2017-06-21T11:27:46+00:00</lastmod>
        <changefreq>daily</changefreq>
    </url>
    <url>
        <loc>http://shop.allplan.com/Bimplus-Optionen/Bimplus-100-GB-Speicher-100.html</loc>
        <priority>1.0</priority>
        <lastmod>2017-06-21T11:27:46+00:00</lastmod>
        <changefreq>daily</changefreq>
        </url>
      </url>  
       <urlset>
        ' ;

        $xmlFromUrl = $this->getJsonFile($url , "urlset" , array('Accept: text/xml, Content-type:text/xml') , FALSE ) ;

        $xml2 = simplexml_load_string ($xmlFromUrl  ) ;

        // this file dies the magic to import the html CODe to a DOM Object
            include_once(__DIR__ . "/simple_html_dom.php") ;
            // see details ..
            // http://www.phpbuilder.com/columns/PHP_HTML_DOM_parser/PHPHTMLDOMParser.cc_09-07-2011.php3

            $htmlParser = new \simple_html_dom();


        $debug .= "<hr>xlm2 from string:<br>" . substr( var_export( $xml2 , true ) , 0 , 200 )  . " .... " . strlen( $xml2 ) . " chars .. <hr />" ;
        $count = 0 ;
        $lastRunRow = $this->getRecordRaw( "tx_kesearch_index" , "`type` = 'shop' ORDER BY starttime DESC ") ;
        // $lastRun = "2014-12-12" ;
        if( is_array($lastRunRow )) {
            $lastRun = date( "Y-m-d" , $lastRunRow['sorttime'] ) ;
            $debug .="<hr> Lastrun from DB = " . $lastRun;
        }
        if( $indexerObject->period > 365 ) {
            $lastRun = date( "Y-m-d" , time() - ( 60 * 60 * 24 * ( $indexerObject->period - 365 )) ) ;
            $debug .="<hr> Lastrun from Indexer config Field Period  = " . $lastRun;

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

                        // ToDo Adjust PIDs für die Sprachen
                        $indexlang = 0  ;
                        switch( substr( $urlSingleArray['path'] , 1,2 )) {
                            case "en":
                                $lang = 0 ;
                                $indexlang = -1 ;
                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            case "it":
                                $lang = 2 ;

                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            case "cz":
                                $lang = 3 ;
                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            case "fr":
                                $lang = 4 ;
                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            case "es":
                                $lang = 18 ;
                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            case "ru":
                                $lang = 14 ;
                                $indexerConfig['pid'] = 5091 ;
                                break ;
                            default:
                                $lang = 1 ;
                                $indexlang = -1 ;
                                $indexerConfig['pid'] = 5090 ;
                                break ;
                        }
                        if (  $indexlang == 0   ) {
                            $indexlang = $lang  ;
                        }
                        $urlSingle = $url->loc  ;


                        $debug .= "<hr>url loc: " . $urlSingle ;

                        $singlepage = $this->getJsonFile( $urlSingle   , "" , array ( 'Accept: text/html, Content-type:text/html') , FALSE ) ;

                       // $debug .= "<hr>" . var_export( $singlepage  , true ) ;
                        // ToDo convert single page t object and strip the HTML Text ..
                        if( strlen( $singlepage ) > 200 ) {


                            $single['uid']  = $this->convertIdToINT ( $url->loc  , $indexlang ) ;

                            $htmlParser->load($singlepage) ;

                            $ret = $htmlParser->find( "title" );
                            $first = $ret[0] ;
                            $single['title'] = strip_tags( str_replace( "Allplan Shop | " , "" , $first->plaintext  )) ;
                            $debug .= "<hr>ID: " . $single['uid'] . " - " . $single['title'] ;

                            $ret = $htmlParser->find( "meta[name=\"description\"]" );
                            $first = $ret[0] ;
                            $meta = $first->attr   ;
                            $single['abstract']  = $meta['content'] ;
                            $debug .= "<br>Abstract: " . $single['abstract'] ;

                            $findContentMarkers = array(".tabbedWidgetBox" , ".infogridView" ) ;

                            $text = "" ;
                            foreach ( $findContentMarkers as $marker ) {
                                $ret = $htmlParser->find( $marker );

                                if( is_array($ret )) {
                                    if( count($ret) > 0) {
                                        $first = $ret[0] ;
                                        $text .= str_replace( "     " , " " , $first->plaintext  ) ;
                                    }
                                }
                            }

                            $text = str_replace( "   " , " " , $text  ) ;
                            $text = str_replace( "  " , " " , $text  ) ;

                            $debug .= "<Hr>Text: " . $text  ;


                            // =  $url->STRSUBJECT  ;
                            $single['text'] =  $single['abstract']  . " " . $text  ;
                            $single['language'] =  $indexlang ;


                            $single['sortdate'] = mktime( 0 , 0 , 0 ,
                                substr(  $url->lastmod ,8 ,2 ) ,
                                substr( $url->lastmod , 5 ,2 ) ,
                                substr( $url->lastmod ,0 ,4 )
                            ) ;
                            $single['url'] = $url->loc  ;

                            if( $this->putToIndex( $single , $indexerObject , $indexerConfig) ) {
                                $debug .= "<hr>" . var_export( $single , true ) ;
                                $count++ ;
                            }
                        }
                        $htmlParser->clear();
                        unset($single) ;
                        unset($singlepage) ;
                        unset($singlepageContent) ;
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
            "details" => "Shop Indexer had updated / inserted " . $i . " entrys" ,
            "tstamp" => time() ,
            "type" => 1 ,
            "message" => $debug ,

        ) ;
        $this->insertSyslog( $insertFields) ;
        return $count ;
    }
    protected function putToIndex(array $single , \TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner $indexerObject , array  $indexerConfig ) {

        // take storage PID form indexexer Configuration ... Hard Coded by Language !!!
        $pid =  $indexerConfig['pid'] ;

        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == "connect" ) {
            $server = "connect.allplan.com" ;
        }
        if( $server == "www-typo3.allplan.com" ||  $server == "vm5012986.psmanaged.com" ||   $server == "allplan" ||   $server == "www") {
            $server = "www.allplan.com" ;
        }
        return $indexerObject->storeInIndex(
            $pid ,			                // folder, where the indexer data should be stored (not where the data records are stored!)
            $single['title'] ,							    // title in the result list
            'shop',				                    // content type ( useful, if you want to use additionalResultMarker)
            $single['url']                              ,	// uid of the targetpage (see indexer-config in the backend)
            strip_tags ( $single['text'] ) , 						                // below the title in the result list
            $indexerConfig['tags'] . $single['tags'] ,						// tags
            '_blank' ,                                      // additional params for the link
            substr( strip_tags( $single['text'] ) , 0 , 200 ) ,			// abstract
            $single['language'] ,				    // sys_language_uid
            0 ,						// starttime (not used here)
            0,						// endtime (not used here)
            '',						// fe_group (not used here)
            false ,					// debug only?
            array( 'sortdate' => $single['sortdate'] , 'orig_uid' => $single['uid'] , 'servername' => $server )				// additional fields added by hooks
        );

    }



}