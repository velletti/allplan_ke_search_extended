<?php
defined('TYPO3_MODE') or die();

$_EXTKEY = 'allplan_ke_search_extended';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
	$_EXTKEY,
	'Configuration/TypoScript',
	'Allplan: ke_search extended'
);
