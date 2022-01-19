<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

/**
 * GeneralUtility
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlexFormUtility
{

	/**
	 * Returns additional content from pi_flexform-field
	 * @param array $ttContentRow
	 * @return string
	 */
	public static function getAdditionalContentFromFlexform(array $ttContentRow): string
	{

		$contentArray = [];

		// Predefine array to avoid php warnings
		$flexFormDataDefault = [
			'data' =>[
				'main' => [
					'lDEF' => [
						'settings.content' => [
							'vDEF'
						],
						'settings.contentLeft' => [
							'vDEF'
						],
						'settings.contentRight' => [
							'vDEF'
						],
					]
				]
			]
		];

		if(!isset($ttContentRow['pi_flexform'])){
			return '';
		}

		$flexFormDataThis = GeneralUtility::xml2array($ttContentRow['pi_flexform']);
		if(is_array($flexFormDataThis)){
			$flexFormData = array_merge($flexFormDataThis, $flexFormDataDefault);
		} else {
			$flexFormData = $flexFormDataDefault;
		}

		if(!isset($flexFormData['data']['main']['lDEF']['settings.content']['vDEF'])){
			return '';
		}

		// Define the fields in the flexforms, which should be indexed
		$cTypes = [

			// Headers
			// =========================================================================================================
			'allplantemplate_ce_headerHome' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerProduct' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerDefault' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerSkew' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerYouTubeVideo' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Sliders
			// =========================================================================================================
			'allplantemplate_ce_sliderDefaultElement' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_sliderReferenceElement' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Teasers & reference & product
			// =========================================================================================================
			'allplantemplate_ce_jumbotronTeaser' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_teaserSkew' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.contentLeft']['vDEF'],
					$flexFormData['data']['main']['lDEF']['settings.contentRight']['vDEF']
				]
			],
			'allplantemplate_ce_reference' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_product' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Multi-column content
			// =========================================================================================================
			'allplantemplate_ce_colElementTheme' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_colElementAssistingSolution' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_colElementInfobox' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Todo: Here we have to find an elegant way, because the fields are arrays

			// 'allplantemplate_ce_accordion' => [
			// ],

			// 'allplantemplate_ce_functionMatrix' => [
			// ],

		];

		// If CType is configured
		if(array_key_exists($ttContentRow['CType'], $cTypes)){

			foreach($cTypes[$ttContentRow['CType']]['fields'] as $content){
				$contentArray[] = strip_tags(html_entity_decode($content));
			}

		}

		return implode(' ', $contentArray);

	}

}