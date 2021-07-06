<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\TyposcriptUtility;

/**
 * Class KeSearchGetQueryPartsHook
 * @package Allplan\AllplanKeSearchExtended\Hooks
 */
class KeSearchGetQueryPartsHook
{

    /**
     * @param array $queryParts
     * @param object $pibase
     * @param string|null $searchwordQuoted
     */
	public function getQueryParts( $queryParts , $pibase , $searchwordQuoted=null ){
	    /*
	     * see connect TYPO Script file f.e.:
	     * http/typo3conf/ext/connect_template/Configuration/TypoScript/Base/Setup/Extensions/ke_search_for_support.ts
	     *
	     * will add conditions f.e. type = "supportfaq%" or set defaultSearch = top10 > 0
	     */
        $debug[] = "line: " . __LINE__  . " init ";
        if(is_array($queryParts) ){
            // @extensionScannerIgnoreLine
             $resultPage = $pibase->conf['resultPage'] ;
            // echo " resultPage " . $resultPage ;
              $settings = TyposcriptUtility::loadTypoScriptFromScratch($resultPage, 'tx_kesearch_pi1') ;
            $debug[] = "line: " . __LINE__  . " queryParts is array ";
              if ( is_array($settings) && array_key_exists("getQuerypartsHook" , $settings )) {
                  $debug[] = "line: " . __LINE__ . " found getQuerypartsHook in Settings ";
                  $hookData = $settings['getQuerypartsHook'] ;
                  if ( is_array($hookData) && array_key_exists("where" , $hookData)) {
                      $debug[] = "line: " . __LINE__ . " hookData is array and has where condition ";
                      $queryParts['WHERE'] .= " AND ( " ;
                      foreach ($hookData['where'] as $key => $query ) {

                          $queryParts['WHERE'] .= " ( `" . trim($query['field']) . "` " . trim($query['type']) . " '" . trim( $query['value'] ) . "' ) " ;

                      }
                      $queryParts['WHERE'] .= " ) " ;

                      $queryParts['SELECT'] = str_replace( ") AS score" , " + (top10)) AS score" , $queryParts['SELECT']) ;


                  }
                  if ( is_array($hookData) && array_key_exists("defaultSearch" , $hookData)) {
                      $debug[] = "line: " . __LINE__ . " searchDefault is configured ";
                      // check if we have empty Search word and we are not filtering by directory
                      // if ( strpos( $queryParts['WHERE'] , "MATCH (title, content) AGAINST ('+' IN BOOLEAN MODE) "  ) > 0  ) {
                          if( strpos( $queryParts['WHERE'] , " `directory`"  )  < 1 ) {
                              $queryParts['WHERE'] = str_replace("MATCH (title, content) AGAINST ('+' IN BOOLEAN MODE) ", "", $queryParts['WHERE']) . " AND ( ";
                          } else {
                              $queryParts['WHERE'] = $queryParts['WHERE']  . " ( " ;
                          }
                          foreach ($hookData['defaultSearch'] as $key => $query) {
                              $queryParts['WHERE'] .= " ( `" . trim($query['field']) . "` " . trim($query['type']);
                              if (trim($query['type']) == ">" or trim($query['type']) == "<") {
                                  $queryParts['WHERE'] .= " " . trim($query['value']) . " ) ";
                              } else {
                                  $queryParts['WHERE'] .= " '" . trim($query['value']) . "' ) ";
                              }
                          }
                          $queryParts['WHERE'] .= " ) ";
                          $queryParts['ORDERBY'] = "top10 DESC" ;

                  }
                  $debug[] = "line: " . __LINE__ ;
                  $queryParts['SELECT'] = str_replace( ") AS score" , " + (top10)) AS score" , $queryParts['SELECT']) ;
              }



/*
                echo "<hr><pre>Hook Data :" ;
                print_r($hookData );
                echo "<hr>" ;

                print_r($queryParts);
                echo "<hr>" ;
                print_r($debug);

                die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );
*/

		}
        return $queryParts ;
	}

}