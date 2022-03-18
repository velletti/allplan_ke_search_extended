<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

class ArrayUtility
{

	/**
	 * Gets a value from an associative array by the given key
	 * If not set returns null
	 * @param array|null $arguments
	 * @param string $key
	 * @return mixed|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getValueByKey(?array $arguments, string $key)
	{

		if(empty($arguments) || !is_array($arguments)){
			return null;
		}

		if(isset($arguments[$key])){
			return $arguments[$key];
		}

		return null;

	}

}