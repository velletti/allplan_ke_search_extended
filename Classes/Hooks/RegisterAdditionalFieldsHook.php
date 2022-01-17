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
	public function registerAdditionalFields(&$additionalFields)
	{
		$additionalFields[] = 'servername';
		$additionalFields[] = 'top10';
	}

}