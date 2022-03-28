<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Indexer\Types\File;

class ModifyFileIndexEntryHook
{

	/**
	 * Modifies the file index entries just before it will be saved into database
	 * tx_kesearch_index.type = 'file:pdf'
	 * @param string $file
	 * @param string $content
	 * @param array $additionalFields
	 * @param array $indexRecordValues
	 * @param File $fileObject
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyFileIndexEntry(
		string $file,
		string $content,
		array &$additionalFields,
		array $indexRecordValues,
		File $fileObject
	) {
		$additionalFields['tx_allplan_ke_search_extended_server_name'] = EnvironmentUtility::getServerName();
	}

}