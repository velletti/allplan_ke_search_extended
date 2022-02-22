<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Www;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Php
 */
use Exception;

/**
 * Indexer for EXT:jv_events
 */
class JvEventsIndexer extends IndexerBase
{

	/**
	 * @param IndexerRunner $indexerRunner
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct($indexerRunner)
	{
		parent::__construct($indexerRunner);
		// Set the indexerRunner (defined in parent class in ke_search, variable name is not-quite-correct)
		$this->pObj = $indexerRunner;
	}

	/**
	 * Indexer for the events (EXT:jv_events)
	 *
	 * Test on LOCAL:
	 * 1) Before every start => DB:
	 * UPDATE tx_scheduler_task SET nextexecution='1642431600', lastexecution_failure='', serialized_executions='' WHERE uid=[scheduler_task_uid];
	 * TRUNCATE Table sys_registry;
	 * TRUNCATE Table tx_kesearch_index;
	 * 2) cli:
	 * /var/www/html/http/typo3/sysext/core/bin/typo3 scheduler:run --task=[scheduler_task_uid] -vv
	 *
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Better variable name
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;

		// Get all upcoming events
		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getQueryBuilderForTable('tx_jvevents_domain_model_event');
		$expr = $queryBuilder->expr();

		$queryBuilder
			->select('event.uid', 'event.name', 'event.teaser', 'event.description', 'event.sys_language_uid', 'event.start_date', 'event.end_date')
			->addSelect('org.name as organizer_name','loc.city as city', 'loc.zip as zip', 'loc.street_and_nr', 'loc.name as location_name')
			->addSelect('loc.description as location_description', 'org.description as organizer_desc')
			->from('tx_jvevents_domain_model_event', 'event')
			->leftJoin('event', 'tx_jvevents_domain_model_organizer','org', $expr->eq('event' . '.organizer','org.uid'))
			->leftJoin('event', 'tx_jvevents_domain_model_location','loc', $expr->eq( 'loc.uid','event.location'))
			->where($expr->gt('event.start_date', time()))
			->andWhere($expr->in('event.pid', DbUtility::getTreeList($indexerConfig['startingpoints_recursive']))) // see also Configuration/TCA/Overrides/tx_kesearch_index.php
		;

		$result = $queryBuilder->execute();
		$count = 0;

		if($result){
			while(($record = $result->fetchAssociative())){

				// Write record to index
				if($this->storeInKeSearchIndex($record, $indexerRunner, $indexerConfig)){
					$count++;
				}

			}
		}

		// Write to sys_log
		DbUtility::saveIndexerResultInSysLog(
			'Indexer: JvEvents (EXT:jv_events)',
			'Updated ' . $count . ' entries'
		);

		return $count;

	}


	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		// Scheduler task configuration
		$taskConfiguration = $indexerRunner->getTaskConfiguration();

		// Set the fields
		$pid = IndexerUtility::getStoragePid($indexerRunner, $indexerConfig); // storage pid, where the indexed data should be stored
		$title = FormatUtility::cleanStringForIndex($record['name']); // title in the result list
		$type = 'jv_events'; // content type (to differ in frontend (css class))
		$targetPid = $indexerConfig['targetpid']; // target pid for the detail link / external url
		$content = $record['name'] . ': '; // below the title in the result list
		$content.= $record['teaser'] . ' ';
		$content.= $record['description'] . ' ';
		$content.= $record['location_name'] . ' ';
		$content.= $record['zip'] . ' ';
		$content.= $record['city'] . ' ';
		$content.= $record['street_and_nr'] . ' ';
		$content.= $record['location_description'] . ' ';
		$content.= $record['organizer_name'] . ' ';
		$content.= $record['organizer_desc'];
		$content = FormatUtility::cleanStringForIndex($content);
		$tags = ''; // tags
		$params = '&' . implode('&', [
			'tx_jvevents_events[event]=' . intval($record['uid']),
			'tx_jvevents_events[action]=show',
			'tx_jvevents_events[controller]=Event'
		]); // additional parameters for the link in frontend
		$abstract = FormatUtility::cleanStringForIndex($record['teaser']);
		$language = IndexerUtility::getLanguage($indexerRunner, $record['sys_language_uid']); // sys_language_uid
		$startTime = 0;
		$endTime = $record['end_date'];
		if ($endTime < 1){
			$endTime = $record['start_date'];
		}
		$feGroup = ''; // not used here
		$debugOnly = false;
		$additionalFields = [
			'orig_uid' => $record['uid'],
			'sortdate' => $record['start_date'],
			'tx_allplan_ke_search_extended_server_name' => EnvironmentUtility::getServerName(),
		];

		// Call the function from ke_search
		return $indexerRunner->storeInIndex(
			$pid,
			$title,
			$type,
			$targetPid,
			$content,
			$tags,
			$params,
			$abstract,
			$language,
			$startTime,
			$endTime,
			$feGroup,
			$debugOnly,
			$additionalFields
		);

	}

}