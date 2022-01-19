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

}