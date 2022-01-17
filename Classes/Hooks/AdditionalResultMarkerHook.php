<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

/**
 * KeSearch
 */
use Tpwd\KeSearch\Lib\Pluginbase;

class AdditionalResultMarkerHook
{

	/**
	 * Hook for additional markers in result row
	 * @param array $tempMarkerArray
	 * @param array $row
	 * @param Pluginbase $pibase
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function additionalResultMarker(array &$tempMarkerArray, array $row, Pluginbase $pibase)
	{

		$tempMarkerArray['abstract'] = $row['abstract'];

		// If type is support FAQ
		if(substr($row['type'],0,10) == 'supportfaq'){

			$tempMarkerArray['faq'] = json_decode($row['content'],true,512,JSON_INVALID_UTF8_IGNORE|JSON_INVALID_UTF8_SUBSTITUTE);

			// If $tempMarkerArray['faq'] is not an array yet => add document-id and understandable subject to it
			if(!is_array($tempMarkerArray['faq'])){
				$docId = substr($row['content'],14,strpos($row['content'],'STRBEARBEITUNGS') -17);
				$tempMarkerArray['faq'] = ['STRDOK_ID' => $docId , 'STRSUBJECT' => '--- outdated index !--- ' , 'outdated' => true];
			}

			// Remove space before 'nem' at the beginning of a line
			$tempMarkerArray['faq']['STRTEXT'] = str_replace('\\ nem' , '\\nem', $tempMarkerArray['faq']['STRTEXT']);

			// Add the top 10
			$tempMarkerArray['top10'] = $row['top10'];

			// We need to overwrite the teaser again, because the json from FAQ as abstract teaser is not useful
			$tempMarkerArray['teaser'] = $pibase->searchResult->buildTeaserContent($row['abstract']);

		}
	}

}