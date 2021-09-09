<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class KeSearchModifySearchWordsHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 * @param array $searchWordInformation
	 * @param mixed $pObj
	 */
	public function modifySearchWords(&$searchWordInformation, &$pObj) {
        $searchWordInformation['wordsAgainst'] = trim( $searchWordInformation['wordsAgainst']) ;

        if ( is_array($_GET ) && array_key_exists( "tx_kesearch_pi1" , $_GET) && array_key_exists( "directory" , $_GET['tx_kesearch_pi1'])) {
            if ( $searchWordInformation['wordsAgainst'] == '' ) {
                $searchWordInformation['wordsAgainst'] = urldecode(trim($_GET['tx_kesearch_pi1']['directory']));
            }
            if ( $searchWordInformation['sword'] == '' ) {
                $searchWordInformation['sword'] = urldecode( trim( $_GET['tx_kesearch_pi1']['directory']) ) ;
            }
        }
        if ( strlen(  $searchWordInformation['wordsAgainst'] ) > 2 ) {
            $searchWordInformation['wordsAgainst'] = str_replace( "+" , "", $searchWordInformation['wordsAgainst'] ) ;
            // var_dump($searchWordInformation['wordsAgainst']);
            // @extensionScannerIgnoreLine
            if( $pObj->conf['encodeWords'] )  {
                $searchWordInformation['wordsAgainst'] = htmlentities( $searchWordInformation['wordsAgainst'] ) ;
            }
            $arr = GeneralUtility::trimExplode(" " , $searchWordInformation['wordsAgainst']) ;
            if ($arr && count($arr) > 3 ) {
                $fixed = "" ;
                foreach ($arr as $key => $sword ) {
                    if ( $key > 2 ) {
                        $sword = str_replace("*" , "" , $sword) ;
                    }
                    $fixed .= " " . $sword ;
                }
                $searchWordInformation['wordsAgainst'] = trim( $fixed ) ;

            }
            // var_dump($searchWordInformation['wordsAgainst']);
            // die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );

            $searchWordInformation['wordsAgainst'] = trim( "+" . str_replace( " " , " +", $searchWordInformation['wordsAgainst'] )) ;
        }




	}



}