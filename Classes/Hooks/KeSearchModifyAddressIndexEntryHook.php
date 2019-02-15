<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifyAddressIndexEntryHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 */
	public function  modifyAddressIndexEntry(
                        $title,
                        $abstract,
                        $fullContent,
                        $params,
                        $tagContent,
                        $addressRow,
                        &$additionalFields,
                        $indexerConfig,
                        $customfields
                    ) {
        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == "connect" ) {
            $server = "connect.allplan.com" ;
        }
        if( $server == "www-typo3.allplan.com" ||  $server == "vm5012986.psmanaged.com" ||   $server == "allplan" ||   $server == "www") {
            $server = "www.allplan.com" ;
        }
        $additionalFields['servername'] = $server ;
    }

}