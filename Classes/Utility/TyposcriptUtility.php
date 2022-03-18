<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Php
 */
use Exception;

/**
 * Class TyposcriptUtility
 * @package Allplan\AllplanKeSearchExtended\Utility
 */
class TyposcriptUtility
{

	/**
	 * Loads the typoscript from scratch
	 * @param string|int $pageUid
	 * @param string $extKey
	 * @param mixed $conditions
	 * @param bool $getConstants default=false, will return constants (all or those from an extension) instead of setup
	 * @return array
	 * @throws Exception
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function loadTypoScriptFromScratch($pageUid = 0, string $extKey = '', $conditions = false, bool $getConstants = false): array
	{

		/**
		 * @var ExtendedTemplateService $extendedTemplateService
		 * @var RootlineUtility $rootLineUtility
		 */
		$extendedTemplateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
		$rootLineUtility = GeneralUtility::makeInstance(RootlineUtility::class, (int)$pageUid);

		$rootLine = $rootLineUtility->get();

		$extendedTemplateService->tt_track = 0;

		// To get static files also
		$extendedTemplateService->setProcessExtensionStatics(true);
		$extendedTemplateService->runThroughTemplates($rootLine);
		if($conditions){
            $extendedTemplateService->matchAlternative = $conditions;
        }
		$extendedTemplateService->generateConfig();
        if($getConstants){
            if(!empty($extKey)){
                $typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup_constants['plugin.'][$extKey . '.']);
            }else{
                $typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup_constants);
            }
        } else {
            if(!empty($extKey)){
                $typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup['plugin.'][$extKey . '.']);
            }else{
                $typoScript = self::removeDotsFromTypoScriptArray($extendedTemplateService->setup);
            }
        }

		return $typoScript;

	}

	/**
	 * Removes the dots from a typoscript array
	 * @param $array
	 * @return array
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	private static function removeDotsFromTypoScriptArray($array): array
	{

		$newArray = [];

		if(is_array($array)){

			foreach ($array as $key => $val){

				if (is_array($val)) {

					// Remove last character (dot)
					$newKey = substr($key, 0, -1);
					$newVal = self::removeDotsFromTypoScriptArray($val);

				} else {

					$newKey = $key;
					$newVal = $val;

				}

				$newArray[$newKey] = $newVal;

			}

		}

		return $newArray;

	}

}