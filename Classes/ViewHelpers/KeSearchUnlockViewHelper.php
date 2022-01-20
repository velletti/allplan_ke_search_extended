<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

class KeSearchUnlockViewHelper extends AbstractViewHelper
{

	/**
	 * Render
	 * @return string
	 * @throws DoctrineDBALDriverException
	 * @throws RouteNotFoundException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): string
	{

		/**
		 * @var AllplanKesearchIndexer $indexer
		 * @var ConnectionPool $connectionPool
		 * @var QueryBuilder $queryBuilder
		 * @var QueryBuilder $updateQueryBuilder
		 * @var UriBuilder $uriBuilder
		 */

		$indexer = GeneralUtility::makeInstance(AllplanKesearchIndexer::class);
		$content = '';
		$nameSpace ='tx_kesearch_extended';
		$getParams = GeneralUtility::_GET();
		$connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);

		// Remove lock from registry - admin only
		if(TYPO3_MODE === 'BE' && $GLOBALS['BE_USER']->isAdmin()){

			if($getParams['route'] = '/web/KeSearchBackendModule/' && intval($getParams['lockUid']) > 0 && $getParams['CMD'] == 'Unlock'){

				$registryKey = 'startTimeOfIndexer' . $getParams['lockUid'];
				$indexer->registry->remove($nameSpace, $registryKey);
				$content = '<div class="well">Indexer lock removed! (uid: ' . $getParams['lockUid'] . ')</div>';
				// https://connectv9.allplan.com.ddev.local/typo3/index.php?route=%2Fweb%2FKeSearchBackendModule%2F&token=bf84db97942acf6e4349d688d79b34896168c6bd&lockUid=16&CMD=Unlock

				$updateQueryBuilder = $connectionPool->getConnectionForTable('tx_scheduler_task')->createQueryBuilder();
				$updateQueryBuilder
					->update('tx_scheduler_task')
					->set('serialized_executions', '')
					->where('uid', $updateQueryBuilder->createNamedParameter(intval($getParams['lockUid']),Connection::PARAM_INT))
					->execute()
				;

			}

		} else {
			$content = '<div>You need to be Admin to use this feature to unlock tasks!</div>';
		}

		// Get all scheduler tasks
		$queryBuilder = $connectionPool->getConnectionForTable('tx_scheduler_task')->createQueryBuilder();
		$rows = $queryBuilder->select('*')->from('tx_scheduler_task')->execute()->fetchAllAssociative();

		$content.= '<div class="typo3-fullDoc">';
		$content.= '<div class="row" style="font-weight: bold">';
		$content.= '<div class="col-xs-2">ID</div>';
		$content.= '<div class="col-xs-4">Name</div>';
		$content.= '<div class="col-xs-4">Status</div>';
		$content.= '<div class="col-xs-2"></div>';
		$content.= '</div>';

		foreach ($rows as $key => $row){
			$registryKey = 'startTimeOfIndexer' . $row['uid'];

			// Put scheduler task to the list, if it is running
			if($indexer->registry->get($nameSpace, $registryKey)){

				$content.= '<div class="row"><div class="col-xs-2">' . $row['uid'] . '</div>';
				$content.= '<div class="col-xs-4">' . $row['description'] . '</div>';
				$content.= '<div class="col-xs-4">running!</div>';
				$parameters = [
					'lockUid' => $row['uid'],
					'CMD' => 'Unlock',
				];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $uri = $uriBuilder->buildUriFromRoute('web_KeSearchBackendModule', $parameters);

				if($GLOBALS['BE_USER']->isAdmin()){
					$link = '<a class="lock-button" href="' . $uri . '">unlock</a>';
				}else{
					$link = "Contact Admin!";
				}
				$content.= '<div class="col-xs-2">' . $link . '</div>';
				$content.= '</div><hr/>';
			}

		}
		$content.= '</div>';
		return $content ;

	}

}