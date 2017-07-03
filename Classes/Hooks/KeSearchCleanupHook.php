<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use org\bovigo\vfs\vfsStreamWrapperTestCase;

class KeSearchCleanupHook {

	/**
	 * Adds indexers to the TCA of the indexer-configuration.
	 * (You can select these ones in the backend by creating a new record "indexer-configuration")
	 * @param array $params
	 * @param mixed $pObj
	 */
	public function cleanup(&$where, $pObj) {
	    $content = "\n In Cleanup Hook: Got $" . "where = " . $where ;
        switch ($pObj->indexerConfig['type']) {
            case 'onlinehelp' :
                $where .= " AND `type` = 'allplanhelp' ";

                break ;
            case 'allplanfaq' :
                $where .= " AND ( `type` = 'allplanfaq' or `type` = 'allplanfaqSP' ) AND tstamp < " . ( time() - ( 60 * 60 * 24 * $pObj->period ) ) ;
                break ;

            case 'allplanforum' :
                $where .= " AND ( `type` LIKE 'allplanforu%' )  AND tstamp < " . ( time() - ( 60 * 60 * 24 * ( $pObj->period ) ) ) ;

                break ;

            default :
                $where .= " AND `type` = '" . $pObj->indexerConfig['type'] .  "' ";
                break ;

        }

        if ( $pObj->language != '' ) {
            $where .= " AND language = " . $pObj->language[0] ;
        }
        $where .= " AND ( servername ='" . $_SERVER['SERVER_NAME'] . "' OR servername = '' ) ";

        if ( $pObj->storagePid ) {
            $where .= " AND pid = " . $pObj->storagePid ;

        } else {
            $where .= " AND pid = " . $pObj->indexerConfig['pid'] ;
        }
        return $content . "\n After Cleanup Hook: Now $" . "where = " . $where ;
	}



}