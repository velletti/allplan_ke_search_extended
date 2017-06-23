<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifiySearchWordsHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 * @param array $searchWordInformation
	 * @param mixed $pObj
	 */
	public function modifySearchWords(&$searchWordInformation, $pObj) {
        $searchWordInformation['wordsAgainst'] = str_replace( "+" , "", $searchWordInformation['wordsAgainst'] ) ;
        $searchWordInformation['wordsAgainst'] = "+" . str_replace( " " , " +", $searchWordInformation['wordsAgainst'] ) ;
	}



}