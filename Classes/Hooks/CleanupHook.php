<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\IndexerRunner;

class CleanupHook
{

	/**
	 * Cleanup for counting and deleting old index records
	 * @param string $where
	 * @param IndexerRunner $pObj
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function cleanup(string &$where, IndexerRunner $pObj): string
	{

		$content = "\n Before CleanupHook: Got $" . "where = " . $where;

		switch ($pObj->indexerConfig['type']){

			// Todo spelling
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

			// Todo spelling
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

			// Todo spelling
			case 'lessons':
				$where .= " AND ( `type` = 'lessons' or `type` = 'lessonslocked' ) AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break;

			// Todo spelling
			case 'documentations':
				$where .= " AND ( `type` = 'documentation' or `type` = 'documentationlocked' ) AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break ;

			// Todo spelling
			case 'allplanforum':
				$where .= " AND (`type` = 'allplanforum' OR `type` = 'allplanforumsp' OR `type` = 'allplanforumlocked') AND tstamp < " . (time() - (60 * 60 * 24 * ($pObj->period)));
				break;


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

	}

}