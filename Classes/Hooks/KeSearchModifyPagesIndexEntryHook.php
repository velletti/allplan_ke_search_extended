<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class KeSearchModifyPagesIndexEntryHook {

    /**
     * Modify search input => sets default to AND-search (+)
     * (You can select these ones in the backend by creating a new record "indexer-configuration")
     * @param array $searchWordInformation
     * @param mixed $pObj
     */
    public function modifyPagesIndexEntry( $uid, $pageContent, $tags, $cachedPageRecords, &$additionalFields, $indexerConfig, $indexEntryDefaultValues, &$pagesThis ) {
        $additionalFields['servername'] = $_SERVER['SERVER_NAME'] ;
        //$indexEntryDefaultValues['pid'] =  $_SERVER['SERVER_NAME'] . "/index.php?id=" . $row['pid'] . "&L=" . $row['sys_language_uid'] ;
    }


}