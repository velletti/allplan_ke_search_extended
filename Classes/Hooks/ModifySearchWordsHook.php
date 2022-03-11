<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Pluginbase;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModifySearchWordsHook
{

	/**
	 * Modify search input => sets default to AND-search (+)
	 * (you can select these in the backend by creating a new record "indexer-configuration")
	 * @param array $searchWordInformation
	 * @param Pluginbase $pObj
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifySearchWords(array &$searchWordInformation, Pluginbase &$pObj)
	{

		// Todo: check this

		$searchWordInformation['wordsAgainst'] = trim($searchWordInformation['wordsAgainst']);

		// Set directory in search input
		if(isset($_GET['tx_kesearch_pi1']['directory'])){
			if ($searchWordInformation['wordsAgainst'] == '') {
				$searchWordInformation['wordsAgainst'] = urldecode(trim($_GET['tx_kesearch_pi1']['directory']));
			}
			if ($searchWordInformation['sword'] == '') {
				$searchWordInformation['sword'] = urldecode(trim($_GET['tx_kesearch_pi1']['directory']));
			}
		}

		if(strlen($searchWordInformation['wordsAgainst']) > 2){
			$searchWordInformation['wordsAgainst'] = str_replace("+", "", $searchWordInformation['wordsAgainst']);

			// @extensionScannerIgnoreLine
			// set user input in search field as html_encoded(), if defined in typoscript
			if($pObj->conf['encodeWords']){
				$searchWordInformation['wordsAgainst'] = htmlentities($searchWordInformation['wordsAgainst']);
			}
			$arr = GeneralUtility::trimExplode(" ", $searchWordInformation['wordsAgainst']);
			if ($arr && count($arr) > 3){
				$fixed = "";
				foreach ($arr as $key => $sword){
					if($key > 2){
						$sword = str_replace("*","", $sword);
					}
					$fixed .= " " . $sword;
				}
				$searchWordInformation['wordsAgainst'] = trim($fixed);

			}

			// var_dump($searchWordInformation['wordsAgainst']);
			// die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );

			$searchWordInformation['wordsAgainst'] = trim("+" . str_replace(" "," +", $searchWordInformation['wordsAgainst']));

		}

	}

}