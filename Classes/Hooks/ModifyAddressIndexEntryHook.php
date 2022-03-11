<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class ModifyAddressIndexEntryHook
{

	/**
	 * Modifies the tt_address data just before it will be saved into database
	 * @param string $title
	 * @param string $abstract
	 * @param string $fullContent
	 * @param string $params
	 * @param string $tagContent
	 * @param array $addressRow
	 * @param array $additionalFields
	 * @param array $indexerConfig
	 * @param array $customFields
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function modifyAddressIndexEntry(
		string $title,
		string $abstract,
		string $fullContent,
		string $params,
		string $tagContent,
		array $addressRow,
		array &$additionalFields,
		array $indexerConfig,
		array $customFields
	) {
		// Not in use at the moment
	}

}