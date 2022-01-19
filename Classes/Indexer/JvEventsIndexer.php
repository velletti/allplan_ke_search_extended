<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class JvEventsIndexer
{
	/**
	 * Indexer for events (jv_events)
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		/** @var ConnectionPool $connectionPool */

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_jvevents_domain_model_event');
		$expr = $queryBuilder->expr();

		$queryBuilder
			->select('event.uid', 'event.name', 'event.teaser', 'event.description', 'event.sys_language_uid', 'event.start_date', 'event.end_date')
			->addSelect('org.name as oname','loc.city as city', 'loc.zip as zip', 'loc.street_and_nr', 'loc.name as lname')
			->addSelect('loc.description as ldesc', 'org.description as odesc')
			->from('tx_jvevents_domain_model_event', 'event')
			->leftJoin('event', 'tx_jvevents_domain_model_organizer','org', $expr->eq('event' . '.organizer','org.uid'))
			->leftJoin('event', 'tx_jvevents_domain_model_location','loc', $expr->eq( 'loc.uid','event.location'))
			->where($expr->gt('event.start_date', time()))
			->andWhere($expr->in('event.pid', DbUtility::getTreeList($indexerConfig['startingpoints_recursive'])))
		;

		$res = $queryBuilder->execute();

		if($res){
			$resCount = 0;
			while(($record = $res->fetch())){

				$resCount++;

				// Prepare data for the indexer
				$title = $record['name'];
				$abstract = $record['teaser'];
				$description = $record['description'];

				$content = $title . PHP_EOL . nl2br($abstract) . PHP_EOL . nl2br($description);
				$content .=  PHP_EOL . $record['lname']
					. PHP_EOL . $record['zip'] . ' ' . $record['city'] . ' ' . $record['street_and_nr']
					. PHP_EOL . $record['odesc'];
				$content .=  PHP_EOL . $record['oname'] . PHP_EOL . $record['odesc'];

				$tags = '';
				$sys_language_uid = $record['sys_language_uid'];
				$sortdate = $record['start_date'];
				$endtime = $record['end_date'];
				if ($endtime < 1){
					$endtime = $record['start_date'];
				}
				$feGroup = '';
				$debugOnly = false;

				$parameters = [
					'tx_jvevents_events[event]=' . intval($record['uid']),
					'tx_jvevents_events[action]=show',
					'tx_jvevents_events[controller]=Event'
				];

				// The following should be filled (in accordance with the documentation), see also:
				// http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
				$additionalFields = array(
					'orig_uid' => $record['uid'] ,
					'sortdate' => $sortdate ,
					'servername' => EnvironmentUtility::getServerName()
				);

				$indexerObject->storeInIndex(
					$indexerConfig['pid'],			// folder, where the indexer Data is stored
					$title,							// title in the result list
					'jv_events',				// content type Important
					$indexerConfig['targetpid'],	// uid of the targetpage (see indexer-config in the backend)
					$content, 						// below the title in the result list
					$tags,							// tags (not used here)
					'&' . implode('&', $parameters), // additional typolink-parameters, e.g. '&tx_jvevents_events[event]=' . $record['uid'];
					$abstract,						// abstract (not used here)
					$sys_language_uid,				// sys_language_uid
					0 ,					// starttime (not used here)
					$endtime,						// endtime (not used here)
					$feGroup,						// fe_group (not used here)
					$debugOnly,						// debug only?
					$additionalFields				// additional fields added by hooks
				);

			}

		}

		DbUtility::writeToSyslog([
			'action'  => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => 'Indexer JvEvents Entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => 'Updated ' . $resCount . ' entries '
		]);

		return $resCount;

	}
}