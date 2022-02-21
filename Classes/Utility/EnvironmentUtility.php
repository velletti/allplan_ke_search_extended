<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

class EnvironmentUtility
{

	/**
	 * Returns the server name, either 'www' or 'connect'
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getServerName(): string
	{

		$serverName = 'www';
		if(preg_match('#connect#', $_SERVER['SERVER_NAME'])){
			$serverName = 'connect';
		}

		if(in_array($_SERVER['SERVER_NAME'], ['connect-typo3.allplan.com', 'vm5012934.psmanaged.com'])){
			$serverName = 'connect';
		}

		if(in_array($_SERVER['SERVER_NAME'], ['www-typo3.allplan.com', 'vm5012986.psmanaged.com', 'allplan'])){
			$serverName = 'www';
		}

		return $serverName;

	}

	/**
	 * Checks, if current environment is a dev environment
	 * @return bool
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function isDevEnvironment(): bool
	{
		if(substr($_SERVER['SERVER_NAME'] , -9 , 9 ) == 'ddev.site'){
			return true;
		}

		if($_ENV['TYPO3_CONTEXT'] == 'Development'){
			return true;
		}

		return false;
	}

}