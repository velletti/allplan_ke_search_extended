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
	 * - If GET parameter 'directory' is set (Allplan faq) => urldecode 'wordsAgainst' and 'sword'
	 * - If search string is at least 3 characters and contains '+' or ' ' => build AND conditions for MATCH AGAINST
	 * @param array $searchWordInformation
	 * @param Pluginbase $pObj
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifySearchWords(array &$searchWordInformation, Pluginbase &$pObj)
	{
		# GeneralUtility::_GP()
		$searchWordInformation['wordsAgainst'] = trim($searchWordInformation['wordsAgainst']);
		// print_r(['start' => $searchWordInformation]);

		// If GET parameter 'directory' is set (Allplan faq) => urldecode 'wordsAgainst' and 'sword'
		if(isset($_GET['tx_kesearch_pi1']['directory'])){

			if ($searchWordInformation['wordsAgainst'] == '') {
				$searchWordInformation['wordsAgainst'] = urldecode(trim($_GET['tx_kesearch_pi1']['directory']));
			}
			if ($searchWordInformation['sword'] == '') {
				$searchWordInformation['sword'] = urldecode(trim($_GET['tx_kesearch_pi1']['directory']));
			}
		}

		// If search string is at least 3 characters and contains '+' or ' ' => build AND conditions for MATCH AGAINST
		$wordsAgainst = trim($searchWordInformation['wordsAgainst']);
		if(
			strlen($wordsAgainst) > 2
			&&
			(
				preg_match('/ /', $wordsAgainst)
				||
				preg_match('/\+/', $wordsAgainst)
			)
		){

			// print_r(['wordsAgainst before' => $searchWordInformation]);

			/**
			 * If we have a '+' or ' ' in search string => build an AND-condition for 'MATCH ... AGAINST'
			 * Example: 'searchString1+searchString2' or 'searchString1 + searchString2' (with spaces) or 'searchString1 searchString2'
			 * => should end in:
			 *
			 * MATCH (title,content) AGAINST ('+searchString1* +searchString2*' IN BOOLEAN MODE)...
			 *
			 * @see https://dev.mysql.com/doc/refman/8.0/en/fulltext-boolean.html
			 * + stands for AND
			 * - stands for NOT
			 * [no operator] implies OR
			 */

			$wordsAgainst = trim($searchWordInformation['wordsAgainst']);
			$wordsAgainst = str_replace('+',' ', $wordsAgainst);
			$wordsAgainst = str_replace('*',' ', $wordsAgainst); // might already come from ke_search
			$wordsAgainst = trim($wordsAgainst);

			$wordsAgainst = preg_replace('#\s+#', ' ', $wordsAgainst); // multiple spaces to one space
			$wordsAgainst = explode(' ', $wordsAgainst);
			$matchesAgainst = [];

			// Now build the new 'wordsAgainst'
			foreach($wordsAgainst as $wordAgainst){
				$matchesAgainst[] = '+' . $wordAgainst . '*';
			}

			// Set 'wordsAgainst' to the new value
			$searchWordInformation['wordsAgainst'] = implode(' ', $matchesAgainst);

			// print_r(['wordsAgainst afterwards' => $searchWordInformation]);

		}

	}

}