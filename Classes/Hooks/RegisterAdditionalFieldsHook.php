<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

class RegisterAdditionalFieldsHook
{

	/**
	 * Extend the indexer table with own columns
	 * @param array $additionalFields
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function registerAdditionalFields(array &$additionalFields)
	{
		$additionalFields[] = 'tx_allplan_ke_search_extended_server_name';
		$additionalFields[] = 'tx_allplan_ke_search_extended_top_10';
	}

}