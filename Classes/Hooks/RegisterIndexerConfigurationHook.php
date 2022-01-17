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

		$iconPath = 'EXT:allplan_ke_search_extended/Resources/Public/Icons/';

		// Events (jv_events)
		// =============================================================================================================
		$params['items'][] = [
			'Events (jv_events)',
			'jv_events',
			$iconPath . 'indexer-jv_events.gif'
		];

		// Allplan Lessions (marit_elearning)
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Lessions (marit_elearning)',
			'lessions',
			$iconPath . 'indexer-video-lesson.png'
		];

		// Allplan Training Documentation (marit_elearning)
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Training Documentation (marit_elearning)',
			'documentation',
			$iconPath . 'indexer-pdf-lesson.png'
		];

		// Allplan ContentServe downloads
		// =============================================================================================================
		$params['items'][] = [
			'Allplan ContentServe downloads',
			'contentserve',
			$iconPath . 'indexer-content-downloads.png'
		];

		// Allplan Forum
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Forum',
			'allplanforum',
			$iconPath . 'indexer-forum.png'
		];

		// Allplan Online Help
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Online Help',
			'onlinehelp',
			$iconPath . 'indexer-allplan-help.png'
		];

		// Allplan Support FAQs
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Support FAQs',
			'supportfaq',
			$iconPath . 'indexer-allplan-faq.png'
		];

		// Allplan Shop
		// =============================================================================================================
		$params['items'][] = [
			'Allplan Shop',
			'shop',
			$iconPath . 'indexer-allplan-shop.png'
		];

	}

}