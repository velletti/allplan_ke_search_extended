<?php
namespace Allplan\AllplanKeSearchExtended\Utility;

class FormatUtility
{

	/**
	 * Formats seconds into hh:mm:ss-format
	 * @param $seconds
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function formatSeconds($seconds): string
	{
		$time = round($seconds);
		return sprintf('%02d:%02d:%02d', ($time/3600),($time/60%60), $time%60);
	}

	/**
	 * Format file size from bytes to human-readable format
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function formatFilesize($size, $decimals = 0): string
	{
		$sizes = [' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
		if ($size == 0){
			return ('n/a');
		} else {
			return (round($size / pow(1024, ($i = floor(log($size, 1024)))), $decimals) . $sizes[$i]);
		}
	}

	/**
	 * Cleans a given string for an entry in table tx_kesearch_index
	 * @param string $string
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function cleanStringForIndex(string $string): string
	{

		$pattern[] = '#@#';
		$replace[] = ' ';

		$pattern[] = "#\\\\t#"; // \t
		$replace[] = ' ';

		$pattern[] = '#\s+#'; // Multiple spaces, tabs and the rest of linebreaks => to spaces
		$replace[] = ' ';

		$string = preg_replace($pattern, $replace, $string);

		$string = strip_tags($string);
		$string = nl2br($string);
		$string = trim($string, "\""); // trim " endings
		return trim($string);

	}

}