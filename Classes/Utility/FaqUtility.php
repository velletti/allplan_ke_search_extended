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

	/**
	 * Get the content of a pdf file, given by a sys_file.uid
	 * @param string|null  $permission
	 * @return array
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 */
	public static function getTagTypeAndUserGroupByPermission( ?string $permission ):array
	{
        switch ( strtolower( $permission )) {
            case "everybody":
                $single['type'] = "supportfaq";
                $single['feGroup'] = '';
                $single['tags'] = ",#allUserAccess#,#customerAccess#";
                break;

            case "beta tester":
            case "betatester":
                $single['type'] = "supportfaqbeta";
                $single['feGroup'] = '38,7,4';
                $single['tags'] = '' ;
                break;

            case "portal user":
            case "portaluser":
                $single['type'] = "supportfaqsp";
                $single['feGroup'] = '38,7,4,3';
                $single['tags'] = ",#customerAccess#";
                break;
            case "nemetschek only":
            case "nemetschekonly":
                $single['type'] = "supportfaqnem";
                $single['feGroup'] = '38,7';
                $single['tags'] = '' ;
                break;

            default:
                $single['type'] = "supportfaqlocked";
                $single['feGroup'] = '38';
                $single['tags'] = '' ;
                break;
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
        if( !$recordObj ) {
            return [];
        }
        $tagTypeAndFeGroup = self::getTagTypeAndUserGroupByPermission($recordObj->getPermission() ) ;
        $syslangAndPid = self::getLangAndPid($recordObj->getLanguage() ) ;

        $record = [];
        $record['pid'] = $syslangAndPid['pid'] ;
        $record['title'] = $recordObj->getTitle()  ;
        $record['type'] = $tagTypeAndFeGroup['type']  ;
        $record['targetPid'] = "ToDo ... DOMAIN .... /knowledge1/s/article/" . $recordObj->getUrlName(). "?language=" . $recordObj->getLanguage();
        $record['content'] = $recordObj->getText()  ;
        $record['tags']     = $defaultTag . $tagTypeAndFeGroup['tags']  ;
        $record['params']   = "todo" ;
        $record['language'] = $syslangAndPid['indexlang'] ;
        $record['abstract']   = strip_tags( $recordObj->getText() ) ;
        $record['startTime']   = strip_tags( $recordObj->getLastPublishedDate() ) ;
        $record['endTime']   = 0 ;
        $record['feGroup']   = $tagTypeAndFeGroup['feGroup'] ;
        return $record ;
    }

}