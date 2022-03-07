<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerBase;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerInterface;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FeGroupUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

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
 * Indexer for EXT:marit_elearning lessons (videos) (EXT:marit_elearning)
 */
class MaritElearningLessonsIndexer extends IndexerBase implements IndexerInterface
{

	/**
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

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_maritelearning_domain_model_lesson')->createQueryBuilder();
		$queryBuilder
			->select('*')
			->from('tx_maritelearning_domain_model_lesson')
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
			'Indexer: Elearning lessons (videos) (EXT:marit_elearning)',
			'Updated ' . $count . ' entries'
		);

		return $count;

	}

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		// Set the fields
		$pid = IndexerUtility::getStoragePid($indexerRunner, $indexerConfig); // storage pid, where the indexed data should be stored
		$title = FormatUtility::cleanStringForIndex($record['title']); // title in the result list
		$type = $indexerConfig['type']; // content type (to differ in frontend (css class))

		// Always Connect is ok (also Campus records)
		$targetPid = 'https://connect.allplan.com/index.php?id=' . $indexerConfig['targetpid'] . '&' . implode('&', [
			'tx_maritelearning_pi1[lesson]=' . intval($record['uid']),
			'tx_maritelearning_pi1[action]=single',
			'tx_maritelearning_pi1[controller]=Lesson'
		]); // target pid for the detail link / external url
		if(intval($record['sys_language_uid']) > 0){
			$targetPid .= '&L=' . $record['sys_language_uid'];
		}
		$content = FormatUtility::buildContentForIndex([
			$record['title'],
			$record['desc_short'],
			$record['desc_long'],
		]);
		$tags = '#videos#'; // tags
		$params = '_blank'; // additional parameters for the link in frontend
		$abstract = FormatUtility::cleanStringForIndex($record['desc_short']);
		$language = IndexerUtility::getLanguage($indexerRunner, $record['sys_language_uid']); // sys_language_uid
		$startTime = 0;
		$endTime = 0;
		$feGroup = FeGroupUtility::getElearningFeGroupForIndex($record['fe_group']);
		$debugOnly = false;
		$additionalFields = [
			'orig_uid' => $record['uid'],
			'sortdate' => intval($record['tstamp']),
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