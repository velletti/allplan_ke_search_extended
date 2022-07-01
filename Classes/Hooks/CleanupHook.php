<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;

/**
 * Php
 */
use Exception;

class CleanupHook
{

	/**
	 * Cleanup for counting and deleting old index records
	 * Called in ke_search/Classes/Indexer/IndexerRunner.php->cleanUpIndex()
	 * -----------------------------------------------------------------------------------------------------------------
	 * Deletes all index elements that are older than starting timestamp in registry (+ conditions from us)
	 * Explanation:
	 * - At first ke_search runs an indexer and inserts / updates index entries in table tx_kesearch_index
	 *   On start ke_search writes a tstamp in sys_registry with data 'tx_kesearch' => 'startTimeOfIndexer'
	 * - After the index process is finished, the ke_search->cleanUpIndex() function is called, which calls this hook here
	 *   ke_search deletes all index entries, where the tstamp is older than the 'startTimeOfIndexer' in sys_registry
	 * -----------------------------------------------------------------------------------------------------------------
	 * Normally all records would be deleted by ke_search
	 * Added conditions here:
	 * - pid
	 * - type
	 * - language
	 * - period of days to delete records
	 * - server name
	 * @param string $where
	 * @param IndexerRunner $indexerRunner
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function cleanup(string &$where, IndexerRunner $indexerRunner): string
	{

		$content = '<p><br>Where-condition before CleanupHook: ' . $where . '</p>';

		$indexerConfig = $indexerRunner->indexerConfig;
		$schedulerTaskConfiguration = $indexerRunner->getTaskConfiguration();
		$deleteOldEntriesPeriodInSeconds = FormatUtility::getSecondsByDays($schedulerTaskConfiguration->getDeleteOldEntriesPeriodInDays());

		$deleteConditions = [];

		// pid and type
		// -------------------------------------------------------------------------------------------------------------
		// Special case: Forum /FAQ indexer (multiple indexer types and storage pids, same in connect and www)
		if(IndexerUtility::isForumIndexerType($indexerConfig['type'])) {

            $deleteConditions[] = "`pid` IN (" . DbUtility::getForumIndexerStoragePidsForSql() . ")";
            $deleteConditions[] = "`type` IN (" . DbUtility::getForumIndexerTypesForSql() . ")";
            // do not consider language
            $language = null;

        }else if(IndexerUtility::isFaqIndexerType($indexerConfig['type'])) {
            $deleteConditions[] = "`pid` IN (" . DbUtility::getFaqIndexerStoragePidsForSql() . ")";
            $deleteConditions[] = "`type` IN (" . DbUtility::getFaqIndexerTypesForSql() . ")";
            // do not consider language
            $language = null;

		// all the other indexers
		}else{

			$deleteConditions[] = "`pid` = " . (int)IndexerUtility::getStoragePid($indexerRunner, $indexerConfig);
			$deleteConditions[] = "`type` = '" . $indexerConfig['type'] . "'";
			try{
				$language = IndexerUtility::getLanguage($indexerRunner);
			}catch(Exception $e){
				$language  = null;
			}

		}

		// language => consider language only, if explicit set
		// -------------------------------------------------------------------------------------------------------------
		if(!is_null($language) && $language != ''){
			$deleteConditions[] = "`language` = '" . $language . "'";
		}

		// period of days to delete records
		// -------------------------------------------------------------------------------------------------------------
		if(!empty($deleteOldEntriesPeriodInSeconds)){
			$deleteConditions[] = "`crdate` < '" . (time() - $deleteOldEntriesPeriodInSeconds) . "'";
		}

		// server name
		// -------------------------------------------------------------------------------------------------------------
		$deleteConditions[] = "`tx_allplan_ke_search_extended_server_name` = '" . EnvironmentUtility::getServerName() . "'";

		$where.= " AND " . implode(' AND ', $deleteConditions);
		$content.= '<p>Where-condition after CleanupHook: ' . $where . '</p>';

		// Used in TYPO3 backend / email (CLI mode)
		return $content;

	}

}