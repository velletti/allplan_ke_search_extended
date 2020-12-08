<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
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

class AllplanFaqIndexerUtility
{
    /* @var array */
    public $indexerConfig ;

    /* @var AllplanKesearchIndexer */
    public $indexerObject ;

    /** @var FaqWrapper  */
    public $faqWrapper ;

    public function init( $indexerConfig=[] , $indexerObject=null) {
        // ToDo  : move this to settings....
        $pathToWebService = 'http://212.29.3.155/hotline/FAQ_HOTD.nsf/FAQ?OpenWebService' ;
        $pathToWSDL = Environment::getProjectPath() . '/wsdl/FAQ_RPC.wsdl' ;
        $namespace = 'Nemetschek:ProxySystem:FAQ:Types:1.0:1.0' ;
        // connect to the webservice
        /** @var FaqWrapper $faqWrapper */
        $this->faqWrapper = GeneralUtility::makeInstance(
            'Allplan\\NemSolution\\Service\\FaqWrapper',
            $pathToWSDL,
            $pathToWebService ,
            $namespace
        );
        if($indexerObject) {
            $this->indexerObject = $indexerObject ;
        } else {
            // load hardcoded Indexer Object to emulate Single Indexer AllplanKesearchIndexer extends IndexerRunner
            $this->indexerObject = GeneralUtility::makeInstance(AllplanKesearchIndexer::class);

            // register additional fields which should be written to DB
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'] as $_classRef) {
                    $_procObj = GeneralUtility::makeInstance($_classRef);
                    $_procObj->registerAdditionalFields($this->indexerObject->additionalFields);
                }
            }

