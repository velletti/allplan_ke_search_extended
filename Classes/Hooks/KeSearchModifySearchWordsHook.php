<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifySearchWordsHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 * @param array $searchWordInformation
	 * @param mixed $pObj
	 */
	public function modifySearchWords(&$searchWordInformation, $pObj) {

        $searchWordInformation['wordsAgainst'] = str_replace( "+" , "", $searchWordInformation['wordsAgainst'] ) ;
        // var_dump($searchWordInformation['wordsAgainst']);
        if( $pObj->conf['encodeWords'] )  {
            $searchWordInformation['wordsAgainst'] = htmlentities( $searchWordInformation['wordsAgainst'] ) ;
            // var_dump($searchWordInformation['wordsAgainst']);
        }

        // die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );

        $searchWordInformation['wordsAgainst'] = "+" . str_replace( " " , " +", $searchWordInformation['wordsAgainst'] ) ;
	}



}