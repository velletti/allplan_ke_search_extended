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
	 * @param Pluginbase $pluginBase
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function additionalResultMarker(array &$tempMarkerArray, array $row, Pluginbase $pluginBase)
	{
        // print_r($row);
        // die(" __FILE__" . __FILE__ . " __LINE__" . __LINE__ );

        if(is_array($tempMarkerArray) && is_array($row)){



            if( substr( $row['type'], 0 , 10) == "supportfaq") {
                $tempMarkerArray['abstract'] = $row['abstract'] ;

                $tempMarkerArray['faq'] = json_decode( $row['content'] , true , 512 , JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE ) ;
              //  $tempMarkerArray['faq'] = json_decode( $row['content'] ) ;
              //    $tempMarkerArray['sffaq'] = $GLOBALS['']
                if( !is_array( $tempMarkerArray['faq'] ) ){
                    $tempMarkerArray['faq'] = array( "additionalFields" => ['orig_uid' => $row['orig_uid']  ] , "title" => "--- outdated index !--- " , "outdated" => true ) ;
                }
                // ToDo : JVE 8.7.2022 : check if replace of of \\ nem is still needed ..
                //   $tempMarkerArray['faq']['STRTEXT'] = str_replace( "\\ nem" , "\\nem", $tempMarkerArray['faq']['STRTEXT'] ) ;
                $tempMarkerArray['tx_allplan_ke_search_extended_top_10'] = $row['tx_allplan_ke_search_extended_top_10'] ;
                // We need to Overwrite the Teaser again as using the Json from FAQ as Abstract Teaser is not usefull
                $tempMarkerArray['teaser'] = $pluginBase->searchResult->buildTeaserContent( $row['abstract'] ) ;
            }
        }

	}

}