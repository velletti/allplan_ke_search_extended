<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


use Allplan\AllplanTools\Utility\TyposcriptUtility;
use Allplan\NemConnections\Utility\Settings;

class KeSearchGetQueryPartsHook{

    /**
     * @param array $markerArray
     * @param $row
     * @param object $pibase
     */
	public function getQueryParts( $queryParts , $pibase ){
        if(is_array($queryParts) ){
             $resultPage = $pibase->conf['resultPage'] ;
            // echo " resultPage " . $resultPage ;
              $settings = TyposcriptUtility::loadTypoScriptFromScratch($resultPage, 'tx_kesearch_pi1') ;
              if ( is_array($settings) && array_key_exists("getQuerypartsHook" , $settings )) {
                  $hookData = $settings['getQuerypartsHook'] ;
                  if ( is_array($hookData) && array_key_exists("where" , $hookData)) {

                      $queryParts['WHERE'] .= " AND ( " ;
                      foreach ($hookData['where'] as $key => $query ) {

                          $queryParts['WHERE'] .= " ( `" . trim($query['field']) . "` " . trim($query['type']) . " '" . trim( $query['value'] ) . "' ) " ;

                      }
                      $queryParts['WHERE'] .= " ) " ;

                  }
              }
            $queryParts['SELECT'] = str_replace( ") AS score" , " + (top10)) AS score" , $queryParts['SELECT']) ;

            //    echo "<hr><pre>" ;
            //    print_r($queryParts);
            //   die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );


		}
        return $queryParts ;
	}

}