            // set some prepare statements
            $this->indexerObject->prepareStatements();
        }
        $this->indexerConfig = $indexerConfig ;

    }

    /**
     * @param string  $url Singel URL to index
     * @param string  $lastRun when was faq Indexer last time updated ?   2020.09.30  set it to future to enforce indexing
     * @param mixed   $faq singe FAQ Search result ... only if already loaded in Nem Solution .. to update index
     * @return boolean
     */

    public function indexSingleFAQ( $url , $lastRun , $faq = false ) {
        $debugOutput = false ;
        if(  $faq) {
     //       $debugOutput = true ;
        }

        $debug[] = array( "LINE:" => __LINE__ ,  "url" => $url ) ;

        $indexerConfig = $this->indexerConfig ;
        $indexerObject = $this->indexerObject ;

        // ToDo Put tags to Indexer object
        $indexerConfig['tags'] = "#allplanfaq#" ;

        $options['htmlfrom'] = array( 'face="Vorgabe Sans Serif"' , 'type="disc"', "&amp;#345;", "\n" ,"&amp;#"
        , "<ul><ol" , "</ol></ul>", "<ul><ul" , "</ul></ul>" , "<br>" , "<br/>") ;

        $options['htmlto'] = array('','','ř' , "", "&#"
        , "<ol" , "</ol>" , "<ul" , "</ul>" , "<BR />", "<BR />") ;

        $options['fromdecode'] = "ISO-8859-1" ;

        $urlSingleArray = parse_url( $url ) ;
        $currentLang =  substr( $urlSingleArray['path'] , 1,2 ) ;
        if( !in_array( $currentLang , array("en" , "de" , "it" , "fr" ,"es" , "ru" , "cz" , "tr" ) )) {
            $currentLang = "en" ;
        }
        $docID = str_replace( ".html" , "" , substr( $urlSingleArray['path'] , strpos( strtolower( $urlSingleArray['path'] ) , "faqid") + 6 ) )  ;

        $urlSingleArray = parse_url( $url ) ;
        $indexlang = 0  ;
        $options['fromdecode'] = "ISO-8859-1" ;
        switch($currentLang ) {
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
                $options['fromdecode'] = "ISO-8859-2" ;
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

        $singleUid = $this->convertIdToINT( $docID , $indexlang );
        $debug[] = array( "LINE:" => __LINE__ ,  "singleUid" => $singleUid ) ;

        $aktIndex = $this->getIndexerById($singleUid)  ;
        if($aktIndex ) {
            $debug[] = array( "LINE:" => __LINE__ ,  "aktIndex" => $aktIndex ) ;
            $lastMod = date( "Y-m-d" , $aktIndex['sortdate'] ) ;
            $debug[] = array( "LINE:" => __LINE__ ,  "lastMod" => $lastMod ) ;
            if( $lastMod >= $lastRun ) {
                if($debugOutput) {  var_dump($debug) ; die; };
                return true ;
            }
            // delete all old entries of this FAX from index. needed as update index takes "type" into account
            // but type may change from supportfaq to a restricted  supportfaqnem
            $this->deleteFromIndexById($singleUid) ;
        } else {
            // if we found one or more  entries in tx_kesearch_allplan_url_ids but no indexed FAQ in kesearch_index, we must remove this garbage
            $debug[] = array( "LINE:" => __LINE__ ,  "delete  tx_kesearch_allplan_url_ids $docID and $indexlang " => $docID . " - " . $indexlang) ;
            $this->deleteIdToINTentries( $docID , $indexlang) ;
            $singleUid = $this->convertIdToINT( $docID , $indexlang );
        }

        $params['VARVERSION'] = array("2020" , "2019");
        $params['STRPRODUKT'] = 'Allplan' ;
        $params['STRLANGUAGE'] = strtoupper( $currentLang ) ;

        $params['INTSORTORDER'] = '32';
        $params['STRQUERY'] = substr( $urlSingleArray['path'] , strpos( strtolower( $urlSingleArray['path'] ) , "faqid") + 6 )  ;

        // Enable  next lines just for testing a spezific FAQ
       // $params['STRQUERY'] = "20150618130717.html" ;
        $params['STRUSERGROUP'] = "ne";

        $params['STRTOPTEN'] = '1-1'  ;

        if( !$faq ) {
            $debug[] = array( "LINE:" => __LINE__ ,  "got no FAQ - search FAQ py params " => $params ) ;
            $faq = $this->faqWrapper->getSingleFAQdirect($params);
        }



        if( is_array($faq) && array_key_exists('FNCSEARCHReturn' , $faq) && array_key_exists('FAQSEARCHLIST' , $faq['FNCSEARCHReturn']) ) {
            $singleFaq = $faq['FNCSEARCHReturn']['FAQSEARCHLIST']['FAQENTRIES'][0] ;
        }
        if( !is_array($singleFaq) ) {
            $debug[] = array( "LINE:" => __LINE__ ,  "Stop here .. Got no FAQ !! " => $singleFaq ) ;
            if($debugOutput) {  var_dump($debug) ; die; };
            return false  ;
        } else {

            // https://connect.allplan.com/de/support/loesungen/show.html?tx_nemsolution_pi1[action]=show&tx_nemsolution_pi1[controller]=Solution&tx_nemsolution_pi1[dokID]=20200313091506&tx_nemsolution_pi1[ug]=ne&tx_nemsolution_pi1[json]=1


            if ( array_key_exists('TODELETE' , $singleFaq ) && $singleFaq['TODELETE'] === true ) {
                // 26.11.2020 "TODELETE"  = TRUE ... as above all entries if this FAQ have been deleted deleteFromIndexById(). it should work now
                $debug[] = array( "LINE:" => __LINE__ ,  "Stop here .. marked as do be deleted " => "!" ) ;
                if($debugOutput) {  var_dump($debug) ; die; };
                return true ;
            }

            // echo " <hr> **********************+ +text html_entity_decode =" ;
            $singleFaq['STRTEXT'] = html_entity_decode(  $singleFaq['STRTEXT']	,ENT_COMPAT  , "UTF-8")  ;

            $single['uid'] = $singleUid ;
            $single['STRSUBJECT'] = html_entity_decode( $singleFaq['STRSUBJECT'] ,ENT_COMPAT  , "UTF-8");
            $single['INTTOPTEN'] =   intval( $singleFaq['INTTOPTEN'] > 0 ) ? substr( "00" . $singleFaq['INTTOPTEN'] , -3 ,3) : "000" ;
            $single['STRCATEGORY'] = $singleFaq[$category];
            $single['STRTEXT'] = $singleFaq[$category] . " \n " . $singleFaq['STRTEXT'] ;


            $single['singleFaqRaw'] = json_encode( $this->repairFAQ($singleFaq , $options )  , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ;

            $single['language'] = $indexlang;

            if (is_array($singleFaq['LSTPROGRAMME'])) {
                foreach ($singleFaq['LSTPROGRAMME'] as $tag) {
                    $single['tags'] .= ",#" . strtolower(str_replace(" ", "", $tag)) . "#";
                }
            }

            $single['sortdate'] = mktime(0, 0, 0, substr($singleFaq['STRBEARBEITUNGSSTAND'], 3, 2),
                substr($singleFaq['STRBEARBEITUNGSSTAND'], 0, 2), substr($singleFaq['STRBEARBEITUNGSSTAND'], 6, 4));
            $single['url'] = $url;


            switch ( strtolower( $singleFaq['STRINTERNET_RELEASE_FOR'])) {
                case "everybody":
                    $single['type'] = "supportfaq";
                    $single['feGroup'] = '';
                    $single['tags'] .= ",#allUserAccess#,#customerAccess#";
                    break;

                case "beta tester":
                case "betatester":
                    $single['type'] = "supportfaqbeta";
                    $single['feGroup'] = '38,7,4';
                    break;

                case "portal user":
                case "portaluser":
                    $single['type'] = "supportfaqsp";
                    $single['feGroup'] = '38,7,4,3';
                    $single['tags'] .= ",#customerAccess#";
                    break;
                case "nemetschek only":
                case "nemetschekonly":
                    $single['type'] = "supportfaqnem";
                    $single['feGroup'] = '38,7';
                    break;

                default:
                    $single['type'] = "supportfaqlocked";
                    $single['feGroup'] = '38';
                    break;
            }
            $debug[] = array( "LINE:" => __LINE__ ,  "single" => $single ) ;

            if (! $this->putToIndex($single, $indexerObject, $indexerConfig)) {
                $debug[] = array( "LINE:" => __LINE__ ,  "indexer Update failed" => "!!!" ) ;
                $debug[] = array( "LINE:" => __LINE__ ,  "params: " => $indexerConfig ) ;
                if($debugOutput) {  print_r($debug) ; die; };
               return false ;
            }

        }

        unset($single) ;
        unset($singleFaq) ;
        return $debug ;
    }

    protected function repairFAQ($entry , $options) {
        if ( ! is_array($entry)) {
            return $entry ;
        }
        $htmlfrom = $options['from'] ;
        $htmlto = $options['to'] ;
        $fromdecode = $options['fromdecode'] ;

        if( array_key_exists(  'STRTEXT' ,  $entry ) ) {
            if ( strip_tags( $entry['STRTEXT']) == $entry['STRTEXT'] ) {
                $entry['NONLTOBR'] = FALSE;
                $entry['STRTEXT'] =  str_replace( "\\n" , "" , $entry['STRTEXT']	)  ;
            } else {
                $entry['NONLTOBR'] = TRUE;
            }
            $entry['STRTEXT'] =  str_replace( "&apos;" , "'" , $entry['STRTEXT']	)  ;
        }

        if( array_key_exists ( 'STRCOMMENT' , $entry    ) ) {
            $entry['STRCOMMENT'] =  html_entity_decode(  $entry['STRCOMMENT']	,ENT_COMPAT  , "UTF-8")  ;
            $entry['STRCOMMENT'] =  str_replace( "&apos;" , "'" , $entry['STRCOMMENT']	)  ;
        }



        if( array_key_exists ( 'LSTPDFNAME' , $entry )  && is_array( $entry['LSTPDFNAME'] )) {
            for ( $ii=0;$ii<count( $entry['LSTPDFNAME'] );$ii++) {
                $entry['NEWLSTPDFNAME'][] = array( 'REALNAME' => $entry['LSTPDFNAME'][$ii] ,"UTF8NAME" => iconv( $fromdecode , "UTF-8" , $entry['LSTPDFNAME'][$ii]	) );
            }
        }
        if( array_key_exists ( 'LSTATTACHMENTS' , $entry )  && is_array( $entry['LSTATTACHMENTS'] )) {
            for ( $ii=0;$ii<count( $entry['LSTATTACHMENTS'] );$ii++) {
                if ( $entry['LSTATTACHMENTS'][$ii] <> "" ) {
                    if ( strtolower( substr(  $entry['LSTATTACHMENTS'][$ii],-3)) == "pdf") {
                        $entry['NEWATTACHMENTS'][] = array('REALNAME' => $entry['LSTATTACHMENTS'][$ii] ,
                                                          "FILENAME" => iconv( $fromdecode , "UTF-8" , $entry['LSTATTACHMENTS'][$ii]	)  ,
                                                            "FILETYPE" => "fileLink pdf" ,
                                                            "FILETEXT" => "tx_nemsolution.button.downloadPDF" ,
                                                        );
                    } else {
                        $entry['NEWATTACHMENTS'][] = array('REALNAME' => $entry['LSTATTACHMENTS'][$ii] ,
                            "FILENAME" => iconv( $fromdecode , "UTF-8" , $entry['LSTATTACHMENTS'][$ii]	)  ,
                            "FILETYPE" => "fileLink" ,
                            "FILETEXT" => "tx_nemsolution.button.download" ,
                        );
                    }
                }
            }
        }
        return $entry ;
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
            $single['type'] ,				                    // content type ( useful, if you want to use additionalResultMarker)
            $single['url']                              ,	// uid of the targetpage (see indexer-config in the backend)
            $single['singleFaqRaw']  , 						                // the Content here RAW Result
            $indexerConfig['tags'] . $single['tags'] ,						// tags
            '_blank' ,                                      // additional params for the link
            substr( strip_tags( $single['STRTEXT'] ) , 0 , 200 ) ,	// abstract below the title in the result list
            $single['language'] ,				    // sys_language_uid
            0 ,						// starttime (not used here)
            0,						// endtime (not used here)
            $single['feGroup'],						// fe_group ('' , '7' , '7,4' , or '7,4,3' )
            false ,					// debug only?
            array( 'sortdate' => $single['sortdate'] , 'orig_uid' => $single['uid'] , 'servername' => $server  , 'directory' => $single['STRCATEGORY'] , 'top10' => $single['INTTOPTEN']  )				// additional fields added by hooks
        );

    }

    protected function deleteIdToINTentries( $notes_id , $lang)
    {

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance("TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_allplan_url_ids')->createQueryBuilder();
        $queryBuilder->delete('tx_kesearch_allplan_url_ids');

        $expr = $queryBuilder->expr();
        $queryBuilder->where(
            $expr->eq('notes_id', $queryBuilder->createNamedParameter($notes_id, Connection::PARAM_STR))
        )->andWhere(
            $expr->eq('sys_language_uid', $queryBuilder->createNamedParameter(intval($lang), Connection::PARAM_INT))
        )->execute() ;
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
        )->orderBy("uid" , 'DESC')->setMaxResults(1) ;


        $row = $queryBuilder->execute()->fetch();

        if ( is_countable( $row ) && count ( $row ) > 0 ) {
            return $row['uid'] ;
            //var_dump($row);
            //die;
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


    protected function getIndexerById( $uid ) {

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_index')->createQueryBuilder();
        $queryBuilder->select( 'uid' , 'sortdate' , 'tstamp')
            ->from('tx_kesearch_index') ;
        $queryBuilder->getRestrictions()->removeAll()->add( GeneralUtility::makeInstance(DeletedRestriction::class));
        $expr = $queryBuilder->expr();
        $queryBuilder->where(
            $expr->eq('orig_uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))

        )->andWhere(
            $expr->like('type', $queryBuilder->createNamedParameter('supportfa%', Connection::PARAM_STR)
            )
        )->orderBy("uid" , 'DESC')->setMaxResults(1) ;


        $result = $queryBuilder->execute();

        // echo $queryBuilder->getSQL() ;
        // echo $queryBuilder->getParameters() ;
        // die ;

        $row = $result->fetch();

        return $row ;

    }

    protected function deleteFromIndexById( $uid ) {

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_index')->createQueryBuilder();
        $queryBuilder->delete('tx_kesearch_index') ;

        $queryBuilder->where(
            $queryBuilder->expr()->eq('orig_uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
        )->andWhere(
            $queryBuilder->expr()->like('type', $queryBuilder->createNamedParameter('supportfa%', Connection::PARAM_STR)
            )
        ) ;
        // echo $queryBuilder->getSQL() ;
        // echo $queryBuilder->getParameters() ;
        // die ;

        $queryBuilder->execute();

    }

}