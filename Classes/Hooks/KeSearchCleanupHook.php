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
                if ( $pObj->storagePid ) {
                    $where .= " AND pid = " . $pObj->storagePid ;

                } else {
                    $where .= " AND pid = " . $pObj->indexerConfig['pid'] ;
                }
                if ( $pObj->language != '' ) {
                    $where .= " AND language = " . $pObj->language[0] ;
                }
                break ;
            case 'allplanfaq' :
                $where .= " AND ( `type` = 'allplanfaq' or `type` = 'allplanfaqsp' ) AND tstamp < " . ( time() - ( 60 * 60 * 24 * $pObj->period ) ) ;
                if ( $pObj->storagePid ) {
                    $where .= " AND pid = " . $pObj->storagePid ;

                } else {
                    $where .= " AND pid = " . $pObj->indexerConfig['pid'] ;
                }
                if ( $pObj->language != '' ) {
                    $where .= " AND language = " . $pObj->language[0] ;
                }
                break ;

            case 'lessons' :
                $where .= " AND ( `type` = 'lessons' or `type` = 'lessonslocked' )  AND tstamp < " . ( time() - ( 60 * 60 * 24 * ( $pObj->period ) ) ) ;
                break ;
            case 'documentations' :
                $where .= " AND ( `type` = 'documentation' or `type` = 'documentationlocked' )  AND tstamp < " . ( time() - ( 60 * 60 * 24 * ( $pObj->period ) ) ) ;
                break ;

            case 'allplanforum' :
                $where .= " AND ( `type` = 'allplanforum' or `type` = 'allplanforumsp' or `type` = 'allplanforumlocked' )  AND tstamp < " . ( time() - ( 60 * 60 * 24 * ( $pObj->period ) ) ) ;
                break ;

            default :
                $where .= " AND `type` = '" . $pObj->indexerConfig['type'] .  "' ";
                if ( $pObj->storagePid ) {
                    $where .= " AND pid = " . $pObj->storagePid ;

                } else {
                    $where .= " AND pid = " . $pObj->indexerConfig['pid'] ;
                }
                if ( $pObj->language != '' ) {
                    $where .= " AND language = " . $pObj->language[0] ;
                }
                break ;

        }


        $server = $_SERVER['SERVER_NAME'] ;
        if( $server == "connect-typo3.allplan.com" ||  $server == "k2591.ims-firmen.de") {
            $server = "connect.allplan.com" ;
        }
        if( $server == "www-typo3.allplan.com" ||  $server == "k2530.ims-firmen.de" ) {
            $server = "www.allplan.com" ;
        }
        $where .= " AND ( servername ='" . $server . "' OR servername = '' ) ";


        return $content . "\n After Cleanup Hook: Now $" . "where = " . $where ;
    }



}