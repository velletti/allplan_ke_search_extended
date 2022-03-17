<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers\Format;

/**
 * AllplanTemplate
 */
use Allplan\AllplanTemplate\Utility\ArrayUtility;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * WriteDateAbbreviationViewHelper
 * Class WriteDateAbbreviationViewHelper
 * @package Allplan\AllplanTemplate\ViewHelpers
 * @author Peter Benke <pbenke@allplan.com>
 */
class WriteDateAbbreviationViewHelper extends AbstractViewHelper{

	/**
	 * Initialize arguments
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function initializeArguments()
	{
		parent::initializeArguments();
		$this->registerArgument('month', 'string', 'Month, 2 numbers, e.g.: 03', true);
	}

	/**
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): string
	{

		$month = ArrayUtility::getValueByKey($this->arguments, 'month');
		return LocalizationUtility::translate('dateFormat.month.abbreviation.' . $month, 'allplan_template');

	}

}