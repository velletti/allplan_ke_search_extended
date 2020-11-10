<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;


class KeSearchAdditionalResultMarkerHook{

    /**
     * @param array $markerArray
     * @param $row
     * @param object $pibase
     */
	public function additionalResultMarker(&$tempMarkerArray, $row , $pibase ){
		 // print_r($tempMarkerArray);
		 // die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );

		if(is_array($tempMarkerArray) && is_array($row)){

            $tempMarkerArray['abstract'] = $row['abstract'] ;

            if( substr( $row['type'], 0 , 10) == "supportfaq") {
                $tempMarkerArray['faq'] = json_decode( $row['content'] , true)  ;
                $tempMarkerArray['top10'] = $row['top10'] ;
                // We need to Overwrite the Teaser again as using the Json from FAQ as Abstract Teaser is not usefull
                $tempMarkerArray['teaser'] = $pibase->searchResult->buildTeaserContent( $row['abstract'] ) ;
            }
		}
	}

}