<?php
namespace Allplan\AllplanKeSearchExtended\Indexer\Connect;

/**
 * AllplanKeSearchExtended
 */
use Allplan\AllplanKeSearchExtended\Indexer\IndexerBase;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerInterface;
use Allplan\AllplanKeSearchExtended\Indexer\IndexerRunner;
use Allplan\AllplanKeSearchExtended\Utility\DbUtility;
use Allplan\AllplanKeSearchExtended\Utility\EnvironmentUtility;
use Allplan\AllplanKeSearchExtended\Utility\FaqUtility;
use Allplan\AllplanKeSearchExtended\Utility\FormatUtility;
use Allplan\AllplanKeSearchExtended\Utility\IndexerUtility;

/**
 * KeSearch
 */

use Allplan\Library\Salesforce\Model\Knowledgebase;
use Allplan\Library\Salesforce\Service\KnowledgeBases;
use Allplan\Library\Salesforce\Utility\DateUtility;
use Allplan\NemMysupport\Utility\GetConfig;
use Tpwd\KeSearch\Indexer\IndexerRunner as KeSearchIndexerRunner;

/**
 * TYPO3
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Doctrine
 */
use Doctrine\DBAL\Driver\Exception as DoctrineDBALDriverException;

/**
 * Php
 */
use Exception;


/**
 * Indexer for forum (EXT:mm_forum)
 */
class FaqIndexer extends IndexerBase implements IndexerInterface
{

	/**
	 * For Development
	 * Limit the number of topics to a number for faster development
	 * @var int|null
	 */
	const FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX = 250 ;

	/**
	 * Faq indexer types
	 */
	const FAQ_INDEXER_TYPE_DEFAULT = 'supportfaq';


    const FAQ_DEFAULT_TAG = "#allplanfaq#" ;



	/**
	 * @return int
	 * @throws DoctrineDBALDriverException
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function startIndexing(): int
	{

		// Todo delete unneeded functions in utilities

		// Better variable name
		/** @var KeSearchIndexerRunner|IndexerRunner $indexerRunner */
		$indexerRunner = $this->pObj;
		$indexerConfig = $this->indexerConfig;
        $starttime = time() ;
		$latestEntry = DbUtility::getLatestSortdateAndOrigUidByIndexerType( self::FAQ_INDEXER_TYPE_DEFAULT . "%" ) ;
        if( $latestEntry ) {
            $latest = $latestEntry["sortdate"];
            // like "000004044"
            // like "000006057"
            $latestNo =  str_pad( $latestEntry["orig_uid"], 9, "0", STR_PAD_LEFT); ;
        } else {
            $latest = 1;
            $latestNo =  "000000000" ;
        }


        $knowledgeBases = new KnowledgeBases( GetConfig::read() ) ;

        $beforeQuery = time() ;

            $result = $knowledgeBases->getKnowledgeBasesModifiedAfterDate(
            DateUtility::convertTimestampToSalesforceDate( $latest , false ) ,
            self::FAQ_INDEXER_NR_OF_TOPICS_TO_INDEX , 0 , 'Online' , '' , true , $latestNo ) ;
        $queryTime = time() - $beforeQuery ;

        $details = "\n" . 'Using this Instance:' . $knowledgeBases->getInstanceUrlFromTokenData() ;
		$count = 0;
		$errorCount = 0;
        $details .= "\n" . ' modified after: '  .  DateUtility::convertTimestampToSalesforceDate($latest , false )  ;
        $details .= "\n" . " Query:  " . $knowledgeBases->getLatestQuery() . " | ";

        $logdata = '' ;
        $faqNeededToImport = 0 ;
		if($result){
            $faqNeededToImport = $result['max']['records'][0]['expr0'] ;
            DbUtility::saveIndexerResultInSysLog(
                '[ke_search] Indexer started: Faqs (knowledge_base articles from Salesforce). ',
                $faqNeededToImport ,
                'We still need to import: ' .  $faqNeededToImport . " FAQs. Search Query took " . $queryTime  . " Seconds \n"  ,
                $logdata
            );



            $details .= "\n" . " faqNeededToImport:  " .  var_export( $result['max']['records'][0] , true) . " | ";
            /** @var Knowledgebase  $recordObj */
            foreach ($result['faqs'] as $recordObj ){
                if ( $count ==  0 ) {
                    $details .= "\n" . " First: " . $recordObj->getId() ;
                }
                if( $recordObj  ) {
                    if( !$recordObj->getTitle()  ) {
                        $recordObj->setText("Missing Title") ;
                    }
                    if(  !$recordObj->getText() ) {
                        $recordObj->setText("Missing Text") ;
                    }
                    $recordObj->setText($knowledgeBases->replaceImageUrlsWithData( $recordObj->getId() , $recordObj->getText() ) ) ;
                    $recordObj->setLinkedFiles( $knowledgeBases->getContentVersionsByDocumentLinksEntityId( $recordObj->getId() ));
                    $record = FaqUtility::getRecordAsArray($recordObj , self::FAQ_DEFAULT_TAG ) ;

                    try{
                        // remove any indexed entry first as type can change
                        DbUtility::deleteIndexedRecord( intval( $recordObj->getArticleNumber() )  ,  $record['pid'] , "supportfaq" ,  $record['language']) ;
                        DbUtility::migrateRatingRecord( $recordObj->getBisherigeID() , $recordObj->getArticleNumber()) ;
                        // Write record to index
                        if($this->storeInKeSearchIndex($record, $indexerRunner, $indexerConfig)){
                            $count++;

                        } else {
                            $errorCount++;
                            $logdata .= " Error on ID: " . $recordObj->getId() . "\n";
                        }
                    } catch (\TYPO3\CMS\Core\Type\Exception $e) {
                        // nothing
                        $errorCount++;
                        $logdata .= " Exception: " . $e->getMessage() . " on ID: ". $recordObj->getId() . "\n";
                    }
                }


			}
            if ($count > 0 ) {
                $details .= "\n" . " Last: " . $recordObj->getId() . " ( " . $recordObj->getLastPublishedDateAsString() . " ) ";
            }

		}
        if (   $errorCount ) {
            $details .= "\n" . "Errors on  " . $errorCount . " entries (see sys_log) "  ;
        }
        $details .= "\n" . "( took " . ( time() - $starttime ) . " seconds) "  ;
        // Write to sys_log
        DbUtility::saveIndexerResultInSysLog(
            '[ke_search] Indexer finished: Faqs (knowledge_base articles from Salesforce)',
            $count ,
            ' Inserted/updated:  ' . $count . " of " . $faqNeededToImport . " FAQs. " . "\n" . $details ,
            $logdata
        );



		return $count;

	}

	/**
	 * Write data to index (tx_kesearch_index)
	 * @param array $record
	 * @param IndexerRunner|KeSearchIndexerRunner $indexerRunner
	 * @param array $indexerConfig
	 * @return bool|int
	 * @throws Exception
	 * @author Peter Benke <pbenke@allplan.com>
	 */
	public function storeInKeSearchIndex(array $record, IndexerRunner $indexerRunner, array $indexerConfig)
	{

		$debugOnly = false;


		// Call the function from ke_search
		return $indexerRunner->storeInIndex(
            $record['pid'] ,
			$record['title'],
			$record['type'],
			$record['targetPid'],
			$record['content'],
			$record['tags'],
			$record['params'],
			$record['abstract'],
			$record['language'],
			$record['startTime'],
			$record['endTime'],
			$record['feGroup'],
			$debugOnly,
			$record['additionalFields']
		);

	}

}