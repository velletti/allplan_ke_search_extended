<?php
namespace Allplan\AllplanKeSearchExtended\Indexer;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\JsonUtility;

/**
 * Php
 */
use DateTime;
use Exception;

// Todo Spelling
class AllplanContentserveIndexer
{

	/**
	 * @param array $indexerConfig configuration from TYPO3 backend
	 * @param AllplanKesearchIndexer $indexerObject reference to the indexer class
	 * @return int
	 */
	public function main(array &$indexerConfig, AllplanKesearchIndexer &$indexerObject): int
	{

		$indexerConfig['tags'] = '#contentserve#';
		$baseUrl = 'https://' . EnvironmentUtility::getServerName() . '/index.php?id=3121&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1'
			. '&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1';

		// https://connect.allplan.com.ddev.local/index.php?id=3121&no_cache=1&tx_nemjvgetcontent_pi1[func]=SHOWITEM&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&amp;tx_nemjvgetcontent_pi1[WLA]=ENU&amp;tx_nemjvgetcontent_pi1[pid]=0
		// https://connect.allplan.com.ddev.local/index.php?id=3121&no_cache=1&tx_nemjvgetcontent_pi1[func]=SHOWITEM&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=101
		// https://connect.allplan.com.ddev.local/index.php?id=3121&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1&tx_nemjvgetcontent_pi1[token]=75f99e11fa7f86fa85329aa36268d753&tx_nemjvgetcontent_pi1[filter_favorite]=2&tx_nemjvgetcontent_pi1[json]=1&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=88

		$count = 0;
		// Todo debug as array
		$debug = '';

		$languages = [
			1 => 'DEU',
			4 => 'FRA',
			2 => 'ITA',
			18 => 'ESP',
			14=> 'RUS',
			3 => 'CZE'
		];

		for($i=1 ; $i < 2000 ; $i++){
			$url = $baseUrl . '&tx_nemjvgetcontent_pi1[WLA]=ENU&tx_nemjvgetcontent_pi1[pid]=' . $i;
			$debug .= 'CP: ' . $i  . ' -> url: ' . $url;
			$json = JsonUtility::getJsonFile($url);
			$debug .= 'response: ' . var_export($json,true);

			if(is_array($json)){
				$debug.= ' is array';
				if($json['error'] < 1 ){
					$debug.= " not an error ";
					if($this->putToIndex($json, $indexerObject, $indexerConfig,0)){
						$debug.= ' English is stored';
						$count++;
						$debug.= ' repeating with languages';
						foreach ($languages as $language => $WLA){

							$url = $baseUrl . '&tx_nemjvgetcontent_pi1[WLA]=' . $WLA . '&tx_nemjvgetcontent_pi1[pid]=' . $i;
							$json = JsonUtility::getJsonFile($url);
							if(is_array($json)){
								$debug .= ' tried  language' . $WLA;
								if($json['error'] < 1 ){
									$debug .= ' Got it ';
									if($this->putToIndex($json, $indexerObject, $indexerConfig, $language)){
										if ($WLA == 'DEU'){
											$debug.= ' adding AT / CH';
											$this->putToIndex($json, $indexerObject, $indexerConfig,6);
											$this->putToIndex($json, $indexerObject, $indexerConfig,7);
										}
										$count++;
									}
								}
							}

							if ($count > 100 && $_SERVER['SERVER_NAME'] == 'connectv10.allplan.com.ddev.local'){
								return $count;
							}
						}
					}
				}
			}
		}

		DbUtility::writeToSyslog([
			'action' => 1,
			'tablename' => 'tx_kesearch_index',
			'error' => 0,
			'event_pid' => $indexerObject->storagePid > 0 ? $indexerObject->storagePid : $indexerConfig['pid'],
			'details' => 'Allplan ContentServe Indexer had updated / inserted ' . $count . ' entries',
			'tstamp' => time(),
			'type' => 1,
			'message' => ' - config: ' . var_export($indexerObject,true) . substr($debug,0,1024),
		]);

		return $count;
	}


