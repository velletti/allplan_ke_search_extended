<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Miscellaneous;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase;

/**
 * Php
 */
use Exception;

/**
 * Indexer for the Allplan Online Help
 */
class AllplanOnlineHelpIndexer extends IndexerBase
{

	/**
	 * Public functions
	 * =================================================================================================================
	 */

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
	 * Indexer for the Allplan Online Help
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
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Better variable name
		$indexerRunner = $this->pObj;

		// Set the configs from scheduler and from the indexer (tx_kesearch_indexerconfig)
		$schedulerTaskConfig = $indexerRunner->getTaskConfiguration();
		$externUrl = $schedulerTaskConfig->getExternUrl() . 'search.json';
		$indexerConfig = $this->indexerConfig;

		$json = JsonUtility::getJsonFile($externUrl);

		// Check, if we have a valid json-array
		if(is_array($json) && $json['error'] > 0){
			return 0;
		}

		// Split the records
		$records = explode('},{', $json);
		$count = 0;
		foreach ($records as $data){

			// Build a useful record
			$record = $this->getOnlineHelpRecordByRawData($data);

			// Write record to index
			if(!empty($record)){
				if($this->storeInKeSearchIndex($record, $indexerRunner, $indexerConfig)){
					$count++;
				}
			}

			// Faster debug
			# if($count > 3){
			# 	return $count;
			# }

			unset($record);

		}

		// Write to sys_log
		DbUtility::saveIndexerResultInSysLog(
			'Indexer: Allplan Online Help',
			'Updated ' . $count . ' entries from URL ' . $externUrl
		);

		return $count;

	}

	/**
	 * Private functions
	 * =================================================================================================================
	 */

	/**
	 * Get a record (associative array), we can use for further processing by a given raw string
	 * Example for $data:
	 * "id":4,"title":"Effektives Verwenden der Allplan Hilfe","url":"5468.htm","text":"@  Willkommen zur Allplan Hilfe..."
	 * Returns;
	 * array[
	 * 		'uid' => ... (comes from html-filename, e.g. 5468.htm => 5468)
	 * 		'url' => ...
	 * 		'title' => ...
	 * 		'text' => ...
	 * ]
	 * @param string $data
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function getOnlineHelpRecordByRawData(string $data): ?array
	{

		// if(preg_match('/url":"89280.htm/', $data)){
		// 	print_r($data);
		// 	die();
		// }

		// Delete everything until string 'title' appears
		$data = substr($data, strpos($data,'title'));

		// Now build pieces from the rest
		$tempData = explode('","', $data);

		// Now build an associative array: 'text': ..., 'url': ...
		$record = [];

		foreach ($tempData as $item){

			$temp = explode('":"', $item);

			$key = $temp[0];
			$value = $temp[1];

			$pattern[] = '#@#';
			$replace[] = ' ';

			$pattern[] = "#\\\\t#"; // \t
			$replace[] = ' ';

			$pattern[] = '#\s+#'; // Multiple spaces, tabs and the rest of linebreaks => to spaces
			$replace[] = ' ';

			$value = preg_replace($pattern, $replace, $value);

			$value = strip_tags($value);
			$value = nl2br($value);
			$value = trim(rtrim($value, "\"")); // trim " endings

			$record[$key] = $value;

		}

		// If we have no text, we do not need the record at all
		if(!isset($record['text']) || empty($record['text'])){
			return null;
		}

		// If we have got more than 2 items => we have an uid (e.g. 46797 from url:46797.htm)
		if(count($record) > 2){
			$piecesUrl = explode('.', $record['url']);
			$record['uid'] = $piecesUrl[0];
		}

		return $record;

	}

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		// Scheduler task configuration
		$taskConfiguration = $indexerRunner->getTaskConfiguration();

		// Set the fields
		$pid = IndexerUtility::getStoragePid($indexerRunner, $indexerConfig); // storage pid, where the indexed data should be stored
		$title = $record['title']; // title in the result list
		$type = 'allplan_online_help'; // content type (to differ in frontend (css class))
		$targetPid = $taskConfiguration->getExternUrl() . 'index.htm#' . $record['url']; // target pid for the detail link / external url
		$content = $record['title'] . ': ' . $record['text']; // below the title in the result list
		$tags = '#onlinehelp#'; // tags
		$params = '_blank'; // additional parameters for the link in frontend
		$abstract = trim(substr($record['text'],0,200));
		$language = $taskConfiguration->getSysLanguageUid(); // sys_language_uid
		$startTime = 0; // not used here
		$endTime = 0; // not used here
		$feGroup = ''; // not used here
		$debugOnly = false;
		$additionalFields = [
			'orig_uid' => $record['uid'],
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