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

}