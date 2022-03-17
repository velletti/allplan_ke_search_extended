<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;

class ModifyContentIndexEntryHook
{

	/**
	 * Modifies the content data just before it will be saved into database
	 * tx_kesearch_index.type = 'content'
	 * Not in use at the moment
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

		$additionalFields['tx_allplan_ke_search_extended_server_name'] = EnvironmentUtility::getServerName();
		$row['pid'] =  EnvironmentUtility::getServerProtocolAndHost() . '/?id=' . $row['pid'] . '&L=' . $row['sys_language_uid'];

	}

}