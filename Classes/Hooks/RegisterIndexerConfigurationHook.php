<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class RegisterIndexerConfigurationHook
{

	/**
	 * Add custom indexers to the TCA of the indexer-configuration
	 * @param array $params
	 * @param mixed $pObj
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function registerIndexerConfiguration(array &$params, $pObj)
	{

		// Todo: check spelling / translate the titles

		$iconPath = 'EXT:allplan_ke_search_extended/Resources/Public/Icons/IndexerConfiguration/';

		/**
		 * Www
		 * =============================================================================================================
		 */
		$params['items'][] = [
			'Events (EXT:jv_events)',
			'jv_events',
			$iconPath . 'Www/jv-events.gif'
		];


		/**
		 * Connect
		 * =============================================================================================================
		 */
		// Todo: Elearning videos (EXT:marit_elearning) => cleanup is still missing
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Elearning lessons (videos) (EXT:marit_elearning)',
			'marit_elearning_lessons',
			$iconPath . 'Connect/marit-elearning-lessons.png'
		];

		// Todo: Elearning documents (EXT:marit_elearning)
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Elearning Documentations (EXT:marit_elearning)',
			'marit_elearning_documents',
			$iconPath . 'Connect/marit-elearning-documents.png'
		];

		// Todo: New indexer... Downloads (EXT:nemjv_getcontent)
		// -------------------------------------------------------------------------------------------------------------
		// $params['items'][] = [
		// 	'Allplan ContentServe downloads',
		// 	'contentserve',
		// 	$iconPath . 'indexer-content-downloads.png'
		// ];
		/*
		$params['items'][] = [
			'Downloads (EXT:nemjv_getcontent)',
			'nemjv_getcontent',
			$iconPath . 'Connect/nemjv-getcontent.png'
		];
		*/

		// Todo: Forum (EXT:mm_forum)
		// -------------------------------------------------------------------------------------------------------------
		/*
		$params['items'][] = [
			'Forum (EXT:mm_forum)',
			'mm_forum',
			$iconPath . 'Connect/mm-forum.png'
		];
		*/


		/**
		 * Miscellaneous
		 * =============================================================================================================
		 */

		// Todo: Allplan FAQ
		// -------------------------------------------------------------------------------------------------------------
		/*
		$params['items'][] = [
			'Allplan FAQ',
			'allplan_faq',
			$iconPath . 'Miscellaneous/allplan-faq.png'
		];
		*/

		// Allplan Online Help
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Allplan Online Help',
			'allplan_online_help',
			$iconPath . 'Miscellaneous/allplan-online-help.png'
		];

		// Todo: Allplan Shop
		// -------------------------------------------------------------------------------------------------------------
		/*
		$params['items'][] = [
			'Allplan Shop',
			'allplan_shop',
			$iconPath . 'Miscellaneous/allplan-shop.png'
		];
		*/

	}

}