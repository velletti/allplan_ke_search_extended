<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * KeSearchUnlockViewHelper
 * @package TYPO3
 * @subpackage Fluid
 */
class KeSearchUnlockViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper{

	/**
	 * Parse content element
	 *
	 * @return string
	 */
	public function render() {

        /** @var \Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer  $indexer */
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Allplan\\AllplanKeSearchExtended\\Indexer\\AllplanKesearchIndexer');
        $content = '' ;
        $nameSpace ='tx_kesearch_extended' ;
        $getParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET() ;
        // $content .= var_export($getParams , true ) ;



        // remove lock from registry - admin only!
        if ( TYPO3_MODE === 'BE' && $GLOBALS['BE_USER']->isAdmin()  ) {
            if ( $getParams['M'] = 'web_KeSearchBackendModule' &&  intval($getParams['lockUid']) >  0 &&  $getParams['CMD'] == 'Unlock' ) {

                $registryKey = 'startTimeOfIndexer' . $getParams['lockUid'] ;
                $indexer->registry->remove($nameSpace, $registryKey ) ;
                $content = '<div class="well"> Indexer Lock removed! (Uid: ' . $getParams['lockUid'] . ")</div>" ;
                // return $content ;

                // https://allplan.local/typo3/index.php?M=system_txschedulerM1&moduleToken=4c3e7968eb4d0cbc9565b4426052d88357b23ab3&CMD=stop&tx_scheduler[uid]=6
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_scheduler_task', 'uid = ' .  intval($getParams['lockUid']) , [
                    'serialized_executions' => ''
                ]);

            }
        } else {
            $content = "<div> Need to be admin to use this feature to unlock tasks !</div>" ;
        }

        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tx_scheduler_task', 'disable = 0') ;

        $content .= "<div class=\"typo3-fullDoc\">" ;
        $content .= "<div class=\"row\" style='font-weight: bold'>" ;
        $content .= "<div class='col-xs-2'> ID </div> " ;
        $content .= "<div class='col-xs-4'> Name </div> " ;
        $content .= "<div class='col-xs-4'> Status </div> " ;
        $content .= "<div class='col-xs-2'>  </div> " ;
        $content .= "</div> " ;

        $foundNone = true ;
        foreach ( $rows as $key => $row ) {
            $registryKey = 'startTimeOfIndexer' . $row['uid'] ;


            if($indexer->registry->get($nameSpace, $registryKey ) ) {
                $content .= "<div class=\"row\"><div class='col-xs-2'> " . $row['uid']  . "</div>" ;
                $content .= "<div class='col-xs-4'> " . $row['description']  . "</div>" ;
                $content .= "<div class='col-xs-4'> running!</div>" ;

                $parameters = "lockUid=" .$row['uid'] . "&CMD=Unlock";

                $uri = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_KeSearchBackendModule').'&'.$parameters ;
                if (!empty($returnUrl)) {
         //           $uri .= '&returnUrl='.rawurlencode($returnUrl);
                } else {
         //           $uri .= '&returnUrl='.rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
                }
                if ( $GLOBALS['BE_USER']->isAdmin() ) {

                    $link ="<a class='lock-button' href='" . $uri . "'>unlock</a>" ;
                } else {
                    $link = "contact Admin!" ;
                }
                $content .= "<div class='col-xs-2'> " . $link  . "</div>" ;
                $content .= "</div><hr/> " ;
                $foundNone = false ;
            }

        }
        if( $foundNone ) {
            $content = "<div class='well well-info'> <h4>Found no running ke search indexer</h4></div>" ;
            if (class_exists(\TYPO3\CMS\Core\Database\ConnectionPool::class)) {
                /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
                $queryBuilder = (new ConnectionPool())->getConnectionForTable('tx_kesearch_index')->createQueryBuilder();
                $allResult = $queryBuilder->select('type', 'tstamp')
                    ->from('tx_kesearch_index')
                    ->groupBy('type')
                    ->orderBy('type', 'ASC')
                    ->addOrderBy('tstamp', 'DESC')
                    ->execute()
                    ->fetchAll();

                $content .= "<div class=\"typo3-fullDoc\">" ;
                    $content .= "<div class=\"row\" style='font-weight: bold'>" ;
                        $content .= "<div class='col-xs-4'> Type </div> " ;
                        $content .= "<div class='col-xs-4'> Last Indexed Document </div> " ;
                        $content .= "<div class='col-xs-4'>  </div> " ;
                    $content .= "</div> " ;

                    foreach ($allResult as $key => $row ) {

                        $content .= "<div class=\"row\">" ;
                            $content .= "<div class='col-xs-4'> " . $row['type'] . "</div> " ;
                            $content .= "<div class='col-xs-4'> " . date( "d.m.Y - H:i" , $row['tstamp'] ) . "</div> " ;
                            $content .= "<div class='col-xs-4'> " . "</div> " ;
                        $content .= "</div> " ;
                    }
                $content .= "</div> " ;
            }
        }

        $content .= "</div> " ;


        return $content ;

	}


}