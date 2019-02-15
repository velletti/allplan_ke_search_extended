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
        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "vm5012934.psmanaged.com" ||  $server == "connect" ) {
            $server = "connect.allplan.com" ;
        }
        if( $server == "www-typo3.allplan.com" ||  $server == "vm5012986.psmanaged.com" ||   $server == "allplan" ||   $server == "www") {
            $server = "www.allplan.com" ;
        }
        $additionalFields['servername'] = $server ;
        //$indexEntryDefaultValues['pid'] =  $_SERVER['SERVER_NAME'] . "/index.php?id=" . $row['pid'] . "&L=" . $row['sys_language_uid'] ;
    }


}