<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\TyposcriptUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tpwd
 */
use Tpwd\KeSearch\Lib\Db;

/**
 * Php
 */
use PDO;

class GetQueryPartsHook
{

	/**
	 * Modifies the last part of query building
	 * Used by Allplan Faq
	 * @param array $queryParts
	 * @param Db|object $pibase
	 * @param string|null $searchwordQuoted
	 * @return array|void
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function getQueryParts(array $queryParts , $pibase , $searchwordQuoted=null)
	{
		// Todo check this


		/*
		 * see connect TYPO Script file f.e.:
		 * http/typo3conf/ext/connect_template/Configuration/TypoScript/Base/Setup/Extensions/ke_search_for_support.ts
		 * will add conditions f.e. type = "supportfaq%" or set defaultSearch = top10 > 0
		 */
		$debug[] = "line: " . __LINE__  . " init";

		// @extensionScannerIgnoreLine
		$resultPage = $pibase->conf['resultPage'];

		$settings = TyposcriptUtility::loadTypoScriptFromScratch($resultPage,'tx_kesearch_pi1');
		$debug[] = "line: " . __LINE__  . " queryParts is array";

		$hookData = null;

		// If typoscript is set (Allplan faq)
		if(is_array($settings) && array_key_exists("getQueryPartsHook", $settings)){

			$debug[] = "line: " . __LINE__ . " found getQueryPartsHook in settings";
			$hookData = $settings['getQueryPartsHook'];

			// If we have a WHERE-condition, consider top10
			if(is_array($hookData) && array_key_exists("where", $hookData)) {
				$debug[] = "line: " . __LINE__ . " hookData is array and has WHERE-condition";
				$queryParts['WHERE'] .= " AND ( " ;
				foreach ($hookData['where'] as $query){
					$queryParts['WHERE'] .= " (`" . trim($query['field']) . "` " . trim($query['type']) . " '" . trim( $query['value'] ) . "') ";
				}
				$queryParts['WHERE'] .= " ) ";
				$queryParts['SELECT'] = str_replace( ") AS score" , " + (top10)) AS score" , $queryParts['SELECT']);
			}

			if(is_array($hookData) && array_key_exists("defaultSearch", $hookData)){

				$debug[] = "line: " . __LINE__ . " searchDefault is configured";
				$debug[] = "line: " . __LINE__ . " queryParts['WHERE'] = " . $queryParts['WHERE'];

				// check if we have an empty search-word, and we are not filtering by directory
				if(
					strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ") > 0
					||
					strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ") < 1
				) {
					$debug[] = "line: " . __LINE__ . " queryParts['WHERE'] matches empty search";
					$debug[] = "line: " . __LINE__ . " match if a) " . strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ");
					$debug[] = "line: " . __LINE__ . " match if b) " . strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ");

					if(strpos($queryParts['WHERE']," `directory`") < 1){
						$queryParts['WHERE'] = str_replace("MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ","", $queryParts['WHERE']) . " AND ( ";
					} else {
						$queryParts['WHERE'] = $queryParts['WHERE'] . " ( ";
					}
					foreach($hookData['defaultSearch'] as $query){
						$queryParts['WHERE'] .= " ( `" . trim($query['field']) . "` " . trim($query['type']);
						if(trim($query['type']) == ">" || trim($query['type']) == "<"){
							$queryParts['WHERE'] .= " " . trim($query['value']) . " ) ";
						} else {
							$queryParts['WHERE'] .= " '" . trim($query['value']) . "' ) ";
						}
					}
					$queryParts['WHERE'] .= " ) ";
					$queryParts['ORDERBY'] = "top10 DESC";
				}
			}

			$debug[] = "line: " . __LINE__ ;
			$queryParts['SELECT'] = str_replace( ") AS score"," + (top10)) AS score", $queryParts['SELECT']);
		}

		$params = GeneralUtility::_GET("tx_kesearch_pi1");
		if(is_array($params) && array_key_exists("directory", $params) && strlen($params["directory"]) > 0 ){
			$debug[] = "line: " . __LINE__ . " got parameter *directory*";
			if(strpos($params["directory"],";") < 1 && strpos($params["directory"],")") < 1 && strpos($params["directory"],"'") < 1){
				$databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_kesearch_index');
				$directory = trim(substr(urldecode($params["directory"]),0,-1)) . "%";
				$debug[] = "line: " . __LINE__ . " parameter *directory* is: " . $directory;
				$directoryQuoted = $databaseConnection->quote(
					str_replace( "\\" , "\\\\", $directory),
					PDO::PARAM_STR
				);
				$debug[] = "line: " . __LINE__ . " parameter *directory* is now: " . $directoryQuoted;
				$queryParts['WHERE'] .= " AND ( directory LIKE " . $directoryQuoted . " ) ";
			}
		}
		$queryParts['WHERE'] = str_replace("MATCH (title,content) AGAINST ","MATCH (title,content,directory) AGAINST ", $queryParts['WHERE']);

		// Output debug
		if(1==2){
			echo "<hr><pre>Hook Data:";
			print_r($hookData);
			echo "<hr>" ;
			print_r($debug);
			echo "<hr>" ;
			print_r($queryParts);
			die("__FILE__" . __FILE__ . " __LINE__" . __LINE__);
		}
		return $queryParts;
	}

}