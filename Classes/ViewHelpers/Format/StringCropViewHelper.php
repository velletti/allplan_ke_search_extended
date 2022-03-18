<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers\Format;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\ArrayUtility;

/**
 * TYPO3Fluid
 */
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class UrlCropViewHelper
 * Crops an url like e.g.:
 * https://wwwv10.allplan.com.ddev.site/de/termine/alle-termine/events-detail/event/event/show/allplan-advanced-engineering-2022-10-19/
 * =>
 * https://wwwv10.allplan.com.ddev.site/de/termine/alle-termine/events-...d-engineering-2022-10-19/
 * @author Peter Benke <pbenke@allplan.com>
 */
class StringCropViewHelper extends AbstractViewHelper
{

	/**
	 * Initialize arguments
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function initializeArguments()
	{
		parent::initializeArguments();
		$this->registerArgument('string', 'string', 'Url to crop', true);
		$this->registerArgument('strLength', 'string', 'String length', false, '50');
		$this->registerArgument('spaceFill', 'string', 'String between the start and the end', false, '...');
	}

	/**
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function render(): string
	{

		$string = ArrayUtility::getValueByKey($this->arguments, 'string');
		$spaceFill = ArrayUtility::getValueByKey($this->arguments, 'spaceFill');
		$strLength = intval(ArrayUtility::getValueByKey($this->arguments, 'strLength')) - strlen($spaceFill);

		// If the string is long enough to crop => get the new, cropped url
		if(strlen($string) > ($strLength)) {

			$strPartLength = $strLength/2;

			$stringCropped = substr($string, 0, (intval($strPartLength) + 1));
			$stringCropped .= $spaceFill;
			$stringCropped .= substr($string, (strlen($string) - intval($strPartLength)), intval($strPartLength));
			$string = $stringCropped;
		}

		return $string;

	}

}
