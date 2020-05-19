<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * KeSearchUnlockViewHelper
 * @package TYPO3
 * @subpackage Fluid
 */
class KeSearchUnlockViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper{

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

        /** @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance( "TYPO3\\CMS\\Core\\Database\\ConnectionPool");



        // remove lock from registry - admin only!
        if ( TYPO3_MODE === 'BE' && $GLOBALS['BE_USER']->isAdmin()  ) {
            if ( $getParams['route'] = '/web/KeSearchBackendModule/' &&  intval($getParams['lockUid']) >  0 &&  $getParams['CMD'] == 'Unlock' ) {

                $registryKey = 'startTimeOfIndexer' . $getParams['lockUid'] ;
                $indexer->registry->remove($nameSpace, $registryKey ) ;
                $content = '<div class="well"> Indexer Lock removed! (Uid: ' . $getParams['lockUid'] . ")</div>" ;
                // https://connectv9.allplan.com.ddev.local/typo3/index.php?route=%2Fweb%2FKeSearchBackendModule%2F&token=bf84db97942acf6e4349d688d79b34896168c6bd&lockUid=16&CMD=Unlock

                /** @var QueryBuilder $UpdateQueryBuilder */
                $UpdateQueryBuilder = $connectionPool->getConnectionForTable('tx_scheduler_task')->createQueryBuilder();
                $UpdateQueryBuilder->update("tx_scheduler_task")->set('serialized_executions' , '')
                    ->where('uid' , $UpdateQueryBuilder->createNamedParameter(intval($getParams['lockUid']), Connection::PARAM_INT) )->execute()  ;

            }
        } else {
            $content = "<div> Need to be admin to use this feature to unlock tasks !</div>" ;
        }
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $connectionPool->getConnectionForTable('tx_scheduler_task')->createQueryBuilder();

        $rows = $queryBuilder->select('*')->from('tx_scheduler_task')->execute()->fetchAll() ;

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

                $parameters = array( "lockUid" => $row['uid'] , "CMD" => "Unlock" ) ;

                if( intval(TYPO3_branch) <  9 ) {
                    $uri = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_KeSearchBackendModule').'&'.$parameters ;
                } else {
                    $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                    $uri = $uriBuilder->buildUriFromRoute('web_KeSearchBackendModule', $parameters);

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
                /** @var QueryBuilder $queryBuilder */
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