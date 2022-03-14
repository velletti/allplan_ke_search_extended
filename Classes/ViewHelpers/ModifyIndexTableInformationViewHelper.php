<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\LanguageUtility;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Php
 */
use Closure;

/**
 * ViewHelper to modify the backend view "Index table information":
 * Add timestamp of the latest index entry for each indexer type
 * @author Peter Benke <pbenke@allplan.com>
 */
class ModifyIndexTableInformationViewHelper extends AbstractViewHelper
{

	/**
	 * Initialize arguments
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function initializeArguments()
	{
		parent::initializeArguments();
		$this->registerArgument('content', 'string', 'Content (html), which is shown in backend by ke_search');
	}

	/**
	 * @param array $arguments
	 * @param Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	static public function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
	{
		
		$content = $arguments['string'];
		if(empty($content)){
			$content = $renderChildrenClosure();
		}

		// Find all patterns like e.g.: <span class="label label-primary">allplan_online_help</span>
		// => All different indexer types
		$pattern = "#<span class=\"label label-primary\">(.*)<\/span>#siU";
		if(preg_match_all($pattern, $content, $matches)){

			// print_r($matches);
			/*
				We have something likes this:

				$matches = >Array
				(
					[0] => Array
						(
							[0] => <span class="label label-primary">allplan_online_help</span>
							[1] => <span class="label label-primary">jv_events</span>
						)
					[1] => Array
						(
							[0] => allplan_online_help
							[1] => jv_events
						)
				)

			*/

			// Now get the latest index entry of every indexer type and add it as formatted date
			for($i=0; $i<count($matches); $i++){

				$latestEntry = LanguageUtility::translate('backend.indexTable.latestEntry') . ': ';
				$latestEntry.= date(LanguageUtility::translate('format.datetime'), DbUtility::getLatestTstampByIndexerType($matches[1][$i]));

				$content = str_replace(
					$matches[0][$i],
					$matches[0][$i] . ' <span style="font-weight:normal;font-style:italic">(' . $latestEntry . ')</span>',
					$content
				);
			}

		}

		return $content;

	}

}