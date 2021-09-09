<?php
namespace Allplan\AllplanKeSearchExtended\ViewHelpers;



/**
 * TYPO3Fluid
 */

use Allplan\NemConnections\Utility\MigrationUtility;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * TYPO3
 */
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class KeSearchDirectoryViewHelper
 * @package Allplan\AllplanKeSearchExtended\ViewHelpers
 */
class KeSearchDirectoryViewHelper extends AbstractViewHelper
{

    /**
     *
     * @var boolean
     */
    protected $escapeOutput = false;

	/**
	 * @return string
	 * @author Jörg Velletti <jvelletti@allplan.com>
	 */
	public function render()
	{

		/**
		 * @var ConnectionPool $connectionPool
		 * @var QueryBuilder $queryBuilder
		 */

		$contentSelect1 = '';
        $contentSelect2 = '' ;
        $contentSelect3 = '' ;

        $targetpid = $this->getTargetLanguage()  ;
        $maxGroup = $this->getFeMaxGroup()  ;

        $connectionPool = GeneralUtility::makeInstance( ConnectionPool::class);
        $queryBuilder = $connectionPool->getConnectionForTable('tx_kesearch_index')->createQueryBuilder();
        $rows = $queryBuilder
            /* we have entries in Database like :
               Allgemein
               Allgemeine Einstellungen\Architektur
               Allgemein\Projekt

               with  ->selectLiteral(' concat(replace(directory , "\\\\" , " \\\\") ,  " \\\\" ) as directory ')
               we get a correct sorted list (Allgemein and Allgemein\Projekt before   Allgemeine Einstellungen \Architektur
               Allgemein \
               Allgemein \Projekt
               Allgemeine Einstellungen \Architektur

            */

            ->selectLiteral(' concat(replace(directory , "\\\\" , " \\\\") ,  " \\\\" ) as directory ')
            ->from('tx_kesearch_index')
            ->where( $queryBuilder->expr()->like('targetpid', $queryBuilder->createNamedParameter($targetpid , Connection::PARAM_STR)))
            ->andWhere( $queryBuilder->expr()->neq('directory', $queryBuilder->createNamedParameter("" , Connection::PARAM_STR) ))
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('fe_group', $queryBuilder->createNamedParameter('' , Connection::PARAM_STR)),
                    $queryBuilder->expr()->like('fe_group', $queryBuilder->createNamedParameter( $maxGroup , Connection::PARAM_STR) )
                )
            )
            ->groupBy("directory")
            ->orderBy("directory")
            ->execute()->fetchAllAssociative() ;
        $directory = false ;
        if ( $rows && count( $rows) > 0 ) {

            $params = GeneralUtility::_GET("tx_kesearch_pi1") ;
            if ( is_array($params) && array_key_exists("directory" , $params) && strlen( $params["directory"]) > 0 ) {
                $directory = GeneralUtility::trimExplode( "\\" , trim(  urldecode( $params["directory"] )) ) ;
            }
            $contentSelect1 .= '<option></option>' ;
            $lastOption = "#-#"  ;
            foreach ($rows as $row ) {
                $subCategory = "" ;


                $thisOption = $this->getOption( $row['directory'] , 0 ) ;
                if( $thisOption ) {
                    if ( $thisOption != $lastOption ) {
                        $selected = "" ;
                        if( $directory && $directory[0] == $thisOption ) {
                            $selected= " selected=\"selected\" " ;
                        }
                        $contentSelect1 .= '<option value="' . urlencode($thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>' ;
                    }
                    $lastOption =  $thisOption ;
                }

            }


            if ( is_array( $directory ) && count($directory) > 0  ) {
                $lastOption = "#-#"  ;
                foreach ($rows as $row ) {
                    if( $directory && $directory[0]  ) {
                        $thisOption = $this->getOption( $row['directory'] , 1 , $directory ) ;
                        if( $thisOption ) {
                            if ($thisOption != $lastOption) {
                                $selected = '' ;
                                if( $directory && $directory[1] == $thisOption ) {
                                    $selected= " selected=\"selected\" " ;
                                }
                                $contentSelect2 .= '<option value="' . urlencode($directory[0] . "\\" . $thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>';
                                $lastOption = $thisOption;
                            }
                        }
                    }
                }
                if ( is_array( $directory ) && count($directory) > 1  ) {
                    $lastOption = "#-#"  ;

                    foreach ($rows as $row ) {
                        if( $directory && $directory[1] ) {
                            $thisOption = $this->getOption( $row['directory'] , 2 , $directory ) ;
                            if( $thisOption ) {
                                if ($thisOption != $lastOption) {
                                    $selected = '' ;
                                    if( $directory && $directory[2] == $thisOption ) {
                                        $selected= " selected=\"selected\" " ;
                                    }
                                    $contentSelect3 .= '<option value="' . urlencode($directory[0] . "\\"  .$directory[1] . "\\" . $thisOption) . '" ' . $selected . '>' . $thisOption . ' </option>';
                                    $lastOption = $thisOption;
                                }
                            }
                        }
                    }
                }
            }
        }
        $content = '' ;
        if ( $contentSelect1) {
            $field = "directory" ;
            if( $contentSelect2) {

            }
            $content .= '<select name="tx_kesearch_pi1[directory1]" data-cat="tx_kesearch_pi1[directory1]" class="form-control kesearch-directory  kesearch-directory1" >' . $contentSelect1 . '</select>';
        }
        if ( $contentSelect2) {
            $content .= '<br><select name="tx_kesearch_pi1[directory2]" data-cat="tx_kesearch_pi1[directory2]"  class="form-control kesearch-directory  kesearch-directory2" >'
                . "<option value=\"" . urlencode($directory[0] ) . "\"></option>"
                . $contentSelect2
                . '</select>';
        }
        if ( $contentSelect3) {
            $content .= '<br><select name="tx_kesearch_pi1[directory3]" data-cat="tx_kesearch_pi1[directory3]"  class="form-control kesearch-directory  kesearch-directory3" >'
                . "<option value=\"" . urlencode($directory[0] . "\\" . $directory[1] ) . "\"></option>"
                . $contentSelect3
                . '</select>';
        }
        $content .= " <script>$('SELECT.kesearch-directory').on('change' , function() { 
     
     $('SELECT.kesearch-directory').each( function() { 
         $( this).attr( 'name' , $( this).data('cat') )
     });
     $( this).attr( 'name' , 'tx_kesearch_pi1[directory]' )   ;
     if($( this).data('cat') =='tx_kesearch_pi1[directory1]') {
         $( 'SELECT.kesearch-directory2').remove() ;
         $( 'SELECT.kesearch-directory3').remove() ;
     }
     if($( this).data('cat') =='tx_kesearch_pi1[directory2]') {
         $( 'SELECT.kesearch-directory3').remove() ;
     }
 }) ; 
 </script>" ;
        return $content ;

	}

    /**
     * @param string $option
     * @param int $level
     * @param array|bool $replace
     * @return string
     */

	private function getOption( $option , $level=0 , $replace = false  ) {
        $options= GeneralUtility::trimExplode( "\\" , $option ) ;
        if( $level == 0 ) {
            return $options[0] ;
        }
        if( count($options ) > 0 ) {
            if( $replace[0] == $options[0] && $level == 1 ) {
                return  $options[1];
            }
        }
        if( count($options ) > 1 && $replace && count($replace ) > 1) {
            if( $replace[0] == $options[0] && $replace[1] == $options[1] && $level == 2 ) {

                return  $options[2];
            }
        }
        return  '' ;
    }

	private function getFeMaxGroup() {
	    if ( $GLOBALS['TSFE']->fe_user && is_array($GLOBALS['TSFE']->fe_user->user)) {
            $usergroups = GeneralUtility::trimExplode( "," , $GLOBALS['TSFE']->fe_user->user['usergroup'] ) ;
            if( in_array( "38" ,$usergroups ) ) {
                return "38%" ;
            }
            if( in_array( "7" ,$usergroups ) ) {
                return "38,7%" ;
            }
            if( in_array( "4" ,$usergroups ) ) {
                return "38,7,4%" ;
            }
            if( in_array( "3" ,$usergroups ) ) {
                return "38,7,4,3" ;
            }
        }
	    return "" ;

    }

	private function getTargetLanguage() {
        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getAspect('language') ;
        $lng = 0 ;
        if (GeneralUtility::_GP("L") && intval(GeneralUtility::_GP("L") > 0)) {
            $lng = GeneralUtility::_GP("L") ;
        }
        if ( $lng == 0 ) {
            $lng =  $languageAspect->getId() ;
        }

        switch ($lng) {
            case 1:
            case 6:
            case 7:
                return "https://connect.allplan.com/de%" ;
            case 2:
                return "https://connect.allplan.com/it%" ;
            case 3:
                return "https://connect.allplan.com/cz%" ;
            case 4:
                return "https://connect.allplan.com/fr%" ;
            case 18:
                return "https://connect.allplan.com/es%" ;

            case 14:
                // ru is englisch = default
            default:
                return "https://connect.allplan.com/en%" ;
        }

    }

}