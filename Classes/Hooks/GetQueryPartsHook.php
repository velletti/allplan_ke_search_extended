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

		$debug = [];

		/*
		 * see connect typoscript file e.g.:
		 * http/typo3conf/ext/connect_template/Configuration/TypoScript/Base/Setup/Extensions/ke_search_for_support.ts
		 * will add conditions e.g. type = "supportfaq%" or set defaultSearch = top10 > 0
		 */
		$debug[] = 'Line: ' . __LINE__  . ' init';

		// @extensionScannerIgnoreLine
		$resultPage = $pibase->conf['resultPage'];

		$settings = TyposcriptUtility::loadTypoScriptFromScratch($resultPage,'tx_kesearch_pi1');
		$debug[] = 'Line: ' . __LINE__  . is_array( $queryParts ) ?  ' queryParts is array' : '' ;

		$tsConfigQueryParts = null;

		// If typoscript is set (Allplan faq)
        // JVE 8.7.2022 : as Peter has renamed this setting, it  BRAKES FAQ Search.
        // so now i Check now BOTH "p" and "P" variants

		if( is_array($settings) && array_key_exists('getQuerypartsHook', $settings)) {
            $tsConfigQueryParts = $settings['getQuerypartsHook'];
        } else if( is_array($settings) && array_key_exists('getQueryPartsHook', $settings)) {
            $tsConfigQueryParts = $settings['getQueryPartsHook'];
        }
        if( is_array($tsConfigQueryParts)) {
			$debug[] = 'Line: ' . __LINE__ . ' "getQuery(P)artsHook" is configured in typoscript';

			// If we have a WHERE-condition in typoscript set => consider top10
			if(is_array($tsConfigQueryParts) && array_key_exists('where', $tsConfigQueryParts)) {

				$debug[] = 'Line: ' . __LINE__ . ' hookData is array and has WHERE-condition';
				$queryParts['WHERE'].= ' AND ( ';

				foreach($tsConfigQueryParts['where'] as $query){
					$queryParts['WHERE'] .= " (`" . trim($query['field']) . "` " . trim($query['type']) . " '" . trim( $query['value'] ) . "') ";
				}

				$queryParts['WHERE'] .= " ) ";
				$queryParts['SELECT'] = str_replace( ") AS score"," + (top10)) AS score" , $queryParts['SELECT']);

			}

			// If we have "defaultSearch" in typoscript set
			if(is_array($tsConfigQueryParts) && array_key_exists('defaultSearch', $tsConfigQueryParts)){

				$debug[] = 'Line: ' . __LINE__ . ' "defaultSearch" is configured in typoscript';
				$debug[] = 'Line: ' . __LINE__ . " queryParts['WHERE'] = " . $queryParts['WHERE'];

				// Check, if we have an empty search-word, and we are not filtering by directory
				if(
					strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ") > 0
					||
					strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ") < 1
				) {
					$debug[] = 'Line: ' . __LINE__ . " queryParts['WHERE'] matches empty search";
					$debug[] = 'Line: ' . __LINE__ . " match if a) " . strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ");
					$debug[] = 'Line: ' . __LINE__ . " match if b) " . strpos($queryParts['WHERE'],"MATCH (title,content) AGAINST ");

					if(strpos($queryParts['WHERE']," `directory`") < 1){
						$queryParts['WHERE'] = str_replace("MATCH (title,content) AGAINST ('+' IN BOOLEAN MODE) ","", $queryParts['WHERE']) . " AND ( ";
					} else {
						$queryParts['WHERE'] = $queryParts['WHERE'] . " ( ";
					}
					foreach($tsConfigQueryParts['defaultSearch'] as $query){
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

			$queryParts['SELECT'] = str_replace( ") AS score"," + (top10)) AS score", $queryParts['SELECT']);
			$debug[] = 'Line: ' . __LINE__ . " queryParts['SELECT'] = " . $queryParts['SELECT'];

		}

		$params = GeneralUtility::_GET('tx_kesearch_pi1');

		// If parameter 'directory' is given as GET parameter (from faq select box)
		if(is_array($params) && array_key_exists('directory', $params) && strlen($params['directory']) > 0 ){

			$debug[] = 'Line: ' . __LINE__ . " GET parameter *directory* given";

			if(strpos($params['directory'],';') < 1 && strpos($params['directory'],')') < 1 && strpos($params['directory'],"'") < 1){
				$databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_kesearch_index');
				$directory = trim(substr(urldecode($params['directory']),0,-1)) . '%';
				$debug[] = 'Line: ' . __LINE__ . " GET parameter *directory* is: " . $directory;
				$directoryQuoted = $databaseConnection->quote(
					str_replace( "\\" , "\\\\", $directory),
					PDO::PARAM_STR
				);
				$debug[] = 'Line: ' . __LINE__ . " parameter *directory* is now: " . $directoryQuoted;
				$queryParts['WHERE'] .= ' AND ( directory LIKE ' . $directoryQuoted . ' ) ';
			}
		}
		$queryParts['WHERE'] = str_replace("MATCH (title,content) AGAINST ","MATCH (title,content,directory) AGAINST ", $queryParts['WHERE']);

		// Output debug
		if(1==2){

			$debugOutput = '<hr><pre>Typoscript config query parts:';
			$debugOutput.= print_r($tsConfigQueryParts, true);
			$debugOutput.= '<hr>Debug<br>';
			$debugOutput.= print_r($debug, true);
			$debugOutput.= '<hr>QueryParts:<br>';
			$debugOutput.= print_r($queryParts, true);
            $debugOutput.= '<hr>settings[getQueryPartsHook] :<br>';
            $debugOutput.= print_r($settings['getQueryPartsHook'], true);
            $debugOutput.= print_r($settings, true);
            $debugOutput.= "<br>" ;
			echo $debugOutput;
            die("__FILE__" . __FILE__ . " __LINE__" . __LINE__);
			// mail('pbenke@allplan.com', 'Faq debug output', print_r(['Debug' => $debugOutput], true));

		}

		return $queryParts;

	}

}