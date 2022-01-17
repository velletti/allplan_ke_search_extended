<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

class EnvironmentUtility
{

	/**
	 * Returns the server name
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getServerName(): string
	{

		$serverName = $_SERVER['SERVER_NAME'];

		if(in_array($serverName, ['connect-typo3.allplan.com', 'vm5012934.psmanaged.com', 'connect'])){
			$serverName = 'connect.allplan.com';
		}

		if(in_array($serverName, ['www-typo3.allplan.com', 'vm5012986.psmanaged.com', 'allplan', 'www'])){
			$serverName = 'www.allplan.com';
		}

		return $serverName;

	}

}