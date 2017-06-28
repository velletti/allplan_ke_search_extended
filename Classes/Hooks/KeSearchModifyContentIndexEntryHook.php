<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifyContentIndexEntryHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 * @param array $searchWordInformation
	 * @param mixed $pObj
	 */
	public function modifyContentIndexEntry( $header , &$row, $tags, $uid , &$additionalFields, &$indexerConfig ) {
        $additionalFields['servername'] = $_SERVER['SERVER_NAME'] ;
        $row['pid'] =  $_SERVER['SERVER_NAME'] . "/index.php?id=" . $row['pid'] . "&L=" . $row['sys_language_uid'] ;
	}


}