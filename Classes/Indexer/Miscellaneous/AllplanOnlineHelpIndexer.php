<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Miscellaneous;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\AllplanKesearchIndexer;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;


/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerBase;

/**
 * Indexer for the Allplan Online Help
 */
class AllplanOnlineHelpIndexer extends IndexerBase
{

	/**
	 * @param IndexerRunner $indexerRunner
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function __construct($indexerRunner)
	{
		parent::__construct($indexerRunner);
		// Set the indexerRunner (defined in parent class in ke_search)
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
	 * @param array $indexerConfig configuration in scheduler task (backend)
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return int
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	###public function startIndexing(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	public function startIndexing(): int
	{

		$test = $this->indexerConfig;
		print_r([
			'indexerConfig' => $this->indexerConfig,
			'pObj' => $this->pObj,
			# 'indexerConfig' => $this->,
		]);
		print_r($test);die();

		// print_r();

		$url = $indexerObject->externalUrl . 'search.json';

		$indexerConfig['tags'] = '#onlinehelp#';
		$json = JsonUtility::getJsonFile($url);

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
				if($this->saveIndex($record, $indexerObject, $indexerConfig)){
					$count++;
				}
			}

			// Faster debug
			if($count > 3){
				// print_r(['$record' => $record]);
				return $count;
			}

			unset($record);

		}

		$insertFields = [
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => 0,
			'details' => 'Indexer Allplan Online Help entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => 'Updated ' . $count . ' entries from URL ' . $indexerObject->externalUrl
		];

		DbUtility::writeToSyslog($insertFields);
		return $count;

	}

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
	 * @return array
	 * @author Peter Benke <pbenke@allplan.com>
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 */
	private function getOnlineHelpRecordByRawData(string $data): array
	{

		// Delete everything until string 'title' appears
		$data = substr($data, strpos($data,'title'));

		// Now build pieces from the rest
		$tempData = explode('","', $data);

		// Now build an array
		$record = [];
		foreach ($tempData as $item){
			$temp = explode('":"', $item);
			$record[$temp[0]] = strip_tags(nl2br(str_replace(["@", "\t"], [" " , " "], $temp[1])));
		}

		if(count($record) > 2){
			$piecesUrl = explode('.', $record['url']);
			$record['uid'] = $piecesUrl[0];
		}

		return $record;

	}

	/**
	 * Write data to indexer
	 * @param array $record
	 * @param AllplanKesearchIndexer $indexerObject
	 * @param array $indexerConfig
	 * @return bool|int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private function saveIndex(array $record, AllplanKesearchIndexer $indexerObject, array $indexerConfig)
	{

		// Prepare data for the indexer
		$content = $record['title'] . PHP_EOL . nl2br($record['text']);

		// The following should be filled (in accordance with the documentation), see also:
		// http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
		$additionalFields = [
			'orig_uid' => $record['uid'] ,
			'servername' => EnvironmentUtility::getServerName()
		];

		// Take storage pid form indexer object or overwrite it with storagePid from indexer task
		$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid : $indexerConfig['pid'];

		// Call the function from ke_search
		return $indexerObject->storeInIndex(

			$pid,             // folder, where the indexer data should be stored (not where the data records are stored)
			$record['title'], // title in the result list

			// content type (can be useful, if you want to use additionalResultMarker to differ in frontend)
			'allplanhelp',

			// externalUrl is set in indexer task in scheduler
			$indexerObject->externalUrl . 'index.htm#' . $record['url'],

			// below the title in the result list
			$record['title'] . PHP_EOL . nl2br($record['text']),


			$indexerConfig['tags'], // tags (not used here)
			'_blank', // additional params for the link
			substr($record['text'],0,200), // abstract
			$indexerObject->language[0], // sys_language_uid
			0, // starttime (not used here)
			0, // endtime (not used here)
			'', // fe_group (not used here)
			false, // debug only?
			$additionalFields // additional fields added by hooks
		);

	}

}