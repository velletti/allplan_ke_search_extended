<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerBase;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerInterface;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FeGroupUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Php
 */
use Exception;

class AllplanContentIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;

		$connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
		$queryBuilder = $connectionPool->getConnectionForTable('tx_allplancontent_domain_model_contentfile')->createQueryBuilder();

		$leftJoinConditions = " AND (`sfr`.`tablenames` = 'tx_allplancontent_domain_model_contentfile') AND (`sfr`.`fieldname` = 'file')";
		$queryBuilder
			->select('cf.uid', 'cf.label', 'cf.tstamp', 'cf.searchtext', 'cf.fe_group', 'cf.sys_language_uid', 'sf.mime_type')
			->from('tx_allplancontent_domain_model_contentfile', 'cf')
			->leftJoin('cf', 'sys_file_reference','sfr', 'cf.uid = sfr.uid_foreign' . $leftJoinConditions)
			->leftJoin('sfr', 'sys_file','sf', 'sfr.uid_local = sf.uid')
		;

		// Faster development
		$queryBuilder->setMaxResults(10);
		// echo $queryBuilder->getSQL() . PHP_EOL;

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
			'Allplan content (EXT:allplan_content)',
			$count
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
		$title = FormatUtility::cleanStringForIndex($record['label']); // title in the result list
		if(!empty($record['mime_type'])){
			$title.=' (' . $record['mime_type'] . ')';
		}
		$type = $indexerConfig['type']; // content type (to differ in frontend (css class))

		// Todo: detail link, if we have one
		// Always Connect is ok
		$targetPid = 'https://connect.allplan.com/TODO.php?id=' . $indexerConfig['targetpid'] . '&' . implode('&', [
				'tx_allplancontent_search[PARAM]=' . intval($record['uid']),
				'tx_allplancontent_search[PARAM]=TEST',
				'tx_allplancontent_search[PARAM]=TEST'
			]); // target pid for the detail link / external url
		if(intval($record['sys_language_uid']) > 0){
			$targetPid .= '&L=' . $record['sys_language_uid'];
		}
		$content = FormatUtility::buildContentForIndex([
			$record['label'],
			$record['searchtext'],
		]);
		$tags = '#allplancontent#';
		$params = '_blank'; // additional parameters for the link in frontend
		$abstract = FormatUtility::cleanStringForIndex($record['label']);
		$language = IndexerUtility::getLanguage($indexerRunner, $record['sys_language_uid']); // sys_language_uid
		$startTime = 0;
		$endTime = 0;
		$feGroup = FeGroupUtility::getFeGroupsForIndex($record['fe_group']);
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