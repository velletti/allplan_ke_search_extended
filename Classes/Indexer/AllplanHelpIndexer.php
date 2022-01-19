<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;

class AllplanHelpIndexer
{

	/**
	 * Indexer for Allplan help
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		$url = $indexerObject->externalUrl . 'search.json';

		# Todo: put tags to indexer object
		$indexerConfig['tags'] = '#onlinehelp#';
		$json = JsonUtility::getJsonFile($url);
		if(is_array($json)){
			if($json['error'] > 0){
				return 0;
			}
		}

		$arr = explode("},{", $json);
		$count = 0;

		foreach ($arr as $string){
			$content = substr($string, strpos($string,'title'));
			$singleString = explode('","', $content);
			$single = [];
			foreach ($singleString as $string2){
				$temp = explode('":"', $string2);
				$single[$temp[0]] = strip_tags(nl2br(str_replace(["@", "\t"], [" " , " "], $temp[1])));
			}
			if(count($single)>2){
				$singleUid = explode('.', $single['url']);
				$single['uid'] = $singleUid[0];
				if($this->putToIndex($single, $indexerObject, $indexerConfig)){
					$count++;
				}
			}
			// For faster dev process
			// if($count > 20){
			//   var_dump($string);
			//   var_dump($single);
			//   return $count;
			// }
			unset($single);
			unset($singleString);

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
	 * Write data to indexer
	 * @param array $single
	 * @param AllplanKesearchIndexer $indexerObject
	 * @param array $indexerConfig
	 * @return bool|int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	protected function putToIndex(array $single, AllplanKesearchIndexer $indexerObject, array $indexerConfig)
	{

		// Prepare data for the indexer
		$content = $single['title'] . PHP_EOL . nl2br($single['text']);

		// The following should be filled (in accordance with the documentation), see also:
		// http://www.typo3-macher.de/facettierte-suche-ke-search/dokumentation/ein-eigener-indexer/
		$additionalFields = [
			'orig_uid' => $single['uid'] ,
			'servername' => EnvironmentUtility::getServerName()
		];

		// Take storage pid form indexer configuration or overwrite it with storagePid from indexer task
		$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid : $indexerConfig['pid'];

		return $indexerObject->storeInIndex(
			$pid, // folder, where the indexer data should be stored (not where the data records are stored)
			$single['title'], // title in the result list
			'allplanhelp', // content type ( useful, if you want to use additionalResultMarker)
			$indexerObject->externalUrl . 'index.htm#' . $single['url'], // uid of the target page (see indexer-config in the backend)
			$content, // below the title in the result list
			$indexerConfig['tags'], // tags (not used here)
			'_blank', // additional params for the link
			substr($single['text'],0,200), // abstract
			$indexerObject->language[0], // sys_language_uid
			0, // starttime (not used here)
			0, // endtime (not used here)
			'', // fe_group (not used here)
			false, // debug only?
			$additionalFields // additional fields added by hooks
		);

	}

}