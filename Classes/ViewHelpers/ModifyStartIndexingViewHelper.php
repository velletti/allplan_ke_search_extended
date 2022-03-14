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
 * ViewHelper to modify the backend view "Start indexing":
 * Hide the buttons for indexing
 * @author Peter Benke <pbenke@allplan.com>
 */
class ModifyStartIndexingViewHelper extends AbstractViewHelper
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

		// Find all patterns like e.g.: <a class="btn ...">...</a> to hide the buttons
		$pattern[] = "#<a class=\"btn (.*)\">(.*)<\/a>#siU";
		// $replace[] = "<a disabled=\"disabled\" class=\"btn $1\">$2</a>";
		$replace[] = "";

		return preg_replace($pattern, $replace, $content);

	}

}