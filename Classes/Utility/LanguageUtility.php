<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

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

		if (class_exists(Context::class)) {

			try{
				/** @var LanguageAspect $languageAspect */
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

	/**
	 * Returns an array of TYPO3 sys_language records (from config.yaml):
	 * [
	 *		'uid' => 'title',
	 * 		'uid' => 'title',
	 * 		'uid' => 'title',
	 * ]
	 * @return array|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getSysLanguageRecords(): ?array
	{
		$languageRecords = [];
		$siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
		$sites = $siteFinder->getAllSites();
		if(is_array($sites)){
			foreach ($sites as $site){
				if($site && is_array($site->getConfiguration()['languages'])){
					foreach($site->getConfiguration()['languages'] as $language){
						$uid = $language['languageId'];
						$languageRecords[$uid] = $language['title'];
					}
				}
			}
		}

		if (!count($languageRecords) > 0){
			return null;
		}

		return $languageRecords;

	}

	/**
	 * Translate a given string
	 * @param string $key
	 * @param string $view
	 * @return string|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function translate(string $key, string $view = 'backend'): ?string
	{

		$prefix = 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_be.xlf:';
		if($view != 'backend'){
			$prefix = '';
		}

		return LocalizationUtility::translate($prefix . $key, 'allplan_ke_search_extended');
	}

}