	/**
	 * @param array $single
	 * @param AllplanKesearchIndexer $indexerObject
	 * @param array $indexerConfig
	 * @param int $language
	 * @return bool|int
	 * @throws Exception
	 */
	protected function putToIndex(array $single , AllplanKesearchIndexer $indexerObject, array $indexerConfig, int $language)
	{
		
		if(!isset($single['Header']['RESULTS']) || $single['Header']['RESULTS'] == 0){
			return false;
		}

		$content = str_replace('"',"", $single['CPs'][0]['LABEL_CP']) . PHP_EOL . str_replace('"' ,"", $single['CPs'][0]['LTX_CP']);
		$content .= PHP_EOL . $single['CPs'][0]['CP_ADE'];
		$content .= PHP_EOL . 'ID: ' . $single['CPs'][0]['CP_IDI'] . PHP_EOL;

		$cfs = '';
		$cats = [];
		$filetypes = [];
		$DES_CF = [];
		$CF_ADE = [];
		$PRO_CF = [];
		$APV_CF = [];

		if(is_array($single['CPs'][0]['LINK_CP'])){
			foreach($single['CPs'][0]['LINK_CP'] as $cfile){

				$cfs .= $cfile['CF_IDI'] . ',';
				$temp = $cfile['FILETYPE'];
				$filetypes[$temp] = $cfile['FILETYPE'];

				$temp = $cfile['CF_ADE'];
				$CF_ADE[$temp] = $cfile['CF_ADE'];

				$temp = $cfile['DES_CF'];
				$DES_CF[$temp] = $cfile['DES_CF'];

				$temp = $cfile['PRO_CF'];
				$PRO_CF[$temp] = $cfile['PRO_CF'];

				$temp = $cfile['APV_CF'];
				$APV_CF[$temp] = $cfile['APV_CF'];

				foreach ($cfile['KAT_CF'] as $kat){
					$id = $kat['KAT_KEY'];
					$cats[$id] = $id . ' - ' . $kat['KAT_LABEL'];
				}
			}
		}

		$content.= PHP_EOL . implode(PHP_EOL, $cats);
		$content.= PHP_EOL . implode(PHP_EOL, array_unique($filetypes));
		$content.= PHP_EOL . implode(PHP_EOL, array_unique($DES_CF));
		$content.=  PHP_EOL . implode(PHP_EOL, array_unique($CF_ADE));
		$content.=  PHP_EOL . implode(PHP_EOL, array_unique($PRO_CF));
		$content.= PHP_EOL . implode(PHP_EOL, array_unique($APV_CF));

		$datum =  new DateTime($single['CPs'][0]['CP_DTA']);

		$additionalFields = [
			'orig_uid' => $single['CPs'][0]['CP_IDI'],
			'servername' => EnvironmentUtility::getServerName(),
			'sortdate' => $datum->getTimestamp()
		];

		$pid = $indexerObject->storagePid > 0 ? $indexerObject->storagePid : $indexerConfig['pid'];
		$pidLink = $indexerConfig['targetpid'] > 0 ? $indexerConfig['targetpid'] : 359;
		$url = 'https://' . EnvironmentUtility::getServerName() . '/index.php?id=' . $pidLink . '&tx_nemjvgetcontent_pi1[func]=SHOWITEM&no_cache=1'
			. '&L=' . $language;
		$url .= '&tx_nemjvgetcontent_pi1[pid]=' . $single['CPs'][0]['CP_IDI'];
		$url .= '&tx_nemjvgetcontent_pi1[cf_ids]=' . $cfs;

		return $indexerObject->storeInIndex(
			$pid,
			str_replace('"',"" , $single['CPs'][0]['LABEL_CP']),
			'contentserve',
			$url,
			$content,
			$indexerConfig['tags'],
			'_blank',
			$single['CPs'][0]['LTX_CP'],
			$language,
			0,
			0,
			'',
			false,
			$additionalFields
		);

	}

}