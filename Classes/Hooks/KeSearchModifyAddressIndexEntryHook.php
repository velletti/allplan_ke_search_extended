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
        $additionalFields['servername'] = $_SERVER['SERVER_NAME'] ;
    }

}