<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers\Format;

/**
 * AllplanTemplate
 */
use Allplan\AllplanTemplate\Utility\ArrayUtility;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

/**
 * Php
 */
use Closure;

/**
 * Class StripTagsAndReplaceWithSpaceViewHelper
 * @package Allplan\AllplanTemplate\ViewHelpers
 * @author Peter Benke <pbenke@allplan.com>
 */
class StripTagsAndReplaceWithSpaceViewHelper extends AbstractViewHelper implements ViewHelperInterface
{

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 * @var bool
	 */
	protected bool $escapingInterceptorEnabled = false;

	/**
	 * Initialize arguments
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function initializeArguments()
	{
		parent::initializeArguments();
		$this->registerArgument('string', 'string', 'String to cleanup');
	}

	/**
	 * Applies strip_tags() on the specified value
	 * @param array $arguments
	 * @param Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
	{

		$string = ArrayUtility::getValueByKey($arguments, 'string');
		if(empty($string)){
			$string = $renderChildrenClosure();
		}

		if (!is_string($string)) {
			return $string;
		}

		// Add a space before any tag
		$string = str_replace('<', ' <', $string);
		return strip_tags($string);

	}

}
