<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageUtility
{

	/**
	 * Get the current sys_language_uid
	 * @return int
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getSysLanguageUid(): int
	{

		if (class_exists(\TYPO3\CMS\Core\Context\Context::class)) {

			try{
				$languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
				if (GeneralUtility::_GP('L') && intval(GeneralUtility::_GP('L') > 0)){
					return intval(GeneralUtility::_GP('L'));
				}
				return $languageAspect->getId();
			}catch(AspectNotFoundException $e){
				return 0;
			}

		} else {
			return $GLOBALS['TSFE']->sys_language_uid;
		}

	}

}