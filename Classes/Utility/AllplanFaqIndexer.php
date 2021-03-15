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


        $url = $indexerObject->externalUrl  ;
        $debug = "url: " . ($url) ;

        $xmlFromUrl = $this->getExampleXml() ;
        // For testing  disable  the next command : $this->getJsonFile ... as something like above should come from ws call
        $xmlFromUrl = $this->getJsonFile($url , "urlset" , array('Accept: text/xml, Content-type:text/xml') , FALSE ) ;

        $xml2 = simplexml_load_string ($xmlFromUrl  ) ;

        $debug .= "<hr>xlm2 from string:<br>" . substr( var_export( $xml2 , true ) , 0 , 200 )  . " .... " . strlen( $xml2 ) . " chars .. <hr />" ;
        $error = 0 ;
        $count = 0 ;
        $numIndexed = 0 ;
        $maxIndex =  $indexerObject->rowcount  ;
        if(  $indexerObject->rowcount < 1 ) {
            $maxIndex = 10000 ;
        }

        if( is_object($xml2)) {
            if( is_object( $xml2->url ) ) {
                foreach ($xml2->url as $url) {
                    $debug .= "<hr>url->loc: " . $url->loc . " : lastmod: " . $url->lastmod . "\n";
                    $numIndexed ++ ;
                    if( $numIndexed < $maxIndex ) {
                        if( $AllplanFaqIndexerUtility->indexSingleFAQ( $url->loc , $url->lastmod )) {
                            $count++;
                        } else {
                            $error++;
                        }
                    }
                }
            }
        }
        // var_dump( $debug ) ;
        if ( $error > 0 ) {
            $error = true ;
        } else {

        }

        $details  =  "Allplan FAQ Indexer : got '" . $numIndexed  . "' entries, got " . $error . " Errors and had updated / inserted : '" . $count . "' entries. Crawled: " . $url
        . " and got xlm2 from string: " . substr( var_export( $xml2 , true ) , 0 , 500 )  . " .... Total: " . strlen( $xml2 ) . " chars .." ;

        MailUtility::debugMail( array("jvelletti@allplan.com" , "slorenz@allplan.com" ) , "[FAQ-Indexer] FAQ Indexer has run on '" . $count . "' objects ", $details . " \n\n " . $debug ) ;



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