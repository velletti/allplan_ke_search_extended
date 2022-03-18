<?php
defined('TYPO3_MODE') or die();

/**
 * Todo:
 * - Sync script: Problem server_name only "connect"...?
 * - Change git repository to git.allplan.com
 * - Configure ke_search, that we get email notices
 * - Search in allplan_template / connect_template for "Todo allplan_ke_search_extended"
 * - Remove templates from allplan_template / connect_template, when new version is online
 * - Include static typoscript from ke_search
 * - Include static typoscript from this extension after typoscript from ke_search
 */

$boot = function(){

	/**
	 * ke_search hooks
	 * =================================================================================================================
	 */

	// Add custom indexers to the TCA of the indexer-configuration
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\RegisterIndexerConfigurationHook::class;

	// Index custom content
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\CustomIndexerHook::class;

	// Modify the page content fields (own columns in tt_content)
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyPageContentFieldsHook::class;

	// Modify content from these custom field(s)
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyContentFromContentElementHook::class;

	// Extend the indexer table with own columns
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\RegisterAdditionalFieldsHook::class;

	// Modifies the indexed data, e.g. tags
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyContentIndexEntryHook::class;

	// Modifies the page data just before it will be saved into database
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyPagesIndexEntryHook::class;

	// Modifies the tt_address data just before it will be saved into database
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyAddressIndexEntry'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyAddressIndexEntryHook::class;

	// Modifies the search filters
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilters'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyFiltersHook::class;

	// Modifies all returned values of the ke_search flexform
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFlexFormData'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyFlexFormDataHook::class;

	// Modifies the news data just before it will be saved into database
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifyExtNewsIndexEntryHook::class;

	// Hook for additional markers in result row
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\AdditionalResultMarkerHook::class;

	// Modifies the last part of query building
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['getQueryParts'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\GetQueryPartsHook::class;

	// Hook to add a custom types
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['GenericRepositoryTablename'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\GenericRepositoryTablenameHook::class;

	// Modifies the search input
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\ModifySearchWordsHook::class;

	// Cleanup for counting and deleting old index records
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['cleanup'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\CleanupHook::class;

	// Change any variable while initializing the plugin
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'][] =
		\Allplan\AllplanKeSearchExtended\Hooks\InitialsHook::class;

	/**
	 * Scheduler task
	 * =================================================================================================================
	 */
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Allplan\AllplanKeSearchExtended\Task\IndexerTask::class] = [
		'extension' => 'allplan_ke_search_extended',
		'title' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_be.xlf:task.indexerTask.title',
		'description' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_be.xlf:task.indexerTask.description',
		'additionalFields' => 'Allplan\AllplanKeSearchExtended\Task\IndexerTaskAdditionalFieldProvider'
	];

};

$boot();
unset($boot);
