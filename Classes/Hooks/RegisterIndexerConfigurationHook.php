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

		// Todo: Contentserve, since the new extension is online
		// Todo: Faq, since migrated to Salesforce
		// Todo: Shop, since online again

		$iconPath = 'EXT:allplan_ke_search_extended/Resources/Public/Icons/IndexerConfiguration/';

		/**
		 * Www
		 * =============================================================================================================
		 */

		// Events (EXT:jv_events)
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Events (EXT:jv_events)',
			'jv_events',
			$iconPath . 'Www/jv-events.gif'
		];


		/**
		 * Connect
		 * =============================================================================================================
		 */

		// Elearning lessons (videos) (EXT:marit_elearning)
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Elearning lessons (videos) (EXT:marit_elearning)',
			'marit_elearning_lessons',
			$iconPath . 'Connect/marit-elearning-lessons.png'
		];

		// Elearning documents (EXT:marit_elearning)
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Elearning documents (EXT:marit_elearning)',
			'marit_elearning_documents',
			$iconPath . 'Connect/marit-elearning-documents.png'
		];

		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Forum (EXT:mm_forum)',
			'mm_forum',
			$iconPath . 'Connect/mm-forum.png'
		];


		/**
		 * Miscellaneous
		 * =============================================================================================================
		 */

		// Allplan Online Help
		// -------------------------------------------------------------------------------------------------------------
		$params['items'][] = [
			'Allplan Online Help',
			'allplan_online_help',
			$iconPath . 'Miscellaneous/allplan-online-help.png'
		];

	}

}