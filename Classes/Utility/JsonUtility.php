<?php
namespace Allplan\AllplanKeSearchExtended\Utility;


class JsonUtility
{

	/**
	 * Returns the curl search index of a specified allplan online help
	 * @param string $url
	 * @param string $searchResponse   // send just a part of result if not correct  JSON encoded result
	 * @param mixed $jsonHeader        // maybe we need in the Future a different request/ response type
	 * @param bool $withHeader        // with http 200 status or not ... FALSE is easier to hande the response ...
	 * @param mixed $timeOut         // timeout in seconds
	 * @return string|array
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public static function getJsonFile(
		string $url,
		string $searchResponse='{"pages"',
		$jsonHeader = ['Accept: application/json', 'Content-type:application/json'],
		bool $withHeader = true,
		$timeOut = false
	)
	{

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		if($fp = tmpfile()){
			// with this option, curl output is not stored to error log
			curl_setopt ($ch, CURLOPT_STDERR, $fp);
		}
		curl_setopt($ch,CURLOPT_POST,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); // don't give anything back (sometimes important in TYPO3!)
		curl_setopt($ch,CURLOPT_HEADER, $withHeader);
		curl_setopt($ch,CURLOPT_VERBOSE,true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,0);
		if($timeOut){
			curl_setopt($ch,CURLOPT_TIMEOUT, intval($timeOut));
		}
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
		if($jsonHeader){
			curl_setopt($ch,CURLOPT_HTTPHEADER, $jsonHeader);
		}

		$result = curl_exec($ch);
		curl_close($ch);
		if(!$withHeader){
			return $result;
		}
		$resultArr = explode("\n", $result);

		$httpVal = explode(' ', $resultArr[0]);
		if($httpVal[1] != '200'){
			return ['error', $httpVal[1]];
		}

		for($i=1; $i < count($resultArr); $i++){
			if(is_array(json_decode($resultArr[$i],true))){
				return json_decode($resultArr[$i], true);
			}else{
				if(substr($resultArr[$i],0,8) == $searchResponse){
					return substr($resultArr[$i],14,strlen($resultArr[$i]) - 17);
				}
			}
		}

		if(count($resultArr) > 8){
			return substr($resultArr[9],14,strlen($resultArr[9]) - 17);
		}
		return $resultArr;

	}

}