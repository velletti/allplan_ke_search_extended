<?php

if (!defined ("TYPO3_MODE")) die ('Access denied');

// custom indexer
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'][]	= 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

// index custom content
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

// adding custom field(s) for indexing
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

// manipulate content from these custom field(s)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

// manipulate the pagebrowser
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['pagebrowseAdditionalMarker'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchPageBrowserHook' ;

// add servername to link and as "Creator" filter for easier sync between allplan and Connect
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchRegisterAdditionalFieldsHook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchModifyContentIndexEntryHook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchModifyPagesIndexEntryHook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyAddressIndexEntry'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchModifyAddressIndexEntryHook';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchModifyExtNewsIndexEntryHook';





// register custom filter renderer hook
# $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customFilterRenderer'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

// register additional markers for search results
// (displays images of tt_news and news in this example)
# $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchIndexerHook';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchModifiySearchWordsHook' ;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['cleanup'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchCleanupHook' ;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'][] = 'Allplan\\AllplanKeSearchExtended\\Hooks\\KeSearchInitialsHook' ;


// add scheduler task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Allplan\AllplanKeSearchExtended\Task\AllplanKesearchIndexerTask'] = array(
    'extension'        => 'ke_search_extended',
    'title' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskTitle',
    'description' => 'LLL:EXT:allplan_ke_search_extended/Resources/Private/Language/locallang_tasks.xlf:indexerTaskDescription',
    'additionalFields' => 'Allplan\AllplanKeSearchExtended\Task\AllplanKesearchIndexerTaskAdditionalFieldProvider'
);