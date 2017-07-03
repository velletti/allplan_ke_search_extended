<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

// j.v. i think next line  is here by accident !
// use org\bovigo\vfs\vfsStreamWrapperTestCase;

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
                $where .= " AND ( `type` = 'allplanfaq' or `type` = 'allplanfaqsp' ) AND tstamp < " . ( time() - ( 60 * 60 * 24 * $pObj->period ) ) ;
                break ;

            case 'allplanforum' :
                $where .= " AND ( `type` = 'allplanforum' or `type` = 'allplanforumsp' or `type` = 'allplanforumlocked' )  AND tstamp < " . ( time() - ( 60 * 60 * 24 * ( $pObj->period ) ) ) ;

                break ;

            default :
                $where .= " AND `type` = '" . $pObj->indexerConfig['type'] .  "' ";
                break ;

        }

        if ( $pObj->language != '' ) {
            $where .= " AND language = " . $pObj->language[0] ;
        }
        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com") {
            $server = "connect.allplan.com" ;
        }
        if( $server == "www-typo3.allplan.com") {
            $server = "www.allplan.com" ;
        }
        $where .= " AND ( servername ='" . $server . "' OR servername = '' ) ";

        if ( $pObj->storagePid ) {
            $where .= " AND pid = " . $pObj->storagePid ;

        } else {
            $where .= " AND pid = " . $pObj->indexerConfig['pid'] ;
        }
        return $content . "\n After Cleanup Hook: Now $" . "where = " . $where ;
    }



}