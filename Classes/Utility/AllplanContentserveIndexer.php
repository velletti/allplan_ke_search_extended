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

class AllplanContentserveIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        $indexerConfig['tags'] = "#contentserve#" ;
        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == '' ||  $server == "connect.allplan.com.ddev.local"  ) {
            $server = "connect.allplan.com" ;
        }
        $BaseUrl = "https://" . $server . "/index.php?id=3121&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1"
            . "&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1" ;



        // https://connect.allplan.com.ddev.local/index.php?id=3121&no_cache=1&tx_nemjvgetcontent_pi1[func]=SHOWITEM&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&amp;tx_nemjvgetcontent_pi1[WLA]=ENU&amp;tx_nemjvgetcontent_pi1[pid]=0
        // https://connect.allplan.com.ddev.local/index.php?id=3121&no_cache=1&tx_nemjvgetcontent_pi1[func]=SHOWITEM&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=101
        // https://connect.allplan.com.ddev.local/index.php?id=3121&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=88

        $result = array() ;
        $count = 0 ;
        $debug = '' ;
        // derzeit sind 1300 item in der Contentserve db .. darum gehen wir einfahc mal durch bis zur max ID und ein wieng mehr ..
        $lngs = array( 1 => "DEU" , 4 => "FRA" , 2 => "ITA" , 18 => "ESP" , 14=> "RUS" , 3 => "CZE") ;
        for( $i=1 ; $i <  2000 ; $i++ ) {
            $url = $BaseUrl . "&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=" .  $i ;
            $debug .= "CP: " . $i  . " -> URL: " . $url ;
            //  $url = "http://www.velletti.de" ;
           //  $json = $this->getJsonFile($url , '' , 'Accept: application/json; charset=utf-8" , "Content-type:application/json' , TRUE  ) ;
            $json = $this->getJsonFile($url   ) ;
            $debug .= "response: " . var_export( $json , true ) ;
            if( is_array($json)) {
                $debug .= " is Array! " ;
                if($json['error']  < 1 ) {
                    $debug .= " not an error ! " ;
                    if( $this->putToIndex( $json , $indexerObject , $indexerConfig , 0 ) ) {
                        $debug .= " Englisch is stored  ! " ;
                        $count++ ;
                        $debug .= " repeading with languages  ! " ;
                        foreach ( $lngs as $lng => $WLA) {

                            $url = $BaseUrl . "&tx_nemjvgetcontent_pi1[WLA]=" . $WLA . "&tx_nemjvgetcontent_pi1[pid]=" .  $i ;
                            $json = $this->getJsonFile($url) ;
                            if( is_array($json)) {
                                $debug .= " tried  language  ! " . $WLA  ;
                                if($json['error']  < 1 ) {
                                    $debug .= " Got it ! "   ;
                                    if( $this->putToIndex( $json , $indexerObject , $indexerConfig , $lng ) ) {
                                        if ( $WLA == "DEU") {
                                            $debug .= " adding AT / CH  "  ;
                                            $this->putToIndex( $json , $indexerObject , $indexerConfig , 6 ) ;
                                            $this->putToIndex( $json , $indexerObject , $indexerConfig , 7 ) ;

                                        }
                                        $count++ ;
                                    }

                                }
                            }
                            // next lines are only needed for faster dev Process .
                               if ( $count > 100  && $_SERVER['SERVER_NAME'] == "connect.allplan.com.ddev.local"  ) {
                                   // var_dump(  $debug  ) ;
                                   return $count ;
                               }

                        }
                    }

                }
            }


        }

        return $count ;
    }
    protected function putToIndex(array $single , \tx_kesearch_indexer $indexerObject , array  $indexerConfig , $language ) {
        if ($single['Header']['RESULTS'] == 0 ) {
            return false ;
        }

        // Prepare data for the indexer
        $content = str_replace( '"' , "" ,  $single['CPs'][0]['LABEL_CP'] ) . PHP_EOL
            . str_replace( '"' , "" ,  $single['CPs'][0]['LTX_CP'] ) ;
        $content .= PHP_EOL . $single['CPs'][0]['CP_ADE']  ;
        $content .= PHP_EOL . "ID: " . $single['CPs'][0]['CP_IDI']  . PHP_EOL ;



        $cfs = '' ;
        $kats = array() ;
        $filetypes = array() ;
        $CF_ADE = array() ;
        $DES_CF = array() ;
        $CF_ADE = array() ;
        $PRO_CF = array() ;
        $APV_CF = array() ;
        if( is_array(  $single['CPs'][0]['LINK_CP']  )) {
            foreach ( $single['CPs'][0]['LINK_CP'] as $cfile ) {
                $cfs .= $cfile['CF_IDI'] . "," ;
                $temp = $cfile['FILETYPE'] ;
                $filetypes[$temp] = $cfile['FILETYPE'] ;

                // +++++ Read  the values with Additional Attributes
                $temp = $cfile['CF_ADE'] ;
                $CF_ADE[$temp] = $cfile['CF_ADE'] ;

                $temp = $cfile['DES_CF'] ;
                $DES_CF[$temp] = $cfile['DES_CF'] ;

                $temp = $cfile['PRO_CF'] ;
                $PRO_CF[$temp] = $cfile['PRO_CF'] ;

                $temp = $cfile['APV_CF'] ;
                $APV_CF[$temp] = $cfile['APV_CF'] ;

                foreach ( $cfile['KAT_CF']  as $kat ) {
                    $id = $kat['KAT_KEY'] ;
                    $kats[$id] = $id . " - "  . $kat['KAT_LABEL'] ;

                }

            }
        }


        // Add the Additional Attributes  but make them unique ...

        $content .=  PHP_EOL .  implode ( PHP_EOL , $kats ) ;
        array_unique($filetypes) ;
        $content .=  PHP_EOL .  implode( PHP_EOL , $filetypes ) ;

        array_unique($DES_CF) ;
        $content .=  PHP_EOL .  implode( PHP_EOL , $DES_CF ) ;
        array_unique($CF_ADE) ;
        $content .=   PHP_EOL . implode( PHP_EOL , $CF_ADE ) ;
        array_unique($PRO_CF) ;
        $content .=   PHP_EOL . implode( PHP_EOL , $PRO_CF ) ;
        array_unique($APV_CF) ;
        $content .=  PHP_EOL .  implode( PHP_EOL , $APV_CF ) ;

        $t = $single['CPs'][0]['CP_DTA'] ;
        /** @var \DateTime $datum */
        $datum =  new \DateTime($single['CPs'][0]['CP_DTA']);

        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == '' ||  $server == "connect" ) {
            $server = "connect.allplan.com" ;
        }

        // The following should be filled (in accordance with the documentation), see also:
        // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
        $additionalFields = array(
            'orig_uid' => $single['CPs'][0]['CP_IDI'] ,
            'servername' => $server ,
            'sortdate' => $datum->getTimestamp()
        );

        // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
        $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;
        $pidLink = $indexerConfig['targetpid']> 0 ? $indexerConfig['targetpid']  : 359 ;


        // correct  uid of single page from is 359
        $url = "https://" . $server . "/index.php?id=" . $pidLink . "&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1"
            . '&L=' . $language  ;
        $url .= "&tx_nemjvgetcontent_pi1[pid]=" . $single['CPs'][0]['CP_IDI'] ;
        $url .= "&tx_nemjvgetcontent_pi1[cf_ids]=" . $cfs  ;


        $msg = $indexerObject->storeInIndex(
            $pid ,			                // folder, where the indexer data should be stored (not where the data records are stored!)
            str_replace( '"' , "" ,  $single['CPs'][0]['LABEL_CP'] ) ,							    // title in the result list
            'contentserve',				                    // content type ( useful, if you want to use additionalResultMarker)
            $url ,	// uid of the targetpage (see indexer-config in the backend)
            $content, 						                // below the title in the result list
            $indexerConfig['tags'] ,						// tags (not used here)
            '_blank' ,                                      // additional params for the link
            $single['CPs'][0]['LTX_CP'] ,			// abstract
            $language ,				    // sys_language_uid
            0,						    // starttime (not used here)
            0,						    // endtime (not used here)
            '' ,						// fe_group (not used here)
            false ,					    // debug only?
            $additionalFields				// additional fields added by hooks
        );
        return $msg ;

    }


}