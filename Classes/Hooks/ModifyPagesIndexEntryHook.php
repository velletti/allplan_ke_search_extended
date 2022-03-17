<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Types\Page;

class ModifyPagesIndexEntryHook
{

	/**
	 * Modifies the page data just before it will be saved into database
	 * @param int|string $uid
	 * @param array $pageContent
	 * @param string $tags
	 * @param array $cachedPageRecords
	 * @param array $additionalFields
	 * @param array $indexerConfig
	 * @param array $indexEntryDefaultValues
	 * @param Page $pagesThis
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyPagesIndexEntry(
		$uid,
		array $pageContent,
		string $tags,
		array $cachedPageRecords,
		array &$additionalFields,
		array $indexerConfig,
		array $indexEntryDefaultValues,
		Page &$pagesThis
	)
	{
		$additionalFields['tx_allplan_ke_search_extended_server_name'] = EnvironmentUtility::getServerName();
	}

}