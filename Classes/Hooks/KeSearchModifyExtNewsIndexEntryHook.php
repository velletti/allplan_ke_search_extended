<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifyExtNewsIndexEntryHook {

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 */
	public function  modifyExtNewsIndexEntry(
                        $title,
                        $abstract,
                        $fullContent,
                        $params,
                        $tags,
                        $newsRecord,
                        &$additionalFields,
                        $indexerConfig,
                        $categoryData,
                        $Newsthis
                    ) {
        $additionalFields['servername'] = $_SERVER['SERVER_NAME'] ;
    }

}