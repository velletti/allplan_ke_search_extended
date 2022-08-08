<?php
namespace Allplan\AllplanKeSearchExtended\Utility;


use Allplan\Library\Salesforce\Model\Knowledgebase;
use Tpwd\KeSearch\Lib\Db;
use TYPO3\CMS\Core\Database\Connection;

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
            case "internalsupportonly":
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
                return array("lang" => 1 , "langiso2" => $lang , "indexlang" => -1 , "pid" => self::FAQ_INDEXER_STORAGE_PID_DACH ) ;
            case "fr":
                return array("lang" => 4 , "langiso2" => $lang ,"indexlang" => 4 , "pid" => self::FAQ_INDEXER_STORAGE_PID_FR  ) ;
            case "it":
                return array("lang" => 2 , "langiso2" => $lang , "indexlang" => 2 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            case "cs":
            case "cz":
                return array("lang" => 3 ,"langiso2" => "cz" , "indexlang" => 3 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;

            case "es":
                return array("lang" => 18 , "langiso2" => $lang ,"indexlang" => 18 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            case "ru":
                return array("lang" => 14 , "langiso2" => $lang ,"indexlang" => 14 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
            default:
                return array("lang" => 0 , "langiso2" => "en" ,"indexlang" => 0 , "pid" => self::FAQ_INDEXER_STORAGE_PID_EN  ) ;
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
        $record['sfid'] = $recordObj->getId()  ;

        $record['title'] = $recordObj->getTitle()  ;
        $record['type'] = $tagTypeAndFeGroup['type']  ;
        $record['targetPid'] = "https://connect.allplan.com/" . $syslangAndPid['langiso2'] . "/faqid/sc-" . $recordObj->getArticleNumber() . ".html";

        $record['directory'] =   $recordObj->getDirectory() ;
        $record['legacyId'] =   $recordObj->getBisherigeID() ;
        $record['language'] = $syslangAndPid['indexlang'] ;
        $record['servicecloud_lang'] = $recordObj->getLanguage() ;
        $record['abstract']   = substr( strip_tags( $recordObj->getText() ) , 0 , 200 ) ;
        $record['startTime']   = max( $recordObj->getLastPublishedDate() , $recordObj->getLastPublishedDateOfTranslation() ) ;
        $record['endTime']   = $recordObj->getDeprecated() ? (time() - 3600) : 0 ;
        $record['feGroup']   = $tagTypeAndFeGroup['feGroup'] ;

        $record['additionalFields']  = [
            'orig_uid' => intval( $recordObj->getArticleNumber() ) ,
            // We take the column sortdate to store the original tstamp of the post
            'sortdate' => intval( $recordObj->getLastModifiedDate() )  ,
            'directory' =>  $recordObj->getDirectory() ,
            'tx_allplan_ke_search_extended_server_name' => EnvironmentUtility::getServerName(),
            'tx_allplan_ke_search_extended_top_10' => $recordObj->getArticleCaseAttachCount() ,
        ];

        $record['versions'] =  $recordObj->getSoftwareVersions()  ;
        $record['permissions'] =  $recordObj->getPermission()   ;
        $record['status'] =  $recordObj->getPublishStatus()   ;
        $record['deprecated'] =  $recordObj->getDeprecated()   ;
        $record['linkedFiles'] =  $recordObj->getLinkedFiles()   ;

        $record['content'] = $recordObj->getText()  ;

        // now all relevant Fields we need are collected so we can later on scho FAQ with all additional infos
        $record['content']    = json_encode( $record ) ;


        // ToDo JVE: Add Download Links after Text Content.

        $record['tags']     = $defaultTag . $tagTypeAndFeGroup['tags']  ;
        $record['params']   = "_blank" ;




        return $record ;
    }

    public static function getVersionTagsFromSting() {

    }

}