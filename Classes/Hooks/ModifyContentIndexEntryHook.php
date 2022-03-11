<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

class ModifyContentIndexEntryHook
{

	/**
	 * Modifications of the indexed data, e.g. tags
	 * (used in the standard tt_content indexer from ke_search)
	 * @param string $header
	 * @param array $row
	 * @param string $tags
	 * @param int|string $uid
	 * @param array $additionalFields
	 * @param array $indexerConfig
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyContentIndexEntry(string $header, array &$row, string $tags, $uid, array &$additionalFields, array &$indexerConfig)
	{

		// Todo check this

		$serverName = EnvironmentUtility::getServerName();

		$additionalFields['servername'] = $serverName;
		$row['pid'] =  $serverName . '/index.php?id=' . $row['pid'] . '&L=' . $row['sys_language_uid'];

	}

}