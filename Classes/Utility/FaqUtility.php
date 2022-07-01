<?php
namespace Allplan\AllplanKeSearchExtended\Utility;


use Allplan\Library\Salesforce\Model\Knowledgebase;

class FaqUtility
{

    /**
     * Forum indexer storage pids
     */
    const FAQ_INDEXER_STORAGE_PID_EN = 5027;
    const FAQ_INDEXER_STORAGE_PID_DACH = 5025;
    const FAQ_INDEXER_STORAGE_PID_FR = 5026;

    const FAQ_INDEXER_TYPE_DEFAULT  = "supportfaq" ;
    const FAQ_INDEXER_TYPE_BETA     = "supportfaqbeta" ;
    const FAQ_INDEXER_TYPE_SP     = "supportfaqsp" ;
    const FAQ_INDEXER_TYPE_NEM     = "supportfaqnem" ;
    const FAQ_INDEXER_TYPE_LOCKED     = "supportfaqlocked" ;


	/**
	 * Get the content of a pdf file, given by a sys_file.uid
	 * @param Knowledgebase|null  $permission
	 * @return array
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 */
	public static function getTagTypeAndUserGroupByPermission( ?Knowledgebase $recordObj  ):array
	{
        if( ! $recordObj) {
            return [] ;
        }
        if( $recordObj->getDeprecated() || $recordObj->getPublishStatus() != "Online" ) {
            $single['type'] = self::FAQ_INDEXER_TYPE_LOCKED;
            $single['feGroup'] = '6';
            if( $recordObj->getDeprecated() ) {
                $single['tags'] = ',#deprecated#' ;
            }
            if( $recordObj->getPublishStatus() != "Online" ) {
                $single['tags'] .= ',#notPublished#' ;
            }
            return $single;
        }
        $permission = $recordObj->getPermission() ;
        $versions   = $recordObj->getSoftwareVersions() ;



        switch ( strtolower( $permission )) {
            case "everyone":
            case "everybody":
                $single['type'] = self::FAQ_INDEXER_TYPE_DEFAULT ;
                $single['feGroup'] = '';
                $single['tags'] = ",#allUserAccess#,#customerAccess#";
                break;

            case "beta tester":
            case "betatester":
                $single['type'] =  self::FAQ_INDEXER_TYPE_BETA ;
                $single['feGroup'] = '38,7,4';
                $single['tags'] = '' ;
                break;

            case "portal user":
            case "portaluser":
            case "CustomerWithSPContract":
            case "customerwithspcontract":
                $single['type'] = self::FAQ_INDEXER_TYPE_SP;
                $single['feGroup'] = '38,7,4,3';
                $single['tags'] = ",#customerAccess#";
                break;

            case "nemetschek only":
            case "notvisibleforcustomersallusersinsalesforce":
            case "nemetschekonly":
                $single['type'] = self::FAQ_INDEXER_TYPE_NEM;
                $single['feGroup'] = '38,7';
                $single['tags'] = '' ;
                break;

            case "InternalSupportOnly":
            default:
                $single['type'] = self::FAQ_INDEXER_TYPE_LOCKED;
                $single['feGroup'] = '38';
                $single['tags'] = '' ;
                break;
        }
        if ( is_string( $versions )) {
            $versions = strtolower( $versions ) ;
            for ( $ver =  8 ; $ver < 17 ; $ver++ ) {
                if ( stripos( $versions , "allplan | " . (string)$ver ) > 0 ) {
                    $single['tags'] .= ',#allplan' . (string)$ver . "#" ;
                }
            }
            for ( $year =  2001 ; $year < date("Y") + 2 ; $year++ ) {
                if ( strpos( $versions , "allplan | " . (string)$year ) > 0 ) {
                    $single['tags'] .= ',#allplan' . (string)$year . "#" ;
                } else if ( strpos( $versions , "allplan " . (string)$year ) > 0 ) {
                    $single['tags'] .= ',#allplan' . (string)$year . "#" ;
                } else if ( strpos( $versions , "allplan bridge" .(string)$year ) > 0 ) {
                    $single['tags'] .= ',#allplanBridge' . (string)$year . "#" ;
                }
            }

        }
		return $single ;

	}

    /**
     * Get the content of a pdf file, given by a sys_file.uid
     * @param string|null  $lang
     * @return array
     * @author Jörg Velletti <jvelletti@allplan.com>
     */
    public static function getLangAndPid( ?string $lang ):array
    {
        switch( strtolower( $lang ) ) {
            case "de":
                return array("lang" => 1 , "indexlang" => -1 , "pid" => self::FAQ_INDEXER_STORAGE_PID_DACH ) ;
            case "it":
                return array("lang" => 2 , "indexlang" => 2 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            case "cz":
                return array("lang" => 3 , "indexlang" => 3 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            case "fr":
                return array("lang" => 4 , "indexlang" => 4 , "pid" => self::FAQ_INDEXER_STORAGE_PID_FR  ) ;
            case "es":
                return array("lang" => 18 , "indexlang" => 18 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            case "ru":
                return array("lang" => 14 , "indexlang" => 14 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            default:
                return array("lang" => 0 , "indexlang" => 0 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
        }
    }

    /**
     * Get the content of a pdf file, given by a sys_file.uid
     * @param Knowledgebase|null  $recordObj
     * @param string  $defaultTag
     * @return array
     * @author Jörg Velletti <jvelletti@allplan.com>
     */
    public static function getRecordAsArray( ?Knowledgebase  $recordObj  , string  $defaultTag  ):array
    {

        $tagTypeAndFeGroup = self::getTagTypeAndUserGroupByPermission($recordObj ) ;
        $syslangAndPid = self::getLangAndPid($recordObj->getLanguage() ) ;

        $record = [];
        $record['pid'] = $syslangAndPid['pid'] ;

        $record['title'] = $recordObj->getTitle()  ;
        $record['type'] = $tagTypeAndFeGroup['type']  ;
        // Todo generate correct DOMAIN
        $record['targetPid'] = "ToDo ... DOMAIN .... /knowledge1/s/article/" . $recordObj->getUrlName(). "?language=" . $recordObj->getLanguage();

        $record['content'] = "Permission: " . $recordObj->getPermission()  ;
        $record['content'] .= "Software: " . $recordObj->getSoftwareVersions()  ;
        $record['content'] .= "Status: " . $recordObj->getPublishStatus()  ;
        $record['content'] .= $recordObj->getText()  ;
        // ToDo JVE: Add Download Links after Text Content.

        $record['tags']     = $defaultTag . $tagTypeAndFeGroup['tags']  ;
        $record['params']   = "_blank" ;
        $record['directory'] =   $recordObj->getDirectory() ;
        $record['language'] = $syslangAndPid['indexlang'] ;
        $record['abstract']   = $recordObj->getSoftwareVersions() . "\n" . strip_tags( $recordObj->getText() ) ;
        $record['startTime']   =  $recordObj->getLastPublishedDate()  ;
        $record['endTime']   = $recordObj->getDeprecated() ? (time() - 3600) : 0 ;

        $record['feGroup']   = $tagTypeAndFeGroup['feGroup'] ;

        $record['additionalFields']  = [
            'orig_uid' => intval( $recordObj->getArticleNumber() ) ,
            // We take the column sortdate to store the original tstamp of the post
            'sortdate' => intval( $recordObj->getLastPublishedDate() ),
            'tx_allplan_ke_search_extended_server_name' => EnvironmentUtility::getServerName(),
        ];
        return $record ;
    }

    public static function getVersionTagsFromSting() {

    }

}