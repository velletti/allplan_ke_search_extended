<?php
namespace Allplan\AllplanKeSearchExtended\Hooks;

use \TYPO3\CMS\Core\Utility\GeneralUtility ;
use \TYPO3\CMS\Core\Database\Query\QueryHelper ;

class BaseKeSearchIndexerHook{

	/**
	 * Returns a tree list of pages starting with the $startPageUid
	 * @param int $startPageUid
	 * @return string
	 */
	protected function getTreeList($startPageUid){
		/**
		 * @var \TYPO3\CMS\Core\Database\QueryGenerator $queryGenerator
		 */

		$startPageUid = intval($startPageUid);
		$depth = 10;

		$queryGenerator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryGenerator');
		$treeList = $queryGenerator->getTreeList($startPageUid, $depth, 0, 1);

		return $treeList;

	}

    /**
     * Returns the first record found from $table with $where as WHERE clause
     * This function does NOT check if a record has the deleted flag set.
     * $table does NOT need to be configured in $GLOBALS['TCA']
     * The query used is simply this:
     * $query = 'SELECT ' . $fields . ' FROM ' . $table . ' WHERE ' . $where;
     *
     * @param string $table Table name (not necessarily in TCA)
     * @param string $where WHERE clause
     * @param string $fields $fields is a list of fields to select, default is '*'
     * @return array|bool First row found, if any, FALSE otherwise
     */
    public function getRecordRaw($table, $where = '', $fields = '*')
    {
        /**
         * @var \TYPO3\CMS\Core\Database\ConnectionPool $connectionPool
         */
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);

        $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $row = $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from($table)
            ->where(QueryHelper::stripLogicalOperatorPrefix($where))
            ->execute()
            ->fetch();

        return $row ?: false;
    }

    /**
     * Returns the Curl Search Index of a specified allplan Online Help
     * @param string $url
     * @param string $searchResponse   // send just a part of result if not correct  JSON encoded result
     * @param string $jsonheader        // maybe we need in the Future a different request/ response type
     * @param boolean $withHeader        // with http 200 status or not ... FALSE is easier to hande the response ...
     * @param integer $timeOut         // timeout in seconds
     * @return string
     */
    protected function getJsonFile($url , $searchResponse='{"pages"' , $jsonheader = array ( "Accept: application/json" , "Content-type:application/json" ) , $withHeader = TRUE  , $timeOut = false )
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); // set the target url

        if($fp = tmpfile()){
            // with this option, CURL out put is not stored to Error Log
            curl_setopt ($ch, CURLOPT_STDERR, $fp);
        }
        curl_setopt($ch, CURLOPT_POST, 0 ); //
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );        // don't give anything back (sometimes important in TYPO3!)
        curl_setopt($ch, CURLOPT_HEADER, $withHeader );            // with HTTP response Code ( Fehler / OK ) ??? ..
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        if( $timeOut ) {
            curl_setopt($ch, CURLOPT_TIMEOUT, intval($timeOut) );                 //timeout in seconds
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if( $jsonheader ) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $jsonheader );
        }

        $result = curl_exec ($ch);
        curl_close ($ch);
        if( !$withHeader ) {
            return $result ;
        }
        $resultarr = explode ( "\n" , $result ) ;
       //  var_dump($result) ;

        $httpval = explode ( " " , $resultarr[0] ) ;
        if ( $httpval[1] != "200" ) {
            return array( "error" , $httpval[1] )  ;
        }
        $resultvals = false ;
        for ( $i=1 ;  $i < count( $resultarr) ; $i++ ) {
            if ( is_array (json_decode( $resultarr[$i] , true)) ){
                return json_decode( $resultarr[$i] , true) ;
            } else {
                if ( substr( $resultarr[$i], 0 , 8 ) == $searchResponse ) {
                    return substr( $resultarr[$i] , 14 , strlen( $resultarr[$i] ) - 17 ) ;
                }
            }
        }

        if( count( $resultarr) > 8 ) {
            return substr( $resultarr[9] , 14 , strlen( $resultarr[9] ) - 17 ) ;
        }

        return $resultarr  ;
    }

	/**
	 * Returns additional content from pi_flexform-field
	 * @param $ttContentRow
	 * @return string
	 */
	protected function getAdditionalContentFromFlexform($ttContentRow){

		$contentArray = array();

		// Predefine array to avoid php warnings
		$flexFormDataDefault = [
			'data' =>[
				'main' => [
					'lDEF' => [
						'settings.content' => [
							'vDEF'
						],
						'settings.contentLeft' => [
							'vDEF'
						],
						'settings.contentRight' => [
							'vDEF'
						],
					]
				]
			]
		];

		$flexFormDataThis = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($ttContentRow['pi_flexform']);
		if( is_array( $flexFormDataThis )) {
            $flexFormData = array_merge($flexFormDataThis , $flexFormDataDefault ) ;
        } else {
            $flexFormData = $flexFormDataDefault  ;
        }


		// Define the fields in the flexforms, which should be indexed
		$cTypes = [

			// Headers
			// =============================================================================================================================
			'allplantemplate_ce_headerHome' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerProduct' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerDefault' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerSkew' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_headerYouTubeVideo' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Sliders
			// =============================================================================================================================
			'allplantemplate_ce_sliderDefaultElement' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_sliderReferenceElement' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Teasers & reference & product
			// =============================================================================================================================
			'allplantemplate_ce_jumbotronTeaser' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_teaserSkew' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.contentLeft']['vDEF'],
					$flexFormData['data']['main']['lDEF']['settings.contentRight']['vDEF']
				]
			],
			'allplantemplate_ce_reference' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_product' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],

			// Multi-column content
			// =============================================================================================================================
			'allplantemplate_ce_colElementTheme' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_colElementAssistingSolution' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],
			'allplantemplate_ce_colElementInfobox' => [
				'fields' => [
					$flexFormData['data']['main']['lDEF']['settings.content']['vDEF']
				]
			],


			// Todo: Here we have to find an elegant way, because the fields are arrays

			// 'allplantemplate_ce_accordion' => [
			// ],

			// 'allplantemplate_ce_functionMatrix' => [
			// ],

		];

		// If CType is configured
		if(array_key_exists($ttContentRow['CType'], $cTypes)){

			foreach($cTypes[$ttContentRow['CType']]['fields'] as $content){

				$contentArray[] = strip_tags(html_entity_decode($content));

			}

		}

		return implode(' ', $contentArray);

	}


}