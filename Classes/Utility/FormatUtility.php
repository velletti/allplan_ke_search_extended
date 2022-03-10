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
	 * @see https://www.php.net/manual/en/regexp.reference.escape.php
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function cleanStringForIndex(string $string): string
	{

		$pattern[] = "#\\\\t#"; // \t
		$replace[] = ' ';

		// $pattern[] = '#\s+#'; // Multiple spaces, tabs and the rest of linebreaks => to spaces
		$pattern[] = '#\h+#'; // Multiple spaces, tabs => to spaces
		$replace[] = ' ';

		// Forum stuff like [...]Text[/...]
		$pattern[] = "/\[(.*)\]/siU";
		$replace[] = '';

		$string = preg_replace($pattern, $replace, $string);

		$string = strip_tags($string);
		$string = trim($string, "\""); // trim " endings

		return trim($string);

	}

	/**
	 * Build the content string for tx_kesearch_index.content by multiple parts
	 * @param array $contentParts
	 * @return string
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function buildContentForIndex(array $contentParts): string
	{

		$cleanContentParts = [];
		foreach($contentParts as $contentPart){
			if(!empty($contentPart)){
				$cleanContentParts[] = self::cleanStringForIndex($contentPart);
			}
		}

		return implode(PHP_EOL, $cleanContentParts);

	}

	/**
	 * Get the number of seconds by a given number of days
	 * @param int|string|null $days
	 * @return int|null
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getSecondsByDays($days)
	{
		$days = (int)$days;
		if(!($days > 0)){
			return null;
		}
		return (int)$days * 24 * 60 * 60;
	}

}