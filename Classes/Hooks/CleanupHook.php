<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
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
	 * Deletes all index elements that are older than starting timestamp in registry + conditions from us
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

		// Todo Delete older than x days...

		$content = '<p><br>Where-condition before CleanupHook: ' . $where . '</p>';
		$indexerConfig = $indexerRunner->indexerConfig;
		$schedulerTaskConfiguration = $indexerRunner->getTaskConfiguration();

		$conditions = [];

		// pid
		$conditions[] = "`pid` = " . (int)IndexerUtility::getStoragePid($indexerRunner, $indexerConfig);

		// type
		$conditions[] = "`type` = '" . $indexerConfig['type'] . "'";

		// language => consider language only, if explicit set
		try{
			$language = IndexerUtility::getLanguage($indexerRunner);
		}catch(Exception $e){
			$language  = null;
		}
		if(!is_null($language) && $language != ''){
			$conditions[] = "`language` = '" . $language . "'";
		}

		// period of days to delete records
		$deleteOldEntriesPeriodInSeconds = FormatUtility::getSecondsByDays($schedulerTaskConfiguration->getDeleteOldEntriesPeriodInDays());
		if(!empty($deleteOldEntriesPeriodInSeconds)){
			$conditions[] = "`crdate` < '" . (time() - $deleteOldEntriesPeriodInSeconds) . "'";
		}

		// server name
		$conditions[] = "`tx_allplan_ke_search_extended_server_name` = '" . EnvironmentUtility::getServerName() . "'";

		$where.= " AND " . implode(' AND ', $conditions);
		$content.= '<p>Where-condition after CleanupHook: ' . $where . '</p>';

		// Used in TYPO3 backend
		return $content;

		#switch($indexerType){
		#	case 'allplan_online_help':
		#		break;
		#}#


		# allplan_online_help

/*
		$content = "\n Before CleanupHook: Got $" . "where = " . $where;

		switch ($pObj->indexerConfig['type']){


			case 'onlinehelp':
				$where .= " AND `type` = 'allplanhelp' ";
				if($pObj->storagePid){
					$where .= " AND pid = " . $pObj->storagePid;
				} else {
					$where .= " AND pid = " . $pObj->indexerConfig['pid'];
				}
				if(isset($pObj->language[0]) && $pObj->language[0] != ''){
					$where .= " AND language = " . intval( $pObj->language[0]);
				}
				break;

			// Todo
			case 'supportfaq':
				$where .= " AND ( `type` = 'supportfaq' or `type` = 'supportfaq' ) AND tstamp < " . (time() - (60 * 60 * 24 * $pObj->period));
				if($pObj->storagePid){
					$where .= " AND pid = " . $pObj->storagePid;
				}else{
					$where .= " AND pid = " . $pObj->indexerConfig['pid'];
				}
				if (isset($pObj->language[0]) && $pObj->language[0] != '') {
					$where .= " AND language = " . intval( $pObj->language[0]);
				}
				break;

			// Todo
			case 'lessons':
				$where .= " AND ( `type` = 'lessons' or `type` = 'lessonslocked' ) AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break;

			// Todo
			case 'documentations':
				$where .= " AND ( `type` = 'documentation' or `type` = 'documentationlocked' ) AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break ;

			// Todo
			case 'allplanforum':
				$where .= " AND (`type` = 'allplanforum' OR `type` = 'allplanforumsp' OR `type` = 'allplanforumlocked') AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break;


			// Todo
			default:
				$where .= " AND `type` = '" . $pObj->indexerConfig['type'] .  "' ";
				if($pObj->storagePid){
					$where .= " AND pid = " . $pObj->storagePid;
				} else {
					$where .= " AND pid = " . $pObj->indexerConfig['pid'];
				}
				if(is_array( $pObj->language ) && $pObj->language[0] != '' ){
					$where .= " AND language = " . intval( $pObj->language[0]);
				}
				break;

		}

		// Todo: change query
		$where .= " AND ( servername ='" . EnvironmentUtility::getServerName() . "' OR servername = '' ) ";
		return $content . "\n After CleanupHook: Now $" . "where = " . $where;
*/
	}

}