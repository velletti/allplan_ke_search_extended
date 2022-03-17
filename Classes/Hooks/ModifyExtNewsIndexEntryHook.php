<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Types\News;

class ModifyExtNewsIndexEntryHook
{

	/**
	 * Modifies the news data just before it will be saved into database
	 * @param string $title
	 * @param string $abstract
	 * @param string $fullContent
	 * @param string $params
	 * @param string $tags
	 * @param array $newsRecord
	 * @param array $additionalFields
	 * @param array $indexerConfig
	 * @param array $categoryData
	 * @param News $news
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyExtNewsIndexEntry(
		string $title,
		string $abstract,
		string $fullContent,
		string $params,
		string $tags,
		array $newsRecord,
		array &$additionalFields,
		array $indexerConfig,
		array $categoryData,
		News $news
	) {
		$additionalFields['tx_allplan_ke_search_extended_server_name'] = EnvironmentUtility::getServerName();
	}

}