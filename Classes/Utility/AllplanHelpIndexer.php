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

class AllplanHelpIndexer extends \Allplan\AllplanKeSearchExtended\Hooks\BaseKeSearchIndexerHook
{
    /**
     * @param array $indexerConfig configuration from TYPO3 backend
     * @param \tx_kesearch_indexer $indexerObject reference to the indexer class
     * @return int
     */

    public function main(&$indexerConfig, &$indexerObject) {
        $url = $indexerObject->externalUrl . "search.json" ;
        // ToDo Put tags to Indexer object
        $indexerConfig['tags'] = "#onlinehelp#" ;

        $json = $this->getJsonFile($url) ;
        if( is_array($json)) {
            if($json['error'] > 0 ) {
                return 0 ;
            }
        }

        $arr = explode( "},{" , $json ) ;
        $result = array() ;
        $count = 0 ;
        foreach ( $arr as $key => $string ) {
                $content =  substr( $string , strpos( $string , 'title' ) ) ;
                // $result[] = $content ;
                // $result[] =  strpos(  $string , 'title' ) ;
                $singleString = explode( '","' , $content ) ;
                $single = array() ;
                foreach ($singleString as $string2 ) {
                    $temp = explode( '":"' , $string2 ) ;
                    $single[$temp[0]] = strip_tags( nl2br( str_replace( array("@" , "\t") , array(" " , " ") , $temp[1] )) );
                }
                if( count($single)> 2  ) {
                    $singleuid = explode( "." , $single['url'] )  ;
                    $single['uid'] = $singleuid[0] ;

                    if( $this->putToIndex( $single , $indexerObject , $indexerConfig) ) {
                        $count++ ;
                    }
                }
                unset($single) ;
                unset($singleString) ;
                // ToDo Disable  next lines .. only needed for faster dev Process ..
                //   if ( $count > 20 ) {
                //       return $count ;
                //   }
        }

        return $count ;
    }
    protected function putToIndex(array $single , \tx_kesearch_indexer $indexerObject , array  $indexerConfig ) {

        // Prepare data for the indexer
        $content = $single['title'] . PHP_EOL . nl2br($single['text']) ;

        // The following should be filled (in accordance with the documentation), see also:
        // http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
        $additionalFields = array(
            'orig_uid' => $single['uid']
        );

        // take storage PID form indexexer Configuration or overwrite it with storagePid From Indexer Task ??
        $pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid  : $indexerConfig['pid'] ;

        return $indexerObject->storeInIndex(
            $pid ,			                // folder, where the indexer data should be stored (not where the data records are stored!)
            $single['title'] ,							    // title in the result list
            'allplanhelp',				                    // content type ( useful, if you want to use additionalResultMarker)
            $indexerObject->externalUrl . "index.htm#" . $single['url'] ,	// uid of the targetpage (see indexer-config in the backend)
            $content, 						                // below the title in the result list
            $indexerConfig['tags'] ,						// tags (not used here)
            '_blank' ,                                      // additional params for the link
            substr( $single['text'] , 0 , 200 ) ,			// abstract
            $indexerObject->language[0] ,				    // sys_language_uid
            0,						// starttime (not used here)
            0,						// endtime (not used here)
            '',						// fe_group (not used here)
            false ,					// debug only?
            $additionalFields				// additional fields added by hooks
        );

    }